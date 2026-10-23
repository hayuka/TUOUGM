<?php
/**
 * The template for displaying 404 pages (Not Found)
 */
$loc = $_GET['loc'] ?? '';
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
get_header(); ?>

<main id="swup" class="transition-fade main_wrap about_wrap">
<div class="parallax about_top">
<img src="<?php echo get_template_directory_uri(); ?>/img/about_kv.png" alt="" class="pc">
<img src="<?php echo get_template_directory_uri(); ?>/img/about_kv_sp.png" alt="" class="sp">
</div>

<div class="about_inner">
<div class="about_kv">
<h1 data-text="404">404</h1>
<?php if ('en_US' == $locale){ ?>
<p>THIS PAGE DOES NOT EXIST.</p>
<?php }else{ ?>
<p>このページは存在しない。</p>
<?php } ?>
</div>

<section class="def_sec sec_concept">
<div class="inner_1200">
<?php if ('en_US' == $locale){ ?>
<p class="ta_c">NO BLUEPRINT.<br>
NO TURN BACK.</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-player-album="533"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play_en.png" alt="PLAY THE FULL ALBUM"></a></div>
<?php }else{ ?>
<!--<h2 class="h2_sec">CONCEPT</h2>-->
<p class="ta_c">それはエラーじゃない。<br>
まだ“鳴っていない”だけだ。</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-player-album="533"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play.png" alt="アルバムを再生"></a></div>
<?php } ?>
</div>
</section>

</div><!--/about_inner-->



<div class="spark-layer">
  <span class="spark small" style="left:10%; --x-move:30px;"></span>
  <span class="spark medium" style="left:25%; --x-move:-40px;"></span>
  <span class="spark large" style="left:40%; --x-move:60px;"></span>
  <span class="spark small" style="left:55%; --x-move:-20px;"></span>
  <span class="spark medium" style="left:70%; --x-move:50px;"></span>
  <span class="spark large" style="left:85%; --x-move:-60px;"></span>
</div>
</main>
<?php get_footer();?>

