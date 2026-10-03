<?php get_header(); ?>
<section class="page-head"><div class="wrap"><h1><?php echo is_home() && ! is_front_page() ? esc_html( get_the_title( get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'eventnest' ) ) : ( is_archive() ? wp_kses_post( get_the_archive_title() ) : esc_html__( 'Latest', 'eventnest' ) ); ?></h1></div></section>
<section class="section section--tight"><div class="wrap wrap--narrow">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article <?php post_class( 'post-row' ); ?>>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<p class="muted"><?php echo esc_html( get_the_date() ); ?></p>
			<?php the_excerpt(); ?>
		</article>
	<?php endwhile; the_posts_pagination(); else : ?>
		<div class="empty"><h2><?php esc_html_e( 'Nothing here yet', 'eventnest' ); ?></h2><p><?php esc_html_e( 'Check back soon.', 'eventnest' ); ?></p></div>
	<?php endif; ?>
</div></section>
<?php get_footer(); ?>
