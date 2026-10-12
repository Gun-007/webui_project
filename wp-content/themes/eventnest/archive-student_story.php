<?php
get_header();
?>
<section class="page-head en-story-archive-head">
	<div class="wrap">
		<p class="eyebrow eyebrow--small">EVENTNEST STORIES</p>
		<h1><?php esc_html_e( 'Student Stories', 'eventnest' ); ?></h1>
		<p class="section__sub"><?php esc_html_e( 'Real experiences. Real students. Real campus moments.', 'eventnest' ); ?></p>
		<?php if ( is_user_logged_in() && function_exists( 'enc_story_is_student' ) && enc_story_is_student() ) : ?>
			<p class="en-story-archive-head__action"><a class="btn btn--brand" href="<?php echo esc_url( home_url( '/share-story/' ) ); ?>"><?php esc_html_e( 'Share your story', 'eventnest' ); ?></a></p>
		<?php endif; ?>
	</div>
</section>
<section class="section section--tight">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="en-story-grid">
				<?php while ( have_posts() ) : the_post();
					$story_id = get_the_ID();
					$course = get_post_meta( $story_id, '_en_story_course', true );
					$year = get_post_meta( $story_id, '_en_story_year', true );
					?>
					<article <?php post_class( 'en-story-card' ); ?>>
						<a class="en-story-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Read %s', 'eventnest' ), get_the_title() ) ); ?>">
							<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'en-card', array( 'loading' => 'lazy' ) ); else : ?><span aria-hidden="true">✦</span><?php endif; ?>
						</a>
						<div class="en-story-card__body">
							<?php echo enc_story_rating( $story_id, 'en-story-rating--card' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<p class="en-story-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 30 ) ); ?></p>
							<p class="en-story-card__author"><?php echo esc_html( get_the_author() ); ?></p>
							<?php if ( $course || $year ) : ?><p class="en-story-card__study"><?php echo esc_html( implode( ' · ', array_filter( array( $course, $year ) ) ) ); ?></p><?php endif; ?>
							<a class="btn btn--brand btn--sm" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Read story', 'eventnest' ); ?> <span aria-hidden="true">→</span></a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'Previous', 'eventnest' ), 'next_text' => __( 'Next', 'eventnest' ) ) ); ?>
		<?php else : ?>
			<div class="empty"><h2><?php esc_html_e( 'No student stories yet', 'eventnest' ); ?></h2><p><?php esc_html_e( 'Check back soon for real experiences from your campus.', 'eventnest' ); ?></p></div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
