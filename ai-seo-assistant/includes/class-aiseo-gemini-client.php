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
    const REQUEST_TIMEOUT = 120;

    /**
     * Gemini API key.
     *
     * @var string
     */
    private $api_key;

    /**
     * Prompt builder.
     *
     * @var AISEO_Prompt_Builder
     */
    private $prompt_builder;

    public function __construct( $api_key, AISEO_Prompt_Builder $prompt_builder = null ) {
        $this->api_key = trim( (string) $api_key );
        $this->prompt_builder = $prompt_builder ? $prompt_builder : new AISEO_Prompt_Builder();
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

    public function generate_article( $keywords, $article_type, $tone = 'standard', array $site_profile = array(), $site_knowledge = '' ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $prompt_parts = $this->build_prompt_parts( $keywords, $article_type, $tone, $site_profile, $site_knowledge );
        $response = $this->request(
            $prompt_parts,
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

    private function request( $prompt_parts, $max_output_tokens ) {
        if ( is_string( $prompt_parts ) ) {
            $prompt_parts = array( $prompt_parts );
        }

        $contents = array();
        foreach ( $prompt_parts as $prompt_text ) {
            $contents[] = array(
                'parts' => array(
                    array(
                        'text' => $prompt_text,
                    ),
                ),
            );
        }

        $body = array(
            'contents' => $contents,
            'generationConfig' => array(
                'maxOutputTokens' => $max_output_tokens,
            ),
        );

        $attempts = 2;
        $response = null;

        for ( $i = 0; $i < $attempts; $i++ ) {
            $response = wp_remote_post(
                self::API_URL,
                array(
                    'timeout' => self::REQUEST_TIMEOUT,
                    'headers' => array(
                        'x-goog-api-key' => $this->api_key,
                        'Content-Type' => 'application/json',
                    ),
                    'body' => wp_json_encode( $body ),
                )
            );

            if ( is_wp_error( $response ) ) {
                // 最後の試行でなければ短いバックオフして再試行
                if ( $i + 1 < $attempts ) {
                    sleep( 1 );
                    continue;
                }

                return $response;
            }

            break;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 > $status_code || 300 <= $status_code ) {
            $message = 'Gemini APIへの接続に失敗しました。';
            if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
                $message = $body_json['error']['message'];
            } elseif ( is_wp_error( $response ) ) {
                $message = $response->get_error_message();
            }
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

    /**
     * Generate booster suggestions using a custom prompt payload.
     *
     * @param string $prompt_text
     * @param int $max_output_tokens
     * @return string|WP_Error
     */
    public function generate_booster_suggestions( $prompt_text, $max_output_tokens = 800 ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $response = $this->request( array( $this->prompt_builder->get_system_prompt(), $prompt_text ), $max_output_tokens );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $content = $this->extract_text( $response );

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'Geminiから提案を取得できませんでした。' );
        }

        return $content;
    }

    /**
     * Generate image via Gemini-like image endpoint.
     * Note: this is a best-effort implementation and may need endpoint/config updates.
     *
     * @param string $prompt
     * @param string $size
     * @return string|WP_Error  Base64-encoded PNG data URI on success.
     */
    public function generate_image( $prompt, $size = '1024x1024' ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $image_api = 'https://generativelanguage.googleapis.com/v1beta/images:generate';

        $body = array(
            'model' => 'image-bison-001',
            'prompt' => (string) $prompt,
            'size' => $size,
        );

        $response = wp_remote_post(
            $image_api,
            array(
                'timeout' => 120,
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
            $message = '画像生成APIへの接続に失敗しました。';
            if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
                $message = $body_json['error']['message'];
            } elseif ( is_string( $body_text = wp_remote_retrieve_body( $response ) ) && '' !== trim( $body_text ) ) {
                $message = $message . ' ' . wp_trim_words( strip_tags( $body_text ), 30, '...' );
            }
            return new WP_Error( 'aiseo_image_api_error', $message );
        }

        // Attempt to extract base64 bytes. Different endpoints may vary.
        if ( isset( $body_json['artifacts'][0]['binary'] ) ) {
            $b64 = $body_json['artifacts'][0]['binary'];
            return 'data:image/png;base64,' . $b64;
        }

        if ( isset( $body_json['artifacts'][0]['image'] ) ) {
            return 'data:image/png;base64,' . $body_json['artifacts'][0]['image'];
        }

        if ( isset( $body_json['data'][0]['b64_json'] ) ) {
            return 'data:image/png;base64,' . $body_json['data'][0]['b64_json'];
        }

        if ( isset( $body_json['data'][0]['image'] ) ) {
            return 'data:image/png;base64,' . $body_json['data'][0]['image'];
        }

        if ( isset( $body_json['image'] ) ) {
            return 'data:image/png;base64,' . $body_json['image'];
        }

        return new WP_Error( 'aiseo_image_no_data', '画像データがレスポンスに含まれていません。' );
    }

    /**
     * Generate an image prompt based on the generated article.
     *
     * @param string $article_content
     * @return string|WP_Error
     */
    public function generate_image_prompt( $article_content ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $prompt = "以下は記事本文です。この記事に最適な画像生成プロンプトを日本語で1つ生成してください。\n" .
            "生成プロンプトは画像生成APIに直接渡せる形式にしてください。\n\n" .
            "記事本文:\n" . trim( (string) $article_content );

        $response = $this->request( array( $this->prompt_builder->get_system_prompt(), $prompt ), 160 );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $content = $this->extract_text( $response );
        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'Geminiから画像生成プロンプトを取得できませんでした。' );
        }

        return $content;
    }

    private function build_prompt_parts( $keywords, $article_type, $tone, array $site_profile = array(), $site_knowledge = '' ) {
        return array(
            $this->prompt_builder->get_system_prompt(),
            $this->prompt_builder->build_user_prompt( $keywords, $article_type, $tone, $site_profile, $site_knowledge ),
        );
    }
}
