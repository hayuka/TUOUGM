<?php
/**
 * Text-generation client factory for TUOUGM.
 *
 * Lets the admin/article-generation code depend on AISEO_AI_Client_Interface
 * only; switching providers is a one-option change, not a code change.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_AI_Client_Factory {
    const PROVIDER_GEMINI = 'gemini';
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_CLAUDE = 'claude';

    public static function get_supported_providers() {
        return array(
            self::PROVIDER_GEMINI => 'Gemini',
            self::PROVIDER_OPENAI => 'OpenAI',
            self::PROVIDER_CLAUDE => 'Anthropic Claude',
        );
    }

    /**
     * @param string $provider
     * @param string $api_key
     * @return AISEO_AI_Client_Interface
     */
    public static function create( $provider, $api_key ) {
        switch ( $provider ) {
            case self::PROVIDER_OPENAI:
                return new AISEO_OpenAI_Client( $api_key );
            case self::PROVIDER_CLAUDE:
                return new AISEO_Claude_Client( $api_key );
            case self::PROVIDER_GEMINI:
            default:
                return new AISEO_Gemini_Client( $api_key );
        }
    }
}
