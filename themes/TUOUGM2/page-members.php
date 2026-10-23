<?php get_header();?>
<main class="main_wrap">
<div class="parallax member_top">
<img src="<?php echo get_template_directory_uri(); ?>/img/bg_member.png" alt="">
</div>
<?php
$names = [
	1 => 'MEMBER',
	2 => 'SUPPORTER',
	3 => 'PREMIUM',
];
$level_cnt = [
    1 => 'WALLPAPERS',
    2 => 'ORIGIN FILES',
    3 => 'RECOVERED IMAGES',
    4 => 'CLASSIFIED FOOTAGE',
];
$member_cnt = [
    1 => 'RE:NA STYLE BOOK',
    2 => 'UNRELEASED AUDIO',
];
$error = false;
$success = false;
$newLevel = 0;
$unlocked = tuougm_get_level();
$membershipLevel = tuougm_get_membership_level();

function tuougm_get_level() {
    if(!is_user_logged_in()){
        return intval(
            $_COOKIE['sin_level'] ?? 0
        );
    }
    $user_id = get_current_user_id();
    $unlocked = get_user_meta(
        $user_id,
        'sin_level',
        true
    );
    if(empty($unlocked)){

        if(isset($_COOKIE['sin_level'])){

            $unlocked = intval($_COOKIE['sin_level']);

        }else{

            $unlocked = 1;
        }

        update_user_meta(
            $user_id,
            'sin_level',
            $unlocked
        );
    }

    return intval($unlocked);
}

$levelCodes = [
	'TUOUGM' => 1,
	'UNBUILT' => 2,
	'REBIRTH' => 3,
	'UNBUILT-04' => 4,
];
$maxLevel = max($levelCodes);

function tuougm_get_membership_level(){
	if(!is_user_logged_in()){
	return 0;
	}

	$level = get_user_meta(
	get_current_user_id(),
	'membership_level',
	true
	);

	if($level === ''){
	$level = 0;
	}

	return intval($level);
}

$membershipCodes = [
	'gunplasuki' => 2,
	'kami'     => 3,
];
$maxmembership = max($membershipCodes);

if(isset($_POST['pass'])){

	if(isset($levelCodes[$_POST['pass']])){

		$newLevel = $levelCodes[$_POST['pass']];
		
		if($newLevel > $unlocked){
			$success = true;
		}

		$unlocked = max($unlocked, $newLevel);

		setcookie(
			'sin_level',
			$unlocked,
			time()+3600,
			'/'
		);
		if(is_user_logged_in()){
			update_user_meta(
				get_current_user_id(),
				'sin_level',
				$unlocked
			);
		}

	}else{
		$error = true;
	}
}
?>
<div class="member_inner">
<div class="about_kv m_kv">
<h1 data-text="MEMBERS">MEMBERS</h1>
<?php if ($unlocked == 0){ ?>
<p>ACCESS RESTRICTED</p>
<?php }else{ ?>
<p>AUTHORIZATION GRANTED</p>
<?php } ?>
</div>

<?php if ($unlocked == 0): ?>
<section class="def_sec sec_concept">
<div class="inner_1200">
<div class="locked">
<div class="boot-screen">
<p class="boot-line">SCANNING USER...</p>
<p class="boot-line status"></p>
</div>

<div class="pass-wrap">
<p class="input_ttl"><span>INPUT ACCESS CODE</span></p>
<form method="POST" autocomplete="off">
<div class="input_pass">
<input type="text" name="pass" placeholder="ENTER CODE">
<p class="hint">ENTER: TUOUGM</p>
</div>
<p class="submit"><input type="submit" value="ACCESS"></p>
</form>
</div>
</div>

<div class="m_info">
<p class="ttl_m_info"><span>DO YOU HAVE CLEARANCE?</span></p>
<div class="flex_wrap_center m_i_wrap">
<a href="<?php echo get_bloginfo('url'); ?>/members/request-access/" class="micnt_box flex_wrap_middle">
<div class="icon_mi"><img src="<?php echo get_template_directory_uri(); ?>/img/icon_add.png" alt=""></div>
<div class="m_i_cnt">
<p class="ttl_m_i_cnt">REQUEST ACCESS</p>
<p>Create a member account</p>
<p class="mi_catch">NEW USER</p>
</div>
</a>

<a href="<?php echo get_bloginfo('url'); ?>/members/login/" class="micnt_box flex_wrap_middle">
<div class="icon_mi"><img src="<?php echo get_template_directory_uri(); ?>/img/icon_login.png" alt=""></div>
<div class="m_i_cnt">
<p class="ttl_m_i_cnt">LOGIN</p>
<p>Resume your progress</p>
<p class="mi_catch">REGISTERED USER</p>
</div>
</a>
</div>
</div>

</div>
</section>
<?php endif; ?>
<div id="error_overlay">
<div class="error_message">
<h2>SIGNAL REJECTED</h2>
<p>INVALID ACCESS CODE</p>
</div>
</div>
<?php if ($error): ?>
<script>
window.addEventListener('load', () => {
    const overlay = document.getElementById('error_overlay');
    overlay.classList.add('show');
    setTimeout(() => {
        overlay.classList.remove('show');
    }, 3000);
});
</script>
<?php endif; ?>
<?php if ($unlocked >= 1): ?>
<section class="def_sec sec_member_unlocked">
<div class="member_outer">
<div class="m_header flex_wrap_between">
<h2 class="eng flex_wrap_middle"><span class="prog_num"><?php echo sprintf('%02d', $unlocked); ?></span><span class="num_total">／<?php echo sprintf('%02d', $maxLevel); ?></span>LEVEL UNLOCKED
<div class="level-progress"><div class="l_progress-bar"></div></div>
</h2>
<?php if ($unlocked < $maxLevel): ?>
<div class="next_level flex_wrap_between"><p>NEXT ACCESS</p> <p>LEVEL <?php echo sprintf('%02d', $unlocked + 1); ?></p></div>
<?php else: ?>
<div class="next_level flex_wrap_between"><p>ALL LEVELS UNLOCKED</p></div>
<?php endif; ?>
<?php if(is_user_logged_in()) : ?>
<?php if ($membershipLevel < 2): ?>
<p class="btn_lup"><a href="<?php echo get_bloginfo('url'); ?>/members/upgrade-access/">UPGRADE ACCESS<span></span></a></p>
<?php endif; ?>
<?php endif; ?>
</div>
<div class="member_wrap flex_wrap">

<div class="aside_member_wrap">
<div class="stiky">
<nav class="m_nav">
<ul class="tab">
<?php for($i = 1; $i <= $maxLevel; $i++): ?>
<?php $isOpen = ($unlocked >= $i); ?>
<li class="<?php echo $isOpen ? 'm_index_unlocked' : 'm_index_locked'; ?> eng">
<div class="flex_wrap_between flex_wrap_middle">
<p class="flex_wrap_middle"><span class="m_i_mark"></span><span class="level_num">LEVEL <?php echo sprintf('%02d', $i); ?></span></p>
<span class="stlevel"><?php echo $isOpen ? 'UNLOCKED' : 'LOCKED'; ?></span>
</div>
<p class="lv_ttl"><?php echo $level_cnt[$i]; ?></p>
</li>
<?php endfor; ?>
</ul>
</nav>

<?php if ($membershipLevel >= 0): ?>
<nav class="m_nav top_b">
<h3 class="ttl_as_h3">CLEARANCE CLASS</h3>
<ul class="tab">
<?php
if ($membershipLevel == 0){
	$m_num = 1;
}else{
	$m_num = $membershipLevel;
}
?>
<?php for($i = 1; $i <= $m_num; $i++): ?>
<?php if ($membershipLevel == 0){ ?>
<li class="m_index_locked eng">
<div class="flex_wrap_between flex_wrap_middle">
<p class="flex_wrap_middle"><span class="m_i_mark"></span><span class="level_num">CLASS <?php echo sprintf('%02d', $i); ?></span></p>
<span class="stlevel">LOCKED</span>
</div>
<p class="lv_ttl"><?php echo $member_cnt[$i]; ?></p>
</li>
<?php }else{ ?>
<li class="m_index_unlocked eng">
<div class="flex_wrap_between flex_wrap_middle">
<p class="flex_wrap_middle"><span class="m_i_mark"></span><span class="level_num">CLASS <?php echo sprintf('%02d', $i); ?></span></p>
<span class="stlevel">UNLOCKED</span>
</div>
<p class="lv_ttl"><?php echo $member_cnt[$i]; ?></p>
</li>
<?php } ?>
<?php endfor; ?>
</ul>
</nav>
<?php endif; ?>

<div class="m_terminal">
<h3 class="ttl_as_h3">MEMBER TERMINAL</h3>
<dl>
<dt>CALLSIGN</dt>
<?php if(is_user_logged_in()) : ?>
<dd><?php echo esc_html(wp_get_current_user()->display_name); ?></dd>
<?php else : ?>
<dd>NO RECORD</dd>
<?php endif; ?>
</dl>
<dl>
<dt>STATUS</dt>
<?php if(is_user_logged_in()) : ?>
<dd><?php echo esc_html($names[$membershipLevel] ?? 'MEMBER'); ?></dd>
<?php else : ?>
<dd>GUEST</dd>
<?php endif; ?>
</dl>
</div>

<div class="m_terminal">
<?php if(is_user_logged_in()) : ?>
<div class="btn"><a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">LOGOUT</a></div>
<?php else : ?>
<p class="text_req">NEXT LEVEL AWAITS</p>
<div class="btn"><a href="<?php echo get_bloginfo('url'); ?>/members/request-access/">REQUEST ACCESS</a></div>
<?php endif; ?>
</div>

</div>
</div><!--/aside_member-->

<div class="m_body tab_wrap">

<?php for($i = 1; $i <= $maxLevel; $i++): ?>
<div class="m_cnt tab_body" id="level_<?php echo $i; ?>">
<?php $isOpen = ($unlocked >= $i); ?>
<div class="m_cnt_header flex_wrap_between flex_wrap_middle">
<h3 class="m_cnt_ttl eng">LEVEL <?php echo sprintf('%02d', $i); ?></h3>
<p class="eng <?php echo $isOpen ? 'm_l_st_unlocked' : 'm_l_st_locked'; ?>"><?php echo $isOpen ? 'UNLOCKED' : 'LOCKED'; ?></p>
</div>
<div class="m_cnt_body">
<?php include get_theme_file_path('memberbody'.$i.'.php'); ?>
</div>
</div>
<?php endfor; ?>

<?php
if ($membershipLevel == 0){
	$m_num = 1;
}else{
	$m_num = $membershipLevel;
}
?>
<?php for($i = 1; $i <= $m_num; $i++): ?>
<?php $mnum =  $i; ?>
<div class="m_cnt tab_body" id="level_<?php echo $i; ?>">
<?php $isOpen = ($m_num >= $i); ?>
<div class="m_cnt_header flex_wrap_between flex_wrap_middle">
<h3 class="m_cnt_ttl eng">CLASS <?php echo sprintf('%02d', $mnum); ?></h3>
<?php if ($membershipLevel == 0){ ?>
<p class="eng m_l_st_locked">LOCKED</p>
<?php }else{ ?>
<p class="eng m_l_st_unlocked">UNLOCKED</p>
<?php } ?>
</div>
<div class="m_cnt_body">
<?php include get_theme_file_path('memberbody_pre'.$mnum.'.php'); ?>
</div>
</div>
<?php endfor; ?>

</div><!--/m_body-->

</div><!--/member_wrap-->

<div class="sp_m_menu">
<div class="m_terminal">
<h3 class="ttl_as_h3">MEMBER TERMINAL</h3>
<dl>
<dt>CALLSIGN</dt>
<?php if(is_user_logged_in()) : ?>
<dd><?php echo esc_html(wp_get_current_user()->display_name); ?></dd>
<?php else : ?>
<dd>NO RECORD</dd>
<?php endif; ?>
</dl>
<dl>
<dt>STATUS</dt>
<?php if(is_user_logged_in()) : ?>
<dd><?php echo esc_html($names[$membershipLevel] ?? 'MEMBER'); ?></dd>
<?php else : ?>
<dd>GUEST</dd>
<?php endif; ?>
</dl>
</div>

<div class="m_terminal">
<?php if(is_user_logged_in()) : ?>
<div class="btn"><a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">LOGOUT</a></div>
<?php else : ?>
<p class="text_req">NEXT LEVEL AWAITS</p>
<div class="btn"><a href="<?php echo get_bloginfo('url'); ?>/members/request-access/">REQUEST ACCESS</a></div>
<?php endif; ?>
</div>
</div>

</div><!--/member_outer-->
</section>
<?php endif; ?>



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

<div class="player">
<div class="wrap_plyer flex_wrap">
<div class="inner_player flex_wrap_middle">

<div class="player_lr flex_wrap_middle player_l">
<div class="play_skin"><img src="<?php echo get_template_directory_uri(); ?>/img/sample_skin.png" alt=""></div>
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

<script>
window.addEventListener('pageshow', function() {

    const status = document.querySelector('.status');
    const passWrap = document.querySelector('.pass-wrap');
    const boot = document.querySelector('.boot-screen');

    setTimeout(() => {
        status.textContent = 'AUTHORIZATION PORT OPEN';
    }, 500);

    setTimeout(() => {
        boot.style.display = 'none';
        passWrap.style.display = 'block';
    }, 1000);

});
</script>


<?php get_footer();?>