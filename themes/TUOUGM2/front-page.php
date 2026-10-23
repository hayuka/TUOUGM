<?php 
$loc = $_GET['loc'];
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
?>
<?php get_header();?>
<main class="main_wrap">
<div class="kv">
<p class="main_pic"><img src="<?php echo get_template_directory_uri(); ?>/img/kv.webp" alt="ガンプラ カスタム機体 バンドビジュアル" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/kv_sp.webp" alt="ガンプラ カスタム機体 バンドビジュアル" class="sp"></p>
<div class="kv_catch">
<?php if ('en_US' == $locale){ ?>
<h1 class="h1_kv" data-text="THE ULTIMATE SIN OF UNBUILT GUNPLA">THE ULTIMATE SIN OF UNBUILT GUNPLA</h1>
<p>An Industrial Metal Project<br class="sp">Inspired by Gunpla</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-album="album01"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play_en.webp" alt="PLAY THE FULL ALBUM"></a></div>
<?php }else{ ?>
<h1 class="h1_kv" data-text="罪プラの逆襲">罪プラの逆襲</h1>
<p>ガンプラ × インダストリアルメタルの<br class="sp">音楽プロジェクト</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-album="album01"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play.webp" alt="アルバムを再生"></a></div>
<?php } ?>
</div>
</div>

<div class="cta">
<div class="inner_cta">
<?php if ('en_US' == $locale){ ?>
<p><img src="<?php echo get_template_directory_uri(); ?>/img/share_en.webp" alt="罪プラの逆襲、開幕"></p>
<?php }else{ ?>
<p><img src="<?php echo get_template_directory_uri(); ?>/img/share.webp" alt="罪プラの逆襲、開幕"></p>
<?php } ?>
<ul class="flex_wrap_center">
<li>
<div class="sns_button twitter">
<a href="https://twitter.com/intent/tweet?text=罪プラの逆襲、開幕。%0Ahttps://tuougm.zakubaka.com/" title="Tweet" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
</div>
</li>
<li>
<div class="sns_button facebook">
<a href="https://www.facebook.com/sharer/sharer.php?u=https%3A%2F%2Ftuougm.zakubaka.com%2F" title="fb" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-facebook-f"></i></a>
</div>
</li>
<li>
<div class="sns_button line">
<a href="https://social-plugins.line.me/lineit/share?url=https://tuougm.zakubaka.com/" title="fb" target="_blank"><i class="fa-brands fa-line"></i></a>
</div>
</li>
</ul>
</div>
</div>

<section id="anc04" class="def_sec sec_goods">
<div class="inner_1200">
<h2 class="h2_sec">GOODS</h2>
<p class="sub_ttl_info">Official band merch</p>
<ul class="flex_wrap list_goods">
<li>
<a href="https://suzuri.jp/zakubaka/20056056/t-shirt/s/black" target="_blank">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/goods04.webp" alt=""></figure>
<p>NOT DEAD Tシャツ</p>
</a>
</li>
<li>
<a href="https://suzuri.jp/zakubaka/20056165/t-shirt/l/white" target="_blank">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/goods05.webp" alt=""></figure>
<p>NOT DEAD Tシャツ 白</p>
</a>
</li>
<li>
<a href="https://suzuri.jp/zakubaka/19935950/t-shirt/l/black" target="_blank">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/goods01.webp" alt=""></figure>
<p>ヘビーウェイトTシャツ</p>
</a>
</li>
<li>
<a href="https://suzuri.jp/zakubaka/19937029/hoodie/s/black" target="_blank">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/goods02.webp" alt=""></figure>
<p>ビッグシルエットフーディー</p>
</a>
</li>
<li>
<a href="https://suzuri.jp/zakubaka/19962928/united-athle-cotton-twill-low-cap-for-shop/f/black" target="_blank">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/goods03.webp" alt=""></figure>
<p>ワークキャップ</p>
</a>
</li>
</ul>
<p class="btn"><a href="https://suzuri.jp/zakubaka/products" target="_blank">BUY NOW</a></p>
</div>
</section>

<section id="anc01" class="def_sec">
<div class="inner_1200">
<p class="logo_big"><img src="<?php echo get_template_directory_uri(); ?>/img/logo_big.webp" alt=""></p>
<h2 class="h2_sec">ABOUT</h2>
<?php if ('en_US' == $locale){ ?>
<p class="ta_c">THE ULTIMATE SIN OF <br class="sp">UNBUILT GUNPLA MOUNTAIN<br>
is the world’s first “Gunpla Band”,<br class="sp">born from backgrounds in hardcore punk,<br>
thrash metal, and modern heavy sounds.<br>
We turn the common struggles of Gunpla builders<br class="sp">
into aggressive, mechanical sound.<br>
This is not just music.<br class="sp">This is a project for builders,<br>
for those who understand the weight of unbuilt kits.<br>
If you love music and Gunpla, this will hit you.</p>
<?php }else{ ?>
<p class="ta_c">THE ULTIMATE SIN OF <br class="sp">UNBUILT GUNPLA MOUNTAIN は <br>
hard core punk, thrash metal, <br class="sp">new core などのバックグラウンドを持つ<br>
世界初の“ガンプラバンド”。<br>
ガンプラあるあるをヘビーなサウンドに乗せて<br class="sp">叫ぶガンプラモデラー応援バンドです。<br>
音楽好きでガンプラ好きには<br class="sp">必ず刺さる楽曲を現在制作・公開中！</p>
<?php } ?>
<p class="btn btn_t_about"><a href="<?php echo get_bloginfo('url'); ?>/about">ABOUT</a></p>
</div>
</section>


<?php if ('en_US' == $locale){ ?>
<section class="sec_info def_sec">
<div class="inner_1200">
<h2 class="ttl_sec_info"><span>LATEST SIGNAL</span></h2>
<p class="sub_ttl_info">Updating with the latest releases, videos, and articles</p>
<div class="frame">
<a href="<?php echo get_bloginfo('url'); ?>/archives/gunpla-metal-album-tuougm">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/top_info@2x.webp" alt=""></figure>
<div class="frame_cnt">
<h3>Industrial Metal × Gunpla Album – OUT NOW</h3>
</div>
</a>
</div>
<p class="btn"><a href="<?php echo get_bloginfo('url'); ?>/archives">ARCHIVES</a></p>
</div>
</section>
<?php }else{ ?>
<section class="sec_info def_sec">
<div class="inner_1200">
<h2 class="ttl_sec_info"><span>LATEST SIGNAL</span></h2>
<p class="sub_ttl_info">最新のリリース・動画・コラムを更新中</p>
<div class="frame">
<a href="<?php echo get_bloginfo('url'); ?>/archives/gunpla-metal-album-tuougm">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/top_info@2x.webp" alt=""></figure>
<div class="frame_cnt">
<h3>ガンプラ×インダストリアルメタルアルバム爆誕</h3>
</div>
</a>
</div>
<p class="btn"><a href="<?php echo get_bloginfo('url'); ?>/archives">ARCHIVESを見る</a></p>
</div>
</section>
<?php } ?>

<section id="anc03" class="def_sec">

<div class="player">
<div class="wrap_plyer flex_wrap">
<div class="inner_player flex_wrap_middle">

<div class="player_lr flex_wrap_middle player_l">
<div class="play_skin"><img src="img/sample_skin.webp" alt=""></div>
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

<div class="inner_1200 musics">
<h2 class="h2_sec">MUSIC</h2>

<h3 class="h3_music">ALBUM</h3>
<div class="album_wrap">
<div id="album01" class="album flex_wrap audio-player">
<div class="wrap_alist_skin">
<div class="stiky">
<figure class="all_btn">
<img src="<?php echo get_template_directory_uri(); ?>/img/album1.webp" alt="">
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

<h3 class="h3_music">SINGLES</h3>
<div class="album_wrap">

<div id="single01" class="album flex_wrap audio-player">
<div class="wrap_alist_skin">
<div class="stiky">
<figure class="all_btn">
<img src="<?php echo get_template_directory_uri(); ?>/img/single3.png" alt="">
<div class="playall"><span></span><span></span><span></span><span></span><span></span></div>
</figure>
<div class="play_al_btn all_btn"></div>
</div>
</div>
<div class="list_music_wrap">
<div class="stiky">
<div class="lm_top flex_wrap_between">
<h3>BANNED</h3>
<p class="btn_buy"><a href="<?php echo get_bloginfo('url'); ?>/banned/" target="_blank"><span>曲の<br>詳細</span>はこちら</a></p>
</div>
<ul class="audio_list">
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track02_13.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>BANNED</p></div>
</li>
</ul>
</div>
</div>
</div>

<div class="album flex_wrap audio-player">
<div class="wrap_alist_skin">
<div class="stiky">
<figure class="all_btn">
<img src="<?php echo get_template_directory_uri(); ?>/img/isthis.webp" alt="">
<div class="playall"><span></span><span></span><span></span><span></span><span></span></div>
</figure>
<div class="play_al_btn all_btn"></div>
</div>
</div>
<div class="list_music_wrap">
<div class="stiky">
<div class="lm_top flex_wrap_between">
<h3>IS THIS EVOLVED OR WRECKED？</h3>
<p class="btn_buy"><a href="<?php echo get_bloginfo('url'); ?>/is-this-evolved-or-wrecked/" target="_blank"><span>曲の<br>詳細</span>はこちら</a></p>
</div>
<ul class="audio_list">
<li class="flex_wrap flex_wrap_middle">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_01.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>IS THIS EVOLVED OR WRECKED？</p></div>
</li>
</ul>
</div>
</div>
</div>

</div>

</div>
</section>

<section id="anc02" class="def_sec sec_video">
<div class="inner_1200">
<h2 class="h2_sec">VIDEO</h2>
<ul class="list_vd flex_wrap_center">
<li><a href="https://www.youtube.com/embed/R3qHdZU51Ik?si=gdSOZ2Doe_ou3sgy" class="modal"><img src="<?php echo get_template_directory_uri(); ?>/img/skin01.webp" alt=""></a></li>
<li><a href="https://www.youtube.com/embed/rhuXWYPLiKc?si=tWc_5Z-AqrPpNPAE" class="modal"><img src="<?php echo get_template_directory_uri(); ?>/img/skin02.webp" alt=""></a></li>
</ul>
<p class="btn"><a href="https://youtube.com/playlist?list=PL9RoyLowXKXjOTQiNCq4J5IgI1gDqE2Dk&si=sI9jlEDN-UkN3Eka" target="_blank">YOUTUBE</a></p>
</div>
</section>

<div class="cta2">
<div class="inner_cta">
<?php if ('en_US' == $locale){ ?>
<p><a href="https://www.youtube.com/@zakubakamodeling" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/img/bn2_en.webp" alt="罪プラの逆襲、開幕"></a></p>
<?php }else{ ?>
<p><a href="https://www.youtube.com/@zakubakamodeling" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/img/bn2.webp" alt="罪プラの逆襲、開幕"></a></p>
<?php } ?>
</div>
</div>

</main>

<?php get_footer();?>