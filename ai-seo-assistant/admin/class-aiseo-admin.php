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
    const NONCE_ACTION = 'aiseo_admin_action';
    const NONCE_NAME = 'aiseo_nonce';

    private $messages = array();
    private $generated_content = '';
    private $generated_title = '';
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

        if ( 'save_draft' === $action ) {
            $this->active_tab = 'generate';
            $this->save_draft();
        }
    }

    public function render_page() {
        $api_key = get_option( self::OPTION_API_KEY, '' );
        $article_types = $this->get_article_types();
        $tones = $this->get_tones();
        $messages = $this->messages;
        $generated_content = $this->generated_content;
        $generated_title = $this->generated_title;
        $selected_tone = $this->selected_tone;
        $active_tab = $this->active_tab;

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

        $client = new AISEO_Gemini_Client( get_option( self::OPTION_API_KEY, '' ) );
        $content = $client->generate_article( $keywords, $article_type, $tone );

        if ( is_wp_error( $content ) ) {
            $this->add_message( 'error', '記事生成に失敗しました: ' . $content->get_error_message() );
            return;
        }

        $this->generated_title = $this->extract_title( $content, $keywords );
        $content = $this->normalize_generated_content( $content, $this->generated_title );
        $this->generated_content = $content;
        $this->add_message( 'success', '記事を生成しました。内容を確認して下書き保存できます。' );
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
        $skip_next_body_heading = false;
        $first_content_line_processed = false;

        foreach ( $lines as $line ) {
            $trimmed = trim( $line );

            if ( '' === $trimmed ) {
                if ( ! $skip_next_title_line && ! $skip_next_body_heading ) {
                    $cleaned[] = '';
                }
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
                continue;
            }

            if ( preg_match( '/^本文[:：]\s*(.*)$/u', $trimmed, $body_matches ) ) {
                if ( '' !== $body_matches[1] ) {
                    $cleaned[] = $body_matches[1];
                } else {
                    $skip_next_body_heading = true;
                }
                continue;
            }

            if ( $skip_next_body_heading ) {
                $skip_next_body_heading = false;
                if ( preg_match( '/^#{1,6}\s*(.*)$/u', $trimmed, $body_heading_matches ) ) {
                    $normalized_heading = trim( $body_heading_matches[1] );
                    if ( $normalized_heading === $title ) {
                        continue;
                    }
                }
            }

            if ( ! $first_content_line_processed && preg_match( '/^#{1,6}\s*(.*)$/u', $trimmed, $heading_matches ) ) {
                $first_content_line_processed = true;
                continue;
            }

            $first_content_line_processed = true;
            $cleaned[] = $line;
        }

        return trim( implode( "\n", $cleaned ) );
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
