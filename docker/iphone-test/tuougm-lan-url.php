<?php
/**
 * Temporary request-time URL adaptation for explicitly enabled iPhone LAN testing.
 * This file is bind-mounted only by docker-compose.iphone.yml and never changes DB data.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tuougm_lan_test_url = rtrim( (string) getenv( 'TUOUGM_LAN_TEST_URL' ), '/' );
$tuougm_local_base_url = rtrim( (string) getenv( 'TUOUGM_LOCAL_BASE_URL' ), '/' );

if ( '' === $tuougm_lan_test_url || '' === $tuougm_local_base_url ) {
	return;
}

$tuougm_lan_test_host = strtolower( (string) wp_parse_url( $tuougm_lan_test_url, PHP_URL_HOST ) );
$tuougm_lan_test_port = (int) wp_parse_url( $tuougm_lan_test_url, PHP_URL_PORT );
$tuougm_request_host  = strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) );
$tuougm_expected_host = $tuougm_lan_test_host . ( $tuougm_lan_test_port ? ':' . $tuougm_lan_test_port : '' );

if ( '' === $tuougm_lan_test_host || $tuougm_request_host !== $tuougm_expected_host ) {
	return;
}

/** Replace only the known local base URL, leaving production and unrelated hosts intact. */
function tuougm_iphone_test_rewrite_url( $value ) {
	$local_base = rtrim( (string) getenv( 'TUOUGM_LOCAL_BASE_URL' ), '/' );
	$lan_base   = rtrim( (string) getenv( 'TUOUGM_LAN_TEST_URL' ), '/' );
	return is_string( $value ) && '' !== $local_base && '' !== $lan_base
		? str_replace( $local_base, $lan_base, $value )
		: $value;
}

/** Add a LAN-only cache key so browsers don't reuse the earlier localhost CSS. */
function tuougm_iphone_test_rewrite_html( $html ) {
	$html = tuougm_iphone_test_rewrite_url( $html );
	return preg_replace_callback(
		'~(<link\b[^>]*\bhref=["\'])([^"\']*/wp-content/cache/autoptimize/css/[^"\']+\.css)(\?[^"\']*)?(["\'])~i',
		static function( $matches ) {
			$query = $matches[3] ?? '';
			$join  = '' === $query ? '?' : '&';
			return $matches[1] . $matches[2] . $query . $join . 'tuougm_lan_css=1' . $matches[4];
		},
		$html
	);
}

add_filter( 'pre_option_home', function( $pre_option ) use ( $tuougm_lan_test_url ) {
	return $tuougm_lan_test_url;
} );
add_filter( 'pre_option_siteurl', function( $pre_option ) use ( $tuougm_lan_test_url ) {
	return $tuougm_lan_test_url;
} );
add_filter( 'wp_get_attachment_url', 'tuougm_iphone_test_rewrite_url' );
add_filter( 'the_content', 'tuougm_iphone_test_rewrite_url', 99 );

/**
 * Rewrite stale absolute URLs in frontend HTML (for example cached Autoptimize
 * asset URLs) without touching REST responses, audio streams, or database data.
 */
add_action( 'template_redirect', function() {
	if ( is_admin() || is_feed() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}

	ob_start( 'tuougm_iphone_test_rewrite_html' );
}, 0 );

add_filter( 'rest_post_dispatch', function( $response, $server, $request ) {
	if ( ! $response instanceof WP_REST_Response || ! preg_match( '#^/tuougm/v1/(player|track-detail)$#', $request->get_route() ) ) {
		return $response;
	}

	$rewrite = static function( $value ) use ( &$rewrite ) {
		if ( is_string( $value ) ) {
			return tuougm_iphone_test_rewrite_url( $value );
		}
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $item ) {
				$value[ $key ] = $rewrite( $item );
			}
		}
		return $value;
	};

	$response->set_data( $rewrite( $response->get_data() ) );
	return $response;
}, 10, 3 );
