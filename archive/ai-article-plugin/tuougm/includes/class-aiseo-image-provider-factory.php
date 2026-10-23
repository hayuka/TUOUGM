<?php
/**
 * Image provider factory for TUOUGM.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Image_Provider_Factory {
    const PROVIDER_GEMINI = 'gemini';
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_STABILITY = 'stability';

    public static function get_supported_providers() {
        return array(
            self::PROVIDER_GEMINI => 'Gemini',
            self::PROVIDER_OPENAI => 'OpenAI',
            self::PROVIDER_STABILITY => 'Stability AI',
        );
    }

    /**
     * @param string $provider
     * @param string $api_key
     * @return AISEO_Image_Provider_Interface
     */
    public static function create( $provider, $api_key ) {
        switch ( $provider ) {
            case self::PROVIDER_OPENAI:
                return new AISEO_OpenAI_Image_Provider( $api_key );
            case self::PROVIDER_STABILITY:
                return new AISEO_Stability_Image_Provider( $api_key );
            case self::PROVIDER_GEMINI:
            default:
                return new AISEO_Gemini_Image_Provider( $api_key );
        }
    }
}
