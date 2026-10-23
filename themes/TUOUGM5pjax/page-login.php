<?php
if(is_user_logged_in()){

    wp_redirect(
        home_url('/members/')
    );

    exit;
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

<?php
if($_POST){
    $creds = [
        'user_login'    => $_POST['log'],
        'user_password' => $_POST['pwd'],
        'remember'      => true
    ];
    $user = wp_signon($creds);
    if(is_wp_error($user)){
        $error = 'AUTHENTICATION FAILED';
    }else{
        wp_redirect(home_url('/members/'));
        exit;
    }
}
?>

<p class="input_ttl it_red"><span>LOGIN TERMINAL</span></p>
<p class="sub_ttl_input">AUTHORIZED PERSONNEL ONLY</p>
<div class="wrap_wp_form">
<?php if(!empty($error)): ?>
<p class="m_error"><?php echo $error; ?></p>
<?php endif; ?>
<form method="post">
<div class="input_pass">
<p>CALLSIGN / EMAIL</p>
<input type="text" name="log" required placeholder="Enter your callsign or email">
</div>
<div class="input_pass">
<p>PASSWORD</p>
<input type="password" name="pwd" placeholder="Enter your password">
</div>
<p class="submit"><input type="submit" value="LOGIN"></p>
</form>

<p class="link_arrow"><a href="<?php echo get_bloginfo('url'); ?>/members/recover-credentials/">RECOVER CREDENTIALS<span></span></a></p>
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