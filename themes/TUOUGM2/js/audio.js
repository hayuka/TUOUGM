(function($){
  let globalCurrentAudio = null;
  let globalCurrentAlbum = null;
  let globalCurrentPlaylist = [];
  let globalCurrentIndex = 0;
  let globalIsPlayingAll = false;
  let isSeeking = false;
  let isTransitioning = false;
  let isRepeat = true;

  const ARTIST_NAME =
    document.querySelector('.album .ttl_band')?.textContent.trim()
    || 'THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN';

  function formatTime(sec) {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return m + ':' + String(s).padStart(2, '0');
  }

  function updateSeek() {
    if (isSeeking) return;
    if (globalCurrentAudio) {
      const duration = globalCurrentAudio.duration || 0;
      const currentTime = globalCurrentAudio.currentTime || 0;
      const percent = duration ? (currentTime / duration) * 100 : 0;
      $('.player').find('.seek_progress').css('width', percent + '%');
      $('.player').find('.time_current').text(formatTime(currentTime));
      $('.player').find('.time_duration').text(formatTime(duration));
    }
  }

  function resetSeek() {
    $('.player').find('.seek_progress').css('width', '0%');
    $('.player').find('.time_current').text('0:00');
    $('.player').find('.time_duration').text('0:00');
  }

  // 音源は初回再生時まで読み込まない。
  // 一度 src を設定した audio はそのまま保持し、以降はブラウザキャッシュを利用する。
  function loadAudio(audio) {
    if (!audio || audio.src) return;

    const src = audio.dataset.src;
    if (!src) return;

    audio.src = src;
    audio.preload = 'none';
    audio.load();
  }

  function playAudio(audio) {
    if (!audio) return Promise.reject();

    loadAudio(audio);
    return audio.play();
  }

  function getSeekRatio($bar, clientX) {
    const offset = clientX - $bar.offset().left;
    const width = $bar.width();
    return Math.min(Math.max(offset / width, 0), 1);
  }

  function updateMediaSession() {
    if (!('mediaSession' in navigator)) return;
    if (!globalCurrentAudio || !globalCurrentAlbum) return;

    const title = $(globalCurrentAudio).closest('li').find('.ad_cnt p').text().trim();
    const albumArt = globalCurrentAlbum.find('.all_btn img').attr('src');
    const artworkUrl = new URL(albumArt, location.href).href;

    navigator.mediaSession.metadata = new MediaMetadata({
      title: title,
      artist: ARTIST_NAME,
      artwork: [{ src: artworkUrl, sizes: '512x512', type: 'image/png' }]
    });

    navigator.mediaSession.setActionHandler('play', function() {
      playAudio(globalCurrentAudio).catch(() => {});
      updateUI();
    });
    navigator.mediaSession.setActionHandler('pause', function() {
      globalCurrentAudio.pause();
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
      }
    });
  }

  function updateUI() {
    // アルバム側のクラスリセット
    $('.audio-player').find('.play_btn, .all_btn').removeClass('pause');
    $('.audio-player').find('.audio_list li').removeClass('playing');

    // プレイヤー側のクラスリセット
    $('.player').find('.player_play, .player_send').removeClass('pause disabled');
    $('.player').removeClass('active');

    if (globalCurrentAudio) {
      $(globalCurrentAudio).closest('li').addClass('playing');

      if (globalCurrentAlbum) {
        $('.player').addClass('active');
        const title = $(globalCurrentAudio).closest('li').find('.ad_cnt p').text().trim();
        $('.player').find('.player_title span').text(title);
		const albumArt = globalCurrentAlbum.find('.all_btn img').attr('src');
		$('.player').find('.play_skin img').attr('src', albumArt);
        const $titleEl = $('.player').find('.player_title');
        if (globalCurrentAudio.paused) {
          $titleEl.addClass('paused');
        } else {
          $titleEl.removeClass('paused');
        }
      }

      if (!globalCurrentAudio.paused) {
        $(globalCurrentAudio).prev('.play_btn').addClass('pause');
        if (globalCurrentAlbum) {
          globalCurrentAlbum.find('.all_btn').addClass('pause');
          $('.player').find('.player_play').addClass('pause');
        }
      }

      if (globalCurrentIndex === 0) {
        $('.player').find('.player_send.pre').addClass('disabled');
      }
      if (globalCurrentIndex === globalCurrentPlaylist.length - 1) {
        $('.player').find('.player_send.next').addClass('disabled');
      }
    } else {
      $('.player').find('.player_title span').text('No track selected');
    }
  }

  function playTrack(index) {
    if (!globalCurrentPlaylist.length) return;
    if (isTransitioning) return;
    isTransitioning = true;

    if (globalCurrentAudio) {
      globalCurrentAudio.pause();
      globalCurrentAudio.currentTime = 0;
    }
    $('.player').find('.player_title span').text('No track selected');
    globalCurrentAudio = globalCurrentPlaylist[index];
    globalCurrentIndex = index;
    resetSeek();

    playAudio(globalCurrentAudio)
      .then(() => {
        isTransitioning = false;
        updateMediaSession();
        updateUI();
      })
      .catch(() => {
        isTransitioning = false;
      });
  }

  // 共通プレイヤーのイベント（1回だけバインド）
  function initPlayer() {
    const $player = $('.player');

    // シークバー mouse
    $player.find('.seek_bar').on('mousedown', function(e) {
      const $bar = $(this);
      if (!globalCurrentAudio) return;
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

    // シークバー touch
    $player.find('.seek_bar').on('touchstart', function(e) {
      const $bar = $(this);
      if (!globalCurrentAudio) return;
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

    // 再生/一時停止
    $player.find('.player_play').on('click', function() {
      if (!globalCurrentAudio) return;
      if (!globalCurrentAudio.paused) {
        globalCurrentAudio.pause();
        globalIsPlayingAll = false;
        updateUI();
      } else {
        playAudio(globalCurrentAudio).catch(() => {});
        globalIsPlayingAll = true;
        updateMediaSession();
        updateUI();
      }
    });

    // 前の曲
    $player.find('.player_send.pre').on('click', function() {
      if (!globalCurrentAlbum) return;
      if (globalCurrentIndex <= 0) return;
      globalIsPlayingAll = true;
      playTrack(globalCurrentIndex - 1);
    });

    // 次の曲
    $player.find('.player_send.next').on('click', function() {
      if (!globalCurrentAlbum) return;
      if (globalCurrentIndex >= globalCurrentPlaylist.length - 1) return;
      globalIsPlayingAll = true;
      playTrack(globalCurrentIndex + 1);
    });

    // リピート
    $player.find('.player_repeat').on('click', function() {
      isRepeat = !isRepeat;
      $(this).toggleClass('active', isRepeat);
    });

    // リセット
    $player.find('.player_reset').on('click', function() {
      if (globalCurrentAudio) {
        globalCurrentAudio.pause();
        globalCurrentAudio.currentTime = 0;
      }
      globalCurrentAudio = null;
      globalCurrentAlbum = null;
      globalCurrentPlaylist = [];
      globalCurrentIndex = 0;
      globalIsPlayingAll = false;
      resetSeek();
      if ('mediaSession' in navigator) navigator.mediaSession.metadata = null;
      updateUI();
    });
  }
  
  // albumクラス外のall_btnクリック（id指定）
	$(document).on('click', '.all_btn[data-album]', function() {
	  const albumId = $(this).data('album');
	  const $album = $('#' + albumId);
	  if (!$album.length) return;

	  const isSameAlbum = globalCurrentAlbum && globalCurrentAlbum.is($album);
	  const isPlaying = globalCurrentAudio && !globalCurrentAudio.paused;

	  if (isSameAlbum && isPlaying) {
		globalCurrentAudio.pause();
		globalIsPlayingAll = false;
		updateUI();
	  } else if (isSameAlbum && !isPlaying && globalCurrentAudio) {
		playAudio(globalCurrentAudio).catch(() => {});
		globalIsPlayingAll = true;
		updateMediaSession();
		updateUI();
	  } else {
		if (globalCurrentAudio) {
		  globalCurrentAudio.pause();
		  globalCurrentAudio.currentTime = 0;
		}
		globalCurrentAlbum = $album;
		globalCurrentPlaylist = $album.find('audio').toArray();
		globalCurrentIndex = 0;
		globalIsPlayingAll = true;
		playTrack(globalCurrentIndex);
	  }
	});

  function initAudioPlayer($root) {
    // リスト行クリック
    $root.find('.audio_list li').on('click', function(e) {
      if ($(e.target).closest('.play_btn').length) return;
      $(this).find('.play_btn').trigger('click');
    });

    // 個別再生ボタン
    $root.find('.play_btn').on('click', function() {
      const $btn = $(this);
      const audio = $btn.next('audio').get(0);
      const $album = $btn.closest('.album');

      if (globalCurrentAudio && globalCurrentAudio === audio && !audio.paused) {
        globalCurrentAudio.pause();
        globalIsPlayingAll = false;
        updateUI();
      } else if (globalCurrentAudio && globalCurrentAudio === audio && audio.paused) {
        playAudio(globalCurrentAudio).catch(() => {});
        updateMediaSession();
        updateUI();
      } else {
        if (globalCurrentAudio) {
          globalCurrentAudio.pause();
          globalCurrentAudio.currentTime = 0;
        }
        globalCurrentAlbum = $album;
        globalCurrentPlaylist = $album.find('audio').toArray();
        globalCurrentIndex = globalCurrentPlaylist.indexOf(audio);
        globalIsPlayingAll = false;
        playTrack(globalCurrentIndex);
      }
    });

    // アルバム全曲再生
    $root.find('.all_btn').on('click', function() {
      const $album = $(this).closest('.album');
      const isSameAlbum = globalCurrentAlbum && globalCurrentAlbum.is($album);
      const isPlaying = globalCurrentAudio && !globalCurrentAudio.paused;

      if (isSameAlbum && isPlaying) {
        globalCurrentAudio.pause();
        globalIsPlayingAll = false;
        updateUI();
      } else if (isSameAlbum && !isPlaying && globalCurrentAudio) {
        playAudio(globalCurrentAudio).catch(() => {});
        globalIsPlayingAll = true;
        updateMediaSession();
        updateUI();
      } else {
        if (globalCurrentAudio) {
          globalCurrentAudio.pause();
          globalCurrentAudio.currentTime = 0;
        }
        globalCurrentAlbum = $album;
        globalCurrentPlaylist = $album.find('audio').toArray();
        globalCurrentIndex = 0;
        globalIsPlayingAll = true;
        playTrack(globalCurrentIndex);
      }
    });

    // 他アプリ割り込みによる停止検知
    $root.find('audio').on('pause', function() {
      if (this !== globalCurrentAudio) return;
      updateUI();
    });

    // 曲終了
    $root.find('audio').on('ended', function() {
      if (isTransitioning) return;
      if (isRepeat) {
        globalCurrentIndex++;
        if (globalCurrentIndex >= globalCurrentPlaylist.length) {
          globalCurrentIndex = 0;
        }
        playTrack(globalCurrentIndex);
      } else {
        globalCurrentAudio = null;
        globalIsPlayingAll = false;
        resetSeek();
        if ('mediaSession' in navigator) navigator.mediaSession.metadata = null;
        updateUI();
      }
    });
  }

  setInterval(updateSeek, 500);

  document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'visible') {
      isTransitioning = false;
      $('.audio-player').find('audio').each(function() {
        if (this !== globalCurrentAudio && !this.paused) {
          this.pause();
          this.currentTime = 0;
        }
      });
      updateUI();
    }
  });

  $(function() {
    initPlayer();
    $('.audio-player').each(function() {
      initAudioPlayer($(this));
    });
  });
})(jQuery);