<?php
/**
 * OpenAI text-generation client for TUOUGM.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_OpenAI_Client implements AISEO_AI_Client_Interface {
    use AISEO_AI_Client_Connection_Test_Trait;

    const API_URL = 'https://api.openai.com/v1/chat/completions';
    const PROVIDER = 'openai';
    const DEFAULT_MODEL = 'gpt-4o-mini';
    const MAX_OUTPUT_TOKENS = 3600;
    const REQUEST_TIMEOUT = 120;
    const OPTION_MODEL = 'aiseo_openai_model';

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

        return new WP_Error( 'aiseo_openai_connection_test', $result['message'] );
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
            'messages' => $this->build_messages( '接続テストです。「OK」とだけ返してください。' ),
            'max_tokens' => 5,
        );

        $response = $this->execute_openai_request( $body );
        $status_code = 0;
        $retry_count = 0;
        $message = '';

        if ( is_wp_error( $response ) ) {
            $message = $response->get_error_message();
            $status = 'error';
            return $this->build_connection_test_result( self::PROVIDER, $status, $status_code, $retry_count, $message );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 === $status_code ) {
            return $this->build_connection_test_result( self::PROVIDER, 'success', 200, 0, '' );
        }

        if ( in_array( $status_code, array( 429, 503 ), true ) ) {
            $message = 'OpenAI APIが現在混雑しています。数分後にもう一度お試しください。';
            if ( 429 === $status_code ) {
                $message = 'OpenAI APIがレート制限に達しました。しばらくしてから再試行してください。';
            }
            return $this->build_connection_test_result( self::PROVIDER, 'warning', $status_code, 0, $message );
        }

        if ( 401 === $status_code ) {
            $message = 'OpenAI APIキーが無効です。設定を確認してください。';
            return $this->build_connection_test_result( self::PROVIDER, 'error', 401, 0, $message );
        }

        $message = 'OpenAI APIへの接続に失敗しました。';
        if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
            $message = $body_json['error']['message'];
        }

        return $this->build_connection_test_result( self::PROVIDER, 'error', $status_code, 0, $message );
    }

    public function getProviderInfo() {
        return array(
            'name' => 'OpenAI',
            'model' => $this->model,
            'stability' => 4,
            'speed' => 4,
            'seo' => 4,
            'cost' => 'medium',
            'recommended_for' => '安定した本番運用',
            'note' => '安定した本番運用におすすめです。',
        );
    }

    public function generate_article( $keywords, $article_type, $tone = 'standard', array $site_profile = array(), $site_knowledge = '' ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $user_prompt = $this->prompt_builder->build_user_prompt( $keywords, $article_type, $tone, $site_profile, $site_knowledge );
        $content = $this->request( $this->build_messages( $user_prompt ), self::MAX_OUTPUT_TOKENS );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'OpenAIから記事本文を取得できませんでした。' );
        }

        return $content;
    }

    public function generate_booster_suggestions( $prompt_text, $max_output_tokens = 800 ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーが設定されていません。' );
        }

        $content = $this->request( $this->build_messages( $prompt_text ), $max_output_tokens );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'OpenAIから提案を取得できませんでした。' );
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

        $content = $this->request( $this->build_messages( $prompt ), 160 );

        if ( is_wp_error( $content ) ) {
            return $content;
        }

        if ( '' === $content ) {
            return new WP_Error( 'aiseo_empty_response', 'OpenAIから画像生成プロンプトを取得できませんでした。' );
        }

        return $content;
    }

    private function build_messages( $user_prompt ) {
        return array(
            array(
                'role' => 'system',
                'content' => $this->prompt_builder->get_system_prompt(),
            ),
            array(
                'role' => 'user',
                'content' => $user_prompt,
            ),
        );
    }

    private function request( array $messages, $max_output_tokens ) {
        $body = array(
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $max_output_tokens,
        );

        $response = $this->execute_openai_request( $body );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body_json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 > $status_code || 300 <= $status_code ) {
            $message = 'OpenAI APIへの接続に失敗しました。';
            if ( is_array( $body_json ) && isset( $body_json['error']['message'] ) ) {
                $message = $body_json['error']['message'];
            }
            return new WP_Error( 'aiseo_openai_error', $message );
        }

        if ( ! is_array( $body_json ) ) {
            return new WP_Error( 'aiseo_invalid_response', 'OpenAI APIのレスポンス形式が不正です。' );
        }

        $content = isset( $body_json['choices'][0]['message']['content'] ) ? (string) $body_json['choices'][0]['message']['content'] : '';

        return trim( $content );
    }

    private function execute_openai_request( array $body ) {
        return wp_remote_post(
            self::API_URL,
            array(
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode( $body ),
            )
        );
    }
}
