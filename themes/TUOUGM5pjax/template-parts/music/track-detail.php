<?php
/** Shared track content; explicit presentation data, no main-query dependency. */
$track = $args['track'];
$track_album = $args['album'];
$track_title = $args['title'];
$album_title = $args['album_title'];
$track_description = $args['description'];
$is_english = $args['is_english'];
$title_id = $args['title_id'] ?? 'music-track-title';
$about_id = $args['about_id'] ?? 'music-track-about-title';
?>
			<section class="music_album flex_wrap music_track_top" aria-labelledby="<?php echo esc_attr( $title_id ); ?>">
				<figure class="music_album_artwork music_track_jacket">
					<?php if ( $track_album['artwork'] ) : ?>
						<img src="<?php echo esc_url( $track_album['artwork'] ); ?>" alt="<?php echo esc_attr( $album_title ); ?>">
					<?php endif; ?>
				</figure>
				<div class="music_album_content music_track_info">
					<p class="music_track_from_label eng">FROM ALBUM</p>
					<?php
					$music_url = trailingslashit( get_option( 'home' ) ) . 'music/';
					if ( $is_english ) {
						$music_url = add_query_arg( 'loc', 'en_US', $music_url );
					}
					$album_anchor_url = $music_url . '#album-' . absint( $track_album['id'] );
					?>
					<p class="music_track_album_title eng"><a href="<?php echo esc_url( $album_anchor_url ); ?>"><?php echo esc_html( $album_title ); ?></a></p>
					<h1 id="<?php echo esc_attr( $title_id ); ?>" class="music_album_title music_track_heading eng"><?php echo esc_html( $track_title ); ?></h1>
					<button type="button" class="tuougm-track-trigger music_track_detail_play" data-player-track="<?php echo esc_attr( $track['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf( $is_english ? 'Play %s' : '「%s」を再生', $track_title ) ); ?>">
						<span aria-hidden="true">▶</span><?php echo esc_html( $is_english ? 'PLAY TRACK' : '曲を再生' ); ?>
					</button>
				</div>
			</section>

			<section class="music_track_about" aria-labelledby="<?php echo esc_attr( $about_id ); ?>">
				<h2 id="<?php echo esc_attr( $about_id ); ?>" class="music_track_about_title eng">ABOUT THIS TRACK</h2>
				<?php if ( '' !== trim( $track_description ) ) : ?>
					<div class="music_track_description"><?php echo wp_kses_post( wpautop( $track_description ) ); ?></div>
			<?php endif; ?>
			</section>
