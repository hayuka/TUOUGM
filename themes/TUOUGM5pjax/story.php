<?php
$loc = $_GET['loc'] ?? '';
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
/**
 * The template for displaying Archive pages
 */
get_header(); ?>
<main id="swup" class="transition-fade main_wrap">
<div class="st_kv">
<h1>STORY<span>CHRONICLES OF <br class="sp">GUNPLA CITY</span></h1>
</div>

<div class="st_wrap flex_wrap">
<aside class="aside_st">
<div class="aside_st_nav">
<h2>ARCHIVE NAVIGATION</h2>
<ul>
<li><a href="<?php echo get_bloginfo('url'); ?>/story/history/" class="flex_wrap_middle"><span class="num_st_nav">001</span><p>HISTORY<span>Analyzing...<!--Recovered--></span></p></a></li>
<li><a href="<?php echo get_bloginfo('url'); ?>/story/characters/" class="flex_wrap_middle"><span class="num_st_nav">002</span><p>CHARACTERS<span>Analyzing...</span></p></a></li>
<li><a href="" class="flex_wrap_middle"><span class="num_st_nav">003</span><p>LOCATION<span>QUEUED</span></p></a></li>
<li><a href="" class="flex_wrap_middle"><span class="num_st_nav">004</span><p>ARCHIVES<span>QUEUED</span></p></a></li>
<!--<li><a href="" class="flex_wrap_middle"><span class="num_st_nav">005</span><p>TIMELINE<span>Analyzing...</span></p></a></li>-->
</ul>
</div>

<?php
$history_query = new WP_Query(array(
    'post_type'      => 'story',
    'posts_per_page' => -1,

    'tax_query' => array(
        array(
            'taxonomy' => 'story_category',
            'field'    => 'slug',
            'terms'    => 'history',
        ),
    ),

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
<div class="aside_st_timeline">
<h2>TIMELINE</h2>
<?php if ( ! empty($history_timeline) ) : ?>
<ul class="list_as_timeline">
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


$is_active = (
	$current_year == $year &&
	$current_era == $timeline_era
);
$era_name = $era_term
	? $era_term->name
	: strtoupper($era);
?>
<li class="<?php echo $is_active ? 'active' : ''; ?>"><span class="timemark"></span><?php echo esc_html($era_name); ?> <?php echo esc_html($year); ?>
<?php foreach ( $timeline['posts'] as $post_id ) : ?>
<p data-story-id="<?php echo esc_attr($post_id); ?>"><a href="<?php echo esc_url(get_permalink($post_id)); ?>"><?php echo esc_html(get_the_title($post_id)); ?><span><?php echo esc_html(get_post_meta( $post_id, 'subttl', true )); ?></span></a></p>
<?php endforeach; ?>
</li>
<?php endforeach; ?>
<li><span class="timemark"></span>A.D. XXXX</li>
</ul>
<?php endif; ?>
</div>
<div class="st_link_home">
<a href="<?php echo get_bloginfo('url'); ?>/story/">STORY TOP</a>
</div>
</aside>

<?php 
if(is_page('story') || is_post_type_archive('story')){
	get_template_part('story_top');
}
if ( is_tax('story_type') ) {
	get_template_part('story_history');
}
if ( is_tax('story_category') ) {
    $term = get_queried_object();
    switch ( $term->slug ) {

        case 'history':
            get_template_part('story_history');
            break;

        case 'characters':
            get_template_part('story_characters');
            break;

        case 'location':
            get_template_part('story_location');
            break;

        case 'archives':
            get_template_part('story_archives');
            break;

        default:
            get_template_part('story');
            break;
    }
}
if(is_singular('story')){
	get_template_part('story_single');
}
?>
</div>
</main>
<?php get_footer();?>