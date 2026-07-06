<?php
/**
 * Admin screen for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Admin {
    const OPTION_API_KEY = 'aiseo_gemini_api_key';
    const OPTION_SITE_PROFILE = 'ai_site_profile';
    const OPTION_SITE_KNOWLEDGE = 'ai_site_knowledge';
    const OPTION_SITE_PROFILE_UPDATED = 'ai_site_profile_updated';
    const OPTION_SITE_PROFILE_POSTS = 'ai_site_profile_posts';
    const OPTION_SITE_PROFILE_LIMIT = 'aiseo_site_profile_limit';
    const OPTION_SITE_PROFILE_INCLUDE_PAGES = 'aiseo_site_profile_include_pages';
    const OPTION_SITE_PROFILE_SOURCE = 'aiseo_site_profile_source';
    const NONCE_ACTION = 'aiseo_admin_action';
    const NONCE_NAME = 'aiseo_nonce';

    private $messages = array();
    private $generated_content = '';
    private $generated_title = '';
    private $generated_image_prompt = '';
    private $selected_tone = 'standard';
    private $active_tab = 'generate';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_post' ) );
    }

    public function register_menu() {
        add_menu_page(
            'AI SEO Assistant',
            'AI SEO Assistant',
            'manage_options',
            'aiseo-assistant',
            array( $this, 'render_page' ),
            'dashicons-edit-page',
            80
        );
    }

    public function handle_post() {
        if ( empty( $_POST['aiseo_action'] ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

        $action = sanitize_key( wp_unslash( $_POST['aiseo_action'] ) );

        if ( 'save_settings' === $action ) {
            $this->active_tab = 'settings';
            $this->save_settings();
            return;
        }

        if ( 'test_connection' === $action ) {
            $this->active_tab = 'settings';
            $this->test_connection();
            return;
        }

        if ( 'generate_article' === $action ) {
            $this->active_tab = 'generate';
            $this->generate_article();
            return;
        }

        if ( 'learn_site_profile' === $action ) {
            $this->active_tab = 'profile';
            $this->learn_site_profile();
            return;
        }

        if ( 'save_draft' === $action ) {
            $this->active_tab = 'generate';
            $this->save_draft();
        }

        if ( 'run_booster' === $action ) {
            $this->active_tab = 'generate';
            $this->run_booster();
            return;
        }

        if ( 'generate_image' === $action ) {
            $this->active_tab = 'generate';
            $this->generate_image();
            return;
        }
    }

    public function render_page() {
        $api_key = get_option( self::OPTION_API_KEY, '' );
        $article_types = $this->get_article_types();
        $tones = $this->get_tones();
        $messages = $this->messages;
        $generated_content = $this->generated_content;
        $generated_title = $this->generated_title;
        $generated_image_prompt = $this->generated_image_prompt;
        $selected_tone = $this->selected_tone;
        $active_tab = $this->active_tab;
        $site_profile = get_option( self::OPTION_SITE_PROFILE, array() );
        $site_knowledge = get_option( self::OPTION_SITE_KNOWLEDGE, '' );
        $site_profile_updated = get_option( self::OPTION_SITE_PROFILE_UPDATED, '' );
        $site_profile_posts = absint( get_option( self::OPTION_SITE_PROFILE_POSTS, 0 ) );
        $site_profile_limit = absint( get_option( self::OPTION_SITE_PROFILE_LIMIT, 20 ) );
        $site_profile_source = get_option( self::OPTION_SITE_PROFILE_SOURCE, '' );
        if ( '' === $site_profile_source ) {
            $site_profile_source = (bool) get_option( self::OPTION_SITE_PROFILE_INCLUDE_PAGES, false ) ? 'all' : 'posts';
        }
        $site_profile_json = ! empty( $site_profile ) ? wp_json_encode( $site_profile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) : '';

        require AISEO_ASSISTANT_DIR . 'templates/admin-page.php';
    }

    private function save_settings() {
        $api_key = isset( $_POST['gemini_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['gemini_api_key'] ) ) : '';
        update_option( self::OPTION_API_KEY, $api_key, false );
        $this->add_message( 'success', 'APIキーを保存しました。' );
    }

    private function test_connection() {
        $api_key = get_option( self::OPTION_API_KEY, '' );
        $client = new AISEO_Gemini_Client( $api_key );
        $result = $client->test_connection();

        if ( is_wp_error( $result ) ) {
            $this->add_message( 'error', '接続テストに失敗しました: ' . $result->get_error_message() );
            return;
        }

        $this->add_message( 'success', 'Gemini APIへの接続に成功しました。' );
    }

    private function generate_article() {
        $keywords = isset( $_POST['keywords'] ) ? sanitize_text_field( wp_unslash( $_POST['keywords'] ) ) : '';
        $article_type = isset( $_POST['article_type'] ) ? sanitize_key( wp_unslash( $_POST['article_type'] ) ) : 'blog';

        if ( '' === $keywords ) {
            $this->add_message( 'error', 'キーワードを入力してください。' );
            return;
        }

        $tone = isset( $_POST['tone'] ) ? sanitize_key( wp_unslash( $_POST['tone'] ) ) : 'standard';
        if ( ! array_key_exists( $tone, $this->get_tones() ) ) {
            $tone = 'standard';
        }
        $this->selected_tone = $tone;

        if ( ! array_key_exists( $article_type, $this->get_article_types() ) ) {
            $article_type = 'blog';
        }

        $site_profile = get_option( self::OPTION_SITE_PROFILE, array() );
        if ( ! is_array( $site_profile ) ) {
            $site_profile = array();
        }
        $site_knowledge = get_option( self::OPTION_SITE_KNOWLEDGE, '' );

        $client = new AISEO_Gemini_Client( get_option( self::OPTION_API_KEY, '' ) );
        $content = $client->generate_article( $keywords, $article_type, $tone, $site_profile, $site_knowledge );

        if ( is_wp_error( $content ) ) {
            $this->add_message( 'error', '記事生成に失敗しました: ' . $content->get_error_message() );
            return;
        }

        $this->generated_title = $this->extract_title( $content, $keywords );
        $content = $this->normalize_generated_content( $content, $this->generated_title );
        $this->generated_content = $content;

        $image_prompt = $client->generate_image_prompt( $content );
        if ( ! is_wp_error( $image_prompt ) ) {
            $this->generated_image_prompt = $image_prompt;
        } else {
            $this->generated_image_prompt = '';
            $this->add_message( 'warning', '画像生成プロンプトの自動作成に失敗しました: ' . $image_prompt->get_error_message() );
        }

        $this->add_message( 'success', '記事を生成しました。内容を確認して下書き保存できます。' );
    }

    private function learn_site_profile() {
        $limit = isset( $_POST['site_profile_limit'] ) ? absint( wp_unslash( $_POST['site_profile_limit'] ) ) : 20;
        $source = isset( $_POST['site_profile_source'] ) ? sanitize_key( wp_unslash( $_POST['site_profile_source'] ) ) : 'posts';

        $limit = max( 1, min( 100, $limit ) );
        if ( ! in_array( $source, array( 'posts', 'pages', 'all' ), true ) ) {
            $source = 'posts';
        }

        update_option( self::OPTION_SITE_PROFILE_LIMIT, $limit, false );
        update_option( self::OPTION_SITE_PROFILE_SOURCE, $source, false );
        update_option( self::OPTION_SITE_PROFILE_INCLUDE_PAGES, 'all' === $source ? 1 : 0, false );

        $analyzer = new AISEO_Site_Profile_Analyzer();
        $profile = $analyzer->analyze( $limit, $source );

        if ( is_wp_error( $profile ) ) {
            $this->add_message( 'error', 'サイトプロファイルの学習に失敗しました: ' . $profile->get_error_message() );
            return;
        }

        $site_knowledge = $analyzer->build_site_knowledge( $profile );

        update_option( self::OPTION_SITE_PROFILE, $profile, false );
        update_option( self::OPTION_SITE_KNOWLEDGE, $site_knowledge, false );
        update_option( self::OPTION_SITE_PROFILE_UPDATED, current_time( 'mysql' ), false );
        update_option( self::OPTION_SITE_PROFILE_POSTS, $analyzer->get_last_analyzed_count(), false );

        $this->add_message( 'success', 'サイトプロファイルを更新しました。以降の記事生成で既存記事の傾向を参照します。' );
    }

    private function save_draft() {
        $title = isset( $_POST['generated_title'] ) ? sanitize_text_field( wp_unslash( $_POST['generated_title'] ) ) : '';
        $content = isset( $_POST['generated_content'] ) ? wp_kses_post( wp_unslash( $_POST['generated_content'] ) ) : '';

        if ( '' === $title || '' === $content ) {
            $this->add_message( 'error', '下書き保存するタイトルと本文がありません。' );
            return;
        }

        $content = $this->convert_markdown_headings_to_html( $content );

        $post_id = wp_insert_post(
            array(
                'post_title' => $title,
                'post_content' => $content,
                'post_status' => 'draft',
                'post_type' => 'post',
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            $this->add_message( 'error', '下書き保存に失敗しました: ' . $post_id->get_error_message() );
            $this->generated_title = $title;
            $this->generated_content = $content;
            return;
        }

        $edit_link = get_edit_post_link( $post_id, '' );
        $message = '下書きを保存しました。';

        if ( $edit_link ) {
            $message .= ' <a href="' . esc_url( $edit_link ) . '">投稿を編集</a>';
        }

        $this->add_message( 'success', $message );
    }

    private function run_booster() {
        $content = isset( $_POST['generated_content'] ) ? wp_kses_post( wp_unslash( $_POST['generated_content'] ) ) : '';

        if ( '' === $content ) {
            $this->add_message( 'error', 'ブーストを実行する本文がありません。' );
            return;
        }

        $site_profile = get_option( self::OPTION_SITE_PROFILE, array() );
        if ( ! is_array( $site_profile ) ) {
            $site_profile = array();
        }

        $client = new AISEO_Gemini_Client( get_option( self::OPTION_API_KEY, '' ) );
        $booster = new AISEO_Originality_Booster( $client );
        $suggestions = $booster->suggest( $content, $site_profile );

        if ( is_wp_error( $suggestions ) ) {
            $this->add_message( 'error', 'オリジナル性ブースターの実行に失敗しました: ' . $suggestions->get_error_message() );
            return;
        }

        // Append suggestions below the generated content for easy editing.
        $this->generated_content = $content . "\n\n<!-- Originality Booster Suggestions -->\n" . $suggestions;
        $this->generated_title = isset( $_POST['generated_title'] ) ? sanitize_text_field( wp_unslash( $_POST['generated_title'] ) ) : $this->generated_title;
        $this->add_message( 'success', 'オリジナル性ブースターの提案を取得しました。内容を確認して下書き保存できます。' );
    }

    private function generate_image() {
        $prompt = isset( $_POST['image_prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['image_prompt'] ) ) : '';
        $size = isset( $_POST['image_size'] ) ? sanitize_text_field( wp_unslash( $_POST['image_size'] ) ) : '1024x1024';
        $image_action = isset( $_POST['image_action'] ) ? sanitize_key( wp_unslash( $_POST['image_action'] ) ) : 'preview';
        $this->generated_title = isset( $_POST['generated_title'] ) ? sanitize_text_field( wp_unslash( $_POST['generated_title'] ) ) : $this->generated_title;
        $this->generated_content = isset( $_POST['generated_content'] ) ? wp_kses_post( wp_unslash( $_POST['generated_content'] ) ) : $this->generated_content;
        $this->generated_image_prompt = isset( $_POST['image_prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['image_prompt'] ) ) : $this->generated_image_prompt;

        if ( '' === $prompt ) {
            $this->add_message( 'error', '画像生成用のプロンプトを入力してください。' );
            return;
        }

        $client = new AISEO_Gemini_Client( get_option( self::OPTION_API_KEY, '' ) );
        $result = $client->generate_image( $prompt, $size );

        if ( is_wp_error( $result ) ) {
            $this->add_message( 'error', '画像生成に失敗しました: ' . $result->get_error_message() );
            return;
        }

        $img_html = '<div><strong>生成画像プレビュー</strong><br><img src="' . esc_url( $result ) . '" style="max-width:300px;height:auto;border:1px solid #ddd;padding:4px;margin-top:6px;"/></div>';

        if ( 'embed' === $image_action ) {
            $this->generated_content = $this->generated_content . "\n\n<img src='" . esc_url( $result ) . "' alt='生成画像'>\n";
            $this->add_message( 'success', '画像を記事本文に埋め込みました。下書き保存前に内容を確認してください。' );
        } else {
            $this->add_message( 'success', $img_html );
        }
    }

    private function get_article_types() {
        return array(
            'blog' => 'ブログ記事',
            'list' => 'リスト記事',
            'howto' => 'ハウツー記事',
        );
    }

    private function get_tones() {
        return array(
            'standard' => '標準',
            'friendly' => '親しみやすい',
            'professional' => '専門的・信頼',
            'clear' => '分かりやすい',
        );
    }

    private function normalize_generated_content( $content, $title ) {
        $lines = preg_split( '/\r\n|\r|\n/', $content );
        $cleaned = array();
        $skip_next_title_line = false;

        foreach ( $lines as $line ) {
            $trimmed = trim( $line );

            if ( '' === $trimmed ) {
                $cleaned[] = '';
                continue;
            }

            if ( preg_match( '/^タイトル[:：]\s*(.*)$/u', $trimmed, $matches ) ) {
                if ( '' === $matches[1] ) {
                    $skip_next_title_line = true;
                    continue;
                }
                continue;
            }

            if ( $skip_next_title_line ) {
                $skip_next_title_line = false;
                if ( $this->is_title_line( $line, $title ) ) {
                    continue;
                }
            }

            if ( preg_match( '/^本文[:：]\s*(.*)$/u', $trimmed, $body_matches ) ) {
                if ( '' !== $body_matches[1] ) {
                    $cleaned[] = $body_matches[1];
                }
                continue;
            }

            $cleaned[] = $line;
        }

        while ( ! empty( $cleaned ) ) {
            $first_line = trim( $cleaned[0] );
            if ( '' === $first_line ) {
                array_shift( $cleaned );
                continue;
            }

            if ( $this->is_title_line( $first_line, $title ) ) {
                array_shift( $cleaned );
                continue;
            }

            break;
        }

        return trim( implode( "\n", $cleaned ) );
    }

    private function is_title_line( $line, $title ) {
        $normalized_line = $this->normalize_text( $line );
        $normalized_title = $this->normalize_text( $title );

        if ( $normalized_line === $normalized_title ) {
            return true;
        }

        if ( preg_match( '/^#{1,6}\s*(.*)$/u', trim( $line ), $matches ) ) {
            return $this->normalize_text( $matches[1] ) === $normalized_title;
        }

        return false;
    }

    private function normalize_text( $text ) {
        $text = trim( $text );
        $text = preg_replace( '/^#+\s*/', '', $text );
        $text = preg_replace( '/\s+/u', ' ', $text );
        return mb_strtolower( $text, 'UTF-8' );
    }

    private function convert_markdown_headings_to_html( $content ) {
        $lines = preg_split( '/\r\n|\r|\n/', $content );
        foreach ( $lines as &$line ) {
            if ( preg_match( '/^(#{1,6})\s+(.+)$/', $line, $matches ) ) {
                $level = min( 6, strlen( $matches[1] ) );
                $text = esc_html( trim( $matches[2] ) );
                $line = sprintf( '<h%d>%s</h%d>', $level, $text, $level );
            }
        }
        return implode( "\n", $lines );
    }

    private function extract_title( $content, $fallback ) {
        $lines = preg_split( '/\r\n|\r|\n/', $content );
        $next_line_is_title = false;

        foreach ( $lines as $line ) {
            $line = trim( $line );

            if ( '' === $line ) {
                continue;
            }

            if ( $next_line_is_title ) {
                return $this->sanitize_title( $line );
            }

            if ( preg_match( '/^タイトル[:：]\s*(.*)$/u', $line, $matches ) ) {
                if ( '' !== $matches[1] ) {
                    return $this->sanitize_title( $matches[1] );
                }

                $next_line_is_title = true;
                continue;
            }

            if ( preg_match( '/^本文[:：]\s*$/u', $line ) ) {
                break;
            }

            if ( preg_match( '/^#{1,6}\s*(.*)$/u', $line, $heading_matches ) ) {
                return $this->sanitize_title( $heading_matches[1] );
            }

            return $this->sanitize_title( $line );
        }

        return $fallback;
    }

    private function sanitize_title( $title ) {
        $title = trim( $title );
        $title = preg_replace( '/^#+\s*/', '', $title );
        return wp_trim_words( wp_strip_all_tags( $title ), 50, '' );
    }

    private function add_message( $type, $message ) {
        $this->messages[] = array(
            'type' => $type,
            'message' => $message,
        );
    }
}
