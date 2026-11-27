<?php get_header(); ?>

<?php
$locale = get_locale();
?>
<main id="swup" class="main_wrap transition-fade">

<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<?php if ( "en_US" === $locale ) : ?>
			<?php get_template_part( "template-parts/press-en" ); ?>
		<?php else : ?>
			<?php get_template_part( "template-parts/press-ja" ); ?>
		<?php endif; ?>
	<?php endwhile; ?>
<?php endif; ?>
<?php wp_reset_postdata(); wp_reset_query(); ?>

</main>

<?php get_footer(); ?>
