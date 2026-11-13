(function($) {
  'use strict';

  const config = window.tuougmStackismConfig || {};
  const videoId = String(config.videoId || '');
  let youtubeApiPromise = null;
  let currentState = null;

  function loadYoutubeApi() {
    if (window.YT && typeof window.YT.Player === 'function') {
      return Promise.resolve(window.YT);
    }
    if (youtubeApiPromise) return youtubeApiPromise;

    youtubeApiPromise = new Promise(function(resolve, reject) {
      const previousCallback = window.onYouTubeIframeAPIReady;
      let settled = false;
      let loadTimeout = null;
      let script = document.getElementById('youtube-iframe-api');
      const rejectLoad = function() {
        if (settled) return;
        settled = true;
        window.clearTimeout(loadTimeout);
        if (script && script.parentNode) script.parentNode.removeChild(script);
        if (window.onYouTubeIframeAPIReady === handleApiReady) {
          window.onYouTubeIframeAPIReady = previousCallback || null;
        }
        youtubeApiPromise = null;
        reject(new Error('YouTube IFrame API could not be loaded.'));
      };

      const handleApiReady = function() {
        if (typeof previousCallback === 'function') {
          try {
            previousCallback();
          } catch (error) {
            // Preserve the YouTube API callback even if another handler fails.
          }
        }
        if (window.YT && typeof window.YT.Player === 'function' && !settled) {
          settled = true;
          window.clearTimeout(loadTimeout);
          if (window.onYouTubeIframeAPIReady === handleApiReady) {
            window.onYouTubeIframeAPIReady = previousCallback || null;
          }
          resolve(window.YT);
        }
      };
      window.onYouTubeIframeAPIReady = handleApiReady;

      if (!script) {
        script = document.createElement('script');
        script.id = 'youtube-iframe-api';
        script.src = 'https://www.youtube.com/iframe_api';
        script.async = true;
      }
      script.addEventListener('error', rejectLoad, { once: true });
      if (!script.isConnected) document.head.appendChild(script);
      loadTimeout = window.setTimeout(rejectLoad, 15000);
    });

    return youtubeApiPromise;
  }

  function safePlayerCall(player, method) {
    if (!player || typeof player[method] !== 'function') return;
    try {
      player[method]();
    } catch (error) {
      // Player teardown can race with an iframe API callback.
    }
  }

  function showFallback(state, message, canRetry) {
    if (!state || state.destroyed) return;
    state.fallbackMessage.textContent = message;
    state.fallback.hidden = false;
    state.retryButton.hidden = !canRetry;
  }

  function isCurrentState(state) {
    return currentState === state && !state.destroyed && state.root.isConnected;
  }

  function isCurrentModalSession(state, generation) {
    return isCurrentState(state) && state.modalOpen && state.modalGeneration === generation;
  }

  function startPreview(state) {
    if (!isCurrentState(state) || state.previewPlayer || state.previewInitializing || !videoId) return;
    state.previewInitializing = true;

    loadYoutubeApi().then(function(YT) {
      if (!isCurrentState(state)) return;
      state.previewPlayer = new YT.Player(state.previewTarget, {
        videoId: videoId,
        playerVars: {
          autoplay: 1,
          controls: 0,
          disablekb: 1,
          enablejsapi: 1,
          cc_load_policy: 0,
          loop: 1,
          modestbranding: 1,
          playsinline: 1,
          playlist: videoId,
          rel: 0
        },
        events: {
          onReady: function(event) {
            if (!isCurrentState(state)) {
              safePlayerCall(event.target, 'destroy');
              return;
            }
            event.target.mute();
            event.target.setVolume(0);
            if (state.previewVisible && !state.modalOpen) event.target.playVideo();
          },
          onStateChange: function(event) {
            if (!isCurrentState(state)) return;
            if (event.data === YT.PlayerState.PLAYING) {
              state.visual.classList.add('is-ready');
            } else if (event.data === YT.PlayerState.ENDED && state.previewVisible && !state.modalOpen) {
              event.target.playVideo();
            }
          },
          onError: function() {
            if (!isCurrentState(state)) return;
            state.visual.classList.remove('is-ready');
            safePlayerCall(state.previewPlayer, 'destroy');
            state.previewPlayer = null;
          },
          onAutoplayBlocked: function() {
            if (!isCurrentState(state)) return;
            state.visual.classList.remove('is-ready');
            safePlayerCall(state.previewPlayer, 'pauseVideo');
          }
        }
      });
    }).catch(function() {
      if (isCurrentState(state)) state.visual.classList.remove('is-ready');
    }).finally(function() {
      state.previewInitializing = false;
    });
  }

  function resumePreview(state) {
    if (!isCurrentState(state) || state.modalOpen || !state.previewVisible || document.visibilityState === 'hidden') return;
    if (state.previewPlayer) {
      state.previewPlayer.mute();
      state.previewPlayer.setVolume(0);
      state.previewPlayer.playVideo();
      return;
    }
    startPreview(state);
  }

  function pausePreview(state) {
    if (state && state.previewPlayer) safePlayerCall(state.previewPlayer, 'pauseVideo');
  }

  function pauseSiteMusic() {
    const playButton = document.querySelector('.player .player_play.pause');
    if (playButton) playButton.click();
  }

  function openModal(state, trigger) {
    if (!isCurrentState(state) || state.modalOpen) return;
    state.modalOpen = true;
    const modalGeneration = ++state.modalGeneration;
    state.returnFocus = trigger || document.activeElement;
    pausePreview(state);
    pauseSiteMusic();

    state.modal.hidden = false;
    state.modal.setAttribute('aria-hidden', 'false');
    document.body.classList.add('stackism_video_open');
    state.closeButton.focus();

    loadYoutubeApi().then(function(YT) {
      if (!isCurrentModalSession(state, modalGeneration) || state.modalPlayer) return;
      state.fallback.hidden = true;
      state.modalPlayer = new YT.Player(state.modalTarget, {
        videoId: videoId,
        playerVars: {
          autoplay: 1,
          controls: 1,
          enablejsapi: 1,
          playsinline: 1,
          rel: 0
        },
        events: {
          onReady: function(event) {
            if (!isCurrentModalSession(state, modalGeneration)) {
              safePlayerCall(event.target, 'destroy');
              return;
            }
            const iframe = event.target.getIframe();
            if (iframe) iframe.setAttribute('title', 'STACKISM EP MEDLEY video');
            event.target.unMute();
            event.target.setVolume(100);
            event.target.playVideo();
          },
          onStateChange: function(event) {
            if (!isCurrentModalSession(state, modalGeneration)) return;
            if (event.data === YT.PlayerState.PLAYING) state.fallback.hidden = true;
          },
          onAutoplayBlocked: function() {
            if (!isCurrentModalSession(state, modalGeneration)) return;
            showFallback(state, 'Playback was blocked. Press PLAY VIDEO to start with sound.', true);
          },
          onError: function() {
            if (!isCurrentModalSession(state, modalGeneration)) return;
            showFallback(state, 'Video playback is unavailable here.', false);
          }
        }
      });
    }).catch(function() {
      if (isCurrentModalSession(state, modalGeneration)) {
        showFallback(state, 'Video playback is unavailable here.', false);
      }
    });
  }

  function closeModal(state, restoreFocus) {
    if (!state || !state.modalOpen) return;
    state.modalOpen = false;
    state.modalGeneration++;
    safePlayerCall(state.modalPlayer, 'stopVideo');
    safePlayerCall(state.modalPlayer, 'destroy');
    state.modalPlayer = null;
    state.modalTarget.replaceChildren();
    state.fallback.hidden = true;
    state.retryButton.hidden = true;
    state.modal.hidden = true;
    state.modal.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('stackism_video_open');

    if (restoreFocus && state.returnFocus && state.returnFocus.isConnected) {
      state.returnFocus.focus();
    }
    state.returnFocus = null;
    resumePreview(state);
  }

  function trapModalFocus(state, event) {
    if (!state.modalOpen || event.key !== 'Tab') return;
    const focusable = Array.from(state.dialog.querySelectorAll('button:not([hidden]), a[href], iframe, [tabindex]:not([tabindex="-1"])'))
      .filter(function(element) {
        return !element.hidden && element.getClientRects().length > 0;
      });
    if (!focusable.length) {
      event.preventDefault();
      state.closeButton.focus();
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && (document.activeElement === first || !state.dialog.contains(document.activeElement))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && (document.activeElement === last || !state.dialog.contains(document.activeElement))) {
      event.preventDefault();
      first.focus();
    }
  }

  function destroy() {
    const state = currentState;
    if (!state) return;
    state.destroyed = true;
    state.modalGeneration++;
    if (state.observer) state.observer.disconnect();
    document.removeEventListener('keydown', state.onKeydown);
    document.removeEventListener('visibilitychange', state.onVisibilityChange);
    state.root.removeEventListener('click', state.onClick);
    pausePreview(state);
    safePlayerCall(state.previewPlayer, 'destroy');
    state.previewPlayer = null;
    safePlayerCall(state.modalPlayer, 'stopVideo');
    safePlayerCall(state.modalPlayer, 'destroy');
    state.modalPlayer = null;
    document.body.classList.remove('stackism_video_open');
    currentState = null;
  }

  function init() {
    const root = document.querySelector('.stackism_feature');
    if (!root || !videoId) {
      destroy();
      return;
    }
    if (currentState && currentState.root === root) return;
    destroy();

    const state = {
      root: root,
      visual: root.querySelector('[data-stackism-visual]'),
      previewTarget: root.querySelector('[data-stackism-preview]'),
      modal: root.querySelector('[data-stackism-modal]'),
      dialog: root.querySelector('.stackism_video_dialog'),
      modalTarget: root.querySelector('[data-stackism-modal-player]'),
      fallback: root.querySelector('[data-stackism-fallback]'),
      fallbackMessage: root.querySelector('[data-stackism-fallback-message]'),
      retryButton: root.querySelector('[data-stackism-retry]'),
      closeButton: root.querySelector('[data-stackism-close]'),
      previewPlayer: null,
      modalPlayer: null,
      previewInitializing: false,
      previewVisible: false,
      modalOpen: false,
      modalGeneration: 0,
      returnFocus: null,
      observer: null,
      destroyed: false
    };

    if (!state.visual || !state.previewTarget || !state.modal || !state.dialog || !state.modalTarget || !state.closeButton) return;
    currentState = state;

    state.onClick = function(event) {
      const openButton = event.target.closest('[data-stackism-open]');
      if (openButton && state.root.contains(openButton)) {
        event.preventDefault();
        openModal(state, openButton);
        return;
      }
      if (event.target === state.modal || event.target.closest('[data-stackism-close]')) {
        closeModal(state, true);
        return;
      }
      if (event.target.closest('[data-stackism-retry]') && state.modalPlayer) {
        state.fallback.hidden = true;
        state.modalPlayer.unMute();
        state.modalPlayer.setVolume(100);
        state.modalPlayer.playVideo();
      }
    };
    state.onKeydown = function(event) {
      if (!state.modalOpen) return;
      if (event.key === 'Escape') {
        event.preventDefault();
        closeModal(state, true);
        return;
      }
      trapModalFocus(state, event);
    };
    state.onVisibilityChange = function() {
      if (document.visibilityState === 'hidden') pausePreview(state);
      else resumePreview(state);
    };
    state.root.addEventListener('click', state.onClick);
    document.addEventListener('keydown', state.onKeydown);
    document.addEventListener('visibilitychange', state.onVisibilityChange);

    if ('IntersectionObserver' in window) {
      state.observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (!isCurrentState(state)) return;
          state.previewVisible = entry.isIntersecting;
          if (entry.isIntersecting) resumePreview(state);
          else pausePreview(state);
        });
      }, { rootMargin: '300px 0px' });
      state.observer.observe(state.visual);
    } else {
      state.previewVisible = true;
      startPreview(state);
    }
  }

  $(function() {
    init();
    if (window.tuougmSwup && window.tuougmSwup.hooks) {
      window.tuougmSwup.hooks.on('visit:start', destroy);
      window.tuougmSwup.hooks.on('page:view', init);
    }
  });
})(jQuery);
