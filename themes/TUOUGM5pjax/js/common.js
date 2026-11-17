(function ($) {
/* ------------------------------------------------------------------

 common.js
	
------------------------------------------------------------------ */
var membersBootTimers = [];
var membersBootSequence = 0;
var membersAccordionRoot = null;
var membersAccordionMode = null;

function initMembersAccordion() {
	var root = document.querySelector('.member_inner');
	var $headers = $('.m_cnt_header');
	var $bodies = $('.m_cnt_body');
	var accordionEnabled = $(window).width() <= 1170;

	if (root !== membersAccordionRoot) {
		membersAccordionRoot = root;
		membersAccordionMode = null;
	}

	if (!$headers.length || !$bodies.length) {
		membersAccordionMode = null;
		return;
	}

	if (!accordionEnabled) {
		$headers.off('click.tuougmAccordion').removeClass('active');
		$bodies.stop(true, true).show();
		membersAccordionMode = false;
		return;
	}

	if (membersAccordionMode === true) {
		return;
	}

	$bodies.hide();
	$headers.removeClass('active');
	$headers.first().addClass('active');
	$bodies.first().show();
	$headers.off('click.tuougmAccordion').on('click.tuougmAccordion', function() {
		var $header = $(this);
		if ($header.hasClass('active')) {
			return;
		}

		var $body = $header.next('.m_cnt_body');
		$headers.filter('.active').removeClass('active');
		$bodies.filter(':visible').stop().slideUp(300);
		$header.addClass('active');
		$body.stop().slideDown(300, function() {
			$('html, body').animate({
				scrollTop: $header.offset().top - 120
			}, 300);
		});
	});
	membersAccordionMode = true;
}

window.tuougmInitMembersPage = function() {
	// Cancel any pending sequence before looking for the current page's elements.
	membersBootSequence++;
	membersBootTimers.forEach(function(timer) {
		window.clearTimeout(timer);
	});
	membersBootTimers = [];

	var root = document.querySelector('.member_inner');
	var status = root ? root.querySelector('.boot-screen .status') : null;
	var passWrap = root ? root.querySelector('.pass-wrap') : null;
	var boot = root ? root.querySelector('.boot-screen') : null;

	if (status && passWrap && boot) {
		var sequence = membersBootSequence;
		membersBootTimers.push(window.setTimeout(function() {
			if (sequence === membersBootSequence && root.isConnected && status.isConnected) {
				status.textContent = 'AUTHORIZATION PORT OPEN';
			}
		}, 500));

		membersBootTimers.push(window.setTimeout(function() {
			if (sequence === membersBootSequence && root.isConnected && boot.isConnected && passWrap.isConnected) {
				boot.style.display = 'none';
				passWrap.style.display = 'block';
			}
		}, 1000));
	}

	initMembersAccordion();
};

$(function() {
	$(window).scroll(function () {
		if ($(this).scrollTop() > 10) {			
            $('header').addClass('fix');			
		}else{
			$('header').removeClass('fix');
		}
	});
	
	var body = $(document.body); 
	$(document).off('click.tuougmMenu', '.h_btn').on('click.tuougmMenu', '.h_btn', function(){
		body.toggleClass('open');
    });
	$(document).off('click.tuougmNav', '.gnav a').on('click.tuougmNav', '.gnav a', function(){
		body.removeClass('open');
	});
	
	$('.modal').modaal({
		animation_speed: 100,
        background: '#000',
        overlay_opacity: .8,
		type: 'video',
		after_open: function() {
			$('body').addClass('modaal-noscroll');
			$('body').removeClass('is-noscroll');
		},
	});
	 $(window).on('scroll', function() {
        parallaxImg();
    });
	function initTabs() {
		$('.tab li').off('click.tuougmTab').on('click.tuougmTab', function() {
			var index = $('.tab li').index(this);
			$('.tab_wrap .tab_body').removeClass('mb_select');
			$('.tab_wrap .tab_body').eq(index).addClass('mb_select');
			$('.tab li').removeClass('select');
			$(this).addClass('select');
		});
		$('.tab li:first').trigger('click');
	}

	window.tuougmInitPage = function() {
		parallaxImg();
		updateProgress();
		initTabs();
	};
});
function parallaxImg() {
	var parallaxSpeed = 0.1;
	var windowWidth = $(window).width();
	if(windowWidth > 1023){
		parallaxSpeed = 0.2;
	}
	
	let img = $('.parallax');
	img.each(function() {
		let elemPos = $(this).offset().top;
		let scroll = $(window).scrollTop();
		let windowHeight = $(window).height();
		let adjust = -10;
		//要素が画面内に入ってすぐではなく、そこから120上に来たら発動するため

		//let parallaxSpeed = 0.1;
		//画像を動かす量が多すぎると、はみ出してします可能性がある為。この値を大きくすれば、画像が動く量が大きくなる

		if (scroll > elemPos - windowHeight + adjust) {
			$(this).children('img').css({'top': (scroll - elemPos + adjust) * parallaxSpeed + 'px'});
			//スクロール量から要素の位置を引き、調整用の数値をかけ、文字列'px'を足したものをtopに代入
		}
	});
}
function getMemTop() {
	if($('.member_top').length){
		var mth = $('.member_top').outerHeight();
		var mtih = $('.member_inner').outerHeight();
		var mth = mtih * -1;
		$('.member_inner').css({'margin-top':mth});
		$('.member_top').css({'height':mtih});
	}
}
function updateProgress() {
  var total = $('.m_nav li').length;
  var cleared = $('.m_index_unlocked').length;

  var percent = total ? (cleared / total) * 100 : 0;

  $('.l_progress-bar').css('width', percent + '%');
}
$(document).ready(function(){
	window.tuougmInitPage();
	window.tuougmInitMembersPage();
});
$(function() {
//	parallaxImg();
//	getMemTop();
});

$(function () {
	var resizeTimer;
	$(window).off('resize.tuougmAccordion').on('resize.tuougmAccordion', function () {
		window.clearTimeout(resizeTimer);
		resizeTimer = window.setTimeout(function () {
			initMembersAccordion();
		}, 150);
	});
});
//function setModalHeight() {
//  $('.mg_modal').height($('.m_body').outerHeight()) - 90;
//}
$(function(){

	let $items = $();
	let $activeModal = $();
	let currentIndex = 0;
	let isAnimating = false;
	let touchStartX = 0;
	let touchEndX = 0;
	function createSlide(index){
		const $target = $items.eq(index);

		return $(`
			<div class="mg_slide">
			<div class="mg_m_figwrap">
			<figure class="mg_img">
			<img src="${$target.data('img')}" alt="">
			</figure>
			</div>
			<div class="mg_cnt">
			<p class="mg_title">${$target.data('title')}</p>
			<p class="mg_desc">${$target.data('desc')}</p>
			</div>
			</div>
		`);

	}

	function updateMeta(index){
		const $target = $items.eq(index);
		$activeModal.find('.mg_title').text(
			$target.data('title')
		);
		$activeModal.find('.mg_desc').text(
			$target.data('desc')
		);
		$activeModal.find('.mg_download').attr(
			'href',
			$target.data('download')
		);

	}

	function initSlide(index, $modal){
		$activeModal = $modal;
		$activeModal.find('.mg_inner').html(
			createSlide(index)
		);
		currentIndex = index;
		updateMeta(index);
	}

	function slideTo(index,direction){
		if(isAnimating || !$activeModal.length) return;
		isAnimating = true;
		const $inner = $activeModal.find('.mg_inner');
		const $newSlide = createSlide(index);
		updateMeta(index);
		if(direction === 'next'){
			$inner.append($newSlide);
			$inner.css({
				transform:'translateX(0)'
			});
			requestAnimationFrame(function(){
				$inner.css({
					transition:'transform .35s ease',
					transform:'translateX(-100%)'
				});
			});

			$inner.one('transitionend',function(){
				$inner.html($newSlide);
				$inner.css({
					width:'100%',
					transition:'none',
					transform:'translateX(0)'
				});
				currentIndex = index;
				isAnimating = false;
			});

		}else{

			$inner.prepend($newSlide);

			$inner.css({
				transition:'none',
				transform:'translateX(-100%)'
			});

			requestAnimationFrame(function(){

				$inner.css({
					transition:'transform .35s ease',
					transform:'translateX(0)'
				});

			});

			$inner.one('transitionend',function(){

				$inner.html($newSlide);

				$inner.css({
					width:'100%',
					transition:'none',
					transform:'translateX(0)'
				});

				currentIndex = index;
				isAnimating = false;

			});

		}

	}

	function nextSlide(){

		let next = currentIndex + 1;

		if(next >= $items.length){
			next = 0;
		}

		slideTo(next,'next');

	}

	function prevSlide(){

		let prev = currentIndex - 1;

		if(prev < 0){
			prev = $items.length - 1;
		}

		slideTo(prev,'prev');

	}

	$(document).off('click.tuougmGalleryOpen', '.js-gallery').on('click.tuougmGalleryOpen', '.js-gallery', function(e){

    e.preventDefault();

    $items = $(this)
        .closest('.slide_thumb')
        .find('.js-gallery');
	$activeModal = $(this)
		.closest('.sec_m_cnt')
		.find('.mg_modal')
		.first();
	if (!$activeModal.length) {
		return;
	}

    const index = $items.index(this);

    var windowWidth = $(window).width();

    if(windowWidth > 1170){
		$activeModal.height($('.m_body').outerHeight());
    }

    $('body').addClass('modaal-noscroll_sp');

    initSlide(index, $activeModal);

    $activeModal
        .stop(true,true)
        .fadeIn(250);

});

	$(document).off('click.tuougmGalleryClose', '.mg_close').on('click.tuougmGalleryClose', '.mg_close', function(){
		var $modal = $(this).closest('.mg_modal');
		$('body').removeClass('modaal-noscroll_sp');

		$modal
			.stop(true,true)
			.fadeOut(250);

	});
	$(document).off('click.tuougmGalleryNav', '.m_nav li').on('click.tuougmGalleryNav', '.m_nav li', function(){
		$('body').removeClass('modaal-noscroll_sp');

		$('.mg_modal:visible')
			.stop(true,true)
			.fadeOut(250);

	});

	$(document).off('click.tuougmGalleryNext', '.mg_next').on('click.tuougmGalleryNext', '.mg_next', function(){
		$activeModal = $(this).closest('.mg_modal');

		nextSlide();

	});

	$(document).off('click.tuougmGalleryPrev', '.mg_prev').on('click.tuougmGalleryPrev', '.mg_prev', function(){
		$activeModal = $(this).closest('.mg_modal');

		prevSlide();

	});

	$(document).off('touchstart.tuougmGallerySwipe', '.mg_inner').on('touchstart.tuougmGallerySwipe', '.mg_inner', function(e){
		$activeModal = $(this).closest('.mg_modal');

		touchStartX =
			e.originalEvent.touches[0].clientX;

	});

	$(document).off('touchend.tuougmGallerySwipe', '.mg_inner').on('touchend.tuougmGallerySwipe', '.mg_inner', function(e){

		touchEndX =
			e.originalEvent.changedTouches[0].clientX;

		const diff =
			touchStartX - touchEndX;

		if(Math.abs(diff) < 50){
			return;
		}

		if(diff > 0){
			nextSlide();
		}else{
			prevSlide();
		}

	});

});
$(window).resize(function(){
//	getMemTop();
//	setModalHeight();
});
})(jQuery);
