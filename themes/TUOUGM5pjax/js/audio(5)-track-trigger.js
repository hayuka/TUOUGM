(function($){
  let playerData = null;
  let globalCurrentAudio = null;
  let globalCurrentAlbum = null;
  let globalCurrentPlaylist = [];
  let globalCurrentIndex = 0;
  let globalIsPlayingAll = false;
  let isSeeking = false;
  let isTransitioning = false;
  let keepActiveDuringTransition = false;
  let isRepeat = false;
  let isReady = false;

  const ARTIST_NAME = 'THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN';
  const API_URL = new URL('/wp-json/tuougm/v1/player', location.origin).href;
  const REST_NONCE = window.tuougmPlayerConfig && window.tuougmPlayerConfig.restNonce
    ? window.tuougmPlayerConfig.restNonce
    : '';

  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return m + ':' + String(s).padStart(2, '0');
  }

  function getAlbum(id) {
    if (!playerData || !playerData.albums) return null;
    return playerData.albums.find(album => album.id === id) || null;
  }

  function getTrack(albumId, trackId) {
    const album = getAlbum(albumId);
    if (!album) return null;
    return album.tracks.find(track => track.id === trackId) || null;
  }

  function getCurrentTrack() {
    return globalCurrentPlaylist[globalCurrentIndex] || null;
  }

  function updatePreviousButton() {
    $('.player').find('.player_send.pre').toggleClass(
      'disabled',
      globalCurrentIndex === 0 && (!globalCurrentAudio || globalCurrentAudio.currentTime < 3)
    );
  }

  function updateSeek() {
    updatePreviousButton();
    if (isSeeking || !globalCurrentAudio) return;
    const duration = globalCurrentAudio.duration || 0;
    const currentTime = globalCurrentAudio.currentTime || 0;
    const percent = duration ? (currentTime / duration) * 100 : 0;
    $('.player').find('.seek_progress').css('width', percent + '%');
    $('.player').find('.time_current').text(formatTime(currentTime));
    $('.player').find('.time_duration').text(formatTime(duration));
  }

  function resetSeek() {
    $('.player').find('.seek_progress').css('width', '0%');
    $('.player').find('.time_current').text('0:00');
    $('.player').find('.time_duration').text('0:00');
  }

  // GA4: 1回の再生開始につき1回だけ送信する。
  // 曲を変更すると loadAudio() 側でフラグをリセットする。
  function sendMusicPlayEvent(audio) {
    if (typeof window.gtag !== 'function') return;
    if (audio.dataset.gaPlayed === '1') return;

    const track = getCurrentTrack();
    const album = globalCurrentAlbum;
    if (!track || !album) return;

    audio.dataset.gaPlayed = '1';

    window.gtag('event', 'music_play', {
      album_id: String(album.id || ''),
      album_name: String(album.title || ''),
      track_id: String(track.id || ''),
      track_name: String(track.title || '')
    });
  }

  function ensureAudio() {
    if (globalCurrentAudio) return globalCurrentAudio;
    globalCurrentAudio = new Audio();
    globalCurrentAudio.preload = 'none';
    globalCurrentAudio.addEventListener('timeupdate', updateSeek);
    globalCurrentAudio.addEventListener('loadedmetadata', updateSeek);
    globalCurrentAudio.addEventListener('playing', function() {
      sendMusicPlayEvent(globalCurrentAudio);
      updateUI();
    });
    globalCurrentAudio.addEventListener('pause', updateUI);
    globalCurrentAudio.addEventListener('ended', handleEnded);
    globalCurrentAudio.addEventListener('error', updateUI);
    return globalCurrentAudio;
  }

  // 曲を選択した時だけ src を設定する。ページロード時は音源を読まない。
  function loadAudio(track) {
    const audio = ensureAudio();
    if (!track || !track.src) return audio;
    if (audio.dataset.trackId === track.id && audio.src) return audio;

    audio.pause();
    audio.removeAttribute('src');
    audio.dataset.trackId = track.id;
    audio.dataset.gaPlayed = '0';
    audio.src = track.src;
    audio.preload = 'none';
    audio.load();
    return audio;
  }

  function playAudio(track) {
    const audio = loadAudio(track);
    return audio.play();
  }

  function getSeekRatio($bar, clientX) {
    const offset = clientX - $bar.offset().left;
    const width = $bar.width();
    return Math.min(Math.max(offset / width, 0), 1);
  }

  function updateMediaSession() {
    if (!('mediaSession' in navigator)) return;
    const track = getCurrentTrack();
    const album = globalCurrentAlbum;
    if (!track || !album) return;

    const artworkUrl = new URL(album.artwork, location.href).href;
    navigator.mediaSession.metadata = new MediaMetadata({
      title: track.title,
      artist: ARTIST_NAME,
      album: album.title,
      artwork: [{ src: artworkUrl, sizes: '512x512', type: 'image/png' }]
    });

    navigator.mediaSession.setActionHandler('play', function() {
      playAudio(track).catch(() => {});
      updateUI();
    });
    navigator.mediaSession.setActionHandler('pause', function() {
      ensureAudio().pause();
      updateUI();
    });
    navigator.mediaSession.setActionHandler('previoustrack', function() {
      if (globalCurrentIndex > 0) {
        globalIsPlayingAll = true;
        playTrack(globalCurrentIndex - 1);
      }
    });
    navigator.mediaSession.setActionHandler('nexttrack', function() {
      if (globalCurrentIndex < globalCurrentPlaylist.length - 1) {
        globalIsPlayingAll = true;
        playTrack(globalCurrentIndex + 1);
        return;
      }

      const currentAlbumIndex = getAlbumIndex(globalCurrentAlbum && globalCurrentAlbum.id);
      const nextAlbumIndex = currentAlbumIndex + 1;
      const albums = playerData && playerData.albums ? playerData.albums : [];
      const nextAlbum = albums[nextAlbumIndex] || albums[0];

      if (nextAlbum && nextAlbum.tracks && nextAlbum.tracks.length) {
        globalIsPlayingAll = true;
        setAlbum(nextAlbum.id, 0, true);
      }
    });
  }
  function renderLyrics() {
    const track = getCurrentTrack();
    const $lyrics = $('.player_lyrics_content');

    if (!$lyrics.length) return;

    if (!track || !track.lyrics) {
        $lyrics.empty();
        return;
    }

    $lyrics.text(track.lyrics);
}

  function updateUI() {
    $('.audio-player').find('.play_btn, .all_btn').removeClass('pause');
    $('.audio-player').find('.audio_list li').removeClass('playing');
    $('.player').find('.player_play, .player_send').removeClass('pause disabled');

    const audio = ensureAudio();
	$('.player').toggleClass(
	  'active',
	  !audio.paused || isTransitioning || keepActiveDuringTransition || (globalIsPlayingAll && !!getCurrentTrack())
	);

    const track = getCurrentTrack();
    if (!track || !globalCurrentAlbum) {
      $('.player').find('.player_title span').text('No track selected');
      $('.player').find('.player_album_toggle_label').text('Select album');
      renderAlbumView(null);
      return;
    }

    $('.player').find('.player_album_options input[type="radio"]').prop('checked', false);
    $('.player').find('.player_album_options input[value="' + globalCurrentAlbum.id + '"]').prop('checked', true);
    $('.player').find('.player_album_toggle_label').text('Select album');
    renderAlbumView(globalCurrentAlbum);

    $('.audio-player').each(function(){
      const $albumEl = $(this);
      const albumId = $albumEl.data('player-album');
      if (albumId !== globalCurrentAlbum.id) return;

      $albumEl.find('[data-player-track="' + track.id.replace(/"/g, '\\"') + '"]').addClass('playing');
      if (!ensureAudio().paused) {
        $albumEl.find('.play_btn').filter(function(){
          return $(this).closest('li').data('player-track') === track.id;
        }).addClass('pause');
        $albumEl.find('.all_btn').addClass('pause');
      }
    });

    $('.player').find('.player_title span').text(track.title);
    $('.player').find('.play_skin img').attr('src', globalCurrentAlbum.artwork);
    $('.player').find('.player_title').toggleClass('paused', ensureAudio().paused);

    if (!ensureAudio().paused) {
      $('.player').find('.player_play').addClass('pause');
    }
    updatePreviousButton();
    if (globalCurrentIndex === globalCurrentPlaylist.length - 1 && isRepeat) {
      $('.player').find('.player_send.next').removeClass('disabled');
    }
	renderLyrics();
  }

  function playTrack(index) {
    if (!globalCurrentPlaylist.length || isTransitioning) return;
    if (index < 0 || index >= globalCurrentPlaylist.length) return;
    isTransitioning = true;
    keepActiveDuringTransition = true;

    const track = globalCurrentPlaylist[index];
    const audio = ensureAudio();
    audio.pause();
    audio.currentTime = 0;
    globalCurrentIndex = index;
    resetSeek();

    playAudio(track).then(function(){
      isTransitioning = false;
      keepActiveDuringTransition = false;
      updateMediaSession();
      updateUI();
    }).catch(function(){
      isTransitioning = false;
      keepActiveDuringTransition = false;
      globalIsPlayingAll = false;
      updateUI();
    });
  }

  function getAlbumIndex(albumId) {
    if (!playerData || !playerData.albums) return -1;
    return playerData.albums.findIndex(album => String(album.id) === String(albumId));
  }

function handleEnded() {
  if (isTransitioning) return;

  // 1. アルバム内に次の曲があれば、次の曲を再生
  if (globalCurrentIndex < globalCurrentPlaylist.length - 1) {
    playTrack(globalCurrentIndex + 1);
    return;
  }

  // 2. アルバムの最終曲まで来た場合
  // リピートONなら、同じアルバムの1曲目に戻る
  if (isRepeat) {
    playTrack(0);
    return;
  }

  // 3. リピートOFFなら、次のアルバムへ移動
  const currentAlbumIndex = getAlbumIndex(
    globalCurrentAlbum && globalCurrentAlbum.id
  );
  const albums = playerData && playerData.albums
    ? playerData.albums
    : [];
  const nextAlbum = albums[currentAlbumIndex + 1] || albums[0];

  if (nextAlbum && nextAlbum.tracks && nextAlbum.tracks.length) {
    globalIsPlayingAll = true;
    setAlbum(nextAlbum.id, 0, true);
    return;
  }

  // 4. 再生可能なアルバムがなければ終了
  globalIsPlayingAll = false;
  resetSeek();

  if ('mediaSession' in navigator) {
    navigator.mediaSession.metadata = null;
  }

  updateUI();
}

  function setAlbum(albumId, startIndex, autoPlay) {
    const album = getAlbum(albumId);
    if (!album || !album.tracks.length) return;
    globalCurrentAlbum = album;
    globalCurrentPlaylist = album.tracks;
    globalCurrentIndex = Math.max(0, Math.min(startIndex || 0, globalCurrentPlaylist.length - 1));
    globalIsPlayingAll = !!autoPlay;
    renderAlbumView(album);
    if (autoPlay) playTrack(globalCurrentIndex);
    else updateUI();
  }

  // 選択中アルバムの情報と曲一覧をプレイヤー内に表示する。
  // UI/CSSはテーマ側で自由に調整できるよう、必要な要素だけ生成する。
  function renderAlbumView(album) {
    const $player = $('.player');
    const $artwork = $player.find('.player_album_artwork img');
    const $title = $player.find('.player_album_title');
    const $list = $player.find('.player_track_list');

    if (!$artwork.length || !$title.length || !$list.length) return;

    if (!album) {
      $artwork.attr('src', 'img/sample_skin.webp').attr('alt', '');
      $title.text('');
      $list.empty();
      return;
    }

    $artwork.attr('src', album.artwork || 'img/sample_skin.webp').attr('alt', album.title || '');
    $title.text(album.title || '');

    $list.empty();
    album.tracks.forEach(function(track, index) {
      const $item = $('<li>', {
        'data-player-track': track.id
      });

      const $button = $('<button>', {
        type: 'button',
        'class': 'player_track_button',
        'data-player-track': track.id,
        'aria-label': 'Play ' + track.title
      });

      $('<span>', {
        'class': 'player_track_number',
        text: String(index + 1).padStart(2, '0')
      }).appendTo($button);

      $('<span>', {
        'class': 'player_track_title',
        text: track.title
      }).appendTo($button);

      $item.append($button);
      $list.append($item);
    });

    updateAlbumTrackListUI();
  }

  function updateAlbumTrackListUI() {
    const $player = $('.player');
    $player.find('.player_track_list li').removeClass('playing');

    const track = getCurrentTrack();
    if (!track || !globalCurrentAlbum) return;
    if (String(globalCurrentAlbum.id) !== String($player.find('.player_album_options input[type="radio"]:checked').val() || '')) return;

    $player.find('.player_track_list li').filter(function() {
      return String($(this).data('player-track')) === String(track.id);
    }).addClass('playing');
  }

  function renderAlbumSelector() {
    const $wrap = $('.player').find('.player_album_options');
    const $label = $('.player').find('.player_album_toggle_label');
    if (!$wrap.length) return;

    $wrap.empty();

    if (!playerData || !playerData.albums || !playerData.albums.length) {
      $label.text('Select album');
      return;
    }

    playerData.albums.forEach(function(album, index) {
      const inputId = 'player-album-' + String(album.id).replace(/[^a-zA-Z0-9_-]/g, '-') + '-' + index;
      const $item = $('<label>', {
        'class': 'player_album_option',
        'for': inputId
      });

      const $radio = $('<input>', {
        type: 'radio',
        name: 'tuougm-player-album',
        id: inputId,
        value: album.id
      });

      const $artwork = $('<span>', { 'class': 'player_album_option_artwork' });
      $('<img>', {
        src: album.artwork || 'img/sample_skin.webp',
        alt: album.title || ''
      }).appendTo($artwork);

      const $text = $('<span>', { 'class': 'player_album_option_text' });
      $('<span>', {
        'class': 'player_album_option_title',
        text: album.title || ''
      }).appendTo($text);

      if (album.year) {
        $('<span>', {
          'class': 'player_album_option_year',
          text: album.year
        }).appendTo($text);
      }

      $radio.appendTo($item);
      $artwork.appendTo($item);
      $text.appendTo($item);
      $wrap.append($item);
    });

    if (globalCurrentAlbum) {
      $wrap.find('input[type="radio"][value="' + globalCurrentAlbum.id + '"]').prop('checked', true);
      $label.text(globalCurrentAlbum.title || 'Select album');
    } else {
      $label.text('Select album');
    }
  }

  function initPlayer() {
    const $player = $('.player');
    if (!$player.length) return;

    $player.on('change', '.player_album_options input[type="radio"]', function() {
      const albumId = String($(this).val() || '');
      if (!albumId) return;

      const album = getAlbum(albumId);
      if (!album || !album.tracks.length) return;

      // アルバムを選択しただけでは再生せず、1曲目を選択状態にする。
      setAlbum(albumId, 0, true);
    });

    $player.on('click', '.player_track_button', function(e) {
      e.preventDefault();

      if (!isReady || !globalCurrentAlbum) return;

      const trackId = String($(this).data('player-track') || '');
      if (!trackId) return;

      const album = getAlbum(globalCurrentAlbum.id);
      if (!album) return;

      const index = album.tracks.findIndex(function(track) {
        return String(track.id) === trackId;
      });
      if (index < 0) return;

      if (globalCurrentAlbum.id === album.id && getCurrentTrack() && String(getCurrentTrack().id) === trackId) {
        const audio = ensureAudio();
        if (!audio.paused) {
          audio.pause();
          globalIsPlayingAll = false;
          updateUI();
        } else {
          playAudio(getCurrentTrack()).catch(() => {});
          globalIsPlayingAll = true;
          updateMediaSession();
          updateUI();
        }
        return;
      }

      setAlbum(album.id, index, true);
    });

    $player.find('.seek_bar').on('mousedown', function(e) {
      const $bar = $(this);
      if (!globalCurrentAudio || !globalCurrentAudio.duration) return;
      isSeeking = true;
      const ratio = getSeekRatio($bar, e.pageX);
      globalCurrentAudio.currentTime = ratio * globalCurrentAudio.duration;
      $bar.find('.seek_progress').css('width', (ratio * 100) + '%');
      $(document).on('mousemove.seek', function(e) {
        const ratio = getSeekRatio($bar, e.pageX);
        globalCurrentAudio.currentTime = ratio * globalCurrentAudio.duration;
        $bar.find('.seek_progress').css('width', (ratio * 100) + '%');
      });
      $(document).on('mouseup.seek', function() {
        $(document).off('mousemove.seek mouseup.seek');
        isSeeking = false;
      });
    });

    $player.find('.seek_bar').on('touchstart', function(e) {
      const $bar = $(this);
      if (!globalCurrentAudio || !globalCurrentAudio.duration) return;
      isSeeking = true;
      const ratio = getSeekRatio($bar, e.originalEvent.touches[0].pageX);
      globalCurrentAudio.currentTime = ratio * globalCurrentAudio.duration;
      $bar.find('.seek_progress').css('width', (ratio * 100) + '%');
      $(document).on('touchmove.seek', function(e) {
        const ratio = getSeekRatio($bar, e.originalEvent.touches[0].pageX);
        globalCurrentAudio.currentTime = ratio * globalCurrentAudio.duration;
        $bar.find('.seek_progress').css('width', (ratio * 100) + '%');
      });
      $(document).on('touchend.seek', function() {
        $(document).off('touchmove.seek touchend.seek');
        isSeeking = false;
      });
    });

    $player.find('.player_play').on('click', function() {
      if (!globalCurrentAlbum) return;
      const audio = ensureAudio();
      if (!audio.paused) {
        audio.pause();
        globalIsPlayingAll = false;
        updateUI();
      } else {
        playAudio(getCurrentTrack()).catch(() => {});
        globalIsPlayingAll = true;
        updateMediaSession();
        updateUI();
      }
    });

    $player.find('.player_send.pre').on('click', function() {
      if (!globalCurrentPlaylist.length || isTransitioning) return;
      const audio = ensureAudio();
      // 3秒以上進んでいたら同じ曲の先頭へ。3秒未満なら前の曲へ。
      if (audio.currentTime >= 3 || globalCurrentIndex === 0) {
        audio.currentTime = 0;
        updateSeek();
        return;
      }
      globalIsPlayingAll = true;
      playTrack(globalCurrentIndex - 1);
    });
    $player.find('.player_send.next').on('click', function() {
      if (globalCurrentIndex < globalCurrentPlaylist.length - 1) {
        globalIsPlayingAll = true;
        playTrack(globalCurrentIndex + 1);
        return;
      }

      const currentAlbumIndex = getAlbumIndex(globalCurrentAlbum && globalCurrentAlbum.id);
      const nextAlbumIndex = currentAlbumIndex + 1;
      const albums = playerData && playerData.albums ? playerData.albums : [];
      const nextAlbum = albums[nextAlbumIndex] || albums[0];

      if (nextAlbum && nextAlbum.tracks && nextAlbum.tracks.length) {
        globalIsPlayingAll = true;
        setAlbum(nextAlbum.id, 0, true);
      }
    });

    // リピートUI。テーマ側に .player_repeat が無くても自動生成する。
    let $repeat = $player.find('.player_repeat').first();

    if (!$repeat.length) {
      $repeat = $('<button>', {
        type: 'button',
        class: 'player_repeat',
        'aria-label': 'Album repeat',
        'aria-pressed': 'false',
        text: '↻'
      });
      $player.find('.plyer_control').append($repeat);
    }

    $repeat
      .toggleClass('active', isRepeat)
      .attr('aria-pressed', String(isRepeat))
      .attr('aria-label', isRepeat ? 'Album repeat on' : 'Album repeat off');

    $repeat.off('click.tuougmRepeat').on('click.tuougmRepeat', function() {
      isRepeat = !isRepeat;
      $(this)
        .toggleClass('active', isRepeat)
        .attr('aria-pressed', String(isRepeat))
        .attr('aria-label', isRepeat ? 'Album repeat on' : 'Album repeat off');
      updateUI();
    });
    $player.find('.player_reset').on('click', function() {
      if (globalCurrentAudio) {
        globalCurrentAudio.pause();
        globalCurrentAudio.currentTime = 0;
      }
      globalCurrentAlbum = null;
      globalCurrentPlaylist = [];
      globalCurrentIndex = 0;
      globalIsPlayingAll = false;
      resetSeek();
      if ('mediaSession' in navigator) navigator.mediaSession.metadata = null;
      updateUI();
    });
  }

  // ページ側（KV / 404 など）のアルバム再生ボタンからも
  // プレイヤーを起動できるようにする。
  // ページ側には data-player-album だけ持たせ、曲データはプレイヤー側の
  // JSON を参照する。
  // 投稿本文の [tuougm_track] から指定曲を直接再生する。
  // アルバム選択UIは経由せず、対象アルバム＋曲番号を直接指定する。
  $(document).on('click.tuougmTrackTrigger', '.tuougm-track-trigger[data-player-track]', function(e) {
    e.preventDefault();

    if (!isReady || !playerData) return;

    const trackId = String($(this).data('player-track') || '');
    if (!trackId) return;

    let targetAlbum = null;
    let targetIndex = -1;

    playerData.albums.forEach(function(album) {
      if (targetAlbum || !album.tracks) return;

      const index = album.tracks.findIndex(function(track) {
        return String(track.id) === trackId;
      });

      if (index >= 0) {
        targetAlbum = album;
        targetIndex = index;
      }
    });

    if (!targetAlbum || targetIndex < 0) {
      console.warn('[TUOUGM] Track not found:', trackId);
      return;
    }

    // 現在再生中の曲なら再生/一時停止を切り替える。
    if (
      globalCurrentAlbum &&
      String(globalCurrentAlbum.id) === String(targetAlbum.id) &&
      getCurrentTrack() &&
      String(getCurrentTrack().id) === trackId
    ) {
      const audio = ensureAudio();

      if (!audio.paused) {
        audio.pause();
        globalIsPlayingAll = false;
        updateUI();
      } else {
        playAudio(getCurrentTrack()).catch(() => {});
        globalIsPlayingAll = true;
        updateMediaSession();
        updateUI();
      }

      return;
    }

    // 対象アルバム・対象曲を直接指定して再生。
    // アルバムラジオのchangeイベントは発火させないので、
    // 1曲目が先に再生されることはない。
    setAlbum(targetAlbum.id, targetIndex, true);
  });

  $(document).on('click.tuougmPlayer', '.all_btn[data-player-album]', function(e) {
    e.preventDefault();

    if (!isReady || !playerData) return;

    const albumId = String($(this).data('player-album') || '');
    if (!albumId) return;

    const album = getAlbum(albumId);
    if (!album || !album.tracks.length) return;

    if (globalCurrentAlbum && globalCurrentAlbum.id === albumId) {
      const audio = ensureAudio();

      if (!audio.paused) {
        audio.pause();
        globalIsPlayingAll = false;
        updateUI();
      } else {
        playAudio(getCurrentTrack()).catch(() => {});
        globalIsPlayingAll = true;
        updateMediaSession();
        updateUI();
      }
      return;
    }

    setAlbum(albumId, 0, true);
  });

  function initAudioPlayer($root) {
    const albumId = $root.data('player-album');
    if (!albumId || !getAlbum(albumId)) return;

    $root.find('.audio_list li').on('click', function(e) {
      if ($(e.target).closest('.play_btn').length) return;
      $(this).find('.play_btn').trigger('click');
    });

    $root.find('.play_btn').on('click', function() {
      const trackId = $(this).closest('li').data('player-track');
      const track = getTrack(albumId, trackId);
      if (!track) return;

      if (globalCurrentAlbum && globalCurrentAlbum.id === albumId && getCurrentTrack() && getCurrentTrack().id === trackId) {
        const audio = ensureAudio();
        if (!audio.paused) {
          audio.pause();
          globalIsPlayingAll = false;
          updateUI();
        } else {
          playAudio(track).catch(() => {});
          updateMediaSession();
          updateUI();
        }
        return;
      }

      const album = getAlbum(albumId);
      const index = album.tracks.findIndex(item => item.id === trackId);
      setAlbum(albumId, index, true);
    });

    $root.find('.all_btn').on('click', function() {
      const album = getAlbum(albumId);
      if (!album) return;
      if (globalCurrentAlbum && globalCurrentAlbum.id === albumId) {
        const audio = ensureAudio();
        if (!audio.paused) {
          audio.pause();
          globalIsPlayingAll = false;
          updateUI();
        } else {
          playAudio(getCurrentTrack()).catch(() => {});
          globalIsPlayingAll = true;
          updateMediaSession();
          updateUI();
        }
        return;
      }
      setAlbum(albumId, 0, true);
    });
  }

  async function loadPlayerData() {
    try {
      const response = await fetch(API_URL, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: REST_NONCE ? { 'X-WP-Nonce': REST_NONCE } : {}
      });
      if (!response.ok) throw new Error('Player data request failed');
      playerData = await response.json();
      isReady = true;
      renderAlbumSelector();
      initPlayer();

      // API取得後は1枚目のアルバム・1曲目を選択状態にする。
      // 音源はまだロードせず、再生時に読み込む。
      if (playerData.albums && playerData.albums.length) {
        const firstAlbum = playerData.albums[0];
        if (firstAlbum.tracks && firstAlbum.tracks.length) {
          setAlbum(firstAlbum.id, 0, false);
        }
      }

      $('.audio-player[data-player-album]').each(function(){ initAudioPlayer($(this)); });
    } catch (error) {
      console.error('[TUOUGM] Player data could not be loaded.', error);
    }
  }

  setInterval(updateSeek, 500);

  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
      isTransitioning = false;
      updateUI();
    }
  });

  $(function(){ loadPlayerData(); });
})(jQuery);
