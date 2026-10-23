<?php
if(!is_user_logged_in()){

	wp_redirect(home_url('/login/'));
	exit;

}
?>
<?php get_header();?>
<main class="main_wrap">
<div class="parallax member_top">
<img src="<?php echo get_template_directory_uri(); ?>/img/bg_member.png" alt="">
</div>

<div class="member_inner">
<div class="about_kv m_kv">
<h1 data-text="MEMBERS">MEMBERS</h1>
</div>


<section class="def_sec sec_concept">
<div class="inner_1200">
<div class="locked">
<?php
if(!function_exists('tuougm_verify_access_key')){

	function tuougm_verify_access_key($key){

		$membershipCodes = [
			'gunplasuki' => 2,
			'kami'       => 3,
		];

		$key = strtolower(trim($key));

		return $membershipCodes[$key] ?? false;

	}

}
$error = false;

if(isset($_POST['auth_key'])){

	$auth_key = sanitize_text_field(
		$_POST['auth_key']
	);

	$newLevel = tuougm_verify_access_key(
		$auth_key
	);

	if($newLevel !== false){

		$currentLevel = intval(
			get_user_meta(
				get_current_user_id(),
				'membership_level',
				true
			)
		);

		if($newLevel > $currentLevel){

			update_user_meta(
				get_current_user_id(),
				'membership_level',
				$newLevel
			);

		}

		wp_redirect(
			add_query_arg(
				'upgraded',
				'1',
				get_permalink()
			)
		);
		exit;

	}else{

		$error = true;

	}

}
?>

<p class="input_ttl it_red"><span>UPGRADE ACCESS</span></p>
<?php if(isset($_GET['upgraded'])): ?>
<p class="m_success">ACCESS GRANTED</p>
<p><a href="<?php echo get_bloginfo('url'); ?>/members/">メンバーページへ</a></p>
<?php else: ?>
<ul class="list_upgrade">
<li>Join the YouTube <a href="https://www.youtube.com/channel/UCG0wKdIKj5LQq7W7TiViQ8A/join" target="_blank">Membership</a>.</li>
<li>Copy the Authorization Key.</li>
<li>Enter it below.</li>
</ul>
<div class="wrap_wp_form">
<?php if(!empty($error)): ?>
<p class="m_error">INVALID AUTHORIZATION KEY</p>
<?php endif; ?>
<form method="post">
<div class="input_pass">
<p>AUTHORIZATION KEY</p>
<input type="text" name="auth_key" required placeholder="Enter AUTHORIZATION KEY">
</div>
<p class="submit"><input type="submit" value="SEND"></p>
</form>
</div>
<?php endif; ?>
</div>

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