<?php
/**
 * OpenAI image generation provider for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_OpenAI_Image_Provider implements AISEO_Image_Provider_Interface {
    const API_URL = 'https://api.openai.com/v1/images/generations';
    const DEFAULT_MODEL = 'gpt-image-1';
    const REQUEST_TIMEOUT = 120;
    const OPTION_MODEL = 'aiseo_openai_image_model';

    /**
     * @var string
     */
    private $api_key;

    /**
     * @var string
     */
    private $model;

    /**
     * @var array
     */
    private $supported_sizes;

    public function __construct( $api_key ) {
        $this->api_key = trim( (string) $api_key );
        $this->model = (string) get_option( self::OPTION_MODEL, self::DEFAULT_MODEL );
        $this->supported_sizes = array(
            array( 'label' => '1024x1024', 'width' => 1024, 'height' => 1024 ),
            array( 'label' => '1024x1536', 'width' => 1024, 'height' => 1536 ),
            array( 'label' => '1536x1024', 'width' => 1536, 'height' => 1024 ),
        );
    }

    public function has_api_key() {
        return '' !== $this->api_key;
    }

    public function supports_size( $width, $height ) {
        foreach ( $this->supported_sizes as $size ) {
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

        foreach ( $this->supported_sizes as $size ) {
            $ratio = $size['width'] / $size['height'];
            $diff = abs( $ratio - $target_ratio );

            if ( null === $closest_diff || $diff < $closest_diff ) {
                $closest_diff = $diff;
                $closest = $size;
            }
        }

        return $closest ? $closest : array( 'label' => '1024x1024', 'width' => 1024, 'height' => 1024 );
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

        $body = array(
            'model' => $this->model,
            'prompt' => $prompt,
            'size' => $size['label'],
            'n' => 1,
        );

        $response = wp_remote_post(
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

        $b64 = isset( $body_json['data'][0]['b64_json'] ) ? $body_json['data'][0]['b64_json'] : '';

        if ( '' === $b64 ) {
            return new WP_Error( 'aiseo_image_api_error', '画像生成APIのレスポンスに画像データが含まれていません。' );
        }

        $binary = base64_decode( $b64, true );

        if ( false === $binary ) {
            return new WP_Error( 'aiseo_image_decode_error', '画像データのデコードに失敗しました。' );
        }

        return array(
            'binary' => $binary,
            'mime_type' => 'image/png',
            'width' => $size['width'],
            'height' => $size['height'],
            'provider' => 'openai',
            'model' => $this->model,
        );
    }
}
