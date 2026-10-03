<?php get_header(); while ( have_posts() ) : the_post(); ?>
<section class="page-head"><div class="wrap wrap--narrow"><h1><?php the_title(); ?></h1><p class="muted"><?php echo esc_html( get_the_date() ); ?></p></div></section>
<section class="section section--tight"><div class="wrap wrap--narrow"><div class="prose"><?php the_content(); ?></div></div></section>
<?php endwhile; get_footer(); ?>
