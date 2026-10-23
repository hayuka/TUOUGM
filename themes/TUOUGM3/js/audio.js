(function($){
  let globalCurrentAudio = null;
  let globalCurrentAlbum = null;
  let globalCurrentPlaylist = [];
  let globalCurrentIndex = 0;
  let globalIsPlayingAll = false;
  let isSeeking = false;
  let isTransitioning = false;
  let isRepeat = true;
  let libraryAlbum = null;

  const DATA = window.TUOUGM_PLAYER_DATA || {};
  const $player = $('.player').first();
  const playerEl = $player.get(0);
  const audio = $player.find('.player_audio').get(0);
  const audioBase = $player.data('audio-base') || '';
  const themeBase = audioBase ? audioBase.replace(/audio\/?$/, '') : '';
  const ARTIST_NAME = 'THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN';

  function formatTime(sec) {
    if (!isFinite(sec) || sec < 0) return '0:00';
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);
    return m + ':' + String(s).padStart(2, '0');
  }

  function getAlbum(id) {
    return DATA[id] || null;
  }

  function getTrack(albumId, trackId) {
    const album = getAlbum(albumId);
    if (!album) return null;
    return album.tracks.find(track => track.id === trackId) || null;
  }

  function getTrackById(trackId) {
    for (const albumId in DATA) {
      const track = getTrack(albumId, trackId);
      if (track) return { albumId, track };
    }
    return null;
  }

  function getAudioUrl(src) {
    return new URL(src, audioBase || location.href).href;
  }

  function getArtworkUrl(album) {
    return new URL(album.art, themeBase || location.href).href;
  }

  function updateSeek() {
    if (isSeeking || !audio || !globalCurrentAudio) return;
    const duration = audio.duration || 0;
    const currentTime = audio.currentTime || 0;
    const percent = duration ? (currentTime / duration) * 100 : 0;
    $player.find('.seek_progress').css('width', percent + '%');
    $player.find('.time_current').text(formatTime(currentTime));
    $player.find('.time_duration').text(formatTime(duration));
  }

  function resetSeek() {
    $player.find('.seek_progress').css('width', '0%');
    $player.find('.time_current').text('0:00');
    $player.find('.time_duration').text('0:00');
  }

  // MP3は実際に再生する瞬間までsrcを設定しない。
  // audio要素はプレイヤーに1つだけ置き、再生後は同じ要素を使い続ける。
  function loadTrack(track) {
    if (!audio || !track) return;
    const src = getAudioUrl(track.src);
    if (audio.dataset.loadedSrc === src) return;
    audio.pause();
    audio.removeAttribute('src');
    audio.load();
    audio.dataset.loadedSrc = src;
    audio.src = src;
    audio.preload = 'none';
    audio.load();
  }

  function playAudio() {
    if (!audio || !globalCurrentAudio) return Promise.reject();
    const track = globalCurrentAudio;
    loadTrack(track);
    return audio.play();
  }

  function updateMediaSession() {
    if (!('mediaSession' in navigator) || !globalCurrentAudio || !globalCurrentAlbum) return;
    const album = getAlbum(globalCurrentAlbum);
    if (!album) return;

    navigator.mediaSession.metadata = new MediaMetadata({
      title: globalCurrentAudio.title,
      artist: ARTIST_NAME,
      album: album.title,
      artwork: [{ src: getArtworkUrl(album), sizes: '512x512', type: 'image/png' }]
    });

    navigator.mediaSession.setActionHandler('play', function() {
      playAudio().catch(() => {});
      updateUI();
    });
    navigator.mediaSession.setActionHandler('pause', function() {
      audio.pause();
      updateUI();
    });
    navigator.mediaSession.setActionHandler('previoustrack', function() {
      if (globalCurrentIndex > 0) playTrack(globalCurrentIndex - 1, true);
    });
    navigator.mediaSession.setActionHandler('nexttrack', function() {
      if (globalCurrentIndex < globalCurrentPlaylist.length - 1) playTrack(globalCurrentIndex + 1, true);
    });
  }

  function updatePageHooks() {
    $('.audio-player').find('.play_btn').removeClass('pause');
    $('.audio_list li').removeClass('playing');
    if (!globalCurrentAudio) return;

    const selector = '.play_btn[data-player-track="' + CSS.escape(globalCurrentAudio.id) + '"]';
    $(selector).addClass('pause').closest('li').addClass('playing');
  }

  function updateLibraryState() {
    $('.player_library_track').removeClass('playing pause');
    if (!globalCurrentAudio) return;
    $('.player_library_track[data-player-track="' + CSS.escape(globalCurrentAudio.id) + '"]')
      .addClass('playing')
      .toggleClass('pause', !audio.paused);
  }

  function updateUI() {
    $player.find('.player_play, .player_send').removeClass('pause disabled');
    $player.removeClass('active');

    if (globalCurrentAudio) {
      const album = getAlbum(globalCurrentAlbum);
      $player.addClass('active');
      $player.find('.player_title span').text(globalCurrentAudio.title);
      if (album) $player.find('.play_skin img').attr('src', getArtworkUrl(album));

      if (audio.paused) $player.find('.player_title').addClass('paused');
      else $player.find('.player_title').removeClass('paused');

      if (!audio.paused) $player.find('.player_play').addClass('pause');
      if (globalCurrentIndex === 0) $player.find('.player_send.pre').addClass('disabled');
      if (globalCurrentIndex === globalCurrentPlaylist.length - 1) $player.find('.player_send.next').addClass('disabled');
    } else {
      $player.find('.player_title span').text('No track selected');
    }

    updatePageHooks();
    updateLibraryState();
  }

  function playTrack(index, autoplay) {
    if (!globalCurrentPlaylist.length || index < 0 || index >= globalCurrentPlaylist.length) return;
    if (isTransitioning) return;
    isTransitioning = true;

    if (audio) {
      audio.pause();
      audio.currentTime = 0;
    }

    globalCurrentAudio = globalCurrentPlaylist[index];
    globalCurrentIndex = index;
    resetSeek();
    updateUI();

    if (autoplay === false) {
      isTransitioning = false;
      return;
    }

    playAudio()
      .then(function(){
        isTransitioning = false;
        updateMediaSession();
        updateUI();
      })
      .catch(function(){
        isTransitioning = false;
        updateUI();
      });
  }

  function selectAlbum(albumId, autoplay) {
    const album = getAlbum(albumId);
    if (!album || !album.tracks.length) return;
    globalCurrentAlbum = albumId;
    globalCurrentPlaylist = album.tracks.slice();
    globalCurrentIndex = 0;
    globalIsPlayingAll = true;
    renderLibrary(albumId);
    playTrack(0, autoplay !== false);
  }

  function selectTrack(trackId, autoplay) {
    const result = getTrackById(trackId);
    if (!result) return;
    globalCurrentAlbum = result.albumId;
    globalCurrentPlaylist = DATA[result.albumId].tracks.slice();
    globalCurrentIndex = globalCurrentPlaylist.findIndex(track => track.id === trackId);
    globalIsPlayingAll = false;
    renderLibrary(result.albumId);
    playTrack(globalCurrentIndex, autoplay !== false);
  }

  function renderLibrary(albumId) {
    if (!libraryAlbum) libraryAlbum = albumId;
    const album = getAlbum(albumId || libraryAlbum || Object.keys(DATA)[0]);
    if (!album) return;
    libraryAlbum = albumId || libraryAlbum;

    const $select = $player.find('.player_album_select');
    if ($select.val() !== libraryAlbum) $select.val(libraryAlbum);

    const $list = $player.find('.player_library_list').empty();
    album.tracks.forEach(function(track, index){
      const $item = $('<button type="button" class="player_library_track">');
      $item.attr('data-player-track', track.id);
      $('<span class="player_library_num">').text(String(index + 1).padStart(2,'0')).appendTo($item);
      $('<span class="player_library_title">').text(track.title).appendTo($item);
      $('<span class="player_library_icon">').appendTo($item);
      $list.append($item);
    });
    updateLibraryState();
  }

  function initLibrary() {
    if (!$player.length) return;
    if (!$player.find('.player_library').length) {
      const $library = $(
        '<div class="player_library">' +
          '<div class="player_library_head"><span>MUSIC LIBRARY</span><button type="button" class="player_library_close" aria-label="Close">×</button></div>' +
          '<div class="player_library_select_wrap"><select class="player_album_select" aria-label="Select album"></select></div>' +
          '<div class="player_library_list"></div>' +
        '</div>'
      );
      const $toggle = $('<button type="button" class="player_library_toggle">LIBRARY</button>');
      $player.append($library);
      $player.find('.inner_player').append($toggle);
    }

    const $select = $player.find('.player_album_select').empty();
    Object.keys(DATA).forEach(function(albumId){
      const album = DATA[albumId];
      $('<option>').val(albumId).text(album.label + ' — ' + album.title).appendTo($select);
    });
    libraryAlbum = globalCurrentAlbum || Object.keys(DATA)[0];
    renderLibrary(libraryAlbum);

    $player.on('click', '.player_library_toggle', function(){
      $player.addClass('library-open active');
      renderLibrary(globalCurrentAlbum || libraryAlbum);
    });
    $player.on('click', '.player_library_close', function(){
      $player.removeClass('library-open');
    });
    $select.on('change', function(){
      renderLibrary($(this).val());
    });
    $player.on('click', '.player_library_track', function(){
      selectTrack($(this).data('player-track'), true);
    });
  }

  function getSeekRatio($bar, clientX) {
    const offset = clientX - $bar.offset().left;
    const width = $bar.width();
    return Math.min(Math.max(offset / width, 0), 1);
  }

  function initPlayer() {
    if (!$player.length || !audio) return;

    $player.find('.seek_bar').on('mousedown', function(e){
      if (!globalCurrentAudio || !isFinite(audio.duration)) return;
      const $bar = $(this); isSeeking = true;
      const set = x => { const r=getSeekRatio($bar,x); audio.currentTime=r*audio.duration; $bar.find('.seek_progress').css('width',(r*100)+'%'); };
      set(e.pageX);
      $(document).on('mousemove.seek', e => set(e.pageX));
      $(document).on('mouseup.seek', function(){ $(document).off('.seek'); isSeeking=false; });
    });

    $player.find('.seek_bar').on('touchstart', function(e){
      if (!globalCurrentAudio || !isFinite(audio.duration)) return;
      const $bar=$(this); isSeeking=true;
      const set=x=>{const r=getSeekRatio($bar,x);audio.currentTime=r*audio.duration;$bar.find('.seek_progress').css('width',(r*100)+'%');};
      set(e.originalEvent.touches[0].pageX);
      $(document).on('touchmove.seek', e=>set(e.originalEvent.touches[0].pageX));
      $(document).on('touchend.seek', function(){ $(document).off('.seek'); isSeeking=false; });
    });

    $player.find('.player_play').on('click', function(){
      if (!globalCurrentAudio) return;
      if (!audio.paused) { audio.pause(); globalIsPlayingAll=false; }
      else { playAudio().catch(()=>{}); globalIsPlayingAll=true; updateMediaSession(); }
      updateUI();
    });

    $player.find('.player_send.pre').on('click', function(){
      if (globalCurrentIndex > 0) { globalIsPlayingAll=true; playTrack(globalCurrentIndex-1,true); }
    });
    $player.find('.player_send.next').on('click', function(){
      if (globalCurrentIndex < globalCurrentPlaylist.length-1) { globalIsPlayingAll=true; playTrack(globalCurrentIndex+1,true); }
    });
    $player.find('.player_reset').on('click', function(){
      audio.pause(); audio.currentTime=0; audio.removeAttribute('src'); delete audio.dataset.loadedSrc; audio.load();
      globalCurrentAudio=null; globalCurrentAlbum=null; globalCurrentPlaylist=[]; globalCurrentIndex=0; globalIsPlayingAll=false;
      resetSeek();
      if ('mediaSession' in navigator) navigator.mediaSession.metadata=null;
      updateUI();
    });

    $(document).on('click', '.play_btn[data-player-track]', function(e){
      e.stopPropagation();
      const id=$(this).attr('data-player-track');
      if (globalCurrentAudio && globalCurrentAudio.id===id && !audio.paused) { audio.pause(); globalIsPlayingAll=false; updateUI(); return; }
      if (globalCurrentAudio && globalCurrentAudio.id===id && audio.paused) { playAudio().catch(()=>{}); updateMediaSession(); updateUI(); return; }
      selectTrack(id,true);
    });

    $(document).on('click', '.all_btn[data-player-album]', function(e){
      e.preventDefault();
      selectAlbum($(this).attr('data-player-album'), true);
    });

    $(document).on('click', '.audio-player .all_btn:not([data-player-album])', function(e){
      const aid=$(this).closest('.audio-player').attr('data-player-album');
      if (aid) { e.preventDefault(); selectAlbum(aid,true); }
    });

    $(document).on('click', '.audio-player .audio_list li', function(e){
      if ($(e.target).closest('.play_btn').length) return;
      const id=$(this).find('.play_btn[data-player-track]').attr('data-player-track');
      if (id) selectTrack(id,true);
    });

    $(audio).on('pause', updateUI);
    $(audio).on('play', function(){ updateMediaSession(); updateUI(); });
    $(audio).on('timeupdate loadedmetadata durationchange', updateSeek);
    $(audio).on('ended', function(){
      if (isTransitioning) return;
      if (isRepeat) {
        let next=globalCurrentIndex+1;
        if (next>=globalCurrentPlaylist.length) next=0;
        playTrack(next,true);
      } else {
        globalCurrentAudio=null; globalIsPlayingAll=false; resetSeek();
        if ('mediaSession' in navigator) navigator.mediaSession.metadata=null;
        updateUI();
      }
    });

    initLibrary();
  }

  setInterval(updateSeek, 500);

  document.addEventListener('visibilitychange', function(){
    if (document.visibilityState === 'visible') { isTransitioning=false; updateUI(); }
  });

  $(function(){ initPlayer(); });
})(jQuery);
