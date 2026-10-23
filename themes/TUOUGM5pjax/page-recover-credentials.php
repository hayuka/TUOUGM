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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_login = sanitize_text_field(
        $_POST['user_login']
    );

    $result = retrieve_password(
        $user_login
    );

    if (is_wp_error($result)) {
        $error = $result->get_error_message();
    } else {
        $success = true;
    }
}
?>

<p class="input_ttl it_red"><span>RECOVER CREDENTIALS</span></p>
<div class="wrap_wp_form">
<?php if(!empty($error)): ?>
<p class="m_error"><?php echo $error; ?></p>
<?php endif; ?>
<?php if(!empty($success)): ?>
<p class="m_success">
RECOVERY TRANSMISSION SENT
</p>
<?php endif; ?>
<form method="post">
<div class="input_pass">
<p>EMAIL</p>
<input type="email" name="user_login" required placeholder="Enter your email">
</div>
<p class="submit"><input type="submit" value="SEND"></p>
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