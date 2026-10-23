<?php
/**
 * Stability AI image generation provider for AI SEO Assistant.
 *
 * Uses the Stable Image (v2beta) "core" endpoint, which is multipart/form-data
 * and returns the image binary directly instead of JSON.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Stability_Image_Provider implements AISEO_Image_Provider_Interface {
    const DEFAULT_ENDPOINT = 'https://api.stability.ai/v2beta/stable-image/generate/core';
    const REQUEST_TIMEOUT = 120;
    const OPTION_ENDPOINT = 'aiseo_stability_endpoint';

    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $endpoint;

    /**
     * @var array
     */
    private $supported_ratios;

    public function __construct( $api_key ) {
        $this->api_key = trim( (string) $api_key );
        $this->endpoint = (string) get_option( self::OPTION_ENDPOINT, self::DEFAULT_ENDPOINT );
        $this->supported_ratios = array(
            array( 'ratio' => '1:1', 'width' => 1024, 'height' => 1024 ),
            array( 'ratio' => '16:9', 'width' => 1344, 'height' => 768 ),
            array( 'ratio' => '9:16', 'width' => 768, 'height' => 1344 ),
            array( 'ratio' => '3:2', 'width' => 1216, 'height' => 832 ),
            array( 'ratio' => '2:3', 'width' => 832, 'height' => 1216 ),
            array( 'ratio' => '5:4', 'width' => 1120, 'height' => 896 ),
            array( 'ratio' => '4:5', 'width' => 896, 'height' => 1120 ),
        );
    }

    public function has_api_key() {
        return '' !== $this->api_key;
    }

    public function supports_size( $width, $height ) {
        foreach ( $this->supported_ratios as $size ) {
            if ( (int) $size['width'] === (int) $width && (int) $size['height'] === (int) $height ) {
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

        foreach ( $this->supported_ratios as $size ) {
            $ratio = $size['width'] / $size['height'];
            $diff = abs( $ratio - $target_ratio );

            if ( null === $closest_diff || $diff < $closest_diff ) {
                $closest_diff = $diff;
                $closest = $size;
            }
        }

        return $closest ? $closest : array( 'ratio' => '1:1', 'width' => 1024, 'height' => 1024 );
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

        $boundary = wp_generate_password( 24, false );
        $body = $this->build_multipart_body(
            array(
                'prompt' => $prompt,
                'aspect_ratio' => $size['ratio'],
                'output_format' => 'png',
            ),
            $boundary
        );

        $response = wp_remote_post(
            $this->endpoint,
            array(
                'timeout' => self::REQUEST_TIMEOUT,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Accept' => 'image/*',
                    'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                ),
                'body' => $body,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        $raw_body = wp_remote_retrieve_body( $response );

        if ( 200 > $status_code || 300 <= $status_code ) {
            $message = '画像生成APIへの接続に失敗しました。';
            $body_json = json_decode( $raw_body, true );
            if ( is_array( $body_json ) && ! empty( $body_json['errors'][0] ) ) {
                $message = $body_json['errors'][0];
            } elseif ( is_array( $body_json ) && isset( $body_json['message'] ) ) {
                $message = $body_json['message'];
            }
            return new WP_Error( 'aiseo_image_api_error', $message );
        }

        if ( is_string( $content_type ) && '' !== $content_type && false === strpos( $content_type, 'image/' ) ) {
            return new WP_Error( 'aiseo_image_api_error', '画像生成APIのレスポンス形式が不正です。' );
        }

        if ( '' === $raw_body ) {
            return new WP_Error( 'aiseo_image_api_error', '画像生成APIのレスポンスに画像データが含まれていません。' );
        }

        return array(
            'binary' => $raw_body,
            'mime_type' => ( is_string( $content_type ) && '' !== $content_type ) ? $content_type : 'image/png',
            'width' => $size['width'],
            'height' => $size['height'],
            'provider' => 'stability',
            'model' => 'stable-image-core',
        );
    }

    private function build_multipart_body( array $fields, $boundary ) {
        $body = '';

        foreach ( $fields as $name => $value ) {
            $body .= '--' . $boundary . "\r\n";
            $body .= 'Content-Disposition: form-data; name="' . $name . "\"\r\n\r\n";
            $body .= $value . "\r\n";
        }

        $body .= '--' . $boundary . "--\r\n";

        return $body;
    }
}
