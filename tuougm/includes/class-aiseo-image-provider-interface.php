<?php
/**
 * Image provider contract for TUOUGM.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface AISEO_Image_Provider_Interface {
    /**
     * Generate an image.
     *
     * @param array $request {
     *     @type string $prompt
     *     @type int    $width
     *     @type int    $height
     *     @type string $purpose  featured|inline
     *     @type string $alt
     * }
     * @return array|WP_Error {
     *     @type string $binary
     *     @type string $mime_type
     *     @type int    $width
     *     @type int    $height
     *     @type string $provider
     *     @type string $model
     * }
     */
    public function generate( array $request );

    /**
     * Whether the given size is natively supported by the provider.
     *
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function supports_size( $width, $height );

    /**
     * Round the requested size to the nearest size the provider supports.
     *
     * @param int $width
     * @param int $height
     * @return array { width, height, ratio }
     */
    public function normalize_size( $width, $height );
}
