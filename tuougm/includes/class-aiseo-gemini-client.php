<?php
/**
 * Gemini API client for TUOUGM.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Gemini_Client implements AISEO_AI_Client_Interface {
    use AISEO_AI_Client_Connection_Test_Trait;

    const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent';
    const PROVIDER = 'gemini';
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
        $result = $this->connection_test();

        if ( 'success' === $result['status'] ) {
            return true;
        }

        return new WP_Error( 'aiseo_gemini_connection_test', $result['message'] );
    }

    public function connection_test() {
        if ( ! $this->has_api_key() ) {
            return $this->build_connection_test_result(
                self::PROVIDER,
                'error',
                0,
                0,
                'APIキーを入力してください。'
            );
        }

        $retry_count = 0;
        $http_code = 0;
        $response = $this->request_with_retry(
            array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array(
                                'text' => '接続テストです。「OK」とだけ返してください。',
                            ),
                        ),
                    ),
                ),
                'generationConfig' => array(
                    'maxOutputTokens' => 5,
                ),
            ),
            $retry_count,
            $http_code
        );

        if ( is_wp_error( $response ) ) {
            $status = 'error';
            if ( 'aiseo_gemini_busy' === $response->get_error_code() ) {
                $status = 'warning';
            }

            return $this->build_connection_test_result(
                self::PROVIDER,
                $status,
                $http_code,
                $retry_count,
                $response->get_error_message()
            );
        }

        return $this->build_connection_test_result(
            self::PROVIDER,
            'success',
            $http_code,
            $retry_count,
            ''
        );
    }

    public function getProviderInfo() {
        return array(
            'name' => 'Gemini',
            'model' => 'gemini-3.5-flash',
            'stability' => 4,
            'speed' => 4,
            'seo' => 5,
            'cost' => 'low',
            'recommended_for' => 'SEO記事',
            'note' => '無料プランは混雑時に利用できない場合があります。',
        );
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

        return $this->request_with_retry( $body );
    }

    /**
     * Gemini APIへのリクエストをリトライ付きで実行します。
     *
     * @param array $body リクエストボディ
     * @return array|WP_Error
     */
    private function request_with_retry( $body, &$retry_count = 0, &$http_code = 0 ) {
        $attempts = 5;
        $retry_delays = array( 1, 2, 4, 8, 16 );

        for ( $attempt = 0; $attempt < $attempts; $attempt++ ) {
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

            $retry_count = $attempt;

            if ( is_wp_error( $response ) ) {
                if ( $attempt + 1 < $attempts ) {
                    sleep( $retry_delays[ $attempt ] );
                    continue;
                }

                return $response;
            }

            $status_code = wp_remote_retrieve_response_code( $response );
            $http_code = $status_code;
            $body_json = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( in_array( $status_code, array( 429, 503 ), true ) ) {
                if ( $attempt + 1 < $attempts ) {
                    $delay = $this->get_retry_delay( $response, $retry_delays[ $attempt ] );
                    sleep( $delay );
                    continue;
                }

                return new WP_Error(
                    'aiseo_gemini_busy',
                    'Gemini APIが現在混雑しています。数分後にもう一度お試しください。'
                );
            }

            if ( 200 > $status_code || 300 <= $status_code ) {
                $message = 'Gemini APIへの接続に失敗しました。';
                if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
                    $message = $body_json['error']['message'];
                }

                return new WP_Error( 'aiseo_gemini_error', $message );
            }

            if ( ! is_array( $body_json ) ) {
                return new WP_Error( 'aiseo_invalid_response', 'Gemini APIのレスポンス形式が不正です。' );
            }

            return $body_json;
        }

        return new WP_Error( 'aiseo_gemini_error', 'Gemini APIへの接続に失敗しました。' );
    }

    /**
     * Retry-Afterヘッダーまたは指数バックオフに基づく待機時間を取得します。
     *
     * @param array|WP_Error $response wp_remote_postのレスポンス
     * @param int $default_delay デフォルトのバックオフ遅延秒
     * @return int
     */
    private function get_retry_delay( $response, $default_delay ) {
        if ( is_wp_error( $response ) ) {
            return $default_delay;
        }

        $retry_after = wp_remote_retrieve_header( $response, 'retry-after' );
        if ( '' === trim( (string) $retry_after ) ) {
            return $default_delay;
        }

        if ( ctype_digit( $retry_after ) ) {
            return max( 1, absint( $retry_after ) );
        }

        $timestamp = strtotime( $retry_after );
        if ( false !== $timestamp ) {
            $delay = $timestamp - time();
            return $delay > 0 ? $delay : $default_delay;
        }

        return $default_delay;
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
