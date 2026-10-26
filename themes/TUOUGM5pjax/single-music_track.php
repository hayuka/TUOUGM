<?php
/**
 * Single music track page.
 */
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
$is_english = 'en_US' === $locale;
$detail = tuougm_music_track_detail( get_queried_object_id(), 0, $is_english ? 'en_US' : 'ja' );
if ( is_wp_error( $detail ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	return;
}

get_header();
?>
<main id="swup" class="transition-fade main_wrap sub_wrap music_track_page">
	<div class="sub_head">
		<p class="sub_head_text">TRACK<span>DETAIL</span></p>
	</div>

	<div class="inner_1200">
		<div class="wrap_sub_cnt music_track_detail">
			<?php get_template_part( 'template-parts/music/track-detail', null, $detail ); ?>

			<div class="music_track_back_wrap">
				<?php $music_url = trailingslashit( get_option( 'home' ) ) . 'music/'; ?>
				<?php if ( $is_english ) : ?>
					<?php $music_url = add_query_arg( 'loc', 'en_US', $music_url ); ?>
				<?php endif; ?>
				<a class="music_track_back eng" href="<?php echo esc_url( $music_url ); ?>">← <?php echo esc_html( $is_english ? 'BACK TO MUSIC' : 'MUSIC一覧へ戻る' ); ?></a>
			</div>
		</div>
	</div>
</main>
<?php get_footer(); ?>
