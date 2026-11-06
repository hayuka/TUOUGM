<?php
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
$is_english = 'en_US' === $locale;
$footer_page_url = static function ( $path ) use ( $is_english ) {
	$url = home_url( $path );
	if ( '/story/' === $path ) {
		$url = function_exists( 'bogo_url' ) ? bogo_url( $url, 'ja' ) : $url;
		return $is_english ? add_query_arg( 'loc', 'en_US', $url ) : $url;
	}
	return function_exists( 'bogo_url' )
		? bogo_url( $url, $is_english ? 'en_US' : 'ja' )
		: $url;
};
$members_url = '';
$japanese_members_page = function_exists( 'bogo_get_page_by_path' )
	? bogo_get_page_by_path( 'members', 'ja', 'page' )
	: get_page_by_path( 'members' );
if ( $japanese_members_page && 'publish' === get_post_status( $japanese_members_page ) ) {
	$members_page = $japanese_members_page;
	if ( $is_english ) {
		$members_page = function_exists( 'bogo_get_post_translation' )
			? bogo_get_post_translation( $japanese_members_page->ID, 'en_US' )
			: null;
	}
	if ( $members_page && 'publish' === get_post_status( $members_page ) ) {
		$members_url = get_permalink( $members_page );
	}
}
$privacy_url = get_privacy_policy_url();
$footer_language_links = function_exists( 'bogo_language_switcher_links' ) ? bogo_language_switcher_links() : array();
$uses_story_locale_query = is_post_type_archive( 'story' ) || is_tax( array( 'story_category', 'story_type' ) );
$story_locale_url = '';
if ( $uses_story_locale_query ) {
	$story_view_url = is_post_type_archive( 'story' )
		? get_post_type_archive_link( 'story' )
		: get_term_link( get_queried_object() );
	if ( ! is_wp_error( $story_view_url ) ) {
		$story_locale_url = function_exists( 'bogo_url' )
			? bogo_url( $story_view_url, 'ja' )
			: $story_view_url;
	}
}
?>
<footer id="swup-footer" class="ft">
	<div class="inner_1200 ft_info">
		<div class="ft_brand_header">
			<a class="ft_brand_link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/img/newlogo.png' ); ?>" alt="" width="42" height="46">
				<span>TUOUGM</span>
			</a>
		</div>
		<div class="ft_grid">
			<p class="ft_brand_copy">Born from Hardcore.<br>Built on Gunpla.<br>Driven by Noise.</p>

			<nav class="ft_nav" aria-label="Explore">
				<h2>EXPLORE</h2>
				<ul>
					<li><a href="<?php echo esc_url( $footer_page_url( '/music/' ) ); ?>">MUSIC</a></li>
					<li><a href="<?php echo esc_url( $footer_page_url( '/story/' ) ); ?>">STORY</a></li>
					<li><a href="<?php echo esc_url( $footer_page_url( '/archives/' ) ); ?>">ARCHIVES</a></li>
					<li>
						<?php if ( $members_url ) : ?>
							<a href="<?php echo esc_url( $members_url ); ?>">MEMBERS</a>
						<?php else : ?>
							<span class="ft_nav_unavailable" aria-disabled="true">MEMBERS</span>
						<?php endif; ?>
					</li>
				</ul>
			</nav>

			<section class="ft_social" aria-labelledby="ft-social-title">
				<h2 id="ft-social-title">FOLLOW THE NOISE</h2>
				<ul>
					<li><a href="https://www.instagram.com/zakubaka/" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-instagram" aria-hidden="true"></i><span>Instagram</span></a></li>
					<li><span class="ft_social_unavailable"><i class="fa-brands fa-facebook-f" aria-hidden="true"></i><span>Facebook</span></span></li>
					<li><a href="https://www.youtube.com/@zakubakamodeling" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-youtube" aria-hidden="true"></i><span>YouTube</span></a></li>
					<li><a href="https://www.tiktok.com/@zakubaka2" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-tiktok" aria-hidden="true"></i><span>TikTok</span></a></li>
				</ul>
			</section>
		</div>
	</div>

	<div class="ft_bottom">
		<div class="inner_1200 ft_bottom_inner">
			<p class="ft_copy">
				<?php if ( $is_english ) : ?>
					©︎ <?php echo esc_html( wp_date( 'Y' ) ); ?> Gunpla Research Institute GUNPLA MODELING
				<?php else : ?>
					©︎ <?php echo esc_html( wp_date( 'Y' ) ); ?> ガンプラ技術研究所
				<?php endif; ?>
			</p>
			<div class="ft_bottom_links">
				<?php if ( $privacy_url ) : ?>
					<a class="ft_privacy" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo $is_english ? 'Privacy Policy' : 'プライバシーポリシー'; ?></a>
				<?php endif; ?>
				<?php if ( $footer_language_links ) : ?>
					<ul class="bogo-language-switcher list-view">
						<?php foreach ( $footer_language_links as $language_link ) : ?>
							<?php
							$language_locale = $language_link['locale'];
							$language_label  = 'ja' === $language_locale ? 'JP' : 'EN';
							$is_current      = $locale === $language_locale;
							$language_url    = isset( $language_link['href'] ) ? $language_link['href'] : '';
							if ( $uses_story_locale_query && $story_locale_url ) {
								$language_url = 'en_US' === $language_locale
									? add_query_arg( 'loc', 'en_US', $story_locale_url )
									: $story_locale_url;
							}
							$language_class  = array_filter( array(
								function_exists( 'bogo_language_tag' ) ? bogo_language_tag( $language_locale ) : '',
								function_exists( 'bogo_lang_slug' ) ? bogo_lang_slug( $language_locale ) : '',
								$is_current ? 'current' : ( $language_url ? '' : 'unavailable' ),
							) );
							?>
							<li class="<?php echo esc_attr( implode( ' ', $language_class ) ); ?>">
								<span class="bogo-language-name">
									<?php if ( $is_current ) : ?>
										<span class="current" aria-current="page"><?php echo esc_html( $language_label ); ?></span>
									<?php elseif ( $language_url ) : ?>
										<a rel="alternate" hreflang="<?php echo esc_attr( $language_link['lang'] ); ?>" href="<?php echo esc_url( $language_url ); ?>" title="<?php echo esc_attr( $language_label ); ?>"><?php echo esc_html( $language_label ); ?></a>
									<?php else : ?>
										<span class="ft_language_unavailable" aria-disabled="true" title="<?php echo esc_attr( $language_label ); ?>"><?php echo esc_html( $language_label ); ?></span>
									<?php endif; ?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	</div>
</footer>

<div class="player">
<div class="menu_toggle"></div>
<div class="player_menu">
<h2>MUSIC LIBRARY</h2>
<div class="player_menu_inner flex_wrap">

<div class="wrap_album">
<div class="player_album_info flex_wrap">
<div class="player_album_artwork"><img src="<?php echo get_template_directory_uri(); ?>/img/sample_skin.webp" alt=""></div>
<div class="player_album_title"></div>
</div>

<div class="player_album">
<button type="button" class="player_album_toggle" aria-expanded="false" aria-controls="player-album-options">
<span class="player_album_toggle_label">Select album</span>
<span class="player_album_toggle_icon" aria-hidden="true"></span>
</button>
<div class="player_album_options" id="player-album-options" role="radiogroup">
<!-- アルバム選択肢は audio.js で生成 -->
</div>
</div>
</div>

<div class="track_wrap">
<ul class="player_track_list"></ul>
<div class="player_lyrics">
<div class="player_lyrics_content"></div>
</div>
</div>
</div>
</div>

<div class="wrap_plyer flex_wrap">
<div class="inner_player flex_wrap_middle">

<div class="player_lr flex_wrap_middle player_l">
<div class="play_skin"><img src="<?php echo get_template_directory_uri(); ?>/img/sample_skin.webp" alt=""></div>
<div class="player_title"><span>No track selected</span></div>
</div>

<div class="flex_wrap_middle plyer_control_wrap">
<div class="player_seek">
<div class="seek_bar"><div class="seek_progress"></div></div>
</div>
<p class="time_current">0:00</p>
<p class="time_duration">0:00</p>
<div class="plyer_control flex_wrap_center flex_wrap_middle">
<div class="player_send pre"></div>
<div class="player_play"></div>
<div class="player_send next"></div>
</div>
</div>
<div class="player_reset"></div>
</div>
</div>
</div><!--/player-->

<?php wp_footer(); ?>
</body>
</html>
