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
    private $last_analyzed_count = 0;

    public function analyze( $limit = 20, $source = 'posts' ) {
        $limit = max( 1, min( 100, absint( $limit ) ) );
        $source = in_array( $source, array( 'posts', 'pages', 'all' ), true ) ? $source : 'posts';

        $posts = array();
        $post_count = 0;
        $page_count = 0;

        if ( 'posts' === $source || 'all' === $source ) {
            $post_items = $this->get_public_items( 'post', $limit );
            $post_count = count( $post_items );
            $posts = array_merge( $posts, $post_items );
        }

        if ( 'pages' === $source || 'all' === $source ) {
            $page_items = $this->get_public_items( 'page', -1 );
            $page_count = count( $page_items );
            $posts = array_merge( $posts, $page_items );
        }

        if ( empty( $posts ) ) {
            return new WP_Error( 'aiseo_no_posts', '解析できる公開記事が見つかりませんでした。' );
        }

        $items = array();

        foreach ( $posts as $post ) {
            $items[] = $this->analyze_post( $post );
        }

        $this->last_analyzed_count = count( $posts );

        return $this->build_profile( $items, $this->get_identity_pages(), $limit );
    }

    public function get_last_analyzed_count() {
        return $this->last_analyzed_count;
    }

    public function build_site_knowledge( array $profile ) {
        $identity = isset( $profile['site_identity'] ) && is_array( $profile['site_identity'] ) ? $profile['site_identity'] : array();
        $writing = isset( $profile['writing_profile'] ) && is_array( $profile['writing_profile'] ) ? $profile['writing_profile'] : array();
        $design = isset( $profile['design_profile'] ) && is_array( $profile['design_profile'] ) ? $profile['design_profile'] : array();
        $content = isset( $profile['content_profile'] ) && is_array( $profile['content_profile'] ) ? $profile['content_profile'] : array();

        $site_name = isset( $identity['site_name'] ) ? $identity['site_name'] : get_bloginfo( 'name' );
        $catch_copy = isset( $identity['catch_copy'] ) ? $identity['catch_copy'] : get_bloginfo( 'description' );
        $summary = isset( $identity['site_summary'] ) ? $identity['site_summary'] : '';
        $target = isset( $identity['target'] ) ? $identity['target'] : '';
        $services = ! empty( $identity['services'] ) && is_array( $identity['services'] ) ? implode( '、', wp_list_pluck( $identity['services'], 'title' ) ) : '未特定';
        $tone = isset( $writing['writing_style']['tone'] ) ? $writing['writing_style']['tone'] : 'standard';
        $style = isset( $writing['writing_style']['style'] ) ? $writing['writing_style']['style'] : 'polite';
        $average_h2 = isset( $writing['structure']['average_h2'] ) ? $writing['structure']['average_h2'] : 0;
        $average_h3 = isset( $writing['structure']['average_h3'] ) ? $writing['structure']['average_h3'] : 0;
        $cta_position = isset( $writing['cta']['position'] ) ? $writing['cta']['position'] : 'none';
        $image_per_h2 = ! empty( $design['images']['image_per_h2'] ) ? '各H2付近に画像を置く傾向があります' : '画像配置は必要な箇所に絞る傾向があります';
        $primary_color = isset( $design['colors']['primary'] ) ? $design['colors']['primary'] : 'unknown';
        $categories = ! empty( $content['categories'] ) && is_array( $content['categories'] ) ? implode( '、', wp_list_pluck( $content['categories'], 'name' ) ) : '未特定';

        $knowledge = array(
            sprintf( 'このサイトは「%s」です。', $site_name ),
        );

        if ( '' !== $catch_copy ) {
            $knowledge[] = sprintf( 'キャッチコピーは「%s」です。', $catch_copy );
        }

        if ( '' !== $summary ) {
            $knowledge[] = sprintf( 'サイト概要として、%s', $summary );
        }

        $knowledge[] = sprintf( '主なサービスまたは重要ページは「%s」です。', $services );

        if ( '' !== $target ) {
            $knowledge[] = sprintf( '想定ターゲットは%sです。', $target );
        }

        $knowledge[] = sprintf( '文章は%sを基本とし、トーンは%s寄りです。', $style, $tone );
        $knowledge[] = sprintf( '記事構成は平均H2が%s個、平均H3が%s個程度です。', $average_h2, $average_h3 );
        $knowledge[] = sprintf( 'CTAは%sに置かれる傾向があります。', $cta_position );
        $knowledge[] = $image_per_h2 . '。';
        $knowledge[] = sprintf( 'ブランドカラーの推定値は%sです。', $primary_color );
        $knowledge[] = sprintf( '主なカテゴリ傾向は「%s」です。', $categories );
        $knowledge[] = '記事生成時は、サイトの目的、ブランド、ターゲット、文体、レイアウト、SEO傾向との一貫性を最優先してください。';

        return implode( "\n\n", array_filter( $knowledge ) );
    }

    private function get_public_items( $post_type, $limit ) {
        return get_posts(
            array(
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
            )
        );
    }

    private function get_identity_pages() {
        $pages = array();
        $seen = array();
        $front_page_id = (int) get_option( 'page_on_front' );

        if ( $front_page_id > 0 ) {
            $front_page = get_post( $front_page_id );
            if ( $front_page && 'publish' === $front_page->post_status ) {
                $pages[] = $this->format_identity_page( $front_page, 'front_page' );
                $seen[] = $front_page->ID;
            }
        }

        $keywords = array(
            'about',
            'company',
            'service',
            'concept',
            'mission',
            'vision',
            'faq',
            'contact',
            '会社概要',
            '私たち',
            'サービス',
            '事業',
            '理念',
            'ミッション',
            'ビジョン',
            'よくある質問',
            'お問い合わせ',
        );

        $all_pages = get_posts(
            array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'menu_order title',
                'order' => 'ASC',
                'no_found_rows' => true,
            )
        );

        foreach ( $all_pages as $page ) {
            if ( in_array( $page->ID, $seen, true ) ) {
                continue;
            }

            $haystack = mb_strtolower( $page->post_name . ' ' . get_the_title( $page ) . ' ' . wp_strip_all_tags( $page->post_content ), 'UTF-8' );

            foreach ( $keywords as $keyword ) {
                if ( false !== mb_strpos( $haystack, mb_strtolower( $keyword, 'UTF-8' ), 0, 'UTF-8' ) ) {
                    $pages[] = $this->format_identity_page( $page, $keyword );
                    $seen[] = $page->ID;
                    break;
                }
            }
        }

        return array_slice( $pages, 0, 20 );
    }

    private function format_identity_page( WP_Post $page, $matched_by ) {
        $plain = trim( wp_strip_all_tags( strip_shortcodes( $page->post_content ) ) );

        return array(
            'id' => $page->ID,
            'title' => get_the_title( $page ),
            'slug' => $page->post_name,
            'url' => get_permalink( $page ),
            'matched_by' => $matched_by,
            'excerpt' => wp_trim_words( $plain, 80, '' ),
        );
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
            'post_id' => $post->ID,
            'post_type' => $post->post_type,
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
            'icon' => $this->has_pattern( $content, '/class="[^"]*(icon|dashicons|fa-|material-icons)[^"]*"/i' ),
            'comparison_table' => $this->has_pattern( $content, '/比較|<table\b/i' ),
            'featured_image' => has_post_thumbnail( $post ),
            'images' => $this->count_images( $content ),
            'image_sizes' => $this->detect_image_sizes( $content ),
            'meta_description' => $this->has_meta_description( $post->ID ),
            'internal_links' => $links['internal'],
            'external_links' => $links['external'],
        );
    }

    private function build_profile( array $items, array $identity_pages, $featured_image_sample_limit = 20 ) {
        $profile = array(
            'site_identity' => array(
                'site_name' => get_bloginfo( 'name' ),
                'catch_copy' => get_bloginfo( 'description' ),
                'site_summary' => $this->build_site_summary( $identity_pages ),
                'services' => $this->detect_service_pages( $identity_pages ),
                'industry' => '',
                'target' => $this->infer_target( $identity_pages ),
                'persona' => '',
                'brand_concept' => get_bloginfo( 'description' ),
                'strengths' => $this->extract_strengths( $identity_pages ),
                'value_proposition' => $this->build_value_proposition(),
                'cta_goal' => $this->detect_cta_goal( $identity_pages ),
                'brand_tone' => $this->detect_tone( $items ),
                'ng_words' => array( '根拠のない断定', '誇大表現', '不確かなNo.1表現' ),
                'brand' => array(
                    'name' => get_bloginfo( 'name' ),
                    'tagline' => get_bloginfo( 'description' ),
                ),
                'wordpress' => array(
                    'blogname' => get_bloginfo( 'name' ),
                    'blogdescription' => get_bloginfo( 'description' ),
                    'site_title' => get_bloginfo( 'name' ),
                    'tagline' => get_bloginfo( 'description' ),
                    'logo' => $this->get_logo_url(),
                    'favicon' => get_site_icon_url(),
                    'menus' => $this->get_menu_structure(),
                    'categories' => $this->get_top_terms( 'category', 10 ),
                    'tags' => $this->get_top_terms( 'post_tag', 20 ),
                ),
                'identity_pages' => $identity_pages,
            ),
            'writing_profile' => array(
                'writing_style' => array(
                    'tone' => $this->detect_tone( $items ),
                    'style' => $this->detect_style( $items ),
                    'readability' => $this->detect_readability( $items ),
                    'average_sentence_length' => $this->average( $items, 'sentence_length' ),
                    'expertise' => $this->ratio_from_hits( $items, 'expert_hits' ),
                    'casualness' => $this->ratio_from_hits( $items, 'casual_hits' ),
                    'honorific_level' => $this->ratio_from_hits( $items, 'polite_hits' ),
                    'reader_address' => $this->detect_reader_address( $items ),
                ),
                'structure' => array(
                    'average_words' => $this->average( $items, 'word_count' ),
                    'average_h2' => $this->average( $items, 'h2' ),
                    'average_h3' => $this->average( $items, 'h3' ),
                    'average_h4' => $this->average( $items, 'h4' ),
                    'average_paragraphs' => $this->average( $items, 'paragraphs' ),
                    'paragraph_length' => $this->average_paragraph_length( $items ),
                    'list_ratio' => $this->ratio( $items, 'list' ),
                    'table_ratio' => $this->ratio( $items, 'table' ),
                    'summary_position' => $this->dominant_position( $items, 'summary_position' ),
                    'faq' => $this->ratio( $items, 'faq' ) >= 0.3,
                    'faq_ratio' => $this->ratio( $items, 'faq' ),
                ),
                'seo' => array(
                    'title_tendency' => $this->detect_title_tendency( $items ),
                    'meta_description_ratio' => $this->ratio( $items, 'meta_description' ),
                    'keyword_density' => 'not_calculated',
                    'internal_links' => $this->average( $items, 'internal_links' ),
                    'external_links' => $this->average( $items, 'external_links' ),
                    'related_links_position' => 'body',
                ),
                'cta' => array(
                    'position' => $this->dominant_position( $items, 'cta_position' ),
                    'usage_ratio' => $this->position_ratio( $items, 'cta_position' ),
                    'button_ratio' => $this->ratio( $items, 'button' ),
                ),
            ),
            'design_profile' => array(
                'theme' => $this->get_theme_profile(),
                'logo' => $this->get_logo_url(),
                'favicon' => get_site_icon_url(),
                'colors' => $this->get_color_profile(),
                'fonts' => array(
                    'body' => 'unknown',
                    'heading' => 'unknown',
                ),
                'layout' => array(
                    'box_ratio' => $this->ratio( $items, 'box' ),
                    'speech_ratio' => $this->ratio( $items, 'speech' ),
                    'comparison_table_ratio' => $this->ratio( $items, 'comparison_table' ),
                    'quote_ratio' => $this->ratio( $items, 'quote' ),
                    'code_block_ratio' => $this->ratio( $items, 'code' ),
                    'spacing' => 'unknown',
                    'card_design' => $this->ratio( $items, 'box' ) >= 0.3 ? 'frequent' : 'minimal',
                    'border_radius' => 'unknown',
                ),
                'blocks' => array(
                    'box' => $this->ratio( $items, 'box' ),
                    'speech' => $this->ratio( $items, 'speech' ),
                    'faq' => $this->ratio( $items, 'faq' ),
                    'comparison_table' => $this->ratio( $items, 'comparison_table' ),
                    'cta' => $this->position_ratio( $items, 'cta_position' ),
                    'button' => $this->ratio( $items, 'button' ),
                    'quote' => $this->ratio( $items, 'quote' ),
                    'code_block' => $this->ratio( $items, 'code' ),
                ),
                'icons' => array(
                    'usage_ratio' => $this->ratio( $items, 'icon' ),
                ),
                'buttons' => array(
                    'usage_ratio' => $this->ratio( $items, 'button' ),
                    'shape' => 'unknown',
                ),
                'cta_design' => array(
                    'usage_ratio' => $this->position_ratio( $items, 'cta_position' ),
                    'button_ratio' => $this->ratio( $items, 'button' ),
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
            ),
            'media_profile' => array(
                'featured_image' => ( new AISEO_Media_Analyzer() )->analyze_featured_image_sizes( $featured_image_sample_limit ),
            ),
            'content_profile' => array(
                'categories' => $this->get_top_terms( 'category', 10 ),
                'popular_posts' => $this->get_popular_posts( $items ),
                'new_posts' => $this->get_new_posts( 5 ),
                'tags' => $this->get_top_terms( 'post_tag', 20 ),
                'internal_links' => array(
                    'average_internal_links' => $this->average( $items, 'internal_links' ),
                    'average_external_links' => $this->average( $items, 'external_links' ),
                    'related_links_position' => 'body',
                ),
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

    private function build_site_summary( array $identity_pages ) {
        $parts = array();

        foreach ( $identity_pages as $page ) {
            if ( empty( $page['excerpt'] ) ) {
                continue;
            }

            $parts[] = $page['title'] . ': ' . $page['excerpt'];
        }

        if ( empty( $parts ) ) {
            return get_bloginfo( 'description' );
        }

        return wp_trim_words( implode( ' ', $parts ), 120, '' );
    }

    private function detect_service_pages( array $identity_pages ) {
        $services = array();

        foreach ( $identity_pages as $page ) {
            $label = mb_strtolower( $page['title'] . ' ' . $page['slug'] . ' ' . $page['matched_by'], 'UTF-8' );
            if ( false === mb_strpos( $label, 'service', 0, 'UTF-8' ) && false === mb_strpos( $label, 'サービス', 0, 'UTF-8' ) && false === mb_strpos( $label, '事業', 0, 'UTF-8' ) ) {
                continue;
            }

            $services[] = array(
                'title' => $page['title'],
                'url' => $page['url'],
                'summary' => $page['excerpt'],
            );
        }

        return $services;
    }

    private function infer_target( array $identity_pages ) {
        $text = $this->identity_pages_text( $identity_pages );
        $targets = array(
            '中小企業' => '中小企業の経営者・担当者',
            '経営者' => '経営者',
            '採用' => '求職者・採用候補者',
            '初心者' => '初心者',
            'マーケティング' => 'マーケティング担当者',
            'WordPress' => 'WordPressサイト運営者',
            'SEO' => 'SEOや集客に取り組むサイト運営者',
        );

        foreach ( $targets as $needle => $target ) {
            if ( false !== mb_strpos( $text, $needle, 0, 'UTF-8' ) ) {
                return $target;
            }
        }

        return '';
    }

    private function extract_strengths( array $identity_pages ) {
        $text = $this->identity_pages_text( $identity_pages );
        $patterns = array( '強み', '選ばれる理由', '特徴', '実績', '専門', '安心', '高品質' );
        $strengths = array();

        foreach ( $patterns as $pattern ) {
            if ( false !== mb_strpos( $text, $pattern, 0, 'UTF-8' ) ) {
                $strengths[] = $pattern;
            }
        }

        return array_values( array_unique( $strengths ) );
    }

    private function build_value_proposition() {
        $description = get_bloginfo( 'description' );

        if ( '' === $description ) {
            return '';
        }

        return $description;
    }

    private function detect_cta_goal( array $identity_pages ) {
        $text = $this->identity_pages_text( $identity_pages );

        if ( false !== mb_strpos( $text, 'お問い合わせ', 0, 'UTF-8' ) || false !== mb_strpos( $text, 'contact', 0, 'UTF-8' ) ) {
            return 'contact';
        }

        if ( false !== mb_strpos( $text, '資料請求', 0, 'UTF-8' ) ) {
            return 'lead_generation';
        }

        if ( false !== mb_strpos( $text, '購入', 0, 'UTF-8' ) || false !== mb_strpos( $text, '申し込み', 0, 'UTF-8' ) ) {
            return 'conversion';
        }

        return 'unknown';
    }

    private function identity_pages_text( array $identity_pages ) {
        $parts = array();

        foreach ( $identity_pages as $page ) {
            $parts[] = $page['title'];
            $parts[] = $page['slug'];
            $parts[] = $page['excerpt'];
        }

        return implode( ' ', $parts );
    }

    private function get_logo_url() {
        $custom_logo_id = get_theme_mod( 'custom_logo' );

        if ( ! $custom_logo_id ) {
            return '';
        }

        $logo = wp_get_attachment_image_src( $custom_logo_id, 'full' );
        return ! empty( $logo[0] ) ? $logo[0] : '';
    }

    private function get_menu_structure() {
        $locations = get_nav_menu_locations();
        $menus = array();

        foreach ( $locations as $location => $menu_id ) {
            $items = wp_get_nav_menu_items( $menu_id );
            if ( empty( $items ) || is_wp_error( $items ) ) {
                continue;
            }

            $menus[ $location ] = array();
            foreach ( $items as $item ) {
                $menus[ $location ][] = array(
                    'title' => $item->title,
                    'url' => $item->url,
                    'type' => $item->type,
                );
            }
        }

        return $menus;
    }

    private function get_theme_profile() {
        $theme = wp_get_theme();

        return array(
            'name' => $theme->get( 'Name' ),
            'version' => $theme->get( 'Version' ),
            'template' => get_template(),
            'stylesheet' => get_stylesheet(),
        );
    }

    private function get_color_profile() {
        $colors = array();
        $background = get_theme_mod( 'background_color' );
        $header_text = get_theme_mod( 'header_textcolor' );
        $custom_css = wp_get_custom_css();

        if ( $background ) {
            $colors[] = '#' . ltrim( $background, '#' );
        }

        if ( $header_text && 'blank' !== $header_text ) {
            $colors[] = '#' . ltrim( $header_text, '#' );
        }

        if ( preg_match_all( '/#[0-9a-fA-F]{3,6}\b/', $custom_css, $matches ) ) {
            $colors = array_merge( $colors, $matches[0] );
        }

        $colors = array_values( array_unique( array_slice( $colors, 0, 8 ) ) );

        return array(
            'primary' => isset( $colors[0] ) ? $colors[0] : 'unknown',
            'secondary' => isset( $colors[1] ) ? $colors[1] : 'unknown',
            'accent' => isset( $colors[2] ) ? $colors[2] : 'unknown',
            'palette' => $colors,
        );
    }

    private function get_top_terms( $taxonomy, $limit ) {
        $terms = get_terms(
            array(
                'taxonomy' => $taxonomy,
                'hide_empty' => true,
                'number' => $limit,
                'orderby' => 'count',
                'order' => 'DESC',
            )
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }

        $result = array();
        foreach ( $terms as $term ) {
            $result[] = array(
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => (int) $term->count,
            );
        }

        return $result;
    }

    private function get_new_posts( $limit ) {
        $posts = get_posts(
            array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $limit,
                'orderby' => 'date',
                'order' => 'DESC',
                'no_found_rows' => true,
            )
        );

        $result = array();
        foreach ( $posts as $post ) {
            $result[] = array(
                'id' => $post->ID,
                'title' => get_the_title( $post ),
                'url' => get_permalink( $post ),
                'date' => get_the_date( 'Y-m-d', $post ),
            );
        }

        return $result;
    }

    private function get_popular_posts( array $items ) {
        $post_ids = array();

        foreach ( $items as $item ) {
            if ( isset( $item['post_id'], $item['post_type'] ) && 'post' === $item['post_type'] ) {
                $post_ids[] = (int) $item['post_id'];
            }
        }

        if ( empty( $post_ids ) ) {
            return array();
        }

        $posts = get_posts(
            array(
                'post__in' => $post_ids,
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 5,
                'orderby' => 'comment_count',
                'order' => 'DESC',
                'no_found_rows' => true,
            )
        );

        $result = array();
        foreach ( $posts as $post ) {
            $result[] = array(
                'id' => $post->ID,
                'title' => get_the_title( $post ),
                'url' => get_permalink( $post ),
                'comment_count' => (int) $post->comment_count,
            );
        }

        return $result;
    }

    private function sum( array $items, $key ) {
        $total = 0;

        foreach ( $items as $item ) {
            $total += isset( $item[ $key ] ) ? (int) $item[ $key ] : 0;
        }

        return $total;
    }
}
