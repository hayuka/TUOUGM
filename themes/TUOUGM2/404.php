<?php
/**
 * The template for displaying 404 pages (Not Found)
 */
$loc = $_GET['loc'];
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
get_header(); ?>

<main class="main_wrap about_wrap">
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
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-album="album01"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play_en.png" alt="PLAY THE FULL ALBUM"></a></div>
<?php }else{ ?>
<!--<h2 class="h2_sec">CONCEPT</h2>-->
<p class="ta_c">それはエラーじゃない。<br>
まだ“鳴っていない”だけだ。</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-album="album01"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play.png" alt="アルバムを再生"></a></div>
<?php } ?>
</div>
</section>

</div><!--/about_inner-->

<div class="player">
<div class="wrap_plyer flex_wrap">
<div class="inner_player flex_wrap_middle">

<div class="player_lr flex_wrap_middle player_l">
<div class="play_skin"><img src="img/sample_skin.png" alt=""></div>
<div class="player_title"><span>No track selected</span></div>
</div>

<div class="flex_wrap_middle plyer_control_wrap">
<div class="player_seek">
<div class="seek_bar"><div class="seek_progress"></div></div>
</div>
<p class="time_current">0:00</p>
<p class="time_duration">0:00</p>
<div class="plyer_control flex_wrap_center flex_wrap_middle">
<div class="player_send pre"></div>
<div class="player_play"></div>
<div class="player_send next"></div>
</div>
</div>

<div class="player_lr player_r">
</div>
<div class="player_reset"></div>
</div>
</div>
</div><!--/player-->

<div class="album_wrap" style="display: none">
<div id="album01" class="album flex_wrap audio-player">
<div class="wrap_alist_skin">
<div class="stiky">
<figure class="all_btn">
<img src="<?php echo get_template_directory_uri(); ?>/img/album1.png" alt="">
<div class="playall"><span></span><span></span><span></span><span></span><span></span></div>
</figure>
<div class="play_al_btn all_btn"></div>
</div>
</div>
<div class="list_music_wrap">
<div class="stiky">
<div class="lm_top flex_wrap_between">
<h3>THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN</h3>
<!--<p class="btn_buy"><a href="https://tuougm.bandcamp.com/album/the-ultimate-sin-of-unbuilt-gunpla-mountain" target="_blank"><span>高音質版<br>はこちら</span>Listen / Buy</a></p>-->
</div>
<ul class="audio_list">
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_01.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>IS THIS EVOLVED OR WRECKED？</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_02.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>いまどきのガンプラ情報量エグい</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_03.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>仕事する暇あっらガンプラ作れ</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_04.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>NO EXCUSE</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_05.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>8UN674</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_06.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>Ever since 1981</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_07.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>ニッパー片手に</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_08.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>色もついてるし、スナップフィットだから接着剤もバテも塗料もガンプラには必要ないってよ</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_09.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>ガンプラ系YouTuberの憂鬱</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_10.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_11.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>PAYBACK</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_12.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>積みプラは罪？</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_13.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>魔改造中毒</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_14.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>Just picture a world ruled by Gunpla</p></div>
</li>
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_15.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>Chase the Ideal Mobile Suit</p></div>
</li>
</ul>
</div>
</div>
</div>
</div>

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

