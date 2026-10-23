/**
 * TUOUGM Track Trigger
 * 投稿本文の .tuougm-track-trigger を既存の audio(5).js プレイヤーへ接続する。
 * audio(5).js 本体は変更不要。
 */
(function ($) {
  'use strict';

  var API_URL = new URL('/wp-json/tuougm/v1/player', window.location.origin).href;
  var playerData = null;
  var loadingPromise = null;

  function normalizeId(value) {
    return String(value == null ? '' : value);
  }

  function getAlbumAndTrack(trackId) {
    if (!playerData || !Array.isArray(playerData.albums)) return null;
    trackId = normalizeId(trackId);

    for (var i = 0; i < playerData.albums.length; i++) {
      var album = playerData.albums[i];
      if (!album || !Array.isArray(album.tracks)) continue;

      for (var j = 0; j < album.tracks.length; j++) {
        var track = album.tracks[j];
        if (track && normalizeId(track.id) === trackId) {
          return { album: album, track: track, index: j };
        }
      }
    }
    return null;
  }

  function loadPlayerData() {
    if (playerData) return Promise.resolve(playerData);
    if (loadingPromise) return loadingPromise;

    loadingPromise = fetch(API_URL, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: window.tuougmPlayerConfig && window.tuougmPlayerConfig.restNonce
        ? { 'X-WP-Nonce': window.tuougmPlayerConfig.restNonce }
        : {}
    })
      .then(function (response) {
        if (!response.ok) throw new Error('TUOUGM player data request failed.');
        return response.json();
      })
      .then(function (data) {
        playerData = data;
        return data;
      })
      .catch(function (error) {
        loadingPromise = null;
        console.error('[TUOUGM] Track trigger could not load player data.', error);
        throw error;
      });

    return loadingPromise;
  }

  function findAlbumRadio(albumId) {
    var targetId = normalizeId(albumId);
    return $('.player .player_album_options input[type="radio"]').filter(function () {
      return normalizeId($(this).val()) === targetId;
    }).first();
  }

  function findPlayerTrackButton(trackId) {
    var targetId = normalizeId(trackId);
    return $('.player .player_track_button').filter(function () {
      return normalizeId($(this).data('player-track')) === targetId;
    }).first();
  }

  function clickTrackWhenReady(trackId, attempts) {
    attempts = attempts || 0;
    var $button = findPlayerTrackButton(trackId);

    if ($button.length) {
      $button.trigger('click');
      return;
    }

    if (attempts >= 30) {
      console.warn('[TUOUGM] Player track button was not found.', trackId);
      return;
    }

    window.setTimeout(function () {
      clickTrackWhenReady(trackId, attempts + 1);
    }, 50);
  }

  function playTrackFromExistingPlayer(info, retryCount) {
    retryCount = retryCount || 0;

    if (!$('.player').length) {
      console.warn('[TUOUGM] Persistent player was not found.');
      return;
    }

    var albumId = normalizeId(info.album.id);
    var trackId = normalizeId(info.track.id);
    var $radio = findAlbumRadio(albumId);

    if ($radio.length) {
      if (!$radio.prop('checked')) {
        $radio.prop('checked', true).trigger('change');
      }
      clickTrackWhenReady(trackId);
      return;
    }

    if (retryCount >= 30) {
      console.warn('[TUOUGM] Player album selector was not ready.');
      return;
    }

    window.setTimeout(function () {
      playTrackFromExistingPlayer(info, retryCount + 1);
    }, 100);
  }

  $(document).on('click.tuougmTrackTrigger', '.tuougm-track-trigger[data-player-track]', function (e) {
    e.preventDefault();

    var $trigger = $(this);
    var trackId = normalizeId($trigger.attr('data-player-track'));
    if (!trackId) return;

    $trigger.addClass('is-loading');

    loadPlayerData()
      .then(function () {
        var info = getAlbumAndTrack(trackId);
        if (!info) {
          console.warn('[TUOUGM] Track not found:', trackId);
          return;
        }
        playTrackFromExistingPlayer(info);
      })
      .catch(function () {})
      .finally(function () {
        $trigger.removeClass('is-loading');
      });
  });
})(jQuery);
