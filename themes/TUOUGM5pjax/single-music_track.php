<?php
/**
 * Single music track page.
 */
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
$is_english = 'en_US' === $locale;
$track_id = get_queried_object_id();
$track = tuougm_get_track_data( $track_id );
$access_level = tuougm_player_access_level();
$track_album = array();
$track_description = function_exists( 'get_field' )
	? (string) get_field( $is_english ? 'track_description_en' : 'track_description_ja', $track_id )
	: '';

if ( ! empty( $track ) ) {
	$album_query = new WP_Query( array(
		'post_type'           => 'music_album',
		'post_status'         => 'publish',
		'has_password'        => false,
		'posts_per_page'      => -1,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'fields'              => 'ids',
		'orderby'             => array(
			'menu_order' => 'ASC',
			'ID'         => 'DESC',
		),
	) );

	foreach ( $album_query->posts as $album_id ) {
		$album_post = get_post( $album_id );
		if ( ! $album_post || 'publish' !== $album_post->post_status || '' !== $album_post->post_password ) {
			continue;
		}
		if ( (int) get_field( 'required_level', $album_id ) > $access_level ) {
			continue;
		}

		$related_tracks = get_field( 'album_tracks', $album_id );
		if ( ! is_array( $related_tracks ) ) {
			continue;
		}

		foreach ( $related_tracks as $related_track ) {
			$related_track_id = is_object( $related_track ) ? (int) $related_track->ID : (int) $related_track;
			if ( $related_track_id === (int) $track_id ) {
				$track_album = array(
					'id'       => (string) $album_id,
					'title'    => get_the_title( $album_id ),
					'title_en' => get_field( 'album_title_en', $album_id ),
					'artwork'  => get_the_post_thumbnail_url( $album_id, 'full' ),
				);
				break 2;
			}
		}
	}
}

if ( empty( $track ) || empty( $track_album ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	include get_404_template();
	return;
}

$track_title = $is_english && ! empty( $track['title_en'] ) ? $track['title_en'] : $track['title'];
$album_title = $is_english && ! empty( $track_album['title_en'] ) ? $track_album['title_en'] : $track_album['title'];

get_header();
?>
<main id="swup" class="transition-fade main_wrap sub_wrap music_track_page">
	<div class="sub_head">
		<p class="sub_head_text">TRACK<span>DETAIL</span></p>
	</div>

	<div class="inner_1200">
		<div class="wrap_sub_cnt music_track_detail">
			<section class="music_album flex_wrap music_track_top" aria-labelledby="music-track-title">
				<figure class="music_album_artwork music_track_jacket">
					<?php if ( $track_album['artwork'] ) : ?>
						<img src="<?php echo esc_url( $track_album['artwork'] ); ?>" alt="<?php echo esc_attr( $album_title ); ?>">
					<?php endif; ?>
				</figure>
				<div class="music_album_content music_track_info">
					<p class="music_track_from_label eng"><?php echo esc_html( $is_english ? 'FROM ALBUM' : '収録アルバム' ); ?></p>
					<p class="music_track_album_title eng"><?php echo esc_html( $album_title ); ?></p>
					<h1 id="music-track-title" class="music_album_title music_track_heading eng"><?php echo esc_html( $track_title ); ?></h1>
					<button type="button" class="tuougm-track-trigger music_track_detail_play" data-player-track="<?php echo esc_attr( $track['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( $is_english ? 'Play %s' : '「%s」を再生', $track_title ) ); ?>">
						<span aria-hidden="true">▶</span><?php echo esc_html( $is_english ? 'PLAY TRACK' : '曲を再生' ); ?>
					</button>
				</div>
			</section>

			<section class="music_track_about" aria-labelledby="music-track-about-title">
				<h2 id="music-track-about-title" class="music_track_about_title eng"><?php echo esc_html( $is_english ? 'ABOUT THIS TRACK' : 'この曲について' ); ?></h2>
				<?php if ( '' !== trim( $track_description ) ) : ?>
					<div class="music_track_description"><?php echo wp_kses_post( $track_description ); ?></div>
			<?php endif; ?>
			</section>

			<div class="music_track_back_wrap">
				<?php $music_url = home_url( '/music/' ); ?>
				<?php if ( $is_english ) : ?>
					<?php $music_url = add_query_arg( 'loc', 'en_US', $music_url ); ?>
				<?php endif; ?>
				<a class="music_track_back eng" href="<?php echo esc_url( $music_url ); ?>">← <?php echo esc_html( $is_english ? 'BACK TO MUSIC' : 'MUSIC一覧へ戻る' ); ?></a>
			</div>
		</div>
	</div>
</main>
<?php get_footer(); ?>
