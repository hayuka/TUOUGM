<?php
/**
 * TUOUGM editor helper for inserting content shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'media_buttons', 'tuougm_shortcode_inserter_media_button', 21 );

function tuougm_shortcode_inserter_media_button() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	echo '<button type="button" class="button" id="tuougm-shortcode-inserter-open" style="margin-left:6px;">{} ショートコードを挿入</button>';
}

add_action( 'admin_enqueue_scripts', 'tuougm_shortcode_inserter_admin_assets' );

function tuougm_shortcode_inserter_admin_assets( $hook_suffix ) {
	if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->base, array( 'post', 'post-new' ), true ) ) {
		return;
	}

	wp_enqueue_script( 'jquery' );
	wp_register_style( 'tuougm-shortcode-inserter-admin', false, array(), '1.0.0' );
	wp_enqueue_style( 'tuougm-shortcode-inserter-admin' );
	wp_add_inline_style(
		'tuougm-shortcode-inserter-admin',
		'
		#tuougm-shortcode-modal {
			display:none; position:fixed; z-index:100101; inset:0; background:rgba(0,0,0,.55);
		}
		#tuougm-shortcode-modal .tuougm-shortcode-dialog {
			position:absolute; top:50%; left:50%; transform:translate(-50%,-50%);
			width:min(620px, calc(100% - 32px)); max-height:80vh; display:flex; flex-direction:column;
			background:#fff; box-shadow:0 8px 40px rgba(0,0,0,.3);
		}
		#tuougm-shortcode-modal .tuougm-shortcode-header {
			padding:16px 20px; border-bottom:1px solid #ddd; display:flex; align-items:center; justify-content:space-between;
		}
		#tuougm-shortcode-modal .tuougm-shortcode-header h2 { margin:0; font-size:18px; }
		#tuougm-shortcode-modal .tuougm-shortcode-body { padding:16px 20px; overflow:auto; }
		#tuougm-shortcode-modal .tuougm-shortcode-field { margin:0 0 16px; }
		#tuougm-shortcode-modal .tuougm-shortcode-field label { display:block; margin:0 0 6px; font-weight:600; }
		#tuougm-shortcode-modal .tuougm-shortcode-field select,
		#tuougm-shortcode-modal .tuougm-shortcode-field input,
		#tuougm-shortcode-modal .tuougm-shortcode-field textarea { width:100%; }
		#tuougm-shortcode-modal .tuougm-shortcode-field textarea { min-height:150px; }
		#tuougm-shortcode-modal .tuougm-shortcode-status { margin:0 0 12px; color:#646970; }
		#tuougm-shortcode-modal .tuougm-shortcode-footer {
			display:flex; justify-content:flex-end; gap:8px; padding:12px 20px; border-top:1px solid #ddd;
		}
		#tuougm-shortcode-modal .tuougm-shortcode-close {
			border:0; background:transparent; cursor:pointer; font-size:22px; line-height:1;
		}
		'
	);

	wp_register_script( 'tuougm-shortcode-inserter-admin', false, array( 'jquery' ), '1.0.0', true );
	wp_enqueue_script( 'tuougm-shortcode-inserter-admin' );
	wp_add_inline_script(
		'tuougm-shortcode-inserter-admin',
		<<<'JS'
jQuery(function($) {
	const definitions = {
		wrap: { supportsClass: true },
		sub: { supportsClass: false }
	};

	const modalHtml = `
		<div id="tuougm-shortcode-modal" role="dialog" aria-modal="true" aria-labelledby="tuougm-shortcode-modal-title">
			<div class="tuougm-shortcode-dialog">
				<div class="tuougm-shortcode-header">
					<h2 id="tuougm-shortcode-modal-title">ショートコードを挿入</h2>
					<button type="button" class="tuougm-shortcode-close" aria-label="閉じる">&times;</button>
				</div>
				<div class="tuougm-shortcode-body">
					<div class="tuougm-shortcode-field">
						<label for="tuougm-shortcode-type">ショートコード種類</label>
						<select id="tuougm-shortcode-type">
							<option value="wrap">wrap</option>
							<option value="sub">sub</option>
						</select>
					</div>
					<div class="tuougm-shortcode-field" id="tuougm-shortcode-class-field">
						<label for="tuougm-shortcode-class">追加クラス名（任意）</label>
						<input type="text" id="tuougm-shortcode-class" autocomplete="off" placeholder="hardcore-history">
					</div>
					<div class="tuougm-shortcode-field">
						<label for="tuougm-shortcode-content">コンテンツ</label>
						<textarea id="tuougm-shortcode-content" rows="7"></textarea>
					</div>
					<p class="tuougm-shortcode-status" aria-live="polite"></p>
				</div>
				<div class="tuougm-shortcode-footer">
					<button type="button" class="button tuougm-shortcode-cancel">キャンセル</button>
					<button type="button" class="button button-primary tuougm-shortcode-insert">本文に挿入</button>
				</div>
			</div>
		</div>`;

	$('body').append(modalHtml);

	const $modal = $('#tuougm-shortcode-modal');
	const $content = $('#tuougm-shortcode-content');
	const $status = $modal.find('.tuougm-shortcode-status');
	let editorState = null;
	let opener = null;

	function getVisualEditor() {
		if (!window.tinymce) return null;
		const editor = window.tinymce.get('content');
		return editor && !editor.isHidden() ? editor : null;
	}

	function textFromHtml(html) {
		const holder = document.createElement('div');
		holder.innerHTML = html;
		return holder.textContent || '';
	}

	function captureEditorState() {
		const editor = getVisualEditor();
		if (editor) {
			return {
				kind: 'tinymce',
				editor: editor,
				bookmark: editor.selection.getBookmark(2, true),
				html: editor.selection.getContent({ format: 'html' }),
				text: editor.selection.getContent({ format: 'text' })
			};
		}

		const textarea = document.getElementById('content');
		if (!textarea) return null;

		const start = typeof textarea.selectionStart === 'number' ? textarea.selectionStart : textarea.value.length;
		const end = typeof textarea.selectionEnd === 'number' ? textarea.selectionEnd : start;

		return {
			kind: 'textarea',
			textarea: textarea,
			start: start,
			end: end,
			value: textarea.value,
			text: textarea.value.substring(start, end)
		};
	}

	function updateTypeFields() {
		const type = $('#tuougm-shortcode-type').val();
		const definition = definitions[type];
		$('#tuougm-shortcode-class-field').toggle(!!(definition && definition.supportsClass));
	}

	function openModal(button) {
		opener = button;
		editorState = captureEditorState();
		$('#tuougm-shortcode-type').val('wrap');
		$('#tuougm-shortcode-class').val('');
		$content.val(editorState ? editorState.text : '');

		if (editorState && editorState.text) {
			$status.text('選択中の文章を内容欄に入れました。編集すると編集後の内容を挿入します。');
		} else {
			$status.text('文章を選択していない場合は、入力した内容をカーソル位置に挿入します。');
		}

		updateTypeFields();
		$modal.css('display', 'block');
		$('#tuougm-shortcode-type').trigger('focus');
	}

	function restoreSelection() {
		if (!editorState) return;

		if (editorState.kind === 'tinymce') {
			try {
				editorState.editor.focus();
				editorState.editor.selection.moveToBookmark(editorState.bookmark);
			} catch (error) {
				// The selection may no longer exist if the editor content changed externally.
			}
			return;
		}

		if (editorState.textarea.value === editorState.value) {
			editorState.textarea.setSelectionRange(editorState.start, editorState.end);
		}
	}

	function closeModal(restore) {
		$modal.hide();
		if (restore) restoreSelection();
		if (opener) opener.focus();
	}

	function sanitizeClasses(value) {
		const seen = new Set();
		return String(value || '')
			.trim()
			.split(/\s+/)
			.map(function(className) { return className.replace(/[^A-Za-z0-9_-]/g, ''); })
			.filter(function(className) {
				if (!className || seen.has(className)) return false;
				seen.add(className);
				return true;
			});
	}

	function buildShortcodeParts(type) {
		const definition = definitions[type];
		if (!definition) return null;

		const classes = definition.supportsClass ? sanitizeClasses($('#tuougm-shortcode-class').val()) : [];
		const attributes = classes.length ? ' class="' + classes.join(' ') + '"' : '';

		return {
			open: '[' + type + attributes + ']',
			close: '[/' + type + ']'
		};
	}

	function buildShortcode(type, content) {
		const parts = buildShortcodeParts(type);
		return parts ? parts.open + '\n' + content + '\n' + parts.close : null;
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;')
			.replace(/'/g, '&#039;');
	}

	function plainTextToHtml(value) {
		return String(value).split(/\r?\n/).map(escapeHtml).join('<br />');
	}

	function insertIntoTinyMce() {
		const state = editorState;
		const editor = state.editor;
		if (getVisualEditor() !== editor) {
			$status.text('エディターが切り替わりました。モーダルを閉じて選択し直してください。');
			return false;
		}

		let contentHtml;
		if (state.html && $content.val() === state.text) {
			contentHtml = state.html;
		} else {
			contentHtml = plainTextToHtml($content.val());
		}

		const parts = buildShortcodeParts($('#tuougm-shortcode-type').val());
		if (!parts) return false;
		const html = parts.open + contentHtml + parts.close;

		try {
			editor.undoManager.transact(function() {
				editor.selection.moveToBookmark(state.bookmark);
				editor.insertContent(html);
			});
			editor.focus();
			return true;
		} catch (error) {
			$status.text('選択範囲を復元できませんでした。もう一度選択して挿入してください。');
			return false;
		}
	}

	function insertIntoTextarea(shortcode) {
		const state = editorState;
		const textarea = state.textarea;
		if (getVisualEditor() || textarea.value !== state.value) {
			$status.text('エディターまたは本文が変更されました。モーダルを閉じて選択し直してください。');
			return false;
		}

		const start = state.start;
		const end = state.end;
		textarea.value = state.value.substring(0, start) + shortcode + state.value.substring(end);
		const cursor = start + shortcode.length;
		textarea.focus();
		textarea.setSelectionRange(cursor, cursor);
		textarea.dispatchEvent(new Event('input', { bubbles: true }));
		return true;
	}

	function insertShortcode() {
		if (!editorState) {
			$status.text('本文エディターが見つかりません。');
			return;
		}

		const type = $('#tuougm-shortcode-type').val();
		if (!definitions[type]) return;

		const inserted = editorState.kind === 'tinymce'
			? insertIntoTinyMce()
			: insertIntoTextarea(buildShortcode(type, $content.val()));

		if (inserted) closeModal(false);
	}

	$(document).on('click.tuougmShortcodeInserter', '#tuougm-shortcode-inserter-open', function(event) {
		event.preventDefault();
		openModal(this);
	});

	$modal.on('change', '#tuougm-shortcode-type', updateTypeFields);
	$modal.on('click', '.tuougm-shortcode-close, .tuougm-shortcode-cancel', function() {
		closeModal(true);
	});
	$modal.on('click', function(event) {
		if (event.target === this) closeModal(true);
	});
	$modal.on('click', '.tuougm-shortcode-insert', insertShortcode);
	$(document).on('keydown.tuougmShortcodeInserter', function(event) {
		if (event.key === 'Escape' && $modal.is(':visible')) closeModal(true);
	});
});
JS
	);
}
