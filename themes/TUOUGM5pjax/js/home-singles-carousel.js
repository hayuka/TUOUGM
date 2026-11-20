(function() {
  'use strict';

  const mobileCarousel = window.matchMedia('(max-width: 768px)');

  function updateActiveCard(track) {
    if (!mobileCarousel.matches) return;

    const cards = Array.from(track.querySelectorAll('.home_single_card'));
    if (!cards.length) return;

    const trackCenter = track.getBoundingClientRect().left + track.clientWidth / 2;
    let activeCard = cards[0];
    let nearestDistance = Infinity;

    cards.forEach(function(card) {
      const rect = card.getBoundingClientRect();
      const distance = Math.abs(rect.left + rect.width / 2 - trackCenter);
      if (distance < nearestDistance) {
        activeCard = card;
        nearestDistance = distance;
      }
    });

    cards.forEach(function(card) {
      card.classList.toggle('is-active', card === activeCard);
    });
  }

  function initializeTrack(track) {
    if (track.dataset.homeSinglesInitialized) return;
    track.dataset.homeSinglesInitialized = 'true';

    // 新しく表示されたTOPでは、SPの先頭ジャケットから開始する。
    if (mobileCarousel.matches) track.scrollLeft = 0;

    let activeUpdateQueued = false;
    track.addEventListener('scroll', function() {
      if (!mobileCarousel.matches || activeUpdateQueued) return;
      activeUpdateQueued = true;
      window.requestAnimationFrame(function() {
        activeUpdateQueued = false;
        updateActiveCard(track);
      });
    }, { passive: true });

    updateActiveCard(track);
  }

  function initializeCarousels(root) {
    if (root.matches && root.matches('[data-home-singles-track]')) {
      initializeTrack(root);
    }
    root.querySelectorAll('[data-home-singles-track]').forEach(initializeTrack);
  }

  initializeCarousels(document);

  // PJAXで後から追加されるTOPのカルーセルも、一度だけ初期化する。
  const observer = new MutationObserver(function(records) {
    records.forEach(function(record) {
      record.addedNodes.forEach(function(node) {
        if (node.nodeType === Node.ELEMENT_NODE) initializeCarousels(node);
      });
    });
  });
  observer.observe(document.documentElement, { childList: true, subtree: true });

  let pointerStartX = null;
  let swipedCarousel = false;
  let suppressCardClick = false;

  document.addEventListener('pointerdown', function(event) {
    if (!mobileCarousel.matches || !event.target.closest('[data-home-singles-track]')) return;
    pointerStartX = event.clientX;
    swipedCarousel = false;
  }, { passive: true });

  document.addEventListener('pointermove', function(event) {
    if (pointerStartX === null || !mobileCarousel.matches) return;
    if (Math.abs(event.clientX - pointerStartX) > 10) swipedCarousel = true;
  }, { passive: true });

  function finishPointer() {
    if (swipedCarousel) {
      suppressCardClick = true;
      window.setTimeout(function() { suppressCardClick = false; }, 500);
    }
    pointerStartX = null;
    swipedCarousel = false;
  }

  document.addEventListener('pointerup', finishPointer, { passive: true });
  document.addEventListener('pointercancel', finishPointer, { passive: true });

  // タッチスワイプ直後にブラウザがclickを送っても、再生操作に流さない。
  document.addEventListener('click', function(event) {
    if (!suppressCardClick || !event.target.closest('.home_single_play')) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    suppressCardClick = false;
  }, true);

  document.addEventListener('click', function(event) {
    const button = event.target.closest('[data-home-singles-direction]');
    if (!button) return;

    const carousel = button.closest('.home_singles');
    const track = carousel && carousel.querySelector('[data-home-singles-track]');
    if (!track) return;

    const direction = Number(button.getAttribute('data-home-singles-direction'));
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    track.scrollBy({
      left: direction * track.clientWidth * 0.85,
      behavior: reduceMotion ? 'auto' : 'smooth'
    });
  });
})();
