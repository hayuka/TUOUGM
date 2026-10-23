<?php
if(is_user_logged_in()){

    wp_redirect(
        home_url('/members/')
    );

    exit;
}
?>
<?php
$error = '';
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = sanitize_user($_POST['username']);
    $email    = sanitize_email($_POST['email']);
    $password = (string)$_POST['password'];
	
	
    if(username_exists($username)){
        $error = 'CALLSIGN ALREADY EXISTS';
    }elseif(email_exists($email)){
        $error = 'EMAIL ALREADY REGISTERED';
	}elseif(empty($password)){
        $error = 'PASSWORD REQUIRED';
    }else{
        $user_id = wp_create_user(
            $username,
            $password,
            $email
        );
		
        if(!is_wp_error($user_id)){
			$level = 1;
			if(isset($_COOKIE['sin_level'])){
				$level = intval($_COOKIE['sin_level']);
			}
            update_user_meta(
                $user_id,
                'sin_level',
                $level
            );
            $success = true;
			wp_set_current_user($user_id);
			wp_set_auth_cookie($user_id);

			wp_redirect(
				home_url('/members/')
			);
			exit;
        }else{
            $error = $user_id->get_error_message();
        }
    }
}
?>
<?php get_header();?>
<main id="swup" class="transition-fade main_wrap">
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



<p class="input_ttl it_red"><span>REQUEST ACCESS</span></p>
<p class="sub_ttl_input">JOIN THE ARCHIVE</p>
<div class="wrap_wp_form">
<?php if($success): ?>

<div class="success">
ACCESS GRANTED
<br>
AUTHENTICATION AVAILABLE
</div>

<?php else: ?>
<?php if($error): ?>
<div class="error">
<?php echo esc_html($error); ?>
</div>
<?php endif; ?>
<form method="post" action="<?php echo esc_url(get_permalink()); ?>">
<?php wp_nonce_field('register_action', 'register_nonce'); ?>
<div class="input_pass">
<p>CALLSIGN</p>
<input type="text" name="username" required placeholder="Enter your callsign">
</div>
<div class="input_pass">
<p>EMAIL</p>
<input type="email" name="email" required placeholder="Enter your email address">
</div>
<div class="input_pass">
<p>PASSWORD</p>
<input type="password" name="password" placeholder="Create a password">
</div>
<p class="submit"><input type="submit" value="CREATE ACCOUNT"></p>
</form>
<?php endif; ?>

<p class="link_arrow">Already have an account?　<a href="<?php echo get_bloginfo('url'); ?>/members/login/">LOGIN<span></span></a></p>
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