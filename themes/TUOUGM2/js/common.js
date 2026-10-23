/* ------------------------------------------------------------------

 common.js
	
------------------------------------------------------------------ */
$(function() {
	$(window).scroll(function () {
		if ($(this).scrollTop() > 10) {			
            $('header').addClass('fix');			
		}else{
			$('header').removeClass('fix');
		}
	});
	
	var body = $(document.body); 
	$('.h_btn').on('click', function(){
		body.toggleClass('open');
    });
	$('.gnav a').on('click', function(){
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
	$('.tab li').click(function() {

		//.index()を使いクリックされたタブが何番目かを調べ、
		//indexという変数に代入します。
		var index = $('.tab li').index(this);

		//コンテンツを一度すべて非表示にし、
		$('.tab_wrap .tab_body').removeClass('mb_select');

		//クリックされたタブと同じ順番のコンテンツを表示します。
		$('.tab_wrap .tab_body').eq(index).addClass('mb_select');
//		var wrapH = $('.tab_wrap .tab_body').eq(index).outerHeight();
//		$('.tab_wrap').css({'height':wrapH});

		//一度タブについているクラスselectを消し、
		$('.tab li').removeClass('select');

		//クリックされたタブのみにクラスselectをつけます。
		$(this).addClass('select');
	});
	$('.tab li:first').click();
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
	parallaxImg();
//	getMemTop();
	updateProgress();
});
$(function() {
//	parallaxImg();
//	getMemTop();
});

$(function () {
	let isAccordion = false;
	function enableAccordion() {
		isAccordion = true;
		$('.m_cnt_body').hide();
		$('.m_cnt_header').removeClass('active');
		$('.m_cnt_header').first().addClass('active');
		$('.m_cnt_body').first().show();
		$('.m_cnt_header')
			.off('click.accordion')
			.on('click.accordion', function () {
				const $header = $(this);
				if ($header.hasClass('active')) {
					return;
				}
				const $body = $header.next('.m_cnt_body');
				$('.m_cnt_header.active').removeClass('active');
				$('.m_cnt_body:visible').stop().slideUp(300);
				$header.addClass('active');
				$body.stop().slideDown(300, function () {
					$('html, body').animate({
						scrollTop: $header.offset().top - 120
					}, 300);
				});
			});
	}
	function disableAccordion() {
		isAccordion = false;
		$('.m_cnt_header')
			.off('click.accordion')
			.removeClass('active');
		$('.m_cnt_body')
			.stop(true, true)
			.show();
	}
	function checkAccordion() {
		if ($(window).width() <= 1170) {
			if (!isAccordion) {
				enableAccordion();
			}
		} else {
			if (isAccordion) {
				disableAccordion();
			}
		}
	}

	checkAccordion();
	let resizeTimer;
	$(window).on('resize', function () {
		clearTimeout(resizeTimer);
		resizeTimer = setTimeout(function () {
			checkAccordion();
		}, 150);
	});
});
//function setModalHeight() {
//  $('.mg_modal').height($('.m_body').outerHeight()) - 90;
//}
$(function(){

	let $items = $();
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
		$('.mg_title').text(
			$target.data('title')
		);
		$('.mg_desc').text(
			$target.data('desc')
		);
		$('.mg_download').attr(
			'href',
			$target.data('download')
		);

	}

	function initSlide(index){
		$('.mg_inner').html(
			createSlide(index)
		);
		currentIndex = index;
		updateMeta(index);
	}

	function slideTo(index,direction){
		if(isAnimating) return;
		isAnimating = true;
		const $inner = $('.mg_inner');
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

	$(
document).on('click','.js-gallery',function(e){

    e.preventDefault();

    $items = $(this)
        .closest('.slide_thumb')
        .find('.js-gallery');

    const index = $items.index(this);

    var windowWidth = $(window).width();

    if(windowWidth > 1170){
        $('.mg_modal').height($('.m_body').outerHeight());
    }

    $('body').addClass('modaal-noscroll_sp');

    initSlide(index);

    $('.mg_modal')
        .stop(true,true)
        .fadeIn(250);

});

	$('.mg_close').on('click',function(){
		$('body').removeClass('modaal-noscroll_sp');

		$('.mg_modal')
			.stop(true,true)
			.fadeOut(250);

	});
	$('.m_nav li').on('click',function(){
		$('body').removeClass('modaal-noscroll_sp');

		$('.mg_modal')
			.stop(true,true)
			.fadeOut(250);

	});

	$('.mg_next').on('click',function(){

		nextSlide();

	});

	$('.mg_prev').on('click',function(){

		prevSlide();

	});

	$(document).on('touchstart','.mg_inner',function(e){

		touchStartX =
			e.originalEvent.touches[0].clientX;

	});

	$(document).on('touchend','.mg_inner',function(e){

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