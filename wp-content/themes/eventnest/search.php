<?php get_header(); ?>
<section class="page-head"><div class="wrap"><h1><?php printf( esc_html__( 'Results for “%s”', 'eventnest' ), esc_html( get_search_query() ) ); ?></h1></div></section>
<section class="section section--tight"><div class="wrap wrap--narrow">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article class="post-row"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></article>
	<?php endwhile; the_posts_pagination(); else : ?>
		<div class="empty"><h2><?php esc_html_e( 'No results', 'eventnest' ); ?></h2><p><?php esc_html_e( 'Try a different word.', 'eventnest' ); ?></p></div>
	<?php endif; ?>
</div></section>
<?php get_footer(); ?>
