(function ($) {
/* ------------------------------------------------------------------

 accordion.js

------------------------------------------------------------------ */
$(function() {
	$('.player_album_options').hide();

	// プレイヤーメニュー
	$('.menu_toggle').on('click', function() {
		const $toggle = $(this);
		const $menu = $toggle.next('.player_menu');
		$toggle.toggleClass('is-active');
		$menu.stop(true, true).slideToggle(300);
	});

	// プレイヤー内アルバム選択
	// audio.js で動的に生成されるため、イベントは委譲する。
	$(document).on('click.tuougmAlbumAccordion', '.player_album_toggle', function() {
		const $toggle = $(this);
		const $options = $toggle.next('.player_album_options');
		const isOpen = $toggle.hasClass('is-active');

		// 同じプレイヤー内の他のアルバム選択を閉じる
		$('.player_album_toggle').not($toggle)
			.removeClass('is-active')
			.attr('aria-expanded', 'false')
			.next('.player_album_options').stop(true, true).slideUp(200);

		$toggle.toggleClass('is-active', !isOpen);
		$toggle.attr('aria-expanded', !isOpen ? 'true' : 'false');
		$options.stop(true, true).slideToggle(200);
	});

	// ラジオ選択後はパネルを閉じる
	$(document).on('change.tuougmAlbumAccordion', '.player_album_options input[type="radio"]', function() {
		const $options = $(this).closest('.player_album_options');
		const $toggle = $options.prev('.player_album_toggle');
		$toggle.removeClass('is-active').attr('aria-expanded', 'false');
		$options.stop(true, true).slideUp(200);
	});
});

})(jQuery);
