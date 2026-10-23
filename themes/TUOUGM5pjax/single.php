<?php
$loc = $_GET['loc'] ?? '';
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
/**
 * The Template for displaying all single posts
 */

get_header(); ?>

<main id="swup" class="transition-fade main_wrap sub_wrap">

<?php while ( have_posts() ) : the_post(); ?>
<?php
$cats = get_the_category();
$cat = $cats[0];
$cat_slug = $cat->slug;
?>
<div class="sub_head">
<h1 class="sub_head_text"><?php the_title(); ?><span><?php echo $cat_slug; ?></span></h1>
</div>

<div class="inner_1200">
<div class="wrap_sub_cnt">
<?php if(has_post_thumbnail()): ?>

<figure class="eyecatch">
<?php
the_post_thumbnail('large');
?>
</figure>
<?php endif; ?>
<div class="single_body">
<?php the_content(); ?>
</div>

<?php
if ( has_tag('gundam-music') ) :

    $gundam_music = new WP_Query([
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'post__not_in'   => [ get_the_ID() ],
        'tag'            => 'gundam-music',
        'orderby'        => 'date',
        'order'          => 'ASC',
    ]);

    if ( $gundam_music->have_posts() ) :
?>

<section class="gundam-music-related">
<div class="section-title">
<span>GUNDAM MUSIC BACK NUMBER</span>
<?php if ('en_US' == $locale){ ?>
<h2>Gundam Music Through the Decades</h2>
<?php }else{ ?>
<h2>ガンダム歴代主題歌を音楽ジャンルから紐解く</h2>
<?php } ?>
</div>

<ul class="gundam-music-list">

<?php while ( $gundam_music->have_posts() ) : $gundam_music->the_post(); ?>
<li class="gundam-music-item"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
<?php endwhile; ?>

</ul>
</section>

<?php
    endif;

    wp_reset_postdata();

endif;
?>

<div class="single_ft flex_wrap_between">
<?php if ('en_US' == $locale){ ?>
<p class="to_archives"><a href="<?php echo get_bloginfo('url'); ?>/archives" data-text="◀︎ ARCHIVESへ戻る">◀︎ ARCHIVES</a></p>
<?php }else{ ?>
<p class="to_archives"><a href="<?php echo get_bloginfo('url'); ?>/archives" data-text="◀︎ ARCHIVESへ戻る">◀︎ ARCHIVESへ戻る</a></p>
<?php } ?>
<?php
if ( is_singular('post') ) {
  $share_title = get_the_title();
  $share_url   = get_permalink();

  if ( has_excerpt() ) {
    $share_text = get_the_excerpt();
  } else {
    $share_text = wp_trim_words(strip_tags(get_the_content()), 40);
  }

  $share_text = $share_title . "｜" . $share_text;
} else {
  $share_text = "罪プラの逆襲、開幕。";
  $share_url  = home_url();
}

// エンコード
$tw_text = rawurlencode($share_text . "\n" . $share_url);
$fb_url  = rawurlencode($share_url);
$line_url = rawurlencode($share_url);
?>
<ul class="flex_wrap_center">
<li>
<div class="sns_button twitter">
<a href="https://twitter.com/intent/tweet?text=<?php echo $tw_text; ?>" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
</div>
</li>
<li>
<div class="sns_button facebook">
<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $fb_url; ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f"></i></a>
</div>
</li>
<li>
<div class="sns_button line">
<a href="https://social-plugins.line.me/lineit/share?url=<?php echo $line_url; ?>&text=<?php echo rawurlencode($share_text); ?>" target="_blank"><i class="fa-brands fa-line"></i></a>
</div>
</li>
</ul>
</div>
</div>

<?php endwhile; // end of the loop. ?>
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

</div>
</main>

<?php get_footer();?>