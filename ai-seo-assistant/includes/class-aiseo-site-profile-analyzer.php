<?php
/**
 * Site profile analyzer for AI SEO Assistant.
 *
 * @package AISEOAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AISEO_Site_Profile_Analyzer {
    public function analyze( $limit = 20, $include_pages = false ) {
        $limit = max( 1, min( 100, absint( $limit ) ) );
        $post_types = array( 'post' );

        if ( $include_pages ) {
            $post_types[] = 'page';
        }

        $posts = get_posts(
            array(
                'post_type' => $post_types,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
            )
        );

        if ( empty( $posts ) ) {
            return new WP_Error( 'aiseo_no_posts', '解析できる公開記事が見つかりませんでした。' );
        }

        $items = array();

        foreach ( $posts as $post ) {
            $items[] = $this->analyze_post( $post );
        }

        return $this->build_profile( $items, count( $posts ), $include_pages );
    }

    private function analyze_post( WP_Post $post ) {
        $content = (string) $post->post_content;
        $plain_text = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );
        $word_count = $this->count_japanese_chars( $plain_text );
        $sentences = $this->split_sentences( $plain_text );
        $sentence_count = count( $sentences );
        $paragraph_count = $this->count_paragraphs( $content );
        $links = $this->count_links( $content );

        return array(
            'title' => get_the_title( $post ),
            'word_count' => $word_count,
            'h2' => $this->count_heading( $content, 2 ),
            'h3' => $this->count_heading( $content, 3 ),
            'h4' => $this->count_heading( $content, 4 ),
            'paragraphs' => $paragraph_count,
            'sentences' => $sentence_count,
            'sentence_length' => $sentence_count > 0 ? $word_count / $sentence_count : 0,
            'list' => $this->has_pattern( $content, '/<(ul|ol)\b/i' ) || $this->has_pattern( $content, '/^\s*[-*・]\s+/mu' ),
            'table' => $this->has_pattern( $content, '/<table\b/i' ),
            'faq' => $this->has_pattern( $content, '/FAQ|よくある質問|Q[\.：:]|wp:yoast\/faq-block|schema-faq/i' ),
            'cta_position' => $this->detect_position( $content, '/CTA|お問い合わせ|資料請求|無料相談|申し込み|購入|詳細はこちら|ボタン/u' ),
            'summary_position' => $this->detect_position( $content, '/まとめ|結論|最後に|おわりに/u' ),
            'polite_hits' => $this->count_matches( $plain_text, '/です|ます|ください|でしょう/u' ),
            'assertive_hits' => $this->count_matches( $plain_text, '/である|だ。|となる/u' ),
            'casual_hits' => $this->count_matches( $plain_text, '/ですよ|ですね|かも|ちなみに|ぜひ/u' ),
            'expert_hits' => $this->count_matches( $plain_text, '/分析|最適化|指標|設計|戦略|実装|検証|専門|SEO|API/u' ),
            'box' => $this->has_pattern( $content, '/wp:group|class="[^"]*(box|card|note|point|alert)[^"]*"/i' ),
            'speech' => $this->has_pattern( $content, '/class="[^"]*(speech|balloon|hukidashi|吹き出し)[^"]*"/iu' ),
            'quote' => $this->has_pattern( $content, '/<blockquote\b|wp:quote/i' ),
            'code' => $this->has_pattern( $content, '/<pre\b|<code\b|wp:code/i' ),
            'button' => $this->has_pattern( $content, '/wp:button|<a[^>]+class="[^"]*button/i' ),
            'comparison_table' => $this->has_pattern( $content, '/比較|<table\b/i' ),
            'featured_image' => has_post_thumbnail( $post ),
            'images' => $this->count_images( $content ),
            'image_sizes' => $this->detect_image_sizes( $content ),
            'meta_description' => $this->has_meta_description( $post->ID ),
            'internal_links' => $links['internal'],
            'external_links' => $links['external'],
        );
    }

    private function build_profile( array $items, $post_count, $include_pages ) {
        $profile = array(
            'version' => 1,
            'generated_at' => current_time( 'mysql' ),
            'analyzed_posts' => $post_count,
            'include_pages' => (bool) $include_pages,
            'tone' => $this->detect_tone( $items ),
            'style' => $this->detect_style( $items ),
            'average_words' => $this->average( $items, 'word_count' ),
            'average_h2' => $this->average( $items, 'h2' ),
            'average_h3' => $this->average( $items, 'h3' ),
            'average_h4' => $this->average( $items, 'h4' ),
            'average_paragraphs' => $this->average( $items, 'paragraphs' ),
            'paragraph_length' => $this->average_paragraph_length( $items ),
            'average_sentence_length' => $this->average( $items, 'sentence_length' ),
            'readability' => $this->detect_readability( $items ),
            'expertise' => $this->ratio_from_hits( $items, 'expert_hits' ),
            'casualness' => $this->ratio_from_hits( $items, 'casual_hits' ),
            'honorific_level' => $this->ratio_from_hits( $items, 'polite_hits' ),
            'reader_address' => $this->detect_reader_address( $items ),
            'title_tendency' => $this->detect_title_tendency( $items ),
            'meta_description_ratio' => $this->ratio( $items, 'meta_description' ),
            'keyword_density' => 'not_calculated',
            'list_ratio' => $this->ratio( $items, 'list' ),
            'table_ratio' => $this->ratio( $items, 'table' ),
            'faq' => $this->ratio( $items, 'faq' ) >= 0.3,
            'faq_ratio' => $this->ratio( $items, 'faq' ),
            'cta_position' => $this->dominant_position( $items, 'cta_position' ),
            'summary_position' => $this->dominant_position( $items, 'summary_position' ),
            'internal_links' => $this->average( $items, 'internal_links' ),
            'external_links' => $this->average( $items, 'external_links' ),
            'related_links_position' => 'body',
            'layout' => array(
                'box_ratio' => $this->ratio( $items, 'box' ),
                'speech_ratio' => $this->ratio( $items, 'speech' ),
                'faq_ratio' => $this->ratio( $items, 'faq' ),
                'comparison_table_ratio' => $this->ratio( $items, 'comparison_table' ),
                'cta_ratio' => $this->position_ratio( $items, 'cta_position' ),
                'button_ratio' => $this->ratio( $items, 'button' ),
                'quote_ratio' => $this->ratio( $items, 'quote' ),
                'code_block_ratio' => $this->ratio( $items, 'code' ),
            ),
            'images' => array(
                'featured_image_ratio' => $this->ratio( $items, 'featured_image' ),
                'average_images' => $this->average( $items, 'images' ),
                'image_per_h2' => $this->image_per_heading( $items, 'h2' ),
                'image_per_h3' => $this->image_per_heading( $items, 'h3' ),
                'diagram_ratio' => 0,
                'photo_ratio' => 0,
                'illustration_ratio' => 0,
                'size_tendency' => $this->dominant_image_size( $items ),
            ),
        );

        return $profile;
    }

    private function count_heading( $content, $level ) {
        $html_count = $this->count_matches( $content, '/<h' . absint( $level ) . '\b/i' );
        $markdown_count = $this->count_matches( $content, '/^#{' . absint( $level ) . '}\s+/m' );
        $block_count = $this->count_matches( $content, '/wp:heading\s+\{[^}]*"level":' . absint( $level ) . '/i' );

        return $html_count + $markdown_count + $block_count;
    }

    private function count_paragraphs( $content ) {
        $paragraphs = $this->count_matches( $content, '/<p\b/i' );

        if ( $paragraphs > 0 ) {
            return $paragraphs;
        }

        $plain = trim( wp_strip_all_tags( strip_shortcodes( $content ) ) );
        if ( '' === $plain ) {
            return 0;
        }

        $chunks = preg_split( '/\n\s*\n/u', $plain );
        return count( array_filter( array_map( 'trim', $chunks ) ) );
    }

    private function count_images( $content ) {
        return $this->count_matches( $content, '/<img\b|wp:image/i' );
    }

    private function count_links( $content ) {
        $home_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $internal = 0;
        $external = 0;

        if ( preg_match_all( '/<a\s+[^>]*href=["\']([^"\']+)["\']/i', $content, $matches ) ) {
            foreach ( $matches[1] as $url ) {
                $host = wp_parse_url( $url, PHP_URL_HOST );
                if ( empty( $host ) || $host === $home_host ) {
                    ++$internal;
                } else {
                    ++$external;
                }
            }
        }

        return array(
            'internal' => $internal,
            'external' => $external,
        );
    }

    private function detect_position( $content, $pattern ) {
        $plain = wp_strip_all_tags( strip_shortcodes( $content ) );

        if ( ! preg_match( $pattern, $plain, $matches, PREG_OFFSET_CAPTURE ) ) {
            return 'none';
        }

        $length = max( 1, mb_strlen( $plain, 'UTF-8' ) );
        $offset = mb_strlen( substr( $plain, 0, $matches[0][1] ), 'UTF-8' );
        $ratio = $offset / $length;

        if ( $ratio < 0.33 ) {
            return 'first';
        }

        if ( $ratio < 0.66 ) {
            return 'middle';
        }

        return 'last';
    }

    private function detect_image_sizes( $content ) {
        $sizes = array();

        if ( preg_match_all( '/<img[^>]+(?:width|height)=["\']?([0-9]+)/i', $content, $matches ) ) {
            foreach ( $matches[1] as $size ) {
                $sizes[] = absint( $size );
            }
        }

        return $sizes;
    }

    private function has_meta_description( $post_id ) {
        $meta_keys = array(
            '_yoast_wpseo_metadesc',
            '_aioseo_description',
            'rank_math_description',
            '_genesis_description',
        );

        foreach ( $meta_keys as $meta_key ) {
            if ( '' !== trim( (string) get_post_meta( $post_id, $meta_key, true ) ) ) {
                return true;
            }
        }

        return false;
    }

    private function split_sentences( $text ) {
        $sentences = preg_split( '/[。！？!?]+/u', $text );
        return array_values( array_filter( array_map( 'trim', $sentences ) ) );
    }

    private function count_japanese_chars( $text ) {
        $text = preg_replace( '/\s+/u', '', $text );
        return mb_strlen( $text, 'UTF-8' );
    }

    private function has_pattern( $text, $pattern ) {
        return (bool) preg_match( $pattern, $text );
    }

    private function count_matches( $text, $pattern ) {
        if ( ! preg_match_all( $pattern, $text, $matches ) ) {
            return 0;
        }

        return count( $matches[0] );
    }

    private function average( array $items, $key ) {
        if ( empty( $items ) ) {
            return 0;
        }

        $total = 0;
        foreach ( $items as $item ) {
            $total += isset( $item[ $key ] ) ? (float) $item[ $key ] : 0;
        }

        return round( $total / count( $items ), 2 );
    }

    private function ratio( array $items, $key ) {
        if ( empty( $items ) ) {
            return 0;
        }

        $hits = 0;
        foreach ( $items as $item ) {
            if ( ! empty( $item[ $key ] ) ) {
                ++$hits;
            }
        }

        return round( $hits / count( $items ), 2 );
    }

    private function position_ratio( array $items, $key ) {
        if ( empty( $items ) ) {
            return 0;
        }

        $hits = 0;
        foreach ( $items as $item ) {
            if ( isset( $item[ $key ] ) && 'none' !== $item[ $key ] ) {
                ++$hits;
            }
        }

        return round( $hits / count( $items ), 2 );
    }

    private function ratio_from_hits( array $items, $key ) {
        $total_hits = 0;
        $total_words = 0;

        foreach ( $items as $item ) {
            $total_hits += isset( $item[ $key ] ) ? (int) $item[ $key ] : 0;
            $total_words += isset( $item['word_count'] ) ? (int) $item['word_count'] : 0;
        }

        if ( 0 === $total_words ) {
            return 0;
        }

        return round( min( 1, ( $total_hits * 100 ) / $total_words ), 2 );
    }

    private function average_paragraph_length( array $items ) {
        $words = 0;
        $paragraphs = 0;

        foreach ( $items as $item ) {
            $words += isset( $item['word_count'] ) ? (int) $item['word_count'] : 0;
            $paragraphs += isset( $item['paragraphs'] ) ? (int) $item['paragraphs'] : 0;
        }

        if ( 0 === $paragraphs ) {
            return 0;
        }

        return round( $words / $paragraphs, 2 );
    }

    private function image_per_heading( array $items, $heading_key ) {
        $images = 0;
        $headings = 0;

        foreach ( $items as $item ) {
            $images += isset( $item['images'] ) ? (int) $item['images'] : 0;
            $headings += isset( $item[ $heading_key ] ) ? (int) $item[ $heading_key ] : 0;
        }

        if ( 0 === $headings ) {
            return false;
        }

        return ( $images / $headings ) >= 0.75;
    }

    private function dominant_position( array $items, $key ) {
        $positions = array();

        foreach ( $items as $item ) {
            if ( empty( $item[ $key ] ) || 'none' === $item[ $key ] ) {
                continue;
            }

            if ( ! isset( $positions[ $item[ $key ] ] ) ) {
                $positions[ $item[ $key ] ] = 0;
            }

            ++$positions[ $item[ $key ] ];
        }

        if ( empty( $positions ) ) {
            return 'none';
        }

        arsort( $positions );
        return (string) array_key_first( $positions );
    }

    private function dominant_image_size( array $items ) {
        $sizes = array();

        foreach ( $items as $item ) {
            if ( empty( $item['image_sizes'] ) ) {
                continue;
            }

            foreach ( $item['image_sizes'] as $size ) {
                if ( $size < 600 ) {
                    $sizes[] = 'small';
                } elseif ( $size < 1200 ) {
                    $sizes[] = 'medium';
                } else {
                    $sizes[] = 'large';
                }
            }
        }

        if ( empty( $sizes ) ) {
            return 'unknown';
        }

        $counts = array_count_values( $sizes );
        arsort( $counts );
        return (string) array_key_first( $counts );
    }

    private function detect_tone( array $items ) {
        $polite = $this->sum( $items, 'polite_hits' );
        $assertive = $this->sum( $items, 'assertive_hits' );
        $casual = $this->sum( $items, 'casual_hits' );
        $expert = $this->sum( $items, 'expert_hits' );

        if ( $expert >= max( $polite, $casual, $assertive ) ) {
            return 'professional';
        }

        if ( $casual > $polite ) {
            return 'friendly';
        }

        return 'standard';
    }

    private function detect_style( array $items ) {
        $polite = $this->sum( $items, 'polite_hits' );
        $assertive = $this->sum( $items, 'assertive_hits' );

        return $polite >= $assertive ? 'polite' : 'assertive';
    }

    private function detect_readability( array $items ) {
        $sentence_length = $this->average( $items, 'sentence_length' );

        if ( $sentence_length <= 45 ) {
            return 'easy';
        }

        if ( $sentence_length <= 80 ) {
            return 'standard';
        }

        return 'dense';
    }

    private function detect_reader_address( array $items ) {
        return '読者に直接語りかけすぎず、自然な説明調で案内する';
    }

    private function detect_title_tendency( array $items ) {
        $average_length = 0;

        foreach ( $items as $item ) {
            $average_length += mb_strlen( (string) $item['title'], 'UTF-8' );
        }

        $average_length = empty( $items ) ? 0 : round( $average_length / count( $items ), 2 );

        return array(
            'average_length' => $average_length,
            'uses_numbers_ratio' => $this->title_pattern_ratio( $items, '/[0-9０-９]/u' ),
            'question_ratio' => $this->title_pattern_ratio( $items, '/[？?]/u' ),
        );
    }

    private function title_pattern_ratio( array $items, $pattern ) {
        if ( empty( $items ) ) {
            return 0;
        }

        $hits = 0;
        foreach ( $items as $item ) {
            if ( preg_match( $pattern, (string) $item['title'] ) ) {
                ++$hits;
            }
        }

        return round( $hits / count( $items ), 2 );
    }

    private function sum( array $items, $key ) {
        $total = 0;

        foreach ( $items as $item ) {
            $total += isset( $item[ $key ] ) ? (int) $item[ $key ] : 0;
        }

        return $total;
    }
}
