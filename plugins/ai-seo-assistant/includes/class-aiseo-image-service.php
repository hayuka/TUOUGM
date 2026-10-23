<?php
/**
 * Image generation service for AI SEO Assistant.
 *
 * Coordinates the image provider, media library storage, attachment meta
 * and shortcode generation. Admin/article-generation code should depend on
 * this class instead of talking to a provider directly.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Image_Service {
    const META_GENERATED = '_aiseo_generated';
    const META_PROMPT = '_aiseo_image_prompt';
    const META_PROVIDER = '_aiseo_image_provider';
    const META_MODEL = '_aiseo_image_model';
    const META_PURPOSE = '_aiseo_image_purpose';
    const META_SOURCE_POST_ID = '_aiseo_source_post_id';

    /**
     * @var AISEO_Image_Provider_Interface
     */
    private $provider;

    public function __construct( AISEO_Image_Provider_Interface $provider ) {
        $this->provider = $provider;
    }

    /**
     * Generate an image and store it in the media library.
     *
     * @param string $prompt
     * @param int    $width
     * @param int    $height
     * @param string $purpose  featured|inline
     * @param string $alt
     * @param int    $source_post_id
     * @return array|WP_Error { attachment_id, width, height }
     */
    public function generate_and_save( $prompt, $width, $height, $purpose = 'inline', $alt = '', $source_post_id = 0 ) {
        $purpose = in_array( $purpose, array( 'featured', 'inline' ), true ) ? $purpose : 'inline';

        $result = $this->provider->generate(
            array(
                'prompt' => $prompt,
                'width' => $width,
                'height' => $height,
                'purpose' => $purpose,
                'alt' => $alt,
            )
        );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        $attachment_id = $this->save_to_media_library( $result, $prompt, $purpose, $alt, $source_post_id );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        return array(
            'attachment_id' => $attachment_id,
            'width' => $result['width'],
            'height' => $result['height'],
        );
    }

    /**
     * Build the `[aiseo_image]` shortcode for an attachment.
     *
     * @param int    $attachment_id
     * @param string $size
     * @param string $alt
     * @return string
     */
    public function build_shortcode( $attachment_id, $size = 'large', $alt = '' ) {
        return sprintf(
            '[aiseo_image id="%d" size="%s" alt="%s"]',
            absint( $attachment_id ),
            sanitize_key( $size ),
            str_replace( '"', "'", sanitize_text_field( $alt ) )
        );
    }

    /**
     * Assign an attachment as a post's featured image.
     *
     * @param int $post_id
     * @param int $attachment_id
     * @return bool
     */
    public function set_featured_image( $post_id, $attachment_id ) {
        return (bool) set_post_thumbnail( $post_id, $attachment_id );
    }

    /**
     * Update the source post reference on a previously generated attachment.
     *
     * @param int $attachment_id
     * @param int $post_id
     */
    public function link_to_post( $attachment_id, $post_id ) {
        update_post_meta( absint( $attachment_id ), self::META_SOURCE_POST_ID, absint( $post_id ) );
    }

    private function save_to_media_library( array $result, $prompt, $purpose, $alt, $source_post_id ) {
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $extension = $this->extension_from_mime( $result['mime_type'] );
        $filename = 'aiseo-' . $purpose . '-' . uniqid() . '.' . $extension;

        $upload = wp_upload_bits( $filename, null, $result['binary'] );

        if ( ! empty( $upload['error'] ) ) {
            return new WP_Error( 'aiseo_image_save_error', $upload['error'] );
        }

        $attachment = array(
            'post_mime_type' => $result['mime_type'],
            'post_title' => sanitize_file_name( $filename ),
            'post_content' => '',
            'post_status' => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $upload['file'] );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        $metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
        wp_update_attachment_metadata( $attachment_id, $metadata );

        if ( '' !== trim( (string) $alt ) ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
        }

        update_post_meta( $attachment_id, self::META_GENERATED, 1 );
        update_post_meta( $attachment_id, self::META_PROMPT, sanitize_text_field( $prompt ) );
        update_post_meta( $attachment_id, self::META_PROVIDER, sanitize_key( $result['provider'] ) );
        update_post_meta( $attachment_id, self::META_MODEL, sanitize_text_field( $result['model'] ) );
        update_post_meta( $attachment_id, self::META_PURPOSE, sanitize_key( $purpose ) );
        update_post_meta( $attachment_id, self::META_SOURCE_POST_ID, absint( $source_post_id ) );

        return $attachment_id;
    }

    private function extension_from_mime( $mime_type ) {
        $map = array(
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
        );

        return isset( $map[ $mime_type ] ) ? $map[ $mime_type ] : 'png';
    }
}
