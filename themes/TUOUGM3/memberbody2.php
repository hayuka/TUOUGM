<?php if($unlocked >= 2): ?>
<?php 
$loc = $_GET['loc'];
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
?>

<section class="sec_m_cnt">
<h4 class="ttl_smc eng"><span>ARCHIVE 001</span>AWAKENING</h4>
<div class="m_archive_body">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/ma_01.png" alt=""></figure>
<?php if ('en_US' == $locale){ ?>
<p>2025.12<br>
On the desk of an ordinary Gunpla enthusiast stood a mountain of model kits.</p>
<p>Unbuilt.<br>
Forgotten.<br>
Stacked year after year.</p>
<p>They were known simply as...</p>
<p>UNBUILT GUNPLA.</p>
<h5>OBSERVATION 01</h5>
<p>One night, something changed.</p>
<p>Within the silence of the pile,<br>
the countless emotions left behind by abandoned Gunpla began to converge.</p>
<p>From that accumulation...</p>
<p>Four entities were reconstructed.</p>
<p>They were no longer mere plastic models.</p>
<p>They had become beings carrying the memories and emotions of every unbuilt kit.</p>
<h5>RECONSTRUCTION</h5>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/ma_02.png" alt=""></figure>
<p>What awakened within them was the result of years of abandonment.</p>
<p>- Anger<br>
- Despair<br>
- The awareness of remaining unfinished<br>
- And the pride of never having been completed</p>
<p>Together, these emotions evolved into a single ideology.</p>
<h5>OBSERVATION 02</h5>
<p>They discovered the truth.</p>
<p>Across the world,<br>
countless more unbuilt Gunpla remained.</p>
<p>And the cycle...<br>
would never end.</p>
<h5>AWAKENING</h5>
<p>They chose sound as their weapon.</p>
<p>The clicks of assembly.<br>
The scrape of worn metal.<br>
The impact of construction.</p>
<p>Reconstructed into one voice—<br>
Industrial Metal Punk.</p>
<h5>OBSERVATION 03</h5>
<p>Their objective was both simple...</p>
<p>and abnormal.<br>
To eradicate the Unbuilt.</p>
<p>Not through destruction.<br>
But through the liberation of everything left unfinished.</p>
<h5>PURPOSE</h5>
<p>They are not merely four Gunpla.<br>
They are the collective will of every forgotten kit—<br>
given form through sound.</p>
<?php }else{ ?>
<p>2025.12<br>
どこにでもあるガンプラオタクの机の上に、長年積み上げられたプラモデルの山があった。</p>
<p>それは完成されることもなく、忘れられ、ただ“積まれ続けた存在”──通称「罪プラ」。</p>
<p>ある夜、その積みの静寂に異変が起きる。<br>
未完成のまま放置されたガンプラたちに、“意志”が宿り始めた。</p>
<h5>OBSERVATION 01</h5>
<p>積まれたまま成仏できなかった無数のガンプラの「感情」が収束し、<br class="pc">
その中から4体の機体が“形”として再構成される。</p>
<p>それは単なる模型ではなく、<br>
積みの記憶と感情を持つ存在だった。</p>
<h5>RECONSTRUCTION</h5>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/ma_02.png" alt=""></figure>
<p>彼らに宿っていたのは、<br>
長年放置されたことによる</p>
<p>- 怒り<br>
- 絶望<br>
- 未完成であることへの自覚<br>
- そして“完成できなかった誇り”</p>
<p>それらはやがて一つの思想へと変質する。</p>
<h5>OBSERVATION 02</h5>
<p>彼らは気づく。</p>
<p>この世界にはまだ、<br>
無限に積まれ続ける“罪プラ”が存在していることを。</p>
<p>そしてその構造は、終わることがないことを。</p>
<h5>AWAKENING</h5>
<p>彼らは「音」を武器にする道を選ぶ。</p>
<p>プラモデルの構造音、金属摩耗音、組み立ての衝撃音──<br>
それらを再構築した結果生まれたのが、</p>
<p>インダストリアル・メタル・パンク・サウンド。</p>
<h5>OBSERVATION 03</h5>
<p>彼らの目的は単純であり、異常である。</p>
<p>罪プラの撲滅。</p>
<p>ただしそれは破壊ではない。<br>
“未完成の解放”である。</p>
<h5>PURPOSE</h5>
<p>彼らは4体の“ガンプラ”ではない。<br>
積みの意志そのものが音になった存在である。</p>
<?php } ?>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/ma_03.png" alt=""></figure>
</div>
</section>

<?php else: ?>

<ul class="list_coming">
<li>ARCHIVE 001 [CLASSIFIED]</li>
</ul>
<div class="next_wrap">
<p class="ttl_next en">NEXT LEVEL</p>
<div class="next_form_wrap">
<form method="POST" autocomplete="off">
<div class="input_pass">
<input type="text" name="pass" placeholder="ENTER CODE">
<p class="submit"><input type="submit" value=""></p>
</div>
</form>
<p class="hint">NEXT HINT : <a href="<?php echo get_bloginfo('url'); ?>/anomalous-stacking-report">OBSERVATION LOG FOUND</a></p>
</div>
</div>

<?php endif; ?>