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

		var swup = new Swup({
			containers: ['#swup-header', '#swup']
		});

		swup.hooks.on('page:view', function(visit) {
			var url = visit && visit.to ? visit.to.url : window.location.href;
			updatePersistentUI(url);
			if (typeof window.tuougmInitPage === 'function') {
				window.tuougmInitPage();
			}
			window.requestAnimationFrame(function() {
				scrollToCurrentHash(url);
			});
		});

		window.tuougmSwup = swup;
	});
})(jQuery);
