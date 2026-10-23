<?php
/**
 * [aiseo_image] shortcode for TUOUGM.
 *
 * Generated images are inserted into post content as a shortcode instead of
 * a raw <img> tag so the output markup can change centrally later.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Image_Shortcode {
    const TAG = 'aiseo_image';

    public function register() {
        add_shortcode( self::TAG, array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $atts = shortcode_atts(
            array(
                'id' => 0,
                'size' => 'large',
                'alt' => '',
            ),
            $atts,
            self::TAG
        );

        $attachment_id = absint( $atts['id'] );

        if ( ! $attachment_id ) {
            return '';
        }

        $size = sanitize_key( $atts['size'] );
        $alt = sanitize_text_field( $atts['alt'] );

        $image_html = wp_get_attachment_image(
            $attachment_id,
            $size,
            false,
            array(
                'alt' => $alt,
                'loading' => 'lazy',
                'decoding' => 'async',
            )
        );

        if ( '' === $image_html ) {
            return '';
        }

        return '<figure class="aiseo-generated-image">' . $image_html . '</figure>';
    }
}
