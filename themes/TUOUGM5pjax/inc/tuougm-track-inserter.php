<?php
/**
 * TUOUGM Track Inserter
 *
 * functions.php から読み込む:
 *
 * require_once get_template_directory() . '/inc/tuougm-track-inserter.php';
 *
 * 前提:
 * - 曲のカスタム投稿タイプ: music_track
 * - アルバムのカスタム投稿タイプ: music_album
 * - 曲→アルバムのACFフィールド: track_album
 *
 * 本文には [tuougm_track id="123"] を挿入し、
 * フロントでは .tuougm-track-trigger[data-player-track="123"] を出力します。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理画面: 「メディアを追加」の横に「♪ 曲を挿入」を追加。
 */
add_action( 'media_buttons', 'tuougm_track_inserter_media_button', 20 );

function tuougm_track_inserter_media_button() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	echo '<button type="button" class="button" id="tuougm-track-inserter-open" style="margin-left:6px;">♪ 曲を挿入</button>';
}

/**
 * 管理画面のCSS / JS。
 */
add_action( 'admin_enqueue_scripts', 'tuougm_track_inserter_admin_assets' );

function tuougm_track_inserter_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
		return;
	}

	wp_enqueue_script( 'jquery' );

	wp_register_style( 'tuougm-track-inserter-admin', false, array(), '1.0.0' );
	wp_enqueue_style( 'tuougm-track-inserter-admin' );

	wp_add_inline_style(
		'tuougm-track-inserter-admin',
		'
		#tuougm-track-modal {
			display:none;
			position:fixed;
			z-index:100100;
			inset:0;
			background:rgba(0,0,0,.55);
		}
		#tuougm-track-modal .tuougm-track-dialog {
			position:absolute;
			top:50%;
			left:50%;
			transform:translate(-50%,-50%);
			width:min(620px, calc(100% - 32px));
			max-height:80vh;
			display:flex;
			flex-direction:column;
			background:#fff;
			box-shadow:0 8px 40px rgba(0,0,0,.3);
		}
		#tuougm-track-modal .tuougm-track-header {
			padding:16px 20px;
			border-bottom:1px solid #ddd;
			display:flex;
			align-items:center;
			justify-content:space-between;
		}
		#tuougm-track-modal .tuougm-track-header h2 {
			margin:0;
			font-size:18px;
		}
		#tuougm-track-modal .tuougm-track-body {
			padding:16px 20px;
			overflow:auto;
		}
		#tuougm-track-modal .tuougm-track-filters {
			display:flex;
			gap:8px;
			margin-bottom:12px;
		}
		#tuougm-track-modal .tuougm-track-filters input,
		#tuougm-track-modal .tuougm-track-filters select {
			min-width:0;
			width:50%;
		}
		#tuougm-track-modal .tuougm-track-list {
			border:1px solid #ddd;
			max-height:42vh;
			overflow:auto;
		}
		#tuougm-track-modal .tuougm-track-item {
			display:flex;
			gap:10px;
			align-items:flex-start;
			padding:10px 12px;
			border-bottom:1px solid #eee;
			cursor:pointer;
		}
		#tuougm-track-modal .tuougm-track-item:last-child {
			border-bottom:0;
		}
		#tuougm-track-modal .tuougm-track-item:hover {
			background:#f6f7f7;
		}
		#tuougm-track-modal .tuougm-track-item input {
			margin-top:3px;
		}
		#tuougm-track-modal .tuougm-track-meta {
			display:block;
			color:#646970;
			font-size:12px;
			margin-top:3px;
		}
		#tuougm-track-modal .tuougm-track-footer {
			display:flex;
			justify-content:flex-end;
			gap:8px;
			padding:12px 20px;
			border-top:1px solid #ddd;
		}
		#tuougm-track-modal .tuougm-track-status {
			margin:0 0 10px;
			color:#646970;
		}
		#tuougm-track-modal .tuougm-track-close {
			border:0;
			background:transparent;
			cursor:pointer;
			font-size:22px;
			line-height:1;
		}
		'
	);

	$config = array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'tuougm_track_inserter' ),
	);

	wp_register_script( 'tuougm-track-inserter-admin', false, array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'tuougm-track-inserter-admin' );

	wp_add_inline_script(
		'tuougm-track-inserter-admin',
		'window.TUOUGMTrackInserter = ' . wp_json_encode( $config ) . ';',
		'before'
	);

	wp_add_inline_script(
		'tuougm-track-inserter-admin',
		<<<'JS'
jQuery(function($) {
	const cfg = window.TUOUGMTrackInserter;
	if (!cfg) return;

	const modalHtml = `
		<div id="tuougm-track-modal" role="dialog" aria-modal="true" aria-labelledby="tuougm-track-modal-title">
			<div class="tuougm-track-dialog">
				<div class="tuougm-track-header">
					<h2 id="tuougm-track-modal-title">投稿に曲を挿入</h2>
					<button type="button" class="tuougm-track-close" aria-label="閉じる">&times;</button>
				</div>
				<div class="tuougm-track-body">
					<div class="tuougm-track-filters">
						<input type="search" id="tuougm-track-search" placeholder="曲名で検索">
						<select id="tuougm-track-album">
							<option value="">すべてのアルバム</option>
						</select>
					</div>
					<p class="tuougm-track-status" aria-live="polite">曲を読み込み中…</p>
					<div class="tuougm-track-list"></div>
				</div>
				<div class="tuougm-track-footer">
					<button type="button" class="button tuougm-track-cancel">キャンセル</button>
					<button type="button" class="button button-primary tuougm-track-insert">本文に挿入</button>
				</div>
			</div>
		</div>`;

	$('body').append(modalHtml);

	const $modal = $('#tuougm-track-modal');
	const $list = $modal.find('.tuougm-track-list');
	const $status = $modal.find('.tuougm-track-status');

	let tracks = [];
	let selectedId = null;
	let loaded = false;
	let searchTimer = null;

	function closeModal() {
		$modal.hide();
		$('#tuougm-track-inserter-open').trigger('focus');
	}

	function renderTracks() {
		const keyword = ($('#tuougm-track-search').val() || '').toLowerCase().trim();
		const albumId = $('#tuougm-track-album').val();

		const filtered = tracks.filter(function(track) {
			const matchesKeyword =
				!keyword ||
				track.title.toLowerCase().includes(keyword) ||
				track.album.toLowerCase().includes(keyword);

			const matchesAlbum =
				!albumId ||
				String(track.album_id) === String(albumId);

			return matchesKeyword && matchesAlbum;
		});

		$list.empty();

		if (!filtered.length) {
			$status.text('曲が見つかりません。').show();
			return;
		}

		$status.hide();

		filtered.forEach(function(track) {
			const $label = $('<label class="tuougm-track-item"></label>');
			const $radio = $('<input type="radio" name="tuougm-track-choice">').val(track.id);

			if (String(track.id) === String(selectedId)) {
				$radio.prop('checked', true);
			}

			const $copy = $('<span></span>');

			$('<strong></strong>')
				.text(track.title)
				.appendTo($copy);

			$('<span class="tuougm-track-meta"></span>')
				.text(track.album || 'アルバム未設定')
				.appendTo($copy);

			$label.append($radio, $copy);
			$list.append($label);
		});
	}

	function loadTracks() {
		if (loaded) {
			renderTracks();
			return;
		}

		$status.text('曲を読み込み中…').show();
		$list.empty();

		$.post(cfg.ajaxUrl, {
			action: 'tuougm_track_inserter_list',
			nonce: cfg.nonce
		})
		.done(function(response) {
			if (!response || !response.success || !response.data) {
				$status.text('曲の取得に失敗しました。');
				return;
			}

			tracks = response.data.tracks || [];

			const $album = $('#tuougm-track-album');
			$album.find('option:not(:first)').remove();

			(response.data.albums || []).forEach(function(album) {
				$album.append(
					$('<option></option>')
						.val(album.id)
						.text(album.title)
				);
			});

			loaded = true;
			renderTracks();
		})
		.fail(function() {
			$status.text('曲の取得に失敗しました。');
		});
	}

	function insertShortcode() {
		const $checked = $modal.find('input[name="tuougm-track-choice"]:checked');

		if (!$checked.length) {
			$status.text('挿入する曲を選択してください。').show();
			return;
		}

		const id = parseInt($checked.val(), 10);

		if (!Number.isInteger(id) || id < 1) return;

		const shortcode = '[tuougm_track id="' + id + '"]';
		let inserted = false;

		// クラシックエディタ / TinyMCE。
		if (window.tinymce) {
			const editor = window.tinymce.get('content');

			if (editor && !editor.isHidden()) {
				editor.execCommand('mceInsertContent', false, shortcode);
				inserted = true;
			}
		}

		// テキストエディタ。
		if (!inserted) {
			const textarea = document.getElementById('content');

			if (textarea) {
				const start = textarea.selectionStart || 0;
				const end = textarea.selectionEnd || 0;

				textarea.value =
					textarea.value.substring(0, start) +
					shortcode +
					textarea.value.substring(end);

				textarea.focus();

				const cursor = start + shortcode.length;
				textarea.setSelectionRange(cursor, cursor);

				textarea.dispatchEvent(new Event('input', {
					bubbles: true
				}));

				inserted = true;
			}
		}

		if (inserted) {
			closeModal();
		}
	}

	$(document).on('click', '#tuougm-track-inserter-open', function(e) {
		e.preventDefault();
		$modal.css('display', 'block');
		loadTracks();
	});

	$modal.on(
		'click',
		'.tuougm-track-close, .tuougm-track-cancel',
		closeModal
	);

	$modal.on('click', function(e) {
		if (e.target === this) {
			closeModal();
		}
	});

	$(document).on('keydown', function(e) {
		if (e.key === 'Escape' && $modal.is(':visible')) {
			closeModal();
		}
	});

	$modal.on(
		'change',
		'input[name="tuougm-track-choice"]',
		function() {
			selectedId = $(this).val();
		}
	);

	$modal.on('input', '#tuougm-track-search', function() {
		clearTimeout(searchTimer);
		searchTimer = setTimeout(renderTracks, 120);
	});

	$modal.on('change', '#tuougm-track-album', renderTracks);

	$modal.on('click', '.tuougm-track-insert', insertShortcode);
});
JS
	);
}

/**
 * AJAX: 登録済み music_track 一覧。
 */
add_action( 'wp_ajax_tuougm_track_inserter_list', 'tuougm_track_inserter_ajax_list' );

function tuougm_track_inserter_ajax_list() {
	check_ajax_referer( 'tuougm_track_inserter', 'nonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error(
			array( 'message' => '権限がありません。' ),
			403
		);
	}

	if ( ! post_type_exists( 'music_track' ) ) {
		wp_send_json_error(
			array( 'message' => 'music_track 投稿タイプが見つかりません。' ),
			400
		);
	}

	$track_posts = get_posts(
		array(
			'post_type'        => 'music_track',
			'post_status'      => array( 'publish', 'private', 'draft' ),
			'posts_per_page'   => -1,
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => false,
		)
	);

	$tracks = array();
	$albums = array();

	/*
	 * TUOUGMではアルバム→曲のRelationshipが正。
	 * ACFフィールド album_tracks に曲IDが登録されているので、
	 * ここから「曲ID → アルバムID」を逆引きする。
	 */
	$track_album_map = array();

	if ( post_type_exists( 'music_album' ) ) {
		$album_posts = get_posts(
			array(
				'post_type'        => 'music_album',
				'post_status'      => array( 'publish', 'private', 'draft' ),
				'posts_per_page'   => -1,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			)
		);

		foreach ( $album_posts as $album_post ) {
			$album_id    = (int) $album_post->ID;
			$album_title = html_entity_decode(
				get_the_title( $album_post ),
				ENT_QUOTES,
				get_bloginfo( 'charset' ) ?: 'UTF-8'
			);

			$albums[] = array(
				'id'    => $album_id,
				'title' => $album_title,
			);

			$album_tracks = function_exists( 'get_field' )
				? get_field( 'album_tracks', $album_id )
				: get_post_meta( $album_id, 'album_tracks', true );

			if ( ! is_array( $album_tracks ) ) {
				$album_tracks = ! empty( $album_tracks ) ? array( $album_tracks ) : array();
			}

			foreach ( $album_tracks as $album_track ) {
				$track_id = 0;

				if ( is_object( $album_track ) && isset( $album_track->ID ) ) {
					$track_id = (int) $album_track->ID;
				} elseif ( is_numeric( $album_track ) ) {
					$track_id = (int) $album_track;
				} elseif ( is_array( $album_track ) && isset( $album_track['ID'] ) ) {
					$track_id = (int) $album_track['ID'];
				}

				if ( $track_id > 0 ) {
					$track_album_map[ $track_id ] = $album_id;
				}
			}
		}
	}

	foreach ( $track_posts as $track ) {
		$track_id = (int) $track->ID;
		$album_id = isset( $track_album_map[ $track_id ] )
			? (int) $track_album_map[ $track_id ]
			: 0;

		$album_title = '';

		if ( $album_id ) {
			$album_title = get_the_title( $album_id );
		}

		$tracks[] = array(
			'id'       => $track_id,
			'title'    => html_entity_decode(
				get_the_title( $track ),
				ENT_QUOTES,
				get_bloginfo( 'charset' ) ?: 'UTF-8'
			),
			'album_id' => $album_id,
			'album'    => html_entity_decode(
				(string) $album_title,
				ENT_QUOTES,
				get_bloginfo( 'charset' ) ?: 'UTF-8'
			),
		);
	}

	wp_send_json_success(
		array(
			'tracks' => $tracks,
			'albums' => $albums,
		)
	);
}

/**
 * ショートコード:
 *
 * [tuougm_track id="123"]
 *
 * 表示例:
 * <button class="tuougm-track-trigger" data-player-track="123">
 *     ▶ 曲名
 * </button>
 */
add_shortcode( 'tuougm_track', 'tuougm_track_inserter_shortcode' );

function tuougm_track_inserter_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'    => 0,
			'label' => '',
		),
		$atts,
		'tuougm_track'
	);

	$track_id = absint( $atts['id'] );

	if ( ! $track_id || 'music_track' !== get_post_type( $track_id ) ) {
		return '';
	}

	$track = get_post( $track_id );

	if ( ! $track || 'publish' !== $track->post_status ) {
		return '';
	}

	$title = trim( (string) $atts['label'] );

	if ( '' === $title ) {
		$title = get_the_title( $track_id );
	}

	return sprintf(
		'<p class="mbtn_wrap"><button type="button" class="tuougm-track-trigger" data-player-track="%1$d" aria-label="%2$sを再生"><span class="tuougm-track-trigger__icon" aria-hidden="true">▶</span><span class="tuougm-track-trigger__title">%3$s</span></button></p>',
		$track_id,
		esc_attr( $title ),
		esc_html( $title )
	);
}
