(function($){
  'use strict';

  /*
   * TUOUGM Lyrics Scroll
   *
   * audio.js は変更せず、
   * 既存プレイヤーの以下の情報だけを利用する。
   *
   * .player
   * .player_lyrics_content
   * .seek_progress
   *
   * audio.js が seek_progress の幅を再生位置に合わせて更新しているため、
   * その進行率を使って歌詞全体を曲の長さに合わせてスクロールする。
   */

  const SELECTOR = {
    player: '.player',
    lyrics: '.player_lyrics_content',
    progress: '.seek_progress',
    currentTime: '.time_current',
    duration: '.time_duration'
  };

  let lastLyricsText = null;
  let lastProgress = 0;
  let initialized = false;

  // 手動スクロール時の制御
  let manualScrollPaused = false;
  let resumeTimer = null;
  let lastScrollTop = 0;
  let isProgrammaticScroll = false;

  /**
   * 歌詞のスクロール位置を先頭へ戻す。
   */
  function resetLyricsScroll() {
    const $lyrics = $(SELECTOR.lyrics);
    if (!$lyrics.length) return;

    $lyrics.scrollTop(0);
    lastProgress = 0;
    lastScrollTop = 0;
    manualScrollPaused = false;

    if (resumeTimer) {
      window.clearTimeout(resumeTimer);
      resumeTimer = null;
    }
  }

  /**
   * seek_progress の幅から現在の再生進行率を取得。
   *
   * audio.js:
   *   currentTime / duration * 100
   *
   * をそのまま利用する。
   */
  function getProgress() {
    const $progress = $(SELECTOR.progress).first();

    if (!$progress.length) return 0;

    const width = parseFloat($progress.css('width')) || 0;
    const parentWidth = $progress.parent().width() || 0;

    if (!parentWidth) return 0;

    return Math.min(Math.max(width / parentWidth, 0), 1);
  }

  /**
   * mm:ss / hh:mm:ss を秒へ変換。
   */
  function parseTime(timeText) {
    if (!timeText) return 0;

    const parts = timeText.split(':').map(Number);

    if (parts.some(Number.isNaN)) return 0;

    if (parts.length === 2) {
      return parts[0] * 60 + parts[1];
    }

    if (parts.length === 3) {
      return parts[0] * 3600 + parts[1] * 60 + parts[2];
    }

    return 0;
  }

  /**
   * 現在の進行率に合わせて歌詞をスクロール。
   */
  function updateLyricsScroll() {
    const $player = $(SELECTOR.player);
    const $lyrics = $(SELECTOR.lyrics);

    if (!$player.length || !$lyrics.length) return;

    /*
     * プレイヤーが再生中でない場合は位置を保持。
     * audio.js の .player.active を利用する。
     */
    if (!$player.hasClass('active')) return;

    // ユーザーが手動スクロールしている間は自動スクロールしない
    if (manualScrollPaused) return;

    const scrollHeight = $lyrics.prop('scrollHeight');
    const clientHeight = $lyrics.innerHeight();
    const maxScroll = Math.max(scrollHeight - clientHeight, 0);

    if (maxScroll <= 0) return;

    const progress = getProgress();

    /*
     * 曲の冒頭20秒は歌詞をスクロールさせない。
     *
     * audio.js が表示している .time_current / .time_duration から
     * 実際の再生時間を取得するので、曲ごとの長さに関係なく
     * 「最初の20秒だけ停止 → その後、曲終了までに歌詞全体をスクロール」
     * という動きになる。
     */
    const HOLD_SECONDS = 18;

    const currentText = $(SELECTOR.currentTime).first().text().trim();
    const durationText = $(SELECTOR.duration).first().text().trim();

    const currentSeconds = parseTime(currentText);
    const durationSeconds = parseTime(durationText);

    let lyricProgress = progress;

    const END_HOLD_SECONDS = 5;

if (durationSeconds > HOLD_SECONDS + END_HOLD_SECONDS) {
  if (currentSeconds <= HOLD_SECONDS) {
    lyricProgress = 0;
  } else if (currentSeconds >= durationSeconds - END_HOLD_SECONDS) {
    lyricProgress = 1;
  } else {
    lyricProgress = Math.min(
      Math.max(
        (currentSeconds - HOLD_SECONDS) /
          (durationSeconds - HOLD_SECONDS - END_HOLD_SECONDS),
        0
      ),
      1
    );
  }
}

    const target = maxScroll * lyricProgress;
	const current = $lyrics.scrollTop();
	const diff = target - current;
	const next = current + diff * 0.08;

    /*
     * requestAnimationFrame ではなく直接指定。
     * seek_progress 自体が audio.js の timeupdate / 500ms 更新なので、
     * 余計なアニメーションループを持たせない。
     */
    // 自動スクロールによるscrollイベントと手動操作を区別する
    isProgrammaticScroll = true;
    $lyrics.scrollTop(next);
    lastScrollTop = next;

    window.setTimeout(function(){
      isProgrammaticScroll = false;
    }, 50);

    lastProgress = lyricProgress;
  }

  /**
   * 歌詞本文が切り替わったらスクロール位置をリセット。
   *
   * audio.js の renderLyrics() が .text(track.lyrics) を実行するため、
   * MutationObserver で変更を検知する。
   */
  /**
   * 歌詞を手動スクロールしたら自動スクロールを一時停止。
   * 操作が3秒止まったら、自動スクロールへ復帰する。
   */
  function handleManualScroll() {
    const $lyrics = $(SELECTOR.lyrics);
    if (!$lyrics.length) return;

    const currentScrollTop = $lyrics.scrollTop();

    // lyric.js自身のscrollTop変更は手動操作として扱わない
    if (isProgrammaticScroll) {
      lastScrollTop = currentScrollTop;
      return;
    }

    // 実際に位置が変わった場合だけ手動操作と判定
    if (Math.abs(currentScrollTop - lastScrollTop) < 1) return;

    manualScrollPaused = true;
    lastScrollTop = currentScrollTop;

    if (resumeTimer) {
      window.clearTimeout(resumeTimer);
    }

    resumeTimer = window.setTimeout(function(){
      manualScrollPaused = false;
      resumeTimer = null;
    }, 3000);
  }

  function observeLyrics() {
    const target = document.querySelector(SELECTOR.lyrics);

    if (!target) return;

    const observer = new MutationObserver(function(){
      const currentText = target.textContent || '';

      if (currentText === lastLyricsText) return;

      lastLyricsText = currentText;
      resetLyricsScroll();
    });

    observer.observe(target, {
      childList: true,
      characterData: true,
      subtree: true
    });

    lastLyricsText = target.textContent || '';
  }

  /**
   * プレイヤーDOMが後から生成されるケースにも対応。
   */
  function init() {
    if (initialized) return;

    const lyrics = document.querySelector(SELECTOR.lyrics);
    if (!lyrics) return;

    initialized = true;
    observeLyrics();

    // 手動スクロールを検知
    $(SELECTOR.lyrics).on('scroll.tuougmLyrics', handleManualScroll);

    /*
     * audio.js が seek_progress を更新する間隔に合わせる。
     */
    window.setInterval(updateLyricsScroll, 100);
  }

  /*
   * プレイヤーがページロード後に生成される可能性があるため、
   * 少し遅延して初期化を試行する。
   */
  $(function(){
    init();

    if (!initialized) {
      const observer = new MutationObserver(function(){
        init();

        if (initialized) {
          observer.disconnect();
        }
      });

      observer.observe(document.body, {
        childList: true,
        subtree: true
      });
    }
  });

})(jQuery);
