/** Track details only: playback and Swup navigation remain owned by existing code. */
(function ($) {
  'use strict';
  const config = window.tuougmTrackModalConfig;
  if (!config || typeof $.fn.modaal !== 'function' || typeof window.fetch !== 'function' || typeof window.AbortController !== 'function') return;

  let controller, host, instance, shell, content, status, previous, next, pageLink;
  let active = false, closing = false, sourceLink, tracks = [], index = 0, albumId, locale;
  let savedScroll, savedPath, request, timer, generation = 0, background = [], closePromise, resolveClose;
  const english = () => /^en(?:-|$)/i.test(document.documentElement.lang || '');
  const label = (ja, en) => locale === 'en_US' ? en : ja;
  const focus = element => { if (element) element.focus({ preventScroll: true }); };

  function abortRequest() {
    generation++;
    if (request) request.abort();
    request = null;
    window.clearTimeout(timer);
  }

  function restoreBackground() {
    background.forEach(([element, inert]) => { element.inert = inert; });
    background = [];
  }

  function finishClose() {
    abortRequest();
    restoreBackground();
    active = false;
    closing = false;
    if (location.pathname === savedPath) {
      const target = sourceLink && sourceLink.isConnected ? sourceLink : document.querySelector('.music_page h1');
      if (target && target !== sourceLink) target.tabIndex = -1;
      focus(target);
      window.scrollTo(savedScroll.x, savedScroll.y);
    }
    content.replaceChildren();
    if (resolveClose) resolveClose();
    resolveClose = null;
    closePromise = null;
  }

  function close() {
    if (!active) return Promise.resolve();
    if (closing) return closePromise;
    closing = true;
    abortRequest();
    closePromise = new Promise(resolve => { resolveClose = resolve; });
    $(controller).modaal('close');
    return closePromise;
  }

  function prepare() {
    if (controller) return true;
    host = document.createElement('div');
    host.id = 'tuougm-track-modal-source';
    host.hidden = true;
    host.innerHTML = '<div class="music_track_modal_shell"><div class="music_track_modal_status" role="status" aria-live="polite"></div>' +
      '<div class="music_track_modal_content music_track_page" aria-busy="false"></div>' +
      '<nav class="music_track_modal_nav"><button type="button" data-track-prev></button><button type="button" data-track-next></button></nav>' +
      '<a class="music_track_modal_page"></a></div>';
    controller = document.createElement('button');
    controller.type = 'button';
    controller.hidden = true;
    controller.className = 'tuougm-track-modal-controller';
    document.body.append(host, controller);
    shell = host.firstElementChild;
    content = shell.querySelector('.music_track_modal_content');
    status = shell.querySelector('[role="status"]');
    previous = shell.querySelector('[data-track-prev]');
    next = shell.querySelector('[data-track-next]');
    pageLink = shell.querySelector('.music_track_modal_page');
    previous.addEventListener('click', () => { if (!previous.disabled) load(index - 1); });
    next.addEventListener('click', () => { if (!next.disabled) load(index + 1); });
    $(controller).modaal({
      type: 'inline', content_source: '#' + host.id, custom_class: 'music-track-modal',
      background: '#000', overlay_opacity: 0.85, background_scroll: false,
      before_close: function () {
        closing = true;
        if (!closePromise) closePromise = new Promise(resolve => { resolveClose = resolve; });
        abortRequest();
      },
      after_close: finishClose,
      after_open: function () {
        if (!active || closing) return;
        const wrapper = document.getElementById(this.scope.id);
        const dialog = wrapper.querySelector('[role="dialog"]');
        dialog.setAttribute('aria-modal', 'true');
        dialog.setAttribute('aria-label', label('曲の詳細', 'Track details'));
        focus(content.querySelector('.music_track_heading') || dialog);
      }
    });
    instance = $(controller).data('modaal');
    return !!instance;
  }

  function navigation() {
    const prev = tracks[index - 1], following = tracks[index + 1];
    previous.disabled = !prev;
    next.disabled = !following;
    previous.textContent = 'PREV' + (prev ? ' — ' + prev.title : '');
    next.textContent = 'NEXT' + (following ? ' — ' + following.title : '');
    shell.querySelector('nav').setAttribute('aria-label', label('同じアルバムの曲', 'Tracks in this album'));
    pageLink.href = tracks[index].url;
    pageLink.textContent = label('曲詳細を見る', 'View track details');
  }

  async function load(target) {
    if (!active || closing || !tracks[target]) return;
    abortRequest();
    index = target;
    const serial = generation;
    navigation();
    const busy = document.activeElement && content.contains(document.activeElement);
    content.replaceChildren();
    content.setAttribute('aria-busy', 'true');
    const dialog = shell.closest('[role="dialog"]');
    dialog.removeAttribute('aria-labelledby');
    if (busy) focus(dialog);
    status.classList.remove('is-announcement');
    status.textContent = label('読み込み中…', 'Loading…');
    const abort = new AbortController();
    request = abort;
    let timedOut = false;
    timer = window.setTimeout(() => { timedOut = true; abort.abort(); }, 15000);
    try {
      const url = new URL(config.endpoint, location.href);
      url.searchParams.set('track_id', tracks[index].id);
      url.searchParams.set('album_id', albumId);
      url.searchParams.set('locale', locale);
      const response = await fetch(url, { credentials: 'same-origin', cache: 'no-store', signal: abort.signal,
        headers: config.nonce ? { 'X-WP-Nonce': config.nonce } : {} });
      if (!response.ok) throw new Error('HTTP ' + response.status);
      const data = await response.json();
      if (serial !== generation || !active || closing) return;
      if (String(data.track_id) !== tracks[index].id || String(data.album_id) !== albumId || typeof data.html !== 'string') throw new Error('Invalid detail response');
      const parsed = new DOMParser().parseFromString(data.html, 'text/html');
      // Only server-rendered detail markup is admitted; no document scripts/assets.
      if (parsed.querySelector('script, iframe, object, embed, link, style') || !parsed.querySelector('#music-modal-track-title')) throw new Error('Invalid detail markup');
      content.append(...Array.from(parsed.body.childNodes));
      const heading = content.querySelector('#music-modal-track-title');
      heading.tabIndex = -1;
      dialog.setAttribute('aria-labelledby', heading.id);
      status.classList.add('is-announcement');
      status.textContent = label('表示中：', 'Showing: ') + data.title;
      const wrapper = shell.closest('.modaal-wrapper');
      wrapper.scrollTop = 0;
      focus(heading);
    } catch (error) {
      if (serial !== generation || !active || closing || error.name === 'AbortError' && !timedOut) return;
      status.classList.remove('is-announcement');
      status.textContent = label('曲の詳細を読み込めませんでした。再試行するか、曲のページを開いてください。', 'Could not load track details. Retry or open the track page.');
      const retry = document.createElement('button');
      retry.type = 'button';
      retry.textContent = label('再試行', 'Retry');
      retry.addEventListener('click', () => load(index));
      content.append(retry);
      focus(retry);
    } finally {
      if (serial === generation) {
        window.clearTimeout(timer);
        request = null;
        content.setAttribute('aria-busy', 'false');
      }
    }
  }

  function open(link) {
    if (active || document.querySelector('.modaal-wrapper') || !prepare()) return false;
    const list = link.closest('.music_track_list');
    const album = link.closest('[data-detail-album]');
    if (!list || !album) return false;
    const links = Array.from(list.querySelectorAll('a.music_track_title[data-detail-track]'));
    tracks = links.map(element => ({ id: element.dataset.detailTrack, title: element.textContent.trim(), url: element.href }));
    index = links.indexOf(link);
    if (index < 0 || !tracks.length) return false;
    sourceLink = link;
    savedPath = location.pathname;
    savedScroll = { x: window.scrollX, y: window.scrollY };
    albumId = album.dataset.detailAlbum;
    locale = english() ? 'en_US' : 'ja';
    instance.options.close_text = label('閉じる', 'Close');
    instance.options.close_aria_label = label('閉じる（Esc）', 'Close (Esc)');
    instance.options.accessible_title = label('曲の詳細', 'Track details');
    try {
      $(controller).modaal('open');
      const wrapper = document.getElementById(instance.scope.id);
      if (!wrapper || !instance.scope.is_open) return false;
      active = true;
      closing = false;
      wrapper.querySelector('.modaal-close').setAttribute('aria-label', instance.options.close_aria_label);
      const dialog = wrapper.querySelector('[role="dialog"]');
      dialog.setAttribute('aria-modal', 'true');
      dialog.setAttribute('aria-label', instance.options.accessible_title);
      Array.from(document.body.children).forEach(element => {
        if (element === wrapper || element.id === instance.scope.id + '_overlay') return;
        background.push([element, element.inert]);
        element.inert = true;
      });
      load(index);
      return true;
    } catch (error) {
      restoreBackground();
      active = false;
      if (instance.scope.is_open) $(controller).modaal('close');
      return false;
    }
  }

  // Capture before Swup. Suppress native navigation only after a real modal exists.
  document.addEventListener('click', function (event) {
    const link = event.target.closest && event.target.closest('.music_page .music_track_title[data-detail-track]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download') || link.target && link.target !== '_self') return;
    if (open(link)) { event.preventDefault(); event.stopPropagation(); }
  }, true);

  document.addEventListener('keydown', function (event) {
    if (!active || closing) return;
    const wrapper = document.getElementById(instance.scope.id);
    if (!wrapper || !wrapper.contains(event.target)) return;
    if (event.key === 'Tab') {
      // Modaal handles focus entry, Esc and return; only close its boundary gap.
      const items = Array.from(wrapper.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),[tabindex="0"]')).filter(element => element.getClientRects().length);
      const first = items[0], last = items[items.length - 1];
      if (event.shiftKey && (document.activeElement === first || document.activeElement.tabIndex === -1)) { event.preventDefault(); focus(last); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); focus(first); }
    } else if (!event.metaKey && !event.ctrlKey && !event.altKey && !event.shiftKey && !event.target.closest('input,textarea,select,[contenteditable="true"],[role="slider"]')) {
      if (event.key === 'ArrowLeft' && !previous.disabled) { event.preventDefault(); load(index - 1); }
      if (event.key === 'ArrowRight' && !next.disabled) { event.preventDefault(); load(index + 1); }
    }
  });

  function init() {
    if (!prepare()) return;
    document.querySelectorAll('.music_page .music_track_title[data-detail-track]').forEach(link => link.setAttribute('data-no-swup', ''));
  }
  $(function () {
    init();
    if (window.tuougmSwup) {
      window.tuougmSwup.hooks.on('page:view', init);
      window.tuougmSwup.hooks.on('visit:start', close);
    }
  });
})(jQuery);
