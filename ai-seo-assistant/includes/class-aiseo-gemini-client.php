<?php
/**
 * Gemini API client for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Gemini_Client {
    const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';
    const MAX_OUTPUT_TOKENS = 3600;

    /**
     * Gemini API key.
     *
     * @var string
     */
    private $api_key;

    public function __construct( $api_key ) {
        $this->api_key = trim( (string) $api_key );
    }

    public function has_api_key() {
        return '' !== $this->api_key;
    }

    public function test_connection() {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $response = $this->request(
            '接続テストです。「OK」とだけ返してください。',
            16
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
    }

    public function generate_article( $keywords, $article_type ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $prompt = $this->build_prompt( $keywords, $article_type );
        $response = $this->request(
            $prompt,
            self::MAX_OUTPUT_TOKENS
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $content = $this->extract_text( $response );

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'Geminiから記事本文を取得できませんでした。' );
        }

        return $content;
    }

    private function request( $prompt, $max_output_tokens ) {
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt,
                        ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'maxOutputTokens' => $max_output_tokens,
            ),
        );

        $response = wp_remote_post(
            self::API_URL,
            array(
                'timeout' => 60,
                'headers' => array(
                    'x-goog-api-key' => $this->api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 > $status_code || 300 <= $status_code ) {
            $message = isset( $body_json['error']['message'] ) ? $body_json['error']['message'] : 'Gemini APIへの接続に失敗しました。';
            return new WP_Error( 'aiseo_gemini_error', $message );
        }

        if ( ! is_array( $body_json ) ) {
            return new WP_Error( 'aiseo_invalid_response', 'Gemini APIのレスポンス形式が不正です。' );
        }

        return $body_json;
    }

    private function extract_text( $response ) {
        if ( empty( $response['candidates'] ) || ! is_array( $response['candidates'] ) ) {
            return '';
        }

        $texts = array();

        foreach ( $response['candidates'] as $candidate ) {
            if ( empty( $candidate['content']['parts'] ) || ! is_array( $candidate['content']['parts'] ) ) {
                continue;
            }

            foreach ( $candidate['content']['parts'] as $part ) {
                if ( isset( $part['text'] ) && is_string( $part['text'] ) ) {
                    $texts[] = $part['text'];
                }
            }
        }

        return trim( implode( "\n", $texts ) );
    }

    private function build_prompt( $keywords, $article_type ) {
        $type_labels = array(
            'blog' => 'ブログ記事',
            'list' => 'リスト記事',
            'howto' => 'ハウツー記事',
        );

        $type_label = isset( $type_labels[ $article_type ] ) ? $type_labels[ $article_type ] : $type_labels['blog'];

        return sprintf(
            "あなたは日本語SEO記事の編集者です。\nキーワード: %s\n記事タイプ: %s\n\n条件:\n- WordPress投稿用の記事を作成する\n- タイトルを1つ提案する\n- 導入文、見出し、本文、まとめを含める\n- 読者に分かりやすい自然な日本語にする\n- 誇張や根拠のない断定を避ける\n- HTMLではなく、Gutenbergで扱いやすいプレーンテキストと見出し記法で出力する\n- 文章は最後まで切れずに完結させる\n\n出力形式:\nタイトル:\n\n本文:\n",
            $keywords,
            $type_label
        );
    }
}
