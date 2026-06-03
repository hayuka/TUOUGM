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
$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : $active_tab;

if ( ! in_array( $current_tab, array( 'generate', 'settings' ), true ) ) {
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
    <?php else : ?>
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
                <input type="hidden" name="aiseo_action" value="save_draft">

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
                        </td>
                    </tr>
                </table>

                <?php submit_button( '下書き保存' ); ?>
            </form>
        <?php endif; ?>
    <?php endif; ?>
</div>
