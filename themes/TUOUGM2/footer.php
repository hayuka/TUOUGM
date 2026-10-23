<?php 
$loc = $_GET['loc'];
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
?>
<footer class="ft">
<?php if( is_page('members')|| is_page('story')|| is_singular('story') || is_post_type_archive('story')||is_tax('story_category')) : ?>
<?php else: ?>
<div class="inner_1200">
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
<?php endif; ?>
<?php if ('en_US' == $locale){ ?>
<p class="copy">©︎ Gunpla Research Institute GUNPLA MODELING</p>
<?php }else{ ?>
<p class="copy">©︎ ガンプラ技術研究所</p>
<?php } ?>
</footer>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/Modaal/0.4.4/js/modaal.min.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/common.js"></script>
<script src="<?php echo get_template_directory_uri(); ?>/js/audio.js"></script>
</body>
<?php wp_footer(); ?>
</html>