<?php
/**
 * TUOUGM Music Migration Tool
 *
 * 既存の tuougm_player_catalog() のハードコードデータを
 * music_album / music_track CPT + ACF へ一括移行するための一回限りのツール。
 *
 * 安全設計:
 * - ドライランではDB / Media Library / ACFを一切変更しない
 * - 既存の _tuougm_source_id があれば最優先で再利用
 * - source_id がない場合はタイトル完全一致で既存投稿を再利用
 * - 同名投稿が複数ある場合は安全のため自動作成せずエラーにする
 * - 既存投稿のタイトル・本文・既存ACF・アイキャッチは上書きしない
 * - 空のACFだけを補完する
 * - 既存のtrack_audio / artworkも空の場合だけ登録する
 * - album_tracks は移行元の曲順を優先して設定する
 */

if (!defined('ABSPATH')) {
    exit;
}



/*---------------------------------------------
 * 管理画面
 *---------------------------------------------*/
add_action('admin_menu', function () {
    add_management_page(
        'TUOUGM Music Migration',
        'TUOUGM Music Migration',
        'manage_options',
        'tuougm-music-migration',
        'tuougm_render_music_migration_page'
    );
});

/*---------------------------------------------
 * URL → Media Library attachment
 *---------------------------------------------*/
function tuougm_migration_import_media($url, $title = '') {

    if (!$url) {
        return 0;
    }

    $existing = get_posts([
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'meta_key'       => '_tuougm_source_url',
        'meta_value'     => $url,
        'fields'         => 'ids',
    ]);

    if (!empty($existing)) {
        return (int) $existing[0];
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($url);

    if (is_wp_error($tmp)) {
        return 0;
    }

    $filename = wp_basename(parse_url($url, PHP_URL_PATH));

    $file_array = [
        'name'     => $filename,
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload(
        $file_array,
        0,
        $title
    );

    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        return 0;
    }

    update_post_meta($attachment_id, '_tuougm_source_url', $url);

    return (int) $attachment_id;
}

/*---------------------------------------------
 * ACF値が空か確認
 *---------------------------------------------*/
function tuougm_migration_field_is_empty($field_name, $post_id) {

    if (!function_exists('get_field')) {
        return true;
    }

    $value = get_field($field_name, $post_id);

    if (is_array($value)) {
        return empty($value);
    }

    return $value === null || $value === '';
}

/*---------------------------------------------
 * source_id で既存投稿を探す
 *---------------------------------------------*/
function tuougm_migration_find_by_source_id($post_type, $source_id) {

    if (!$source_id) {
        return 0;
    }

    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => '_tuougm_source_id',
        'meta_value'     => $source_id,
        'fields'         => 'ids',
    ]);

    return !empty($posts) ? (int) $posts[0] : 0;
}

/*---------------------------------------------
 * タイトル完全一致で既存投稿を探す
 *
 * 同名が複数ある場合は 0 を返して
 * 自動作成を止める。
 *---------------------------------------------*/
function tuougm_migration_find_by_title($post_type, $title) {

    $posts = get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'title'          => $title,
        'fields'         => 'ids',
    ]);

    if (count($posts) === 1) {
        return (int) $posts[0];
    }

    return 0;
}

/*---------------------------------------------
 * 既存投稿を安全に探す
 *
 * 戻り値:
 * [
 *   'id'     => 投稿ID,
 *   'method' => source_id / title / new / ambiguous
 * ]
 *---------------------------------------------*/
function tuougm_migration_resolve_post($post_type, $source_id, $title) {

    $source_post_id = tuougm_migration_find_by_source_id(
        $post_type,
        $source_id
    );

    if ($source_post_id) {
        return [
            'id'     => $source_post_id,
            'method' => 'source_id',
        ];
    }

    $title_post_id = tuougm_migration_find_by_title(
        $post_type,
        $title
    );

    if ($title_post_id) {
        return [
            'id'     => $title_post_id,
            'method' => 'title',
        ];
    }

    // 同名が複数存在する場合は安全のため作成しない
    $same_title_count = count(get_posts([
        'post_type'      => $post_type,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'title'          => $title,
        'fields'         => 'ids',
    ]));

    if ($same_title_count > 1) {
        return [
            'id'     => 0,
            'method' => 'ambiguous',
        ];
    }

    return [
        'id'     => 0,
        'method' => 'new',
    ];
}

/*---------------------------------------------
 * ドライラン用の変更予定を作る
 *---------------------------------------------*/
function tuougm_migration_preview() {

    $catalog = tuougm_player_catalog();

    $result = [
        'albums'      => [],
        'tracks'      => [],
        'new_albums'  => 0,
        'reuse_albums'=> 0,
        'new_tracks'  => 0,
        'reuse_tracks'=> 0,
        'ambiguous'   => 0,
        'errors'      => [],
    ];

    foreach ($catalog as $album_data) {

        $album_source_id = sanitize_key($album_data['id']);

        $album = tuougm_migration_resolve_post(
            'music_album',
            $album_source_id,
            $album_data['title']
        );

        if ($album['method'] === 'ambiguous') {
            $result['ambiguous']++;
            $result['errors'][] =
                'Album同名が複数存在: ' . $album_data['title'];
        } elseif ($album['id']) {
            $result['reuse_albums']++;
        } else {
            $result['new_albums']++;
        }

        $album_row = [
            'title'  => $album_data['title'],
            'method' => $album['method'],
            'id'     => $album['id'],
            'tracks' => [],
        ];

        foreach ($album_data['tracks'] as $track_data) {

            $track_source_id = sanitize_key($track_data['id']);

            $track = tuougm_migration_resolve_post(
                'music_track',
                $track_source_id,
                $track_data['title']
            );

            if ($track['method'] === 'ambiguous') {
                $result['ambiguous']++;
                $result['errors'][] =
                    'Track同名が複数存在: ' . $track_data['title'];
            } elseif ($track['id']) {
                $result['reuse_tracks']++;
            } else {
                $result['new_tracks']++;
            }

            $album_row['tracks'][] = [
                'title'  => $track_data['title'],
                'method' => $track['method'],
                'id'     => $track['id'],
                'source' => $track_data['src'],
            ];
        }

        $result['albums'][] = $album_row;
    }

    return $result;
}

/*---------------------------------------------
 * 移行本体
 *---------------------------------------------*/
function tuougm_run_music_migration($dry_run = true) {

    if (!function_exists('update_field')) {
        return [
            'error' => 'ACF が有効になっていません。',
        ];
    }

    if ($dry_run) {
        return [
            'dry_run' => true,
            'preview' => tuougm_migration_preview(),
        ];
    }

    $catalog = tuougm_player_catalog();

    $result = [
        'dry_run'      => false,
        'albums'       => 0,
        'tracks'       => 0,
        'new_albums'   => 0,
        'reused_albums'=> 0,
        'new_tracks'   => 0,
        'reused_tracks'=> 0,
        'attachments'  => 0,
        'skipped'      => 0,
        'errors'       => [],
    ];

    foreach ($catalog as $album_data) {

        $album_source_id = sanitize_key($album_data['id']);

        $album_resolved = tuougm_migration_resolve_post(
            'music_album',
            $album_source_id,
            $album_data['title']
        );

        if ($album_resolved['method'] === 'ambiguous') {
            $result['errors'][] =
                'Album同名が複数存在するため停止: ' . $album_data['title'];
            continue;
        }

        $album_id = $album_resolved['id'];

        /*
         * Album作成 / 再利用
         */
        if (!$album_id) {

            $album_id = wp_insert_post([
                'post_type'   => 'music_album',
                'post_status' => 'publish',
                'post_title'  => wp_strip_all_tags($album_data['title']),
            ], true);

            if (is_wp_error($album_id)) {
                $result['errors'][] =
                    'Album作成失敗: ' . $album_data['title'];
                continue;
            }

            update_post_meta(
                $album_id,
                '_tuougm_source_id',
                $album_source_id
            );

            $result['new_albums']++;

        } else {

            $result['reused_albums']++;

            // 手動登録済み投稿にもsource_idを付与して次回以降確実に再利用
            if (!get_post_meta($album_id, '_tuougm_source_id', true)) {
                update_post_meta(
                    $album_id,
                    '_tuougm_source_id',
                    $album_source_id
                );
            }
        }

        /*
         * Artwork
         * 既存アイキャッチがある場合は絶対に上書きしない
         */
        if (!has_post_thumbnail($album_id)) {

            $artwork_id = tuougm_migration_import_media(
                $album_data['artwork'],
                $album_data['title']
            );

            if ($artwork_id) {
                set_post_thumbnail($album_id, $artwork_id);
                $result['attachments']++;
            } else {
                $result['errors'][] =
                    'Artwork import failed: ' . $album_data['title'];
            }
        }

        /*
         * Album ACF
         * 既存値は上書きしない
         */
        if (tuougm_migration_field_is_empty('album_title_en', $album_id)) {
            update_field('album_title_en', '', $album_id);
        }

        if (tuougm_migration_field_is_empty('album_type', $album_id)) {
            update_field('album_type', '', $album_id);
        }

        if (tuougm_migration_field_is_empty('required_level', $album_id)) {
            update_field(
                'required_level',
                intval($album_data['access']),
                $album_id
            );
        }

        /*
         * Tracks
         */
        $track_ids = [];

        foreach ($album_data['tracks'] as $track_data) {

            $track_source_id = sanitize_key($track_data['id']);

            $track_resolved = tuougm_migration_resolve_post(
                'music_track',
                $track_source_id,
                $track_data['title']
            );

            if ($track_resolved['method'] === 'ambiguous') {
                $result['errors'][] =
                    'Track同名が複数存在するためスキップ: ' .
                    $track_data['title'];
                continue;
            }

            $track_id = $track_resolved['id'];

            /*
             * Track作成 / 再利用
             */
            if (!$track_id) {

                $track_id = wp_insert_post([
                    'post_type'   => 'music_track',
                    'post_status' => 'publish',
                    'post_title'  => wp_strip_all_tags($track_data['title']),
                ], true);

                if (is_wp_error($track_id)) {
                    $result['errors'][] =
                        'Track作成失敗: ' . $track_data['title'];
                    continue;
                }

                update_post_meta(
                    $track_id,
                    '_tuougm_source_id',
                    $track_source_id
                );

                $result['new_tracks']++;

            } else {

                $result['reused_tracks']++;

                if (!get_post_meta($track_id, '_tuougm_source_id', true)) {
                    update_post_meta(
                        $track_id,
                        '_tuougm_source_id',
                        $track_source_id
                    );
                }
            }

            /*
             * Track ACF
             * 既存値は上書きしない
             */
            if (tuougm_migration_field_is_empty('track_title_en', $track_id)) {
                update_field('track_title_en', '', $track_id);
            }

            if (tuougm_migration_field_is_empty('track_type', $track_id)) {
                update_field('track_type', '', $track_id);
            }

            if (tuougm_migration_field_is_empty('track_external_url', $track_id)) {
                update_field('track_external_url', '', $track_id);
            }

            /*
             * Audio
             * 既存track_audioがある場合は絶対に上書きしない
             */
            if (tuougm_migration_field_is_empty('track_audio', $track_id)) {

                $audio_id = tuougm_migration_import_media(
                    $track_data['src'],
                    $track_data['title']
                );

                if ($audio_id) {

                    update_field(
                        'track_audio',
                        $audio_id,
                        $track_id
                    );

                    $result['attachments']++;

                } else {

                    $result['errors'][] =
                        'Audio import failed: ' . $track_data['src'];
                }
            }

            $track_ids[] = (int) $track_id;
            $result['tracks']++;
        }

        /*
         * Album側Relationshipで曲順を確定
         *
         * 移行元カタログの順番をそのまま採用。
         */
        if (!empty($track_ids)) {
            update_field(
                'album_tracks',
                $track_ids,
                $album_id
            );
        }

        $result['albums']++;
    }

    /*
     * エラーがある場合でも、既に行った変更を自動rollbackはしない。
     * そのため「実行結果」として明示する。
     */
    if (empty($result['errors'])) {
        update_option(
            'tuougm_music_migration_completed',
            current_time('mysql')
        );
    }

    return $result;
}

/*---------------------------------------------
 * 管理画面表示
 *---------------------------------------------*/
function tuougm_render_music_migration_page() {

    if (!current_user_can('manage_options')) {
        return;
    }

    $result = null;

    if (
        isset($_POST['tuougm_migration_action']) &&
        check_admin_referer('tuougm_music_migration')
    ) {

        $action = sanitize_key(
            $_POST['tuougm_migration_action']
        );

        if ($action === 'dry_run') {
            $result = tuougm_run_music_migration(true);

        } elseif ($action === 'run') {
            $result = tuougm_run_music_migration(false);
        }
    }

    $completed = get_option('tuougm_music_migration_completed');

    ?>
    <div class="wrap">

        <h1>TUOUGM Music Migration</h1>

        <p>
            既存のプレイヤー用ハードコードデータを、
            Album / Track CPT + ACFへ移行します。
        </p>

        <div class="notice notice-warning">
            <p>
                <strong>まず「ドライラン」を実行してください。</strong><br>
                ドライランでは投稿・ACF・Media Libraryを一切変更しません。
            </p>
        </div>

        <?php if ($completed): ?>
            <div class="notice notice-success">
                <p>
                    最終的な移行成功日時：
                    <?php echo esc_html($completed); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($result): ?>

            <?php if (!empty($result['error'])): ?>

                <div class="notice notice-error">
                    <p><?php echo esc_html($result['error']); ?></p>
                </div>

            <?php elseif (!empty($result['dry_run'])): ?>

                <?php $preview = $result['preview']; ?>

                <div class="notice notice-info">
                    <p>
                        <strong>ドライラン結果</strong><br>
                        この時点ではデータベースやMedia Libraryに変更はありません。
                    </p>
                </div>

                <table class="widefat striped" style="max-width:900px;">
                    <tbody>
                        <tr>
                            <td>Album 新規作成予定</td>
                            <td><?php echo esc_html($preview['new_albums']); ?></td>
                        </tr>
                        <tr>
                            <td>Album 既存再利用</td>
                            <td><?php echo esc_html($preview['reuse_albums']); ?></td>
                        </tr>
                        <tr>
                            <td>Track 新規作成予定</td>
                            <td><?php echo esc_html($preview['new_tracks']); ?></td>
                        </tr>
                        <tr>
                            <td>Track 既存再利用</td>
                            <td><?php echo esc_html($preview['reuse_tracks']); ?></td>
                        </tr>
                        <tr>
                            <td>同名重複・要確認</td>
                            <td><?php echo esc_html($preview['ambiguous']); ?></td>
                        </tr>
                    </tbody>
                </table>

                <h2>詳細</h2>

                <?php foreach ($preview['albums'] as $album): ?>

                    <div style="margin:20px 0;padding:15px;background:#fff;border:1px solid #ccd0d4;max-width:1000px;">

                        <h3 style="margin-top:0;">
                            <?php echo esc_html($album['title']); ?>
                        </h3>

                        <p>
                            Album:
                            <strong>
                                <?php
                                echo esc_html(
                                    $album['method'] === 'new'
                                        ? '新規作成'
                                        : ($album['method'] === 'source_id'
                                            ? '既存再利用（source_id）'
                                            : ($album['method'] === 'title'
                                                ? '既存再利用（タイトル一致）'
                                                : '要確認')));
                                ?>
                            </strong>

                            <?php if ($album['id']): ?>
                                / ID <?php echo esc_html($album['id']); ?>
                            <?php endif; ?>
                        </p>

                        <ol>
                            <?php foreach ($album['tracks'] as $track): ?>
                                <li>
                                    <?php echo esc_html($track['title']); ?>
                                    —
                                    <strong>
                                        <?php
                                        echo esc_html(
                                            $track['method'] === 'new'
                                                ? '新規'
                                                : ($track['method'] === 'source_id'
                                                    ? '既存（source_id）'
                                                    : ($track['method'] === 'title'
                                                        ? '既存（タイトル一致）'
                                                        : '要確認')));
                                        ?>
                                    </strong>

                                    <?php if ($track['id']): ?>
                                        / ID <?php echo esc_html($track['id']); ?>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ol>

                    </div>

                <?php endforeach; ?>

                <?php if (!empty($preview['errors'])): ?>
                    <div class="notice notice-error">
                        <p><strong>要確認</strong></p>
                        <ul>
                            <?php foreach ($preview['errors'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

            <?php else: ?>

                <div class="notice <?php echo empty($result['errors']) ? 'notice-success' : 'notice-warning'; ?>">
                    <p>
                        <strong>移行結果</strong>
                    </p>

                    <ul>
                        <li>Album: <?php echo esc_html($result['albums']); ?></li>
                        <li>Album 新規作成: <?php echo esc_html($result['new_albums']); ?></li>
                        <li>Album 再利用: <?php echo esc_html($result['reused_albums']); ?></li>
                        <li>Track: <?php echo esc_html($result['tracks']); ?></li>
                        <li>Track 新規作成: <?php echo esc_html($result['new_tracks']); ?></li>
                        <li>Track 再利用: <?php echo esc_html($result['reused_tracks']); ?></li>
                        <li>Media: <?php echo esc_html($result['attachments']); ?></li>
                        <li>Errors: <?php echo esc_html(count($result['errors'])); ?></li>
                    </ul>
                </div>

                <?php if (!empty($result['errors'])): ?>

                    <div class="notice notice-error">
                        <p><strong>エラー / 要確認</strong></p>
                        <ul>
                            <?php foreach ($result['errors'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <p>
                            すでに実行された変更は自動rollbackされません。
                            エラーが残っている状態では、再実行する前に内容を確認してください。
                        </p>
                    </div>

                <?php endif; ?>

            <?php endif; ?>

        <?php endif; ?>

        <h2>Migration</h2>

        <p>
            <strong>STEP 1:</strong>
            ドライランで既存投稿の再利用 / 新規作成予定を確認します。
        </p>

        <p>
            <strong>STEP 2:</strong>
            内容に問題がなければ「移行を実行」を押します。
        </p>

        <form method="post">
            <?php wp_nonce_field('tuougm_music_migration'); ?>

            <p>
                <button
                    type="submit"
                    name="tuougm_migration_action"
                    value="dry_run"
                    class="button"
                >
                    ドライラン
                </button>

                <button
                    type="submit"
                    name="tuougm_migration_action"
                    value="run"
                    class="button button-primary"
                    onclick="return confirm('本当に移行を実行しますか？既存投稿は原則上書きしませんが、Albumの曲順（album_tracks）は移行元カタログの順番に設定されます。');"
                >
                    移行を実行
                </button>
            </p>
        </form>

        <hr>

        <h2>移行対象</h2>

        <ul>
            <?php foreach (tuougm_player_catalog() as $album): ?>
                <li>
                    <strong>
                        <?php echo esc_html($album['title']); ?>
                    </strong>
                    —
                    <?php echo count($album['tracks']); ?> tracks
                </li>
            <?php endforeach; ?>
        </ul>

        <p>
            <strong>安全仕様：</strong>
            既存投稿のタイトル・本文・入力済みACF・アイキャッチ・track_audioは
            原則上書きしません。空欄のみ補完します。
        </p>

    </div>
    <?php
}