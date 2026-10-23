<?php
/**
 * Anthropic Claude text-generation client for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Claude_Client implements AISEO_AI_Client_Interface {
    use AISEO_AI_Client_Connection_Test_Trait;

    const API_URL = 'https://api.anthropic.com/v1/messages';
    const PROVIDER = 'claude';
    const API_VERSION = '2023-06-01';
    const DEFAULT_MODEL = 'claude-sonnet-5';
    const MAX_OUTPUT_TOKENS = 3600;
    const REQUEST_TIMEOUT = 120;
    const OPTION_MODEL = 'aiseo_claude_model';

    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $model;

    /**
     * @var AISEO_Prompt_Builder
     */
    private $prompt_builder;

    public function __construct( $api_key, AISEO_Prompt_Builder $prompt_builder = null ) {
        $this->api_key = trim( (string) $api_key );
        $this->model = (string) get_option( self::OPTION_MODEL, self::DEFAULT_MODEL );
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

        return new WP_Error( 'aiseo_claude_connection_test', $result['message'] );
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

        $body = array(
            'model' => $this->model,
            'max_tokens' => 5,
            'system' => $this->prompt_builder->get_system_prompt(),
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => '接続テストです。「OK」とだけ返してください。',
                ),
            ),
        );

        $response = wp_remote_post(
            self::API_URL,
            array(
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => array(
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => self::API_VERSION,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $this->build_connection_test_result(
                self::PROVIDER,
                'error',
                0,
                0,
                $response->get_error_message()
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $status_code ) {
            return $this->build_connection_test_result( self::PROVIDER, 'success', 200, 0, '' );
        }

        if ( in_array( $status_code, array( 429, 503 ), true ) ) {
            $message = 'Claude APIが現在混雑しています。数分後にもう一度お試しください。';
            return $this->build_connection_test_result( self::PROVIDER, 'warning', $status_code, 0, $message );
        }

        $message = 'Claude APIへの接続に失敗しました。';
        if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
            $message = $body_json['error']['message'];
        }

        return $this->build_connection_test_result( self::PROVIDER, 'error', $status_code, 0, $message );
    }

    public function getProviderInfo() {
        return array(
            'name' => 'Claude',
            'model' => $this->model,
            'stability' => 4,
            'speed' => 3,
            'seo' => 5,
            'cost' => 'medium',
            'recommended_for' => '自然な長文生成',
            'note' => '自然な長文生成が得意です。',
        );
    }

    public function generate_article( $keywords, $article_type, $tone = 'standard', array $site_profile = array(), $site_knowledge = '' ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $user_prompt = $this->prompt_builder->build_user_prompt( $keywords, $article_type, $tone, $site_profile, $site_knowledge );
        $content = $this->request( $this->prompt_builder->get_system_prompt(), $user_prompt, self::MAX_OUTPUT_TOKENS );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'Claudeから記事本文を取得できませんでした。' );
        }

        return $content;
    }

    public function generate_booster_suggestions( $prompt_text, $max_output_tokens = 800 ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーが設定されていません。' );
        }

        $content = $this->request( $this->prompt_builder->get_system_prompt(), $prompt_text, $max_output_tokens );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'Claudeから提案を取得できませんでした。' );
        }

        return $content;
    }

    public function generate_image_prompt( $article_content ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $prompt = "以下は記事本文です。この記事に最適な画像生成プロンプトを日本語で1つ生成してください。\n" .
            "生成プロンプトは画像生成APIに直接渡せる形式にしてください。\n\n" .
            "記事本文:\n" . trim( (string) $article_content );

        $content = $this->request( $this->prompt_builder->get_system_prompt(), $prompt, 160 );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'Claudeから画像生成プロンプトを取得できませんでした。' );
        }

        return $content;
    }

    private function request( $system_prompt, $user_prompt, $max_output_tokens ) {
        $body = array(
            'model' => $this->model,
            'max_tokens' => $max_output_tokens,
            'system' => $system_prompt,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $user_prompt,
                ),
            ),
        );

        $response = wp_remote_post(
            self::API_URL,
            array(
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => array(
                    'x-api-key' => $this->api_key,
                    'anthropic-version' => self::API_VERSION,
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
            $message = 'Claude APIへの接続に失敗しました。';
            if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
                $message = $body_json['error']['message'];
            }
            return new WP_Error( 'aiseo_claude_error', $message );
        }

        if ( ! is_array( $body_json ) ) {
            return new WP_Error( 'aiseo_invalid_response', 'Claude APIのレスポンス形式が不正です。' );
        }

        $texts = array();

        if ( ! empty( $body_json['content'] ) && is_array( $body_json['content'] ) ) {
            foreach ( $body_json['content'] as $block ) {
                if ( isset( $block['type'], $block['text'] ) && 'text' === $block['type'] ) {
                    $texts[] = $block['text'];
                }
            }
        }

        return trim( implode( "\n", $texts ) );
    }
}
