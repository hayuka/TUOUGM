<?php
/**
 * Originality booster: analyzes generated content and suggests site-specific additions.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Originality_Booster {
    /**
     * AI text-generation client instance.
     *
     * @var AISEO_AI_Client_Interface
     */
    private $client;

    public function __construct( AISEO_AI_Client_Interface $client ) {
        $this->client = $client;
    }

    /**
     * Suggest improvements to increase originality / site-specificity.
     * Returns plain text suggestions which can be inserted or presented to the user.
     *
     * @param string $content
     * @param array $site_profile
     * @return string|WP_Error
     */
    public function suggest( $content, array $site_profile = array() ) {
        if ( ! $this->client->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーが設定されていません。' );
        }

        $prompt = "以下は記事の本文です。サイトプロファイルを参考にして、\n" .
            "・このサイトらしい具体的なエピソードの挿入案（1段落）\n" .
            "・自社サービスや成果を自然に紹介する一文の候補\n" .
            "・一次情報や引用を追加してE-E-A-Tを高めるための具体案\n" .
            "の3点を、読みやすい日本語で箇条書きにして提案してください。\n" .
            "出力はそのまま記事へ貼り付けられる形式でお願いします。\n\n" .
            "---\n\n" .
            "記事本文:\n" . trim( (string) $content ) . "\n\n" .
            "Site Profile (JSON):\n" . wp_json_encode( $site_profile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . "\n";

        $result = $this->client->generate_booster_suggestions( $prompt, 800 );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        return $result;
    }
}
