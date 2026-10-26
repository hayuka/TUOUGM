/* ------------------------------------------------------------------

 TUOUGM PJAX / Swup

 The global audio player lives outside the Swup containers, so it
 survives page navigation. Header + main are replaced by Swup.

------------------------------------------------------------------ */
(function($) {
	'use strict';

	function updatePersistentUI(url) {
		var targetUrl = url || window.location.href;
		var path = new URL(targetUrl, window.location.origin).pathname.replace(/\/+$/, '');
		var isStoryOrMembers = /^\/(members|story)(\/|$)/.test(path);

		$('.ft').toggleClass('is-minimal', isStoryOrMembers);
	}

	function initPage() {
		updatePersistentUI();
		if (typeof window.tuougmInitPage === 'function') {
			window.tuougmInitPage();
		}
	}

	function scrollToCurrentHash(url) {
		var hash = new URL(url || window.location.href, window.location.href).hash;
		if (!hash) return;

		var targetId;
		try {
			targetId = decodeURIComponent(hash.slice(1));
		} catch (error) {
			targetId = hash.slice(1);
		}

		var target = document.getElementById(targetId);
		if (target) target.scrollIntoView({ block: 'start' });
	}

	$(function() {
		if (typeof Swup === 'undefined') {
			return;
		}

		updatePersistentUI();
		var swupPlugins = [];

		if (typeof SwupHeadPlugin === 'function') {
			swupPlugins.push(new SwupHeadPlugin({
				attributes: ['lang', 'dir'],
				persistTags: function(tag) {
					if (tag.tagName === 'SCRIPT') {
						var scriptType = (tag.getAttribute('type') || '').split(';')[0].trim().toLowerCase();
						return scriptType !== 'application/ld+json';
					}
					if (tag.tagName === 'STYLE') {
						return true;
					}
					return tag.tagName === 'LINK' && [
						'stylesheet', 'preload', 'modulepreload', 'preconnect', 'dns-prefetch'
					].some(function(rel) {
						return tag.relList.contains(rel);
					});
				}
			}));
		} else {
			console.error('[TUOUGM] Swup Head Plugin is unavailable.');
		}

		var swup = new Swup({
			containers: ['#swup-header', '#swup'],
			plugins: swupPlugins
		});

		swup.hooks.on('page:view', function(visit) {
			var url = visit && visit.to ? visit.to.url : window.location.href;
			updatePersistentUI(url);
			if (typeof window.tuougmInitPage === 'function') {
				window.tuougmInitPage();
			}
			if (typeof window.tuougmUpdateMusicTrackState === 'function') {
				window.tuougmUpdateMusicTrackState();
			}
			window.requestAnimationFrame(function() {
				scrollToCurrentHash(url);
			});
		});

		window.tuougmSwup = swup;
	});
})(jQuery);
