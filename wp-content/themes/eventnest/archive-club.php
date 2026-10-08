<?php
get_header();
$search = isset( $_GET['club_search'] ) ? sanitize_text_field( wp_unslash( $_GET['club_search'] ) ) : '';
?>
<section class="page-head">
	<div class="wrap">
		<p class="eyebrow eyebrow--small">FIND YOUR COMMUNITY</p>
		<h1><?php esc_html_e( 'Explore clubs', 'eventnest' ); ?></h1>
		<p class="section__sub">Meet people, discover campus communities, and find your next activity.</p>
		<form class="filters en-club-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'club' ) ); ?>">
			<input class="filters__q" type="search" name="club_search" value="<?php echo esc_attr( $search ); ?>" placeholder="Search clubs" aria-label="Search clubs">
			<button class="btn btn--brand btn--sm" type="submit">Search clubs</button>
		</form>
	</div>
</section>
<section class="section section--tight">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="en-club-grid">
				<?php while ( have_posts() ) : the_post(); ?>
					<article <?php post_class( 'en-club-card' ); ?>>
						<a class="en-club-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'eventnest' ), get_the_title() ) ); ?>">
							<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'en-card', array( 'loading' => 'lazy' ) ); else : ?><span aria-hidden="true">✦</span><?php endif; ?>
						</a>
						<div class="en-club-card__body">
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( has_excerpt() || get_the_content() ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p><?php endif; ?>
							<a class="link-more" href="<?php the_permalink(); ?>">View club <span aria-hidden="true">→</span></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'Previous', 'eventnest' ), 'next_text' => __( 'Next', 'eventnest' ) ) ); ?>
		<?php else : ?>
			<div class="empty"><h2><?php esc_html_e( 'No clubs found', 'eventnest' ); ?></h2><p><?php esc_html_e( 'Try another search, or check back when clubs have been added.', 'eventnest' ); ?></p><a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'club' ) ); ?>">View all clubs</a></div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
