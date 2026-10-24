<!DOCTYPE html>
<?php
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
if ('en_US' == $locale){ 
?>
<html lang="en">
<?php }else{ ?>
<html lang="ja" dir="ltr">
<?php } ?>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="format-detection" content="telephone=no">
<?php 
if ('en_US' == $locale){
if ( is_home() || is_front_page() ){
	$canonical = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] ;
	$ttl_og = "The Ultimate Sin of Unbuilt Gunpla | TUOUGM";
	$disc_og = "Unbuilt Gunpla still has a voice.";
}else{
	$canonical = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
}
if ( is_front_page() ) {
  $desc = "Japanese industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.";
} elseif ( is_singular('post') ) {
  if ( has_excerpt() ) {
    $desc = get_the_excerpt();
  } else {
    $desc = wp_trim_words(strip_tags(get_the_content()), 120);
  }
} elseif ( is_archive() ) {
  $desc = "An industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.";
} else {
  $desc = "An industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.";
}
} else {
if ( is_home() || is_front_page() ){
	$canonical = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] ;
	$ttl_og = "罪プラの逆襲、開幕｜TUOUGM";
	$disc_og = "ガンプラ×メタルの衝動。未組みの罪を鳴らせ。";
}else{
	$canonical = (empty($_SERVER["HTTPS"]) ? "http://" : "https://") . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
}
if ( is_front_page() ) {
  $desc = "ガンプラ（Gunpla）をテーマにしたインダストリアルメタル音楽プロジェクトTUOUGM。重厚なサウンドと機械的グルーヴで模型の作り込みを音で表現。";
} elseif ( is_singular('post') ) {
  if ( has_excerpt() ) {
    $desc = get_the_excerpt();
  } else {
    $desc = wp_trim_words(strip_tags(get_the_content()), 120);
  }
} elseif ( is_archive() ) {
  $desc = "ガンプラ×インダストリアルメタルプロジェクトTUOUGMの楽曲・リリース・動画など最新情報一覧。";
} else {
  $desc = "ガンプラをテーマにした音楽プロジェクトTUOUGM。";
}
}
// 改行除去（地味に重要）
$desc = str_replace(array("\r\n", "\n", "\r"), '', $desc);
if ( is_singular('post') && has_post_thumbnail() ) {
  $ogp_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
} else {
  // デフォルト画像（トップやアイキャッチ無し用）
  $ogp_img = get_template_directory_uri() . '/img/ogp.png';
}
?>

<link rel="canonical" href="<?php echo $canonical; ?>">
<?php if ('en_US' == $locale){ ?>
<?php if ( is_archive() ) { ?>
<title>Latest Transmission from the Gunpla × Metal Project | The Ultimate Sin of Unbuilt Gunpla | TUOUGM</title>
<?php }else{ ?>
<title><?php wp_title( '｜', true, 'right' ); ?>The Ultimate Sin of Unbuilt Gunpla | TUOUGM</title>
<?php } ?>
<?php }else{ ?>
<title><?php wp_title( '｜', true, 'right' ); ?><?php bloginfo('name'); ?></title>
<?php } ?>
<meta name="description" content="<?php echo esc_attr($desc); ?>">
<meta property="og:title" content="<?php echo $ttl_og; ?>">
<meta property="og:description" content="<?php echo $disc_og; ?>">
<meta property="og:url" content="<?php echo $canonical; ?>">
<meta property="og:image" content="<?php echo esc_url($ogp_img); ?>">
<meta property="og:type" content="website">
<meta property="fb:app_id" content="1331136569069139">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo $ttl_og; ?>">
<meta name="twitter:description" content="<?php echo $disc_og; ?>">
<meta name="twitter:image" content="<?php echo esc_url($ogp_img); ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Saira:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/Modaal/0.4.4/css/modaal.min.css" rel="stylesheet" type="text/css">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/module.css">
<link rel="stylesheet" href="<?php echo get_template_directory_uri(); ?>/css/style.css">

<script src="https://kit.fontawesome.com/042c9c691e.js" crossorigin="anonymous"></script>
<?php
wp_head();
?>
</head>

<body>
<header id="swup-header" class="transition-fade">
<div class="inner_header flex_wrap_between flex_wrap_middle">
<p class="logo"><a href="<?php echo get_bloginfo('url'); ?>"><img src="<?php echo get_template_directory_uri(); ?>/img/logo.webp" alt="TUOUGM"></a></p>
<div class="flex_wrap_middle nav_wrap">
<nav class="gnav flex_wrap">
<ul class="flex_wrap">
<li><a href="<?php echo get_bloginfo('url'); ?>/about/">ABOUT</a></li>
<li><a href="<?php echo esc_url( home_url( '/music/' ) ); ?>">MUSIC</a></li>
<?php if ( is_home() || is_front_page() ) : ?>
<!--<li><a href="#anc04">GOODS</a></li>-->
<!--<li><a href="#anc03">MUSIC</a></li>-->
<!--<li><a href="#anc02">VIDEO</a></li>-->
<?php else : ?>
<!--<li><a href="<?php echo get_bloginfo('url'); ?>/#anc04">GOODS</a></li>-->
<!--<li><a href="<?php echo get_bloginfo('url'); ?>/#anc03">MUSIC</a></li>-->
<li><a href="<?php echo get_bloginfo('url'); ?>/story/">STORY</a></li>
<!--<li><a href="<?php echo get_bloginfo('url'); ?>/#anc02">VIDEO</a></li>-->
<?php endif; ?>
<li><a href="<?php echo get_bloginfo('url'); ?>/archives/">ARCHIVES</a></li>
<li><a href="<?php echo get_bloginfo('url'); ?>/members/">MEMBERS</a></li>
</ul>
</nav>
<?php echo do_shortcode('[bogo]'); ?> 
<div class="h_btn">
<span></span>
<span></span>
<span></span>
</div>
</div>
</div>
</header>

