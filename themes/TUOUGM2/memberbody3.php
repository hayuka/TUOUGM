<?php if($unlocked >= 3): ?>

<section class="sec_m_cnt">
<h4 class="ttl_smc eng"><span>RECOVERED</span>IMAGE GALLERY</h4>
<ul class="slide_thumb flex_wrap">
<?php
$query = new WP_Query([
    'post_type' => 'gallery',
	'posts_per_page' => -1,
    'tax_query' => [
        [
            'taxonomy' => 'gallery-cat',
            'field'    => 'slug',
            'terms'    => 'recovered',
        ]
    ]
]);
if ($query->have_posts()) :
?>
<?php while ($query->have_posts()) : $query->the_post();?>
<li>
<?php
$image_id = get_post_thumbnail_id();
$image_url = wp_get_attachment_image_src($image_id, 'casethumb');
$image_full = wp_get_attachment_image_src($image_id, 'full');
$thumb = $image_url[0];
$full = $image_full[0];
?>
<a href="#"
class="js-gallery"
data-img="<?php echo $full; ?>"
data-title="<?php echo get_the_title(); ?>"
data-desc="<?php echo get_the_excerpt(); ?>"
data-download="<?php echo $full; ?>">
<img src="<?php echo $thumb; ?>" alt="">
</a>
</li>
<?php endwhile; ?>
<?php
endif;
wp_reset_postdata();
?>

</ul>


<div class="mg_modal">
<div class="mg_inner_wrap">
<div class="mg_close">×</div>

<div class="mg_viewport">
<div class="mg_inner">
<div class="mg_slide">
<div class="mg_m_figwrap">
<figure class="mg_img">
<img src="" alt="">
</figure>
</div>
<div class="mg_cnt">
<p class="mg_title"></p>
<p class="mg_desc"></p>
</div>
</div>
</div>
</div>

<div class="wrap_pager">
<div class="mg_prev pager"></div>
<div class="mg_next pager"></div>
</div>

</div>
</div><!--/mg_modal-->
</section>

<?php else: ?>

<ul class="list_coming">
<li>IMAGE GALLERY</li>
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
<p class="hint">NEXT HINT : <a href="https://youtu.be/coaxmW44F10" target="_blank">CHECK THE VIDEO DESCRIPTION</a></p>
</div>
</div>

<?php endif; ?>