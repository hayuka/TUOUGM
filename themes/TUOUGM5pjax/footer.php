<?php 
$loc = isset( $_GET['loc'] ) && is_string( $_GET['loc'] ) ? wp_unslash( $_GET['loc'] ) : '';
$locale = 'en_US' === $loc ? 'en_US' : get_locale();
?>
<footer class="ft">
<div class="inner_1200 ft_info">
<div class="ft_cnt">
<?php if ('en_US' == $locale){ ?>
<p>TUOUGM is a Japanese industrial metal project<br>
inspired by Gunpla, mecha culture,<br>
anime aesthetics and the world of unbuilt Gundam models.<br>
Combining heavy guitars, cinematic sound design<br>
and original storytelling,<br>
<br>
TUOUGM creates music for Gunpla builders around the world.<br>
<br>

Please do not reupload our audio.
Sharing and feedback are always welcome.</p>
<?php }else{ ?>
<p>インダストリアルメタル × ガンプラ（プラモデル）の世界観で制作している音楽プロジェクト「TUOUGM」<br>
日本発のインダストリアルメタル・プロジェクトです。<br>
アニメ、ロボット、模型文化から影響を受け、アニソンとは異なる重厚なサウンドでガンプラビルダーの物語を表現しています。<br>
本サイトでは楽曲の公開・更新情報をはじめ、制作コンセプトやビジュアルとともに発信しています。<br>
<br>
音源の無断転載はご遠慮ください。<br>
シェアや感想の投稿は大歓迎です。</p>
<?php } ?>
</div>
<div class="sns_box flex_wrap_center">
<div class="sns_button twitter">
<a href="https://x.com/zakubaka" title="Tweet" target="_blank"><i class="fa-brands fa-x-twitter"></i></a>
</div>
<!--
<div class="sns_button facebook">
<a href="###" title="Facebook"><i class="fa fa-facebook"></i></a>
</div>
-->
<div class="sns_button instagram">
<a href="https://www.instagram.com/zakubaka/" title="instagram" target="_blank"><i class="fa fa-instagram"></i></a>
</div>
<div class="sns_button youtube">
<a href="https://www.youtube.com/@zakubakamodeling" title="youtube" target="_blank"><i class="fa fa-youtube"></i></a>
</div><div class="sns_button tiktok">
<a href="https://www.tiktok.com/@zakubaka2" title="tiktok" target="_blank"><i class="fa-brands fa-tiktok"></i></a>
</div>
</div>
</div>
<?php if ('en_US' == $locale){ ?>
<p class="copy">©︎ Gunpla Research Institute GUNPLA MODELING</p>
<?php }else{ ?>
<p class="copy">©︎ ガンプラ技術研究所</p>
<?php } ?>
</footer>

<div class="player">
<div class="menu_toggle"></div>
<div class="player_menu">
<h2>MUSIC LIBRARY</h2>
<div class="player_menu_inner flex_wrap">

<div class="wrap_album">
<div class="player_album_info flex_wrap">
<div class="player_album_artwork"><img src="<?php echo get_template_directory_uri(); ?>/img/sample_skin.webp" alt=""></div>
<div class="player_album_title"></div>
</div>

<div class="player_album">
<button type="button" class="player_album_toggle" aria-expanded="false" aria-controls="player-album-options">
<span class="player_album_toggle_label">Select album</span>
<span class="player_album_toggle_icon" aria-hidden="true"></span>
</button>
<div class="player_album_options" id="player-album-options" role="radiogroup">
<!-- アルバム選択肢は audio.js で生成 -->
</div>
</div>
</div>

<div class="track_wrap">
<ul class="player_track_list"></ul>
<div class="player_lyrics">
<div class="player_lyrics_content"></div>
</div>
</div>
</div>
</div>

<div class="wrap_plyer flex_wrap">
<div class="inner_player flex_wrap_middle">

<div class="player_lr flex_wrap_middle player_l">
<div class="play_skin"><img src="<?php echo get_template_directory_uri(); ?>/img/sample_skin.webp" alt=""></div>
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
<div class="player_reset"></div>
</div>
</div>
</div><!--/player-->

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Modaal/0.4.4/js/modaal.min.js"></script>
<script src="https://unpkg.com/swup@4"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/pjax.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/common.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/accordion.js"></script>
<script>
window.tuougmPlayerConfig = {
    restNonce: <?php echo wp_json_encode(wp_create_nonce('wp_rest')); ?>
};
</script>
<script src="<?php echo get_template_directory_uri(); ?>/js/audio(5)-track-trigger.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/lyric.js"></script>
</body>
<?php wp_footer(); ?>
</html>
