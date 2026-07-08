<?php
/**
 * Admin page template.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings_url = admin_url( 'admin.php?page=aiseo-assistant&tab=settings' );
$generate_url = admin_url( 'admin.php?page=aiseo-assistant&tab=generate' );
$profile_url = admin_url( 'admin.php?page=aiseo-assistant&tab=profile' );
$media_url = admin_url( 'admin.php?page=aiseo-assistant&tab=media' );
$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $active_tab;

if ( ! in_array( $current_tab, array( 'generate', 'profile', 'media', 'settings' ), true ) ) {
    $current_tab = 'generate';
}
?>
<div class="wrap">
    <h1>AI SEO Assistant</h1>

    <?php foreach ( $messages as $message ) : ?>
        <div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> is-dismissible">
            <p><?php echo wp_kses_post( $message['message'] ); ?></p>
        </div>
    <?php endforeach; ?>

    <h2 class="nav-tab-wrapper">
        <a class="nav-tab <?php echo 'generate' === $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $generate_url ); ?>">記事生成</a>
        <a class="nav-tab <?php echo 'profile' === $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $profile_url ); ?>">サイトプロファイル</a>
        <a class="nav-tab <?php echo 'media' === $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $media_url ); ?>">画像設定</a>
        <a class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $settings_url ); ?>">設定</a>
    </h2>

    <?php if ( 'settings' === $current_tab ) : ?>
        <form method="post" action="">
            <?php wp_nonce_field( AISEO_Admin::NONCE_ACTION, AISEO_Admin::NONCE_NAME ); ?>
            <input type="hidden" name="aiseo_action" value="save_settings">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="gemini_api_key">Gemini APIキー</label></th>
                    <td>
                        <input
                            type="password"
                            id="gemini_api_key"
                            name="gemini_api_key"
                            value="<?php echo esc_attr( $api_key ); ?>"
                            class="regular-text"
                            autocomplete="off"
                        >
                    </td>
                </tr>
            </table>

            <?php submit_button( '設定を保存' ); ?>
        </form>

        <form method="post" action="">
            <?php wp_nonce_field( AISEO_Admin::NONCE_ACTION, AISEO_Admin::NONCE_NAME ); ?>
            <input type="hidden" name="aiseo_action" value="test_connection">
            <?php submit_button( 'Gemini接続テスト', 'secondary' ); ?>
        </form>
    <?php elseif ( 'profile' === $current_tab ) : ?>
        <h2>サイトプロファイル</h2>
        <p>公開済み記事を解析し、文体・構成・レイアウト・画像利用傾向をJSONとして保存します。</p>

        <form method="post" action="">
            <?php wp_nonce_field( AISEO_Admin::NONCE_ACTION, AISEO_Admin::NONCE_NAME ); ?>
            <input type="hidden" name="aiseo_action" value="learn_site_profile">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="site_profile_limit">学習記事数</label></th>
                    <td>
                        <input
                            type="number"
                            id="site_profile_limit"
                            name="site_profile_limit"
                            value="<?php echo esc_attr( $site_profile_limit ); ?>"
                            min="1"
                            max="100"
                            class="small-text"
                        >
                        <p class="description">投稿を解析する場合の件数です。固定ページは選択時に公開済みページを全件解析します。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="site_profile_source">解析対象</label></th>
                    <td>
                        <select id="site_profile_source" name="site_profile_source">
                            <option value="posts" <?php selected( $site_profile_source, 'posts' ); ?>>投稿のみ</option>
                            <option value="pages" <?php selected( $site_profile_source, 'pages' ); ?>>固定ページのみ</option>
                            <option value="all" <?php selected( $site_profile_source, 'all' ); ?>>投稿 + 固定ページ</option>
                        </select>
                        <p class="description">固定ページのみ、または投稿と固定ページの両方を対象にできます。</p>
                    </td>
                </tr>
            </table>

            <?php submit_button( empty( $site_profile ) ? 'サイトを学習' : 'サイトプロファイル更新' ); ?>
        </form>

        <hr>
        <h2>学習状況</h2>
        <table class="widefat striped" style="max-width: 720px;">
            <tbody>
                <tr>
                    <th scope="row">最終更新日時</th>
                    <td><?php echo '' !== $site_profile_updated ? esc_html( $site_profile_updated ) : '未学習'; ?></td>
                </tr>
                <tr>
                    <th scope="row">学習記事数</th>
                    <td><?php echo esc_html( (string) $site_profile_posts ); ?></td>
                </tr>
                <tr>
                    <th scope="row">保存キー</th>
                    <td><code>ai_site_profile</code> / <code>ai_site_knowledge</code></td>
                </tr>
            </tbody>
        </table>

        <h2>Site Knowledge</h2>
        <?php if ( '' !== $site_knowledge ) : ?>
            <textarea
                class="large-text"
                rows="12"
                readonly
            ><?php echo esc_textarea( $site_knowledge ); ?></textarea>
        <?php else : ?>
            <p>まだSite Knowledgeは作成されていません。</p>
        <?php endif; ?>

        <h2>JSONプレビュー</h2>
        <?php if ( '' !== $site_profile_json ) : ?>
            <textarea
                class="large-text code"
                rows="22"
                readonly
            ><?php echo esc_textarea( $site_profile_json ); ?></textarea>
        <?php else : ?>
            <p>まだサイトプロファイルは作成されていません。</p>
        <?php endif; ?>
    <?php elseif ( 'media' === $current_tab ) : ?>
        <h2>アイキャッチ画像サイズ</h2>
        <p>既存記事のアイキャッチ画像サイズを解析し、新規生成時の推奨サイズを自動決定します。</p>
        <table class="widefat striped" style="max-width: 720px;">
            <tbody>
                <tr>
                    <th scope="row">推奨サイズ</th>
                    <td><?php echo esc_html( $recommended_featured_size['recommended_width'] . ' x ' . $recommended_featured_size['recommended_height'] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">アスペクト比</th>
                    <td><?php echo esc_html( $recommended_featured_size['aspect_ratio'] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">集計元記事数</th>
                    <td><?php echo esc_html( (string) $recommended_featured_size['source_count'] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">決定方法</th>
                    <td>
                        <?php
                        $decision_labels = array(
                            'mode' => '最頻出サイズ',
                            'median' => '中央値に近いサイズ',
                            'default' => 'デフォルト値(画像情報が不足)',
                        );
                        echo esc_html( $decision_labels[ $recommended_featured_size['decision_method'] ] ?? $recommended_featured_size['decision_method'] );
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2>画像生成API Provider状態</h2>
        <table class="widefat striped" style="max-width: 720px;">
            <tbody>
                <tr>
                    <th scope="row">Provider</th>
                    <td><?php echo esc_html( $image_provider_status['provider'] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">モデル</th>
                    <td><?php echo esc_html( $image_provider_status['model'] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">APIバージョン</th>
                    <td><?php echo esc_html( $image_provider_status['api_version'] ); ?></td>
                </tr>
                <tr>
                    <th scope="row">エンドポイント</th>
                    <td><code><?php echo esc_html( $image_provider_status['endpoint_base'] ); ?></code></td>
                </tr>
                <tr>
                    <th scope="row">APIキー</th>
                    <td><?php echo $image_provider_status['has_api_key'] ? '設定済み' : '未設定（設定タブから登録してください）'; ?></td>
                </tr>
            </tbody>
        </table>
        <p class="description">Provider/モデル/エンドポイントはコード側のデフォルト設定です。APIバージョン変更時はProviderクラスのみを修正すれば記事生成側には影響しません。</p>
    <?php else : ?>
        <?php if ( ! empty( $site_profile ) ) : ?>
            <div class="notice notice-info inline">
                <p>サイトプロファイル学習済みです。記事生成時に既存記事の文体・構成・レイアウト傾向を参照します。</p>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field( AISEO_Admin::NONCE_ACTION, AISEO_Admin::NONCE_NAME ); ?>
            <input type="hidden" name="aiseo_action" value="generate_article">

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="keywords">キーワード</label></th>
                    <td>
                        <input
                            type="text"
                            id="keywords"
                            name="keywords"
                            class="regular-text"
                            placeholder="例: WordPress SEO, AI記事生成"
                            required
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="article_type">記事タイプ</label></th>
                    <td>
                        <select id="article_type" name="article_type">
                            <?php foreach ( $article_types as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="tone">出力トーン</label></th>
                    <td>
                        <select id="tone" name="tone">
                            <?php foreach ( $tones as $value => $label ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_tone, $value ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button( '記事を生成' ); ?>
        </form>

        <?php if ( '' !== $generated_content ) : ?>
            <hr>
            <h2>生成結果</h2>
            <form method="post" action="">
                <?php wp_nonce_field( AISEO_Admin::NONCE_ACTION, AISEO_Admin::NONCE_NAME ); ?>
                <input type="hidden" name="generated_title" value="<?php echo esc_attr( $generated_title ); ?>">
                <input type="hidden" id="image_purpose_hidden" name="image_purpose" value="featured">
                <input type="hidden" id="featured_image_attachment_id" name="featured_image_attachment_id" value="<?php echo esc_attr( (string) $featured_image_attachment_id ); ?>">

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="generated_title">投稿タイトル</label></th>
                        <td>
                            <input
                                type="text"
                                id="generated_title"
                                name="generated_title"
                                value="<?php echo esc_attr( $generated_title ); ?>"
                                class="large-text"
                                required
                            >
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="generated_content">本文</label></th>
                        <td>
                            <textarea
                                id="generated_content"
                                name="generated_content"
                                rows="18"
                                class="large-text code"
                                required
                            ><?php echo esc_textarea( $generated_content ); ?></textarea>
                            <p class="description">本文内画像は <code>[aiseo_image id="..." size="..." alt="..."]</code> ショートコードで挿入されます。挿入位置は自由に編集できます。</p>
                        </td>
                    </tr>
                </table>

                <h3>画像生成</h3>
                <?php if ( $featured_image_attachment_id > 0 ) : ?>
                    <p>
                        <strong>設定中のアイキャッチ画像:</strong><br>
                        <?php echo wp_kses_post( wp_get_attachment_image( $featured_image_attachment_id, 'medium' ) ); ?>
                    </p>
                <?php endif; ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="image_prompt">画像生成プロンプト</label></th>
                        <td>
                            <input
                                type="text"
                                id="image_prompt"
                                name="image_prompt"
                                class="regular-text"
                                placeholder="例: 木製デスクの上でノートPCを使う日本人女性の写真"
                                value="<?php echo esc_attr( $generated_image_prompt ); ?>"
                            >
                            <p class="description">
                                アイキャッチ画像は推奨サイズ
                                <?php echo esc_html( $recommended_featured_size['recommended_width'] . 'x' . $recommended_featured_size['recommended_height'] ); ?>
                                （<a href="<?php echo esc_url( $media_url ); ?>">画像設定タブ</a>で確認）を参考に生成されます。
                            </p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="aiseo_action" value="save_draft" class="button button-primary">下書き保存</button>
                    <button type="submit" name="aiseo_action" value="run_booster" class="button button-secondary">オリジナル性ブースターを実行</button>
                    <button type="submit" name="aiseo_action" value="generate_image" class="button button-secondary" onclick="document.getElementById('image_purpose_hidden').value='featured';">アイキャッチ画像を生成</button>
                    <button type="submit" name="aiseo_action" value="generate_image" class="button button-secondary" onclick="document.getElementById('image_purpose_hidden').value='inline';">本文内画像を生成</button>
                </p>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
