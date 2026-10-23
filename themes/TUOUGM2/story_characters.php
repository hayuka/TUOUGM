<div class="wrap_st_main st_sub_wrap">
<section class="st_single_top st_history_top chara">
<p class="ttl_sub_rena">ROSTER</p>
<h2>CHARACTERS<span>ECOVERED PROFILES</span></h2>
<p>RE:NA、NOVA、SPRUをはじめ、<br>
Gunpla Cityに存在する人物・機体のプロフィールを閲覧できます。</p>
</section>

<?php if ( have_posts() ) : ?>
<ul class="list_characters">
<?php while (have_posts()) : the_post(); ?>
<li>
<a href="<?php echo get_permalink(); ?>" class="flex_wrap">
<?php if ( has_post_thumbnail($post_id) ): ?>
<div class="st_meta_fig">
<figure class="eyecatch">
<?php echo get_the_post_thumbnail($post_id, 'large'); ?>
<div class="st_meta_cnt">
<h3><?php the_title() ?></h3>
</div>
</figure>
</div>
<?php endif; ?>
</a>
</li>
<?php endwhile; ?>
</ul>
<?php endif; ?>
<?php wp_reset_postdata(); wp_reset_query(); ?>

</div>
