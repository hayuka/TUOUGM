<?php $is_english = 'en_US' === get_locale(); ?>
<?php
$members_url = '';
$japanese_members_page = function_exists( 'bogo_get_page_by_path' )
	? bogo_get_page_by_path( 'members', 'ja', 'page' )
	: get_page_by_path( 'members' );
if ( $japanese_members_page && 'publish' === get_post_status( $japanese_members_page ) ) {
	$members_page = $japanese_members_page;
	if ( $is_english ) {
		$members_page = function_exists( 'bogo_get_post_translation' )
			? bogo_get_post_translation( $japanese_members_page->ID, 'en_US' )
			: null;
	}
	if ( $members_page && 'publish' === get_post_status( $members_page ) ) {
		$members_url = get_permalink( $members_page );
	}
}
?>
<div class="wrap_st_main">
<section class="sec_rena">
<div class="rena_cnt_wrap">
<p class="ttl_sub_rena">AUTHOR</p>
<h2>RE:NA<span>Recovered Entity Neutral Archive Unit</span></h2>
<p class="cnt_rena"><?php if ( $is_english ) : ?>
This page contains archives recovered and analyzed by RE:NA.<br><br>
The history of THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN, the Builder, ARCHE CITY, and fragments of this world are gradually being revealed.
<?php else : ?>
このページは、RE:NAが復元・解析したArchiveをまとめた記録です。<br>
THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAINの歴史、Builder、ARCHE CITY、<br class="pc">
そしてこの世界の断片を少しずつ公開しています。
<?php endif; ?></p>
<ul class="list_rena_link">
<li><?php if ( $members_url ) : ?>
<a href="<?php echo esc_url( $members_url ); ?>"><?php if ( $is_english ) : ?>RE:NA's PHOTOBOOK is now available on the MEMBERS PAGE.<br>
Register to unlock access.<?php else : ?>RE:NA の PHOTOBOOKが MEMBER PAGE で公開中<br>
会員登録して権限を解放しよう<?php endif; ?></a>
<?php else : ?>
<span class="ft_nav_unavailable" aria-disabled="true"><?php if ( $is_english ) : ?>RE:NA's PHOTOBOOK is now available on the MEMBERS PAGE.<br>
Register to unlock access.<?php else : ?>RE:NA の PHOTOBOOKが MEMBER PAGE で公開中<br>
会員登録して権限を解放しよう<?php endif; ?></span>
<?php endif; ?></li>
<li><a href="https://www.instagram.com/recoveredentityneuralarchive/" target="_blank"><?php echo $is_english ? 'Follow RE:NA on her official Instagram.' : 'RE:NA の公式 INSTAGRAMはこちらから'; ?></a></li>
</ul>
</div>
</section>

<ul class="list_ar_cate flex_wrap">
<li>
<a href="<?php echo esc_url( function_exists( 'bogo_url' ) ? bogo_url( home_url( '/story/history/' ), 'en_US' === get_locale() ? 'en_US' : 'ja' ) : home_url( '/story/history/' ) ); ?>">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/st_archive_01.jpg" alt=""></figure>
<h2><span class="num">001</span>HISTORY<span class="st_c_sub_ttl">THE ORIGIN OF TUOUGM</span></h2>
<p class="st_cate_text"><?php echo esc_html( $is_english ? 'From the formation of TUOUGM to the awakening of the Builder and the birth of ARCHE CITY.' : 'TUOUGMの結成、Builderの覚醒、そしてARCHE CITYの誕生まで。' ); ?></p>
<p class="more">MORE</p>
</a>
</li>
<!--
<li>
<a hrf="">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/st_archive_02.jpg" alt=""></figure>
<h2><span class="num">002</span>CHARACTERS<span class="st_c_sub_ttl">RECOVERED PROFILES</span></h2>
<p class="st_cate_text">RE:NA、NOVA、SPRUをはじめ、<br>
Gunpla Cityに存在する人物・機体のプロフィールを閲覧できます。</p>
<p class="more">MORE</p>
</a>
</li>
<li>
<a hrf="">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/st_archive_03.jpg" alt=""></figure>
<h2><span class="num">003</span>LOCATION<span class="st_c_sub_ttl">CITY DATABASE</span></h2>
<p class="st_cate_text">発展途上のGunpla Cityを構成するエリア、<br>
施設、ランドマークを記録した都市アーカイブです。</p>
<p class="more">MORE</p>
</a>
</li>
<li>
<a hrf="">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/st_archive_04.jpg" alt=""></figure>
<h2><span class="num">004</span>ARCHIVES<span class="st_c_sub_ttl">RECOVERED RECORDS</span></h2>
<p class="st_cate_text">復元途中の資料、映像、ログ、<br>
未分類のデータを時系列で保管しています。</p>
<p class="more">MORE</p>
</a>
</li>
-->
</ul>
</div>
