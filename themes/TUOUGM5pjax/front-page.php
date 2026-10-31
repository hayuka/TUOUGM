<?php 
$loc = $_GET['loc'] ?? '';
if($loc){
   $locale = 'en_US';
}else{
   $locale = get_locale();
}
?>
<?php get_header();?>
<main id="swup" class="transition-fade main_wrap">
<div class="kv">
<p class="main_pic"><img src="<?php echo get_template_directory_uri(); ?>/img/kv.webp" alt="ガンプラ カスタム機体 バンドビジュアル" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/kv_sp.webp" alt="ガンプラ カスタム機体 バンドビジュアル" class="sp"></p>
<div class="kv_catch">
<?php if ('en_US' == $locale){ ?>
<h1 class="h1_kv" data-text="THE ULTIMATE SIN OF UNBUILT GUNPLA">THE ULTIMATE SIN OF UNBUILT GUNPLA</h1>
<p>An Industrial Metal Project<br class="sp">Inspired by Gunpla</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-player-album="533"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play_en.webp" alt="PLAY THE FULL ALBUM"></a></div>
<?php }else{ ?>
<h1 class="h1_kv" data-text="罪プラの逆襲">罪プラの逆襲</h1>
<p>ガンプラ × インダストリアルメタルの<br class="sp">音楽プロジェクト</p>
<div class="btn_kv ta_c"><a href="javascript:void(0)" class="all_btn" data-player-album="533"><img src="<?php echo get_template_directory_uri(); ?>/img/btn_play.webp" alt="アルバムを再生"></a></div>
<?php } ?>
</div>
</div>

<div class="cta">
<div class="inner_cta">
<?php if ('en_US' == $locale){ ?>
<p><a href="https://www.youtube.com/@zakubakamodeling" title="youtube" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/img/share_en.webp" alt="罪プラの逆襲、開幕"></a></p>
<?php }else{ ?>
<p><a href="https://www.youtube.com/@zakubakamodeling" title="youtube" target="_blank"><img src="<?php echo get_template_directory_uri(); ?>/img/share.webp" alt="罪プラの逆襲、開幕"></a></p>
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

<?php
$latest_album = array();
if ( function_exists( 'get_field' ) ) {
    $release_query = new WP_Query( array(
        'post_type'      => 'music_album',
        'post_status'    => 'publish',
        'has_password'   => false,
        'posts_per_page' => 1,
        'no_found_rows'  => true,
        'orderby'       => array( 'menu_order' => 'ASC', 'ID' => 'DESC' ),
        'meta_query'    => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array( 'key' => 'required_level', 'compare' => 'NOT EXISTS' ),
                array( 'key' => 'required_level', 'value' => 0, 'compare' => '<=', 'type' => 'NUMERIC' ),
            ),
            array(
                // ACF True/False: form saves '0'/'1'; false via update_field() may save ''.
                'relation' => 'OR',
                array( 'key' => 'archive_only', 'compare' => 'NOT EXISTS' ),
                array( 'key' => 'archive_only', 'value' => array( '0', '' ), 'compare' => 'IN' ),
            ),
        ),
    ) );
    foreach ( $release_query->posts as $release_post ) {
        $candidate = tuougm_get_album_data( $release_post->ID );
        if ( ! empty( $candidate ) && ! $candidate['archive_only'] && $candidate['required_level'] <= 0 ) {
            $latest_album = $candidate;
            break;
        }
    }
}
$is_release_english = ( 'en_US' === $locale );
if ( $latest_album ) :
    $release_title = $is_release_english && ! empty( $latest_album['title_en'] )
        ? $latest_album['title_en'] : $latest_album['title'];
    $release_type = $latest_album['type'];
    if ( is_array( $release_type ) ) {
        $release_type = $release_type['label'] ?? $release_type['value'] ?? '';
    }
    if ( ! $is_release_english && 'EP' === $release_type ) {
        $release_type = 'MINI ALBUM';
    }
    $release_description = (string) get_field(
        $is_release_english ? 'album_description_en' : 'album_description_ja',
        (int) $latest_album['id']
    );
?>
<section class="sec_info def_sec sec_latest_release">
<div class="inner_1200">
<h2 class="ttl_sec_info"><span>LATEST RELEASE</span><a class="latest_release_view_all" href="<?php echo esc_url( function_exists( 'bogo_url' ) ? bogo_url( home_url( '/music/' ), $is_release_english ? 'en_US' : 'ja' ) : home_url( '/music/' ) ); ?>">VIEW ALL <span class="latest_release_view_all_music">MUSIC</span></a></h2>
<p class="sub_ttl_info"><?php echo $is_release_english ? 'Updating with the latest releases' : '最新のリリース'; ?></p>
<div class="wrap_release flex_wrap">
<figure><?php if ( $latest_album['artwork'] ) : ?><img src="<?php echo esc_url( $latest_album['artwork'] ); ?>" alt="<?php echo esc_attr( $release_title ); ?>"><?php endif; ?></figure>
<div class="release_cnt">
<h3 class="eng"><span><?php echo esc_html( (string) $release_type ); ?></span><?php echo esc_html( $release_title ); ?></h3>
<?php if ( '' !== trim( $release_description ) ) : ?>
<p><?php echo nl2br( esc_html( $release_description ), false ); ?></p>
<?php endif; ?>
<ul class="list_release">
<?php foreach ( $latest_album['tracks'] as $release_track ) : ?>
<?php
    if ( 'publish' !== get_post_status( $release_track['id'] ) || get_post_field( 'post_password', $release_track['id'] ) ) {
        continue;
    }
    $release_track_title = $is_release_english && ! empty( $release_track['title_en'] )
        ? $release_track['title_en'] : $release_track['title'];
?>
<li><?php echo esc_html( $release_track_title ); ?></li>
<?php endforeach; ?>
</ul>
<p class="btn"><a href="javascript:void(0)" class="all_btn" data-player-album="<?php echo esc_attr( $latest_album['id'] ); ?>">PLAY ALL</a></p>
</div>
</div>
</div>
</section>
<?php endif; ?>

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
<p class="ta_c">THE ULTIMATE SIN OF <br class="sp">UNBUILT GUNPLA MOUNTAIN（TUOUGM）は <br>
ガンプラをテーマにした音楽プロジェクト。<br>
積み上げられたガンプラ、作りたいのに作れない葛藤、<br class="pc">モデラーなら誰もが一度は経験する「ガンプラあるある」。<br>
そんな日常を、パンク、メタル、ハードコアなどのエッジを効かせたヘヴィなサウンドへと変換する。<br>
ガンプラが好きで、音楽が好き。<br>
そんなすべてのモデラーに向けて、まだ組まれていないガンプラの山から、新しい音を鳴らしていきます。</p>
<?php } ?>
<p class="btn btn_t_about"><a href="<?php echo get_bloginfo('url'); ?>/about/">ABOUT</a></p>
</div>
</section>

<?php
$query = new WP_Query([
	'post_type'      => 'post',
	'posts_per_page' => 3,
]);
?>
<?php if ($query->have_posts()) : ?>
<section class="sec_top_info">
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/bg_info_top.png" alt=""></figure>
<div class="inner_1200 inner_top_info">
<h2 class="h2_sec">INFORMATION</h2>
<ul class="list_info">
<?php while ($query->have_posts()) : $query->the_post();?>
<li><a href="<?php the_permalink(); ?>" class="flex_wrap">
<?php 
$categories = get_the_category();
if ($categories) {
?>
<?php foreach ($categories as $category) { ?>
<span class="cate_tab"><?php echo $category->name; ?></span>
<?php } ?>
<?php } ?>
<?php the_title(); ?></a></li>
<?php endwhile; ?>
</ul>
<p class="btn btn_t_about"><a href="<?php echo get_bloginfo('url'); ?>/archives/">ARCHIVES</a></p>
</div>
</section>
<?php
endif;
wp_reset_postdata();
?>

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


<?php if ('en_US' == $locale){ ?>
<section class="def_sec">
<div class="inner_1200">
<h2 class="h2_howto">HOW TO USE THE PLAYER</h2>
<p>
This site is more than just a place to listen to music. It features a music library where you can freely select albums and tracks, as well as a persistent player that lets you keep listening as you explore the site.<br>
Here’s a quick guide to the basic functions.
</p>
<ul class="list_howto flex_wrap">
<li>
<h3><span class="eng num">01</span>PLAY / PAUSE</h3>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/howto_01.png" alt="" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/howto_01_sp.png" alt="" class="sp"></figure>
<p>The player stays active as you move between pages, allowing you to keep listening without interruption.</p>
</li>

<li>
<h3><span class="eng num">02</span>MUSIC LIBRARY</h3>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/howto_02.png" alt="" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/howto_02_sp.png" alt="" class="sp"></figure>
<p>Click the △ in the upper-right corner of the player to open the library. You can select tracks from the currently playing album and view the lyrics of the current track.</p>
</li>

<li>
<h3><span class="eng num">03</span>ALBUM SELECT</h3>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/howto_03.png" alt="" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/howto_03_sp.png" alt="" class="sp"></figure>
<p>Click “Select album” to browse and select an album.</p>
</li>
</ul>
</div>
</section>
<?php }else{ ?>
<section class="def_sec">
<div class="inner_1200">
<h2 class="h2_howto">HOW TO USE THE PLAYER</h2>
<p>当サイトは、音楽を聴くだけでなく、アルバムや曲を自由に選べる音楽ライブラリ、そしてサイトを巡りながら再生を続けられる機能を備えています。<br>
ここでは、基本的な使い方をわかりやすくご紹介します。</p>
<ul class="list_howto flex_wrap">
<li>
<h3><span class="eng num">01</span>PLAY / PAUSE</h3>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/howto_01.png" alt="" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/howto_01_sp.png" alt="" class="sp"></figure>
<p>サイト下部にあるプレイヤーは、ページを移動してもそのまま再生され続けます。</p>
</li>
<li>
<h3><span class="eng num">02</span>MUSIC LIBRARY</h3>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/howto_02.png" alt="" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/howto_02_sp.png" alt="" class="sp"></figure>
<p>プレイヤー右上の△をクリックでライブラリが展開し再生中のアルバムの曲を選択でき、再生中の曲の歌詞も確認できます。</p>
</li>
<li>
<h3><span class="eng num">03</span>ALBUM  SELECT</h3>
<figure><img src="<?php echo get_template_directory_uri(); ?>/img/howto_03.png" alt="" class="pc"><img src="<?php echo get_template_directory_uri(); ?>/img/howto_03_sp.png" alt="" class="sp"></figure>
<p>「Select album」をクリックでアルバムが選択可能です。</p>
</li>
</ul>
</div>
</section>
<?php } ?>


<!--
<section id="anc03" class="def_sec">

<div class="inner_1200 musics">
<h2 class="h2_sec">MUSIC</h2>

<h3 class="h3_music">ALBUM</h3>
<div class="album_wrap">
<div id="album01" class="album flex_wrap audio-player" data-player-album="533">
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
<p class="btn_buy"><a href="https://tuougm.bandcamp.com/album/the-ultimate-sin-of-unbuilt-gunpla-mountain" target="_blank"><span>高音質版<br>はこちら</span>Listen / Buy</a></p>
</div>
<ul class="audio_list">
<li class="flex_wrap flex_wrap_middle" data-player-track="529">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_01.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>IS THIS EVOLVED OR WRECKED？</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="535">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_02.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>いまどきのガンプラ情報量エグい</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="538">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_03.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>仕事する暇あっらガンプラ作れ</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="541">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_04.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>NO EXCUSE</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="544">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_05.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>8UN674</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="547">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_06.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>Ever since 1981</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="550">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_07.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>ニッパー片手に</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="554">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_08.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>色もついてるし、スナップフィットだから接着剤もバテも塗料もガンプラには必要ないってよ</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="557">
<div class="ad_play">
<div class="play_btn"></div>
</div>
<div class="ad_cnt"><p>ガンプラ系YouTuberの憂鬱</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="560">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_10.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>THE ULTIMATE SIN OF UNBUILT GUNPLA MOUNTAIN</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="563">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_11.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>PAYBACK</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="566">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_12.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>積みプラは罪？</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="569">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_13.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>魔改造中毒</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="572">
<div class="ad_play">
<div class="play_btn"></div>
<audio data-src="<?php echo get_template_directory_uri(); ?>/audio/track01_14.mp3" preload="none"></audio>
</div>
<div class="ad_cnt"><p>Just picture a world ruled by Gunpla</p></div>
</li>
<li class="flex_wrap flex_wrap_middle" data-player-track="575">
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

<div id="single01" class="album flex_wrap audio-player" data-player-album="670">
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
<li class="flex_wrap flex_wrap_middle" data-player-album="670">
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

</div>

</div>
</section>
-->

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
