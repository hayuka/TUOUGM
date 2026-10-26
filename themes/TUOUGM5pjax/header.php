<!DOCTYPE html>
<?php
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
if ( is_singular( 'music_track' ) ) {
	$track_meta_id = get_queried_object_id();
	$track_meta_title = get_the_title( $track_meta_id );
	$track_meta_description = function_exists( 'get_field' )
		? (string) get_field( 'en_US' === $locale ? 'track_description_en' : 'track_description_ja', $track_meta_id )
		: '';
	$track_meta_description_plain = trim( wp_strip_all_tags( $track_meta_description ) );
	$track_meta_description_fallback = 'en_US' === $locale
		? 'An industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.'
		: 'ガンプラをテーマにした音楽プロジェクトTUOUGM。';
	if ( 'en_US' === $locale && function_exists( 'get_field' ) ) {
		$track_meta_title_en = get_field( 'track_title_en', $track_meta_id );
		if ( $track_meta_title_en ) {
			$track_meta_title = $track_meta_title_en;
		}
	}
	$ttl_og = $track_meta_title . ' | TUOUGM';
	$disc_og = '' !== $track_meta_description_plain
		? wp_trim_words( $track_meta_description_plain, 32 )
		: $track_meta_description_fallback;
}
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
	$ttl_og = "The Ultimate Sin of Unbuilt Gunpla | TUOUGM";
	$disc_og = "Unbuilt Gunpla still has a voice.";
}else{
}
if ( is_front_page() ) {
  $desc = "Japanese industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.";
} elseif ( is_singular('post') ) {
  if ( has_excerpt() ) {
    $desc = get_the_excerpt();
  } else {
    $desc = wp_trim_words(strip_tags(get_the_content()), 120);
  }
} elseif ( is_singular('music_track') ) {
  $desc = '' !== $track_meta_description_plain
    ? wp_trim_words( $track_meta_description_plain, 120 )
    : $track_meta_description_fallback;
} elseif ( is_archive() ) {
  $desc = "An industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.";
} else {
  $desc = "An industrial metal project inspired by Gunpla. Heavy sound, mechanical grooves, and the urge to build — transformed into music.";
}
} else {
if ( is_home() || is_front_page() ){
	$ttl_og = "罪プラの逆襲、開幕｜TUOUGM";
	$disc_og = "ガンプラ×メタルの衝動。未組みの罪を鳴らせ。";
}else{
}
if ( is_front_page() ) {
  $desc = "ガンプラ（Gunpla）をテーマにしたインダストリアルメタル音楽プロジェクトTUOUGM。重厚なサウンドと機械的グルーヴで模型の作り込みを音で表現。";
} elseif ( is_singular('post') ) {
  if ( has_excerpt() ) {
    $desc = get_the_excerpt();
  } else {
    $desc = wp_trim_words(strip_tags(get_the_content()), 120);
  }
} elseif ( is_singular('music_track') ) {
  $desc = '' !== $track_meta_description_plain
    ? wp_trim_words( $track_meta_description_plain, 120 )
    : $track_meta_description_fallback;
} elseif ( is_archive() ) {
  $desc = "ガンプラ×インダストリアルメタルプロジェクトTUOUGMの楽曲・リリース・動画など最新情報一覧。";
} else {
  $desc = "ガンプラをテーマにした音楽プロジェクトTUOUGM。";
}
}
// 改行除去（地味に重要）
$desc = str_replace(array("\r\n", "\n", "\r"), '', $desc);
$request_path = isset( $_SERVER['REQUEST_URI'] )
	? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH )
	: '/';
if ( ! is_string( $request_path ) || '' === $request_path ) {
	$request_path = '/';
}
$canonical = home_url( user_trailingslashit( $request_path ) );
if ( ! in_array( untrailingslashit( $request_path ), array( '', '/en' ), true ) && ! is_singular( 'music_track' ) ) {
	$page_title = wp_title( '｜', false, 'right' );
	$site_title = 'en_US' === $locale ? 'The Ultimate Sin of Unbuilt Gunpla | TUOUGM' : get_bloginfo( 'name' );
	$ttl_og = trim( $page_title ) . ' ' . $site_title;
	$disc_og = $desc;
}
if ( ! isset( $ttl_og ) || '' === trim( (string) $ttl_og ) ) {
	$ttl_og = wp_get_document_title();
}
if ( ! isset( $disc_og ) || '' === trim( (string) $disc_og ) ) {
	$disc_og = $desc;
}
if ( is_singular('post') && has_post_thumbnail() ) {
  $ogp_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
} else {
  // デフォルト画像（トップやアイキャッチ無し用）
  $ogp_img = get_template_directory_uri() . '/img/ogp.png';
}
?>

<link rel="canonical" href="<?php echo esc_url( $canonical ); ?>">
<?php if ( is_singular( 'music_track' ) ) { ?>
<title><?php echo esc_html( $ttl_og ); ?></title>
<?php } elseif ('en_US' == $locale){ ?>
<?php if ( is_archive() ) { ?>
<title>Latest Transmission from the Gunpla × Metal Project | The Ultimate Sin of Unbuilt Gunpla | TUOUGM</title>
<?php }else{ ?>
<title><?php wp_title( '｜', true, 'right' ); ?>The Ultimate Sin of Unbuilt Gunpla | TUOUGM</title>
<?php } ?>
<?php }else{ ?>
<title><?php wp_title( '｜', true, 'right' ); ?><?php bloginfo('name'); ?></title>
<?php } ?>
<meta name="description" content="<?php echo esc_attr($desc); ?>">
<meta property="og:title" content="<?php echo esc_attr( $ttl_og ); ?>">
<meta property="og:description" content="<?php echo esc_attr( $disc_og ); ?>">
<meta property="og:url" content="<?php echo $canonical; ?>">
<meta property="og:image" content="<?php echo esc_url($ogp_img); ?>">
<meta property="og:type" content="website">
<meta property="fb:app_id" content="1331136569069139">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo esc_attr( $ttl_og ); ?>">
<meta name="twitter:description" content="<?php echo esc_attr( $disc_og ); ?>">
<meta name="twitter:image" content="<?php echo esc_url($ogp_img); ?>">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@100..900&family=Saira:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

<script src="https://kit.fontawesome.com/042c9c691e.js" crossorigin="anonymous"></script>
<?php
remove_action( 'wp_head', 'rel_canonical' );
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

