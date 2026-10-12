<?php
get_header();
while ( have_posts() ) : the_post();
	$story_id = get_the_ID();
	$event = get_post_meta( $story_id, '_en_story_event', true );
	$course = get_post_meta( $story_id, '_en_story_course', true );
	$year = get_post_meta( $story_id, '_en_story_year', true );
	$favourite = get_post_meta( $story_id, '_en_story_favourite', true );
	$learned = get_post_meta( $story_id, '_en_story_learned', true );
	$author = get_userdata( (int) get_post_field( 'post_author', $story_id ) );
?>
<section class="en-story-single-head">
	<div class="wrap wrap--narrow">
		<p class="eyebrow eyebrow--small">EVENTNEST STORIES</p>
		<h1><?php the_title(); ?></h1>
		<?php echo enc_story_rating( $story_id, 'en-story-rating--large' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="en-story-single__tags">
			<?php if ( $author ) : ?><span><?php echo esc_html( $author->display_name ); ?></span><?php endif; ?>
			<?php if ( $course ) : ?><span><?php echo esc_html( $course ); ?></span><?php endif; ?>
			<?php if ( $year ) : ?><span><?php echo esc_html( $year ); ?></span><?php endif; ?>
			<?php if ( $event ) : ?><span><?php echo esc_html( $event ); ?></span><?php endif; ?>
		</div>
	</div>
</section>
<article class="section en-story-single">
	<div class="wrap wrap--narrow">
		<?php if ( has_post_thumbnail() ) : ?><figure class="en-story-single__image"><?php the_post_thumbnail( 'en-hero', array( 'fetchpriority' => 'high' ) ); ?></figure><?php endif; ?>
		<div class="prose en-story-single__content"><?php the_content(); ?></div>
		<?php if ( $favourite ) : ?><section class="en-story-highlight"><h2><span aria-hidden="true">♥</span> <?php esc_html_e( 'Favourite Moment', 'eventnest' ); ?></h2><p><?php echo nl2br( esc_html( $favourite ) ); ?></p></section><?php endif; ?>
		<?php if ( $learned ) : ?><section class="en-story-highlight"><h2><span aria-hidden="true">✦</span> <?php esc_html_e( 'What I Learned', 'eventnest' ); ?></h2><p><?php echo nl2br( esc_html( $learned ) ); ?></p></section><?php endif; ?>
		<footer class="en-story-single__footer">
			<?php if ( $author ) : ?><h2><?php echo esc_html( $author->display_name ); ?></h2><?php endif; ?>
			<?php if ( $course || $year ) : ?><p class="muted"><?php echo esc_html( implode( ' · ', array_filter( array( $course, $year ) ) ) ); ?></p><?php endif; ?>
			<a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'student_story' ) ); ?>">← <?php esc_html_e( 'Back to Student Stories', 'eventnest' ); ?></a>
		</footer>
	</div>
</article>
<?php endwhile; get_footer(); ?>
