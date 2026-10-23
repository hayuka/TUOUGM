<?php
/**
 * Media analyzer for AI SEO Assistant.
 *
 * Looks at existing featured images to recommend a size for newly
 * generated featured images.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Media_Analyzer {
    const DEFAULT_WIDTH = 1200;
    const DEFAULT_HEIGHT = 630;

    /**
     * Analyze featured image sizes across published posts and recommend a size.
     *
     * @param int $limit
     * @return array {
     *     @type int    $recommended_width
     *     @type int    $recommended_height
     *     @type string $aspect_ratio
     *     @type int    $source_count
     *     @type string $decision_method  mode|median|default
     * }
     */
    public function analyze_featured_image_sizes( $limit = 50 ) {
        $limit = max( 1, min( 500, absint( $limit ) ) );

        $posts = get_posts(
            array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
                'meta_key' => '_thumbnail_id',
            )
        );

        $sizes = array();

        foreach ( $posts as $post ) {
            $thumbnail_id = get_post_thumbnail_id( $post );

            if ( ! $thumbnail_id ) {
                continue;
            }

            $metadata = wp_get_attachment_metadata( $thumbnail_id );

            if ( empty( $metadata['width'] ) || empty( $metadata['height'] ) ) {
                continue;
            }

            $sizes[] = array(
                'width' => (int) $metadata['width'],
                'height' => (int) $metadata['height'],
            );
        }

        if ( empty( $sizes ) ) {
            return $this->default_recommendation( 0 );
        }

        return $this->decide_size( $sizes );
    }

    private function decide_size( array $sizes ) {
        $counts = array();

        foreach ( $sizes as $size ) {
            $key = $size['width'] . 'x' . $size['height'];

            if ( ! isset( $counts[ $key ] ) ) {
                $counts[ $key ] = array(
                    'count' => 0,
                    'width' => $size['width'],
                    'height' => $size['height'],
                );
            }

            ++$counts[ $key ]['count'];
        }

        uasort(
            $counts,
            function ( $a, $b ) {
                return $b['count'] <=> $a['count'];
            }
        );

        $counts = array_values( $counts );
        $total = count( $sizes );
        $top = $counts[0];

        if ( $top['count'] / $total >= 0.4 ) {
            return array(
                'recommended_width' => $top['width'],
                'recommended_height' => $top['height'],
                'aspect_ratio' => $this->format_ratio( $top['width'], $top['height'] ),
                'source_count' => $total,
                'decision_method' => 'mode',
            );
        }

        $widths = wp_list_pluck( $sizes, 'width' );
        $heights = wp_list_pluck( $sizes, 'height' );
        sort( $widths );
        sort( $heights );

        $median_width = $this->median( $widths );
        $median_height = $this->median( $heights );

        return array(
            'recommended_width' => $median_width,
            'recommended_height' => $median_height,
            'aspect_ratio' => $this->format_ratio( $median_width, $median_height ),
            'source_count' => $total,
            'decision_method' => 'median',
        );
    }

    private function default_recommendation( $source_count ) {
        return array(
            'recommended_width' => self::DEFAULT_WIDTH,
            'recommended_height' => self::DEFAULT_HEIGHT,
            'aspect_ratio' => $this->format_ratio( self::DEFAULT_WIDTH, self::DEFAULT_HEIGHT ),
            'source_count' => $source_count,
            'decision_method' => 'default',
        );
    }

    private function median( array $values ) {
        $count = count( $values );

        if ( 0 === $count ) {
            return 0;
        }

        $middle = (int) floor( $count / 2 );

        if ( $count % 2 ) {
            return (int) $values[ $middle ];
        }

        return (int) round( ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2 );
    }

    private function format_ratio( $width, $height ) {
        if ( 0 === (int) $height ) {
            return 'unknown';
        }

        return round( $width / $height, 2 ) . ':1';
    }
}
