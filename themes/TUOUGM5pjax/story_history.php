<div class="wrap_st_main st_sub_wrap">
<section class="st_single_top st_history_top">
<p class="ttl_sub_rena">TIMELINE</p>
<h2>HISTORY<span>THE ORIGIN OF TUOUGM</span></h2>
<p>TUOUGMの結成から、Builderの目覚め、そしてGunpla City誕生まで。<br>
復元された歴史記録を公開しています。</p>
</section>
<?php
/*
 * History Type Filter
 */
$current_type = isset($_GET['type'])
    ? sanitize_key($_GET['type'])
    : '';

$story_types = get_terms(array(
    'taxonomy'   => 'story_type',
    'hide_empty' => true,
));
?>

<?php if ( ! empty($story_types) && ! is_wp_error($story_types) ) : ?>
<nav class="story_type_filter flex_wrap">
    <a
        href="<?php echo esc_url( remove_query_arg('type') ); ?>"
        class="<?php echo $current_type === '' ? 'is-active' : ''; ?>"
    >
        ALL
    </a>
    <?php foreach ( $story_types as $type ) : ?>
        <a
            href="<?php echo esc_url(
                add_query_arg(
                    'type',
                    $type->slug
                )
            ); ?>"
            class="<?php echo $current_type === $type->slug ? 'is-active' : ''; ?>"
        >
            <?php echo esc_html($type->name); ?>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>
<?php
$tax_query = array(
    array(
        'taxonomy' => 'story_category',
        'field'    => 'slug',
        'terms'    => 'history',
    ),
);

/*
 * story_type が指定されている場合
 */
if ( ! empty($current_type) ) {

    $tax_query[] = array(
        'taxonomy' => 'story_type',
        'field'    => 'slug',
        'terms'    => $current_type,
    );
}

$history_query = new WP_Query(array(
    'post_type'      => 'story',
    'posts_per_page' => -1,

    'tax_query'      => $tax_query,

    /*
     * Timeline order
     */
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

        /* Era */
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

        /* Year */
        $year = get_post_meta(
            $post_id,
            'timeline_year',
            true
        );

        /*
         * Era + Year をグループキーにする
         *
         * 例:
         * se_001
         * se_002
         * ad_2026
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
usort($history_timeline, function($a, $b) use ($era_order) {

    $era_a = $era_order[$a['era']] ?? 999;
    $era_b = $era_order[$b['era']] ?? 999;

    // Eraが違う → タクソノミーの順番
    if ( $era_a !== $era_b ) {
        return $era_a <=> $era_b;
    }

    // 同じEra → Year順
    return (int) $a['year'] <=> (int) $b['year'];
});
?>
<?php if ( ! empty($history_timeline) ) : ?>
<ul class="list_history_timeline">
<?php
$current_era  = 'a-d';
$current_year = date('Y');

if ( is_singular('story') ) {
    $current_year = get_post_meta(
        get_the_ID(),
        'timeline_year',
        true
    );
    $current_eras = get_the_terms(
        get_the_ID(),
        'story_timeline'
    );
    if ( ! empty($current_eras) && ! is_wp_error($current_eras) ) {
        $current_era = $current_eras[0]->slug;
    }
}
?>
<?php
foreach ( $history_timeline as $timeline ) :
$era  = $timeline['era'];
$year = $timeline['year'];
$era_term = get_term_by(
	'slug',
	$era,
	'story_timeline'
);
$timeline_era = $era_term
	? $era_term->slug
	: $era;


//$is_active = (
//	$current_year == $year &&
//	$current_era == $timeline_era
//);
$era_name = $era_term
	? $era_term->name
	: strtoupper($era);
?>
<li><span class="timemark"></span><p class="ttl_sub_rena"><?php echo esc_html($era_name); ?> <?php echo esc_html($year); ?></p>
<?php foreach ( $timeline['posts'] as $post_id ) : ?>
<div>
<a href="<?php echo esc_url(get_permalink($post_id)); ?>" class="flex_wrap">
<?php if ( has_post_thumbnail($post_id) ): ?>
<div class="st_meta_fig">
<figure class="eyecatch">
<?php echo get_the_post_thumbnail($post_id, 'large'); ?>
</figure>
</div>
<?php endif; ?>
<div class="st_meta_cnt">
<h3><?php echo esc_html(get_the_title($post_id)); ?><span><?php echo esc_html(get_post_meta( $post_id, 'subttl', true )); ?></span></h3>
<p><?php
$content = get_post_field('post_content', $post_id);
echo esc_html(wp_strip_all_tags($content));
?></p>
</div>
</a>
</div>
<?php endforeach; ?>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
