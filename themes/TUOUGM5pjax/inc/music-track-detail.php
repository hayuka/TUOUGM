<?php
/** Shared presentation and access rules for standalone and modal track details. */
function tuougm_music_track_detail( $track_id, $album_id = 0, $locale = 'ja' ) {
    $missing = new WP_Error( 'tuougm_track_unavailable', 'Track unavailable.', array( 'status' => 404 ) );
    $track = tuougm_get_track_data( $track_id );
    if ( ! $track ) {
        return $missing;
    }
    $level = tuougm_player_access_level();
    if ( $album_id ) {
        $ids = array( (int) $album_id );
    } else {
        // Preserve the standalone page's original query filters and album order.
        $query = new WP_Query( array(
            'post_type' => 'music_album', 'post_status' => 'publish', 'has_password' => false,
            'posts_per_page' => -1, 'fields' => 'ids', 'ignore_sticky_posts' => true, 'no_found_rows' => true,
            'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'DESC' ),
        ) );
        $ids = $query->posts;
    }
    foreach ( $ids as $id ) {
        $post = get_post( $id );
        if ( ! $post || 'music_album' !== $post->post_type || 'publish' !== $post->post_status || '' !== $post->post_password
            || (int) get_field( 'required_level', $id ) > $level ) {
            continue;
        }
        $related = get_field( 'album_tracks', $id );
        if ( ! is_array( $related ) ) {
            continue;
        }
        $related = array_map( static function ( $item ) { return is_object( $item ) ? (int) $item->ID : (int) $item; }, $related );
        if ( ! in_array( (int) $track_id, $related, true ) ) {
            continue;
        }
        $english = 'en_US' === $locale;
        $album = array( 'id' => (string) $id, 'title' => get_the_title( $id ),
            'title_en' => get_field( 'album_title_en', $id ), 'artwork' => get_the_post_thumbnail_url( $id, 'full' ) );
        return array(
            'track' => $track, 'album' => $album, 'is_english' => $english,
            'title' => $english && ! empty( $track['title_en'] ) ? $track['title_en'] : $track['title'],
            'album_title' => $english && ! empty( $album['title_en'] ) ? $album['title_en'] : $album['title'],
            'description' => (string) get_field( $english ? 'track_description_en' : 'track_description_ja', $track_id ),
        );
    }
    return $missing;
}

add_action( 'rest_api_init', static function () {
    register_rest_route( 'tuougm/v1', '/track-detail', array(
        'methods' => WP_REST_Server::READABLE,
        // Public tracks are anonymous-readable; the callback enforces album access.
        'permission_callback' => '__return_true',
        'args' => array(
            'track_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
            'album_id' => array( 'required' => true, 'type' => 'integer', 'minimum' => 1 ),
            'locale' => array( 'required' => true, 'type' => 'string', 'enum' => array( 'ja', 'en_US' ) ),
        ),
        'callback' => static function ( WP_REST_Request $request ) {
            $detail = tuougm_music_track_detail( $request['track_id'], $request['album_id'], $request['locale'] );
            if ( is_wp_error( $detail ) ) {
                $response = new WP_REST_Response( array( 'code' => $detail->get_error_code(), 'message' => $detail->get_error_message() ), 404 );
            } else {
                $detail['title_id'] = 'music-modal-track-title';
                $detail['about_id'] = 'music-modal-track-about-title';
                ob_start();
                get_template_part( 'template-parts/music/track-detail', null, $detail );
                $html = ob_get_clean();
                $response = new WP_REST_Response( array(
                    'track_id' => (int) $request['track_id'], 'album_id' => (int) $request['album_id'],
                    'title' => $detail['title'], 'html' => $html,
                    'url' => tuougm_get_track_language_url( $request['track_id'], $request['locale'] ),
                ) );
            }
            $response->header( 'Cache-Control', 'private, no-store, max-age=0' );
            return $response;
        },
    ) );
});
