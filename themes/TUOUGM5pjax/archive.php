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
<main id="swup" class="transition-fade main_wrap sub_wrap">
<?php
if ('en_US' == $locale){
$ttl = 'Gunpla × Metal – Backlog';
$cat_slug = 'ARCHIVES';
}else{
$ttl = 'ガンプラ×メタルプロジェクト最新情報';
$cat_slug = 'ARCHIVES';
}
if(is_category()){
$category = get_category($cat);
$cat_slug = $category->slug;
//$ttl = $category->name;
}
?>
<div class="sub_head">
<h1 class="sub_head_text"><?php echo $ttl; ?><span><?php echo $cat_slug; ?></span></h1>
</div>

<div class="inner_1200">

<nav class="sub_nav">
<ul class="flex_wrap">
<li><a href="<?php echo get_bloginfo('url'); ?>/archives"<?php if(!is_category()){ echo ' class="active"';} ?>>ALL</a></li>
<?php
$categories = get_categories();
foreach($categories as $taxonomyss):
$cat_link = get_category_link($taxonomyss->term_id);
?>
<li><a href="<?php echo $cat_link; ?>"<?php if($cat_slug == $taxonomyss->slug){ echo ' class="active"';} ?>><?php echo $taxonomyss->name; ?></a></li>
<?php
endforeach;
?>
</ul>
</nav>

<div class="wrap_sub_cnt">
<?php if ( have_posts() ) : ?>
<ul class="list_archives flex_wrap">
<?php while (have_posts()) : the_post(); ?>
<?php
$cats = get_the_category();
?>
<?php
if(has_post_thumbnail()){
	$image_id = get_post_thumbnail_id();
	$image_url = wp_get_attachment_image_src($image_id, 'newthumb');
	$thumb = $image_url[0];
}else{
	$thumb = get_template_directory_uri().'/img/sample.png';
}
?>
<li>
<a href="<?php echo get_permalink(); ?>">
<figure><img src="<?php echo $thumb; ?>" alt=""></figure>
<div class="list_cnt">
<h2 class="ttl_list"><?php the_title() ?></h2>
<p class="cate flex_wrap">
<?php foreach($cats as $cat): ?>
<span class="cate_tab"><?php echo $cat->name; ?></span>
<?php endforeach; ?>
</p>
</div>
</a>
</li>
<?php endwhile; ?>
</ul>
<?php pagination($wp_query->max_num_pages, 1); ?>
<?php else: ?><!--  キーワードが見つからないときの処理 -->
<?php if ('en_US' == $locale){ ?>
<p>No posts yet.</p>
<?php }else{ ?>
<p>このカテゴリーの投稿はまだありません。</p>
<?php } ?>
<?php endif; ?>
</div><!--/cnt_news-->
<?php wp_reset_postdata(); wp_reset_query(); ?>
<div class="cta">
<div class="inner_cta">
<?php if ('en_US' == $locale){ ?>
<p><a href="https://www.youtube.com/@zakubakamodeling" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/img/bn2_en.png" alt="罪プラの逆襲、開幕"></a></p>
<?php }else{ ?>
<p><a href="https://www.youtube.com/@zakubakamodeling" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/img/bn2.png" alt="罪プラの逆襲、開幕"></a></p>
<?php } ?>
</div>
</div>
</div>

</main>
<?php get_footer();?>