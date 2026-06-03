<?php
/**
 * OpenAI API client for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_OpenAI_Client {
    const API_URL = 'https://api.openai.com/v1/responses';

    /**
     * OpenAI API key.
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
            array(
                'model' => 'gpt-5-mini',
                'input' => '接続テストです。「OK」とだけ返してください。',
                'max_output_tokens' => 16,
            )
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
            array(
                'model' => 'gpt-5-mini',
                'input' => $prompt,
                'max_output_tokens' => 2200,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $content = $this->extract_text( $response );

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'OpenAIから記事本文を取得できませんでした。' );
        }

        return $content;
    }

    private function request( $body ) {
        $response = wp_remote_post(
            self::API_URL,
            array(
                'timeout' => 60,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
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
            $message = isset( $body_json['error']['message'] ) ? $body_json['error']['message'] : 'OpenAI APIへの接続に失敗しました。';
            return new WP_Error( 'aiseo_openai_error', $message );
        }

        if ( ! is_array( $body_json ) ) {
            return new WP_Error( 'aiseo_invalid_response', 'OpenAI APIのレスポンス形式が不正です。' );
        }

        return $body_json;
    }

    private function extract_text( $response ) {
        if ( isset( $response['output_text'] ) && is_string( $response['output_text'] ) ) {
            return trim( $response['output_text'] );
        }

        if ( empty( $response['output'] ) || ! is_array( $response['output'] ) ) {
            return '';
        }

        $texts = array();

        foreach ( $response['output'] as $output ) {
            if ( empty( $output['content'] ) || ! is_array( $output['content'] ) ) {
                continue;
            }

            foreach ( $output['content'] as $content ) {
                if ( isset( $content['text'] ) && is_string( $content['text'] ) ) {
                    $texts[] = $content['text'];
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
            "あなたは日本語SEO記事の編集者です。\nキーワード: %s\n記事タイプ: %s\n\n条件:\n- WordPress投稿用の記事を作成する\n- タイトルを1つ提案する\n- 導入文、見出し、本文、まとめを含める\n- 読者に分かりやすい自然な日本語にする\n- 誇張や根拠のない断定を避ける\n- HTMLではなく、Gutenbergで扱いやすいプレーンテキストと見出し記法で出力する\n\n出力形式:\nタイトル: ...\n\n本文:\n...",
            $keywords,
            $type_label
        );
    }
}
