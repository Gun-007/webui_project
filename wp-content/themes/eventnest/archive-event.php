<?php
/**
 * EventNest Events Archive
 */

get_header();

/*
 * ---------------------------------------------------------
 * Get available filters
 * ---------------------------------------------------------
 */

$cities = get_terms(
	array(
		'taxonomy'   => 'event_city',
		'hide_empty' => false,
	)
);

$cats = get_terms(
	array(
		'taxonomy'   => 'event_category',
		'hide_empty' => false,
	)
);

$cities = is_wp_error( $cities ) ? array() : $cities;
$cats   = is_wp_error( $cats ) ? array() : $cats;


/*
 * ---------------------------------------------------------
 * Current filter values
 * ---------------------------------------------------------
 */

$cur_search = isset( $_GET['s'] )
	? sanitize_text_field( wp_unslash( $_GET['s'] ) )
	: '';

$cur_city = isset( $_GET['event_city'] )
	? sanitize_key( wp_unslash( $_GET['event_city'] ) )
	: '';

$cur_cat = isset( $_GET['event_category'] )
	? sanitize_key( wp_unslash( $_GET['event_category'] ) )
	: '';

$cur_when = isset( $_GET['when'] )
	? sanitize_key( wp_unslash( $_GET['when'] ) )
	: '';

$cur_free = isset( $_GET['free'] )
		&& '1' === sanitize_text_field( wp_unslash( $_GET['free'] ) );


/*
 * ---------------------------------------------------------
 * Page title
 * ---------------------------------------------------------
 */

if ( $cur_search ) {

	$title = sprintf(
		__( 'Results for “%s”', 'eventnest' ),
		$cur_search
	);

} elseif ( is_tax( 'event_city' ) ) {

	$title = single_term_title( '', false );

} elseif ( is_tax( 'event_category' ) ) {

	$title = single_term_title( '', false );

} else {

	$title = __( 'Explore events', 'eventnest' );

}


/*
 * ---------------------------------------------------------
 * Count results
 * ---------------------------------------------------------
 */

global $wp_query;

$result_count = isset( $wp_query->found_posts )
	? (int) $wp_query->found_posts
	: 0;

?>

<!-- =====================================================
     EVENTS PAGE HEADER
     ===================================================== -->

<section class="page-head page-head--events">

	<div class="wrap">

		<div class="page-head__top">

			<div>

				<p class="eyebrow eyebrow--small">
					EventNest
				</p>

				<h1>
					<?php echo esc_html( $title ); ?>
				</h1>

				<p class="section__sub">
					Discover competitions, workshops, club activities
					and campus events.
				</p>

			</div>

		</div>


		<!-- =================================================
		     FILTER FORM
		     IMPORTANT:
		     Submit directly to /events/
		     ================================================= -->

		<form
			class="filters filters--events"
			method="get"
			action="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"
		>

			<!-- Search -->

			<div class="filters--events__search">

				<input
					class="filters__q"
					type="search"
					name="s"
					value="<?php echo esc_attr( $cur_search ); ?>"
					placeholder="<?php esc_attr_e( 'Search events...', 'eventnest' ); ?>"
					aria-label="<?php esc_attr_e( 'Search events', 'eventnest' ); ?>"
				>

			</div>


			<!-- Category -->

			<select
				name="event_category"
				aria-label="<?php esc_attr_e( 'Category', 'eventnest' ); ?>"
			>

				<option value="">
					<?php esc_html_e( 'All categories', 'eventnest' ); ?>
				</option>

				<?php foreach ( $cats as $c ) : ?>

					<option
						value="<?php echo esc_attr( $c->slug ); ?>"
						<?php selected( $cur_cat, $c->slug ); ?>
					>
						<?php echo esc_html( $c->name ); ?>
					</option>

				<?php endforeach; ?>

			</select>


			<!-- City -->

			<select
				name="event_city"
				aria-label="<?php esc_attr_e( 'City', 'eventnest' ); ?>"
			>

				<option value="">
					<?php esc_html_e( 'All cities', 'eventnest' ); ?>
				</option>

				<?php foreach ( $cities as $c ) : ?>

					<option
						value="<?php echo esc_attr( $c->slug ); ?>"
						<?php selected( $cur_city, $c->slug ); ?>
					>
						<?php echo esc_html( $c->name ); ?>
					</option>

				<?php endforeach; ?>

			</select>


			<!-- Date -->

			<select
				name="when"
				aria-label="<?php esc_attr_e( 'When', 'eventnest' ); ?>"
			>

				<option value="">
					<?php esc_html_e( 'Any time', 'eventnest' ); ?>
				</option>

				<option
					value="week"
					<?php selected( $cur_when, 'week' ); ?>
				>
					This week
				</option>

				<option
					value="month"
					<?php selected( $cur_when, 'month' ); ?>
				>
					This month
				</option>

			</select>


			<!-- Free -->

			<label class="check">

				<input
					type="checkbox"
					name="free"
					value="1"
					<?php checked( $cur_free ); ?>
				>

				<span>
					<?php esc_html_e( 'Free only', 'eventnest' ); ?>
				</span>

			</label>


			<!-- Apply -->

			<button
				class="btn btn--brand btn--sm"
				type="submit"
			>
				<?php esc_html_e( 'Apply filters', 'eventnest' ); ?>
			</button>


			<!-- Clear -->

			<?php
			$has_filters =
				$cur_search ||
				$cur_city ||
				$cur_cat ||
				$cur_when ||
				$cur_free;
			?>

			<?php if ( $has_filters ) : ?>

				<a
					class="filters__clear"
					href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"
				>
					Clear
				</a>

			<?php endif; ?>

		</form>

	</div>

</section>


<!-- =====================================================
     RESULTS
     ===================================================== -->

<section class="section section--tight">

	<div class="wrap">


		<!-- Results bar -->

		<div class="results-bar">

			<p class="results-count">

				<strong>
					<?php echo esc_html( $result_count ); ?>
				</strong>

				<?php
				echo esc_html(
					_n(
						'event found',
						'events found',
						$result_count,
						'eventnest'
					)
				);
				?>

			</p>


			<?php if ( $has_filters ) : ?>

				<p class="results-bar__hint">
					Showing events matching your filters
				</p>

			<?php else : ?>

				<p class="results-bar__hint">
					Upcoming campus events
				</p>

			<?php endif; ?>

		</div>


		<!-- =================================================
		     EVENT RESULTS
		     ================================================= -->

		<?php if ( have_posts() ) : ?>

			<div class="grid event-grid">

				<?php while ( have_posts() ) : the_post(); ?>

					<?php
					en_card(
						en_card_data(
							get_the_ID()
						)
					);
					?>

				<?php endwhile; ?>

			</div>


			<!-- Pagination -->

			<?php

			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => __( 'Previous', 'eventnest' ),
					'next_text' => __( 'Next', 'eventnest' ),
				)
			);

			?>


		<?php else : ?>


			<!-- =================================================
			     NO RESULTS
			     ================================================= -->

			<div class="empty empty--events">

				<div class="empty__icon">
					⌕
				</div>

				<h2>
					No events found
				</h2>

				<p>
					We couldn't find any upcoming events matching
					your selected filters.
				</p>

				<a
					class="btn btn--brand"
					href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"
				>
					View all events
				</a>

			</div>


		<?php endif; ?>

	</div>

</section>


<?php get_footer(); ?>