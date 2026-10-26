<?php
/**
 * Music library page.
 */
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
$is_english = 'en_US' === $locale;
$access_level = function_exists( 'tuougm_player_access_level' )
	? tuougm_player_access_level()
	: ( is_user_logged_in() ? (int) get_user_meta( get_current_user_id(), 'membership_level', true ) : 0 );

$music_albums = new WP_Query( array(
	'post_type'           => 'music_album',
	'post_status'         => 'publish',
	'has_password'        => false,
	'posts_per_page'      => -1,
	'no_found_rows'       => true,
	'ignore_sticky_posts' => true,
	'orderby'             => array(
		'menu_order' => 'ASC',
		'ID'         => 'DESC',
	),
) );
$displayed_album_count = 0;

get_header();
?>
<main id="swup" class="transition-fade main_wrap sub_wrap music_page">
	<div class="sub_head">
		<h1 class="sub_head_text">MUSIC<span>ALBUMS</span></h1>
	</div>

	<div class="inner_1200">
		<div class="wrap_sub_cnt music_list">
			<?php if ( $music_albums->have_posts() ) : ?>
				<?php while ( $music_albums->have_posts() ) : $music_albums->the_post(); ?>
					<?php
					$album = tuougm_get_album_data( get_the_ID() );
					if ( empty( $album ) || (int) $album['required_level'] > $access_level ) {
						continue;
					}
					++$displayed_album_count;

					$album_title = $is_english && ! empty( $album['title_en'] )
						? $album['title_en']
						: $album['title'];
					$album_type = $album['type'];
					if ( is_array( $album_type ) ) {
						$album_type = $album_type['label'] ?? $album_type['value'] ?? '';
					}
					$album_type = (string) $album_type;
					if ( ! $is_english && 'EP' === $album_type ) {
						$album_type = 'MINI ALBUM';
					}
					$description_field = $is_english ? 'album_description_en' : 'album_description_ja';
					$description = function_exists( 'get_field' ) ? (string) get_field( $description_field, (int) $album['id'] ) : '';
					$album_tracks = $album['tracks'];
					?>
					<section data-detail-album="<?php echo esc_attr( $album['id'] ); ?>" id="album-<?php echo esc_attr( $album['id'] ); ?>" class="music_album flex_wrap" aria-labelledby="music-album-<?php echo esc_attr( $album['id'] ); ?>">
						<figure class="music_album_artwork">
							<?php if ( $album['artwork'] ) : ?>
								<img src="<?php echo esc_url( $album['artwork'] ); ?>" alt="<?php echo esc_attr( $album_title ); ?>">
							<?php endif; ?>
						</figure>
						<div class="music_album_content">
							<p class="music_album_type eng"><?php echo esc_html( $album_type ); ?></p>
							<h2 id="music-album-<?php echo esc_attr( $album['id'] ); ?>" class="music_album_title eng"><?php echo esc_html( $album_title ); ?></h2>
							<?php if ( '' !== trim( $description ) ) : ?>
								<div class="music_album_description"><?php echo nl2br( esc_html( $description ), false ); ?></div>
							<?php endif; ?>
							<?php if ( $album_tracks ) : ?>
								<ol class="music_track_list">
									<?php foreach ( $album_tracks as $track ) : ?>
										<?php $track_title = $is_english && ! empty( $track['title_en'] ) ? $track['title_en'] : $track['title']; ?>
										<li class="music_track_item">
											<?php $track_url = tuougm_get_track_language_url( (int) $track['id'], $is_english ? 'en_US' : 'ja' ); ?>
											<a class="music_track_title" data-detail-track="<?php echo esc_attr( $track['id'] ); ?>" href="<?php echo esc_url( $track_url ); ?>"><?php echo esc_html( $track_title ); ?></a>
											<button type="button" class="tuougm-track-trigger music_track_play" data-player-track="<?php echo esc_attr( $track['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( $is_english ? 'Play %s' : '「%s」を再生', $track_title ) ); ?>"><span aria-hidden="true">▶</span></button>
										</li>
									<?php endforeach; ?>
								</ol>
							<?php endif; ?>
							<?php if ( $album_tracks ) : ?>
								<p class="btn music_album_play"><button type="button" class="all_btn" data-player-album="<?php echo esc_attr( $album['id'] ); ?>"><?php echo esc_html( $is_english ? 'PLAY ALBUM' : 'アルバムを再生' ); ?></button></p>
							<?php endif; ?>
						</div>
					</section>
				<?php endwhile; ?>
			<?php endif; ?>
			<?php if ( 0 === $displayed_album_count ) : ?>
				<p><?php echo esc_html( $is_english ? 'No albums are available.' : '公開中のアルバムはありません。' ); ?></p>
			<?php endif; ?>
			<?php wp_reset_postdata(); ?>
		</div>
	</div>
</main>
<?php get_footer(); ?>
