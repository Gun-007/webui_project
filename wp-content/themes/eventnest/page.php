<?php get_header(); while ( have_posts() ) : the_post(); ?>
<section class="page-head"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="section section--tight"><div class="wrap<?php echo is_page( 'competitions' ) ? '' : ' wrap--narrow'; ?>"><div<?php echo is_page( 'competitions' ) ? '' : ' class="prose"'; ?>><?php the_content(); ?></div></div></section>
<?php endwhile; get_footer(); ?>
