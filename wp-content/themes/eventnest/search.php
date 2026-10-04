<?php
/**
 * EventNest Search Template
 *
 * Normal searches use the normal search layout.
 * Event searches are sent to archive-event.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/*
 * ---------------------------------------------------------
 * EVENT SEARCH
 * ---------------------------------------------------------
 *
 * The Events filter form submits:
 *
 * ?post_type=event
 *
 * WordPress otherwise loads search.php.
 *
 * We don't want that.
 *
 * Event searches must use archive-event.php because that
 * template contains the EventNest filters and event cards.
 */

$is_event_search = false;


/*
 * Check the WordPress query.
 */
$post_type = get_query_var( 'post_type' );

if ( is_array( $post_type ) ) {

	$is_event_search = in_array(
		'event',
		$post_type,
		true
	);

} else {

	$is_event_search =
		( 'event' === $post_type );
}


/*
 * Also check the URL directly.
 *
 * This handles:
 *
 * ?post_type=event
 *
 * even if WordPress has not populated the query var yet.
 */
if (
	isset( $_GET['post_type'] )
	&&
	! is_array( $_GET['post_type'] )
	&&
	'event' === sanitize_key(
		wp_unslash(
			$_GET['post_type']
		)
	)
) {

	$is_event_search = true;
}


/*
 * If this is an EventNest search, use the Events archive.
 */
if ( $is_event_search ) {

	$event_template =
		locate_template(
			'archive-event.php'
		);

	if ( $event_template ) {

		include $event_template;

		return;
	}
}


/*
 * ---------------------------------------------------------
 * NORMAL WORDPRESS SEARCH
 * ---------------------------------------------------------
 */

get_header();
?>

<section class="page-head">

	<div class="wrap">

		<h1>

			<?php

			printf(
				esc_html__(
					'Results for “%s”',
					'eventnest'
				),
				esc_html(
					get_search_query()
				)
			);

			?>

		</h1>

	</div>

</section>


<section class="section section--tight">

	<div class="wrap wrap--narrow">

		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post(); ?>

				<article class="post-row">

					<h2>

						<a href="<?php the_permalink(); ?>">

							<?php the_title(); ?>

						</a>

					</h2>


					<?php the_excerpt(); ?>

				</article>

			<?php endwhile; ?>


			<?php the_posts_pagination(); ?>


		<?php else : ?>

			<div class="empty">

				<h2>

					<?php

					esc_html_e(
						'No results',
						'eventnest'
					);

					?>

				</h2>


				<p>

					<?php

					esc_html_e(
						'Try a different word.',
						'eventnest'
					);

					?>

				</p>

			</div>

		<?php endif; ?>

	</div>

</section>


<?php get_footer(); ?>