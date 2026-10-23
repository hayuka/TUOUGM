<?php
/**
 * Gemini image generation provider for TUOUGM.
 *
 * Absorbs Gemini-specific endpoint / request / response details so that
 * API version or model changes only require edits in this class.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Gemini_Image_Provider implements AISEO_Image_Provider_Interface {
    const DEFAULT_MODEL = 'gemini-2.5-flash-image';
    const DEFAULT_API_VERSION = 'v1beta';
    const DEFAULT_ENDPOINT_BASE = 'https://generativelanguage.googleapis.com';
    const REQUEST_TIMEOUT = 120;

    const OPTION_MODEL = 'aiseo_image_model';
    const OPTION_API_VERSION = 'aiseo_image_api_version';
    const OPTION_ENDPOINT_BASE = 'aiseo_image_endpoint_base';
    const OPTION_SUPPORTED_SIZES = 'aiseo_image_supported_sizes';

    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $model;

    /**
     * @var string
     */
    private $api_version;

    /**
     * @var string
     */
    private $endpoint_base;

    /**
     * @var array
     */
    private $supported_sizes;

    public function __construct( $api_key ) {
        $this->api_key = trim( (string) $api_key );
        $this->model = (string) get_option( self::OPTION_MODEL, self::DEFAULT_MODEL );
        $this->api_version = (string) get_option( self::OPTION_API_VERSION, self::DEFAULT_API_VERSION );
        $this->endpoint_base = rtrim( (string) get_option( self::OPTION_ENDPOINT_BASE, self::DEFAULT_ENDPOINT_BASE ), '/' );

        $stored_sizes = get_option( self::OPTION_SUPPORTED_SIZES, array() );
        $this->supported_sizes = ( ! empty( $stored_sizes ) && is_array( $stored_sizes ) ) ? $stored_sizes : $this->get_default_supported_sizes();
    }

    public function has_api_key() {
        return '' !== $this->api_key;
    }

    public function supports_size( $width, $height ) {
        $width = (int) $width;
        $height = (int) $height;

        foreach ( $this->supported_sizes as $size ) {
            if ( (int) $size['width'] === $width && (int) $size['height'] === $height ) {
                return true;
            }
        }

        return false;
    }

    public function normalize_size( $width, $height ) {
        $width = max( 1, (int) $width );
        $height = max( 1, (int) $height );
        $target_ratio = $width / $height;

        $closest = null;
        $closest_diff = null;

        foreach ( $this->supported_sizes as $size ) {
            if ( empty( $size['height'] ) ) {
                continue;
            }

            $ratio = $size['width'] / $size['height'];
            $diff = abs( $ratio - $target_ratio );

            if ( null === $closest_diff || $diff < $closest_diff ) {
                $closest_diff = $diff;
                $closest = $size;
            }
        }

        if ( null === $closest ) {
            $closest = array(
                'ratio' => '1:1',
                'width' => 1024,
                'height' => 1024,
            );
        }

        return $closest;
    }

    public function generate( array $request ) {
        if ( ! $this->has_api_key() ) {
            return new WP_Error( 'aiseo_missing_api_key', 'APIキーを入力してください。' );
        }

        $prompt = isset( $request['prompt'] ) ? trim( (string) $request['prompt'] ) : '';

        if ( '' === $prompt ) {
            return new WP_Error( 'aiseo_missing_prompt', '画像生成プロンプトが指定されていません。' );
        }

        $requested_width = isset( $request['width'] ) ? (int) $request['width'] : 1024;
        $requested_height = isset( $request['height'] ) ? (int) $request['height'] : 1024;
        $size = $this->normalize_size( $requested_width, $requested_height );

        $endpoint = sprintf(
            '%s/%s/models/%s:generateContent',
            $this->endpoint_base,
            $this->api_version,
            $this->model
        );

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array( 'text' => $prompt ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'responseModalities' => array( 'IMAGE' ),
                'imageConfig' => array(
                    'aspectRatio' => $size['ratio'],
                ),
            ),
        );

        $response = wp_remote_post(
            $endpoint . '?key=' . rawurlencode( $this->api_key ),
            array(
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $this->api_key,
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
            }
            return new WP_Error( 'aiseo_image_api_error', $message );
        }

        if ( ! is_array( $body_json ) ) {
            return new WP_Error( 'aiseo_invalid_response', '画像生成APIのレスポンス形式が不正です。' );
        }

        $inline_image = $this->extract_inline_image( $body_json );

        if ( null === $inline_image ) {
            return new WP_Error( 'aiseo_image_api_error', '画像生成APIのレスポンスに画像データが含まれていません。' );
        }

        $binary = base64_decode( $inline_image['data'], true );

        if ( false === $binary ) {
            return new WP_Error( 'aiseo_image_decode_error', '画像データのデコードに失敗しました。' );
        }

        return array(
            'binary' => $binary,
            'mime_type' => $inline_image['mime_type'],
            'width' => $size['width'],
            'height' => $size['height'],
            'provider' => 'gemini',
            'model' => $this->model,
        );
    }

    /**
     * Default aspect-ratio buckets supported by the current Gemini image model.
     * Kept here so an API/model change only touches this class.
     *
     * @return array
     */
    private function get_default_supported_sizes() {
        return array(
            array( 'ratio' => '1:1', 'width' => 1024, 'height' => 1024 ),
            array( 'ratio' => '4:3', 'width' => 1184, 'height' => 864 ),
            array( 'ratio' => '3:4', 'width' => 864, 'height' => 1184 ),
            array( 'ratio' => '16:9', 'width' => 1344, 'height' => 768 ),
            array( 'ratio' => '9:16', 'width' => 768, 'height' => 1344 ),
        );
    }

    private function extract_inline_image( array $response ) {
        if ( empty( $response['candidates'] ) || ! is_array( $response['candidates'] ) ) {
            return null;
        }

        foreach ( $response['candidates'] as $candidate ) {
            if ( empty( $candidate['content']['parts'] ) || ! is_array( $candidate['content']['parts'] ) ) {
                continue;
            }

            foreach ( $candidate['content']['parts'] as $part ) {
                if ( isset( $part['inlineData']['data'] ) ) {
                    return array(
                        'data' => $part['inlineData']['data'],
                        'mime_type' => isset( $part['inlineData']['mimeType'] ) ? $part['inlineData']['mimeType'] : 'image/png',
                    );
                }

                if ( isset( $part['inline_data']['data'] ) ) {
                    return array(
                        'data' => $part['inline_data']['data'],
                        'mime_type' => isset( $part['inline_data']['mime_type'] ) ? $part['inline_data']['mime_type'] : 'image/png',
                    );
                }
            }
        }

        return null;
    }
}
