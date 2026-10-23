<?php
/**
 * AI text-generation client contract for TUOUGM.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface AISEO_AI_Client_Interface {
    public function has_api_key();

    /**
     * プロバイダー共通の接続テストを実行します。
     *
     * @return array{
     *     provider: string,
     *     status: string,
     *     http_code: int,
     *     retry_count: int,
     *     message: string,
     * }
     */
    public function connection_test();

    /**
     * 従来互換の接続テストラッパー。
     *
     * @return true|WP_Error
     */
    public function test_connection();

    /**
     * プロバイダー固有の情報を返します。
     *
     * @return array{
     *     name: string,
     *     model: string,
     *     stability: int,
     *     speed: int,
     *     seo: int,
     *     cost: string,
     *     recommended_for: string,
     *     note: string,
     * }
     */
    public function getProviderInfo();

    /**
     * @return string|WP_Error
     */
    public function generate_article( $keywords, $article_type, $tone = 'standard', array $site_profile = array(), $site_knowledge = '' );

    /**
     * @return string|WP_Error
     */
    public function generate_booster_suggestions( $prompt_text, $max_output_tokens = 800 );

    /**
     * @return string|WP_Error
     */
    public function generate_image_prompt( $article_content );
}

trait AISEO_AI_Client_Connection_Test_Trait {
    /**
     * 共通の接続テスト結果配列を構築します。
     *
     * @param string $provider
     * @param string $status
     * @param int    $http_code
     * @param int    $retry_count
     * @param string $message
     * @return array{
     *     provider: string,
     *     status: string,
     *     http_code: int,
     *     retry_count: int,
     *     message: string,
     * }
     */
    protected function build_connection_test_result( $provider, $status, $http_code, $retry_count, $message ) {
        return array(
            'provider'    => $provider,
            'status'      => $status,
            'http_code'   => (int) $http_code,
            'retry_count' => (int) $retry_count,
            'message'     => (string) $message,
        );
    }
}
