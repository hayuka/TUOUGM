<div class="wrap_st_main st_sub_wrap">
<?php while ( have_posts() ) : the_post(); ?>
<?php
$era_terms = get_the_terms(get_the_ID(), 'story_timeline');
$era = '';
if ($era_terms && !is_wp_error($era_terms)) {
    $era = $era_terms[0]->name;
}
$year = get_post_meta(get_the_ID(), 'timeline_year', true);
if(get_field( 'subttl' )){
	$subttl = get_field( 'subttl' );
}
?>
<div class="wrap_st_single">
<section class="st_single_top">
<p class="ttl_sub_rena"><?php echo esc_html($era); ?> <?php echo esc_html($year); ?></p>
<h2><?php the_title(); ?><?php if($subttl){ ?><span><?php echo $subttl; ?></span><?php } ?></h2>
</section>
<div class="wrap_st_s_cnt">
<div class="st_s_meta flex_wrap">
<?php if(has_post_thumbnail()): ?>
<div class="st_meta_fig">
<figure class="eyecatch">
<?php
the_post_thumbnail('large');
?>
</figure>
</div>
<?php endif; ?>
<div class="st_meta_cnt">
<?php
$story_types = get_the_terms(
    get_the_ID(),
    'story_type'
);
?>
<?php if ( ! empty($story_types) && ! is_wp_error($story_types) ) : ?>
<p class="st_type flex_wrap">
<?php foreach ( $story_types as $type ) : ?>
<?php
$history_url = get_term_link(
    get_term_by(
        'slug',
        'history',
        'story_category'
    )
);

$type_link = add_query_arg(
    'type',
    $type->slug,
    $history_url
);
?>
<a href="<?php echo esc_url($type_link); ?>"><?php echo esc_html( $type->name ); ?></a>
<?php endforeach; ?>
<?php endif; ?>
</p>
<ul class="related-links list_rena_link">
<?php
for ( $i = 1; $i <= 5; $i++ ) :
$related = get_field( 'related' . $i );
if ( empty( $related ) ) {
	continue;
}
$link     = $related['link'] ?? '';
$linktext = $related['linktext'] ?? '';
$blank    = $related['blank'] ?? false;
if ( empty( $link ) || empty( $linktext ) ) {
	continue;
}
$target = $blank ? ' target="_blank" rel="noopener noreferrer"' : '';
?>
<li>
<a href="<?php echo esc_url( $link ); ?>"<?php echo $target; ?>><?php echo esc_html( $linktext ); ?></a>
</li>
<?php endfor; ?>
</ul>
</div>
</div>
<section class="st_single_body">
<?php the_content(); ?>
</section>
<?php

$current_post_id = get_the_ID();

/*
 * 現在の記事のカテゴリー
 * ※ 1記事 = 1カテゴリー前提
 */
$story_categories = get_the_terms(
    $current_post_id,
    'story_category'
);

$current_category = '';

if ( ! empty($story_categories) && ! is_wp_error($story_categories) ) {
    $current_category = $story_categories[0]->slug;
}


/*
 * ========================================
 * History
 * ========================================
 *
 * Timeline順
 * Era → Year → timeline_order
 */
if ( $current_category === 'history' ) {

    $history_query = new WP_Query(array(
        'post_type'      => 'story',
        'posts_per_page' => -1,

        'tax_query' => array(
            array(
                'taxonomy' => 'story_category',
                'field'    => 'slug',
                'terms'     => 'history',
            ),
        ),

        'meta_key'       => 'timeline_order',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',

        'no_found_rows'  => true,
    ));

    $history_timeline = array();
    $era_order = array();

    if ( $history_query->have_posts() ) {

        while ( $history_query->have_posts() ) {

            $history_query->the_post();

            $post_id = get_the_ID();

            /*
             * Era
             */
            $eras = get_the_terms(
                $post_id,
                'story_timeline'
            );

            $era = '';

            if ( ! empty($eras) && ! is_wp_error($eras) ) {

                $era = $eras[0]->slug;

                if ( ! isset($era_order[$era]) ) {
                    $era_order[$era] = count($era_order);
                }
            }

            /*
             * Year
             */
            $year = get_post_meta(
                $post_id,
                'timeline_year',
                true
            );

            /*
             * Era + Year
             */
            $group_key = $era . '_' . $year;

            if ( ! isset($history_timeline[$group_key]) ) {

                $history_timeline[$group_key] = array(
                    'era'   => $era,
                    'year'  => $year,
                    'posts' => array(),
                );
            }

            $history_timeline[$group_key]['posts'][] = $post_id;
        }

        wp_reset_postdata();
    }


    /*
     * Era → Year順
     */
    usort(
        $history_timeline,
        function($a, $b) use ($era_order) {

            $era_a = $era_order[$a['era']] ?? 999;
            $era_b = $era_order[$b['era']] ?? 999;

            if ( $era_a !== $era_b ) {
                return $era_a <=> $era_b;
            }

            return (int) $a['year'] <=> (int) $b['year'];
        }
    );


    /*
     * Timelineと同じ順番でフラット化
     */
    $story_posts = array();

    foreach ( $history_timeline as $group ) {

        foreach ( $group['posts'] as $post_id ) {
            $story_posts[] = $post_id;
        }
    }


/*
 * ========================================
 * その他のカテゴリー
 * ========================================
 *
 * ここは「そのカテゴリーの一覧ページ」と
 * 同じQuery条件を入れる。
 */
} else {

    $story_query = new WP_Query(array(
        'post_type'      => 'story',
        'posts_per_page' => -1,

        'tax_query' => array(
            array(
                'taxonomy' => 'story_category',
                'field'    => 'slug',
                'terms'     => $current_category,
            ),
        ),

        /*
         * ↓ここを一覧ページと同じ並び順にする
         */
        'orderby'        => 'date',
        'order'          => 'DESC',

        'no_found_rows'  => true,
    ));

    $story_posts = array();

    if ( $story_query->have_posts() ) {

        while ( $story_query->have_posts() ) {

            $story_query->the_post();

            $story_posts[] = get_the_ID();
        }

        wp_reset_postdata();
    }
}


/*
 * ========================================
 * 前後記事
 * ========================================
 */

$current_index = array_search(
    $current_post_id,
    $story_posts,
    true
);

$prev_post = null;
$next_post = null;

if ( $current_index !== false ) {

    if ( isset($story_posts[$current_index - 1]) ) {
        $prev_post = $story_posts[$current_index - 1];
    }

    if ( isset($story_posts[$current_index + 1]) ) {
        $next_post = $story_posts[$current_index + 1];
    }
}

?>
<?php if ( $prev_post || $next_post ) : ?>

<div class="story-navigation">
<div class="story-nav-prev">
    <?php if ( $prev_post ) : ?>

        <a href="<?php echo esc_url( get_permalink($prev_post) ); ?>"
           class="story-nav">

            <span class="story-nav-label">PREV</span>

            <span class="story-nav-title">
                <?php echo esc_html( get_the_title($prev_post) ); ?>
            </span>

        </a>

    <?php endif; ?>
</div>
<div class="story-nav-next">
    <?php if ( $next_post ) : ?>

        <a href="<?php echo esc_url( get_permalink($next_post) ); ?>"
           class="story-nav">

            <span class="story-nav-label">NEXT</span>

            <span class="story-nav-title">
                <?php echo esc_html( get_the_title($next_post) ); ?>
            </span>

        </a>

    <?php endif; ?>
</div>
</div>

<?php endif; ?>
</div>
<?php endwhile; // end of the loop. ?>
</div>
