<?php get_header(); while ( have_posts() ) : the_post(); $wide_content_page = is_page( array( 'competitions', 'dashboard' ) ); ?>
<section class="page-head"><div class="wrap"><h1><?php the_title(); ?></h1></div></section>
<section class="section section--tight"><div class="wrap<?php echo $wide_content_page ? '' : ' wrap--narrow'; ?>"><div<?php echo $wide_content_page ? '' : ' class="prose"'; ?>><?php the_content(); ?></div></div></section>
<?php endwhile; get_footer(); ?>
