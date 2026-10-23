<?php
/**
 * TUOUGM Music Data
 *
 * Album / Track CPT のデータを取得・整形する
 */


/**
 * Albumデータを取得
 *
 * @param int $album_id Album post ID
 * @return array
 */
function tuougm_get_album_data($album_id) {

    $album_id = (int) $album_id;

    if (!$album_id || get_post_type($album_id) !== 'music_album') {
    return [];
}

    $album = [
        'id'             => (string) $album_id,
        'title'          => get_the_title($album_id),
        'title_en'       => get_field('album_title_en', $album_id),
        'type'           => get_field('album_type', $album_id),
        'artwork'        => get_the_post_thumbnail_url($album_id, 'full'),
        'release_date'   => get_the_date('Y-m-d', $album_id),
        'description'    => apply_filters(
            'the_content',
            get_post_field('post_content', $album_id)
        ),
        'required_level' => (int) get_field('required_level', $album_id),
        'tracks'         => [],
    ];

    // Albumに紐付けられたTrack
    $track_ids = get_field('album_tracks', $album_id);

    if (!empty($track_ids) && is_array($track_ids)) {

        foreach ($track_ids as $track_id) {

            // RelationshipがWP_Postを返す場合に対応
            if (is_object($track_id)) {
                $track_id = $track_id->ID;
            }

            $track = tuougm_get_track_data($track_id);

            if (!empty($track)) {
                $album['tracks'][] = $track;
            }
        }
    }

    return $album;
}


/**
 * Trackデータを取得
 *
 * @param int $track_id Track post ID
 * @return array
 */
function tuougm_get_track_data($track_id) {

    $track_id = (int) $track_id;

    if (!$track_id || get_post_type($track_id) !== 'music_track') {
    return [];
}

    // Audio
    $audio = get_field('track_audio', $track_id);

    /*
     * ACF File field の Return Format が
     * ID / URL / Array のどれでも動くように対応
     */
    if (is_array($audio)) {

        $audio_url = $audio['url'] ?? '';

    } elseif (is_numeric($audio)) {

        $audio_url = wp_get_attachment_url($audio);

    } else {

        $audio_url = $audio;
    }

    $track = [
    'id'           => (string) $track_id,
    'title'        => get_the_title($track_id),
    'title_en'     => get_field('track_title_en', $track_id),
    'type'         => get_field('track_type', $track_id),
    'src'          => $audio_url,
    'lyrics' => str_replace(
		["\xc2\xa0", '&nbsp;'],
		'',
		wp_strip_all_tags(
			str_replace(
				['</p>', '<br>', '<br />', '<br/>'],
				["\n", "\n", "\n", "\n"],
				get_post_field('post_content', $track_id)
			)
		)
	),
    'external_url' => get_field('track_external_url', $track_id),
];

    return $track;
}