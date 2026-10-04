<?php
/**
 * EventNest Events Archive
 *
 * Displays:
 * - Event search
 * - Category filter
 * - City filter
 * - Date range filter
 * - Free event filter
 * - Dynamic event cards
 * - Pagination
 */

get_header();

global $wp_query;


/* =========================================================
 * GET EVENT FILTER DATA
 * ========================================================= */

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


/* =========================================================
 * CURRENT FILTER VALUES
 * ========================================================= */

$cur_city = get_query_var( 'event_city' );
$cur_cat  = get_query_var( 'event_category' );

$when = isset( $_GET['when'] )
	? sanitize_key( wp_unslash( $_GET['when'] ) )
	: '';

$free = isset( $_GET['free'] )
	&& '1' === sanitize_text_field( wp_unslash( $_GET['free'] ) );


/*
 * If filtering through GET parameters,
 * read the selected taxonomy values.
 */
if ( isset( $_GET['event_city'] ) ) {
	$cur_city = sanitize_title(
		wp_unslash( $_GET['event_city'] )
	);
}

if ( isset( $_GET['event_category'] ) ) {
	$cur_cat = sanitize_title(
		wp_unslash( $_GET['event_category'] )
	);
}


/*
 * If the visitor arrived directly on a taxonomy archive,
 * use the queried taxonomy term.
 */
if ( is_tax( 'event_city' ) ) {
	$cur_city = get_queried_object()->slug;
}

if ( is_tax( 'event_category' ) ) {
	$cur_cat = get_queried_object()->slug;
}


/* =========================================================
 * PAGE TITLE
 * ========================================================= */

if ( is_search() ) {

	$title = sprintf(
		__( 'Results for “%s”', 'eventnest' ),
		get_search_query()
	);

} elseif ( is_tax() ) {

	$title = single_term_title( '', false );

} else {

	$title = __( 'Explore events', 'eventnest' );

}


/* =========================================================
 * CHECK WHETHER FILTERS ARE ACTIVE
 * ========================================================= */

$has_filters = (bool) (
	get_search_query()
	|| $cur_city
	|| $cur_cat
	|| $when
	|| $free
);


/* =========================================================
 * CLEAR FILTER URL
 * ========================================================= */

$clear_url = get_post_type_archive_link( 'event' );

?>

<section class="page-head page-head--events">

	<div class="wrap">

		<div class="page-head__top">

			<div>

				<p class="eyebrow eyebrow--small">
					<?php
					esc_html_e(
						'Find your next campus moment',
						'eventnest'
					);
					?>
				</p>

				<h1>
					<?php echo esc_html( $title ); ?>
				</h1>

				<p class="section__sub">
					<?php
					esc_html_e(
						'Discover competitions, workshops, club activities and everything happening across your campus.',
						'eventnest'
					);
					?>
				</p>

			</div>


			<?php if ( current_user_can( 'edit_posts' ) ) : ?>

				<a
					class="btn btn--ghost btn--sm page-head__create"
					href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event' ) ); ?>"
				>
					+ <?php esc_html_e( 'Add event', 'eventnest' ); ?>
				</a>

			<?php endif; ?>

		</div>


		<!-- =================================================
		     EVENT FILTER FORM
		================================================== -->

		<form
			class="filters filters--events"
			method="get"
			action="<?php echo esc_url( $clear_url ); ?>"
		>

			<!-- IMPORTANT:
			     Force WordPress search to search the Event CPT -->
			<input
				type="hidden"
				name="post_type"
				value="event"
			>


			<!-- SEARCH -->

			<div class="filters__search">

				<svg
					aria-hidden="true"
					viewBox="0 0 24 24"
				>
					<circle
						cx="11"
						cy="11"
						r="7"
					></circle>

					<path
						d="m20 20-4-4"
					></path>
				</svg>

				<input
					class="filters__q"
					type="search"
					name="s"
					value="<?php echo esc_attr( get_search_query() ); ?>"
					placeholder="<?php esc_attr_e( 'Search events, workshops, competitions...', 'eventnest' ); ?>"
					aria-label="<?php esc_attr_e( 'Search events', 'eventnest' ); ?>"
				>

			</div>


			<!-- CATEGORY -->

			<?php if ( $cats ) : ?>

				<select
					name="event_category"
					aria-label="<?php esc_attr_e( 'Category', 'eventnest' ); ?>"
				>

					<option value="">
						<?php
						esc_html_e(
							'All categories',
							'eventnest'
						);
						?>
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

			<?php endif; ?>


			<!-- CITY -->

			<?php if ( $cities ) : ?>

				<select
					name="event_city"
					aria-label="<?php esc_attr_e( 'City', 'eventnest' ); ?>"
				>

					<option value="">
						<?php
						esc_html_e(
							'All cities',
							'eventnest'
						);
						?>
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

			<?php endif; ?>


			<!-- DATE RANGE -->

			<select
				name="when"
				aria-label="<?php esc_attr_e( 'Date range', 'eventnest' ); ?>"
			>

				<option value="">
					<?php
					esc_html_e(
						'Any upcoming date',
						'eventnest'
					);
					?>
				</option>

				<option
					value="week"
					<?php selected( $when, 'week' ); ?>
				>
					<?php
					esc_html_e(
						'Next 7 days',
						'eventnest'
					);
					?>
				</option>

				<option
					value="month"
					<?php selected( $when, 'month' ); ?>
				>
					<?php
					esc_html_e(
						'This month',
						'eventnest'
					);
					?>
				</option>

			</select>


			<!-- FREE EVENTS -->

			<label class="check">

				<input
					type="checkbox"
					name="free"
					value="1"
					<?php checked( $free ); ?>
				>

				<span>
					<?php
					esc_html_e(
						'Free only',
						'eventnest'
					);
					?>
				</span>

			</label>


			<!-- SUBMIT -->

			<button
				class="btn btn--brand btn--sm"
				type="submit"
			>
				<?php
				esc_html_e(
					'Show events',
					'eventnest'
				);
				?>
			</button>


			<!-- CLEAR -->

			<?php if ( $has_filters ) : ?>

				<a
					class="filters__clear"
					href="<?php echo esc_url( $clear_url ); ?>"
				>
					<?php
					esc_html_e(
						'Clear',
						'eventnest'
					);
					?>
				</a>

			<?php endif; ?>

		</form>

	</div>

</section>


<!-- =====================================================
     EVENT RESULTS
====================================================== -->

<section class="section section--tight events-results">

	<div class="wrap">


		<!-- RESULTS BAR -->

		<div class="results-bar">

			<?php if ( have_posts() ) : ?>

				<p class="results-count">

					<strong>
						<?php
						echo esc_html(
							number_format_i18n(
								(int) $wp_query->found_posts
							)
						);
						?>
					</strong>

					<?php
					echo esc_html(
						_n(
							'event',
							'events',
							(int) $wp_query->found_posts,
							'eventnest'
						)
					);
					?>

				</p>

			<?php else : ?>

				<p class="results-count">
					<?php
					esc_html_e(
						'No matching events',
						'eventnest'
					);
					?>
				</p>

			<?php endif; ?>


			<?php if ( $has_filters ) : ?>

				<span class="results-bar__hint">
					<?php
					esc_html_e(
						'Filters are applied to upcoming events.',
						'eventnest'
					);
					?>
				</span>

			<?php endif; ?>

		</div>


		<!-- =================================================
		     EVENT CARDS
		================================================== -->

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


			<!-- =================================================
			     PAGINATION
			================================================== -->

			<?php

			$pagination_args = array(
				'mid_size'  => 1,
				'prev_text' => __( 'Previous', 'eventnest' ),
				'next_text' => __( 'Next', 'eventnest' ),
			);


			/*
			 * Preserve active filters while moving
			 * between pagination pages.
			 */
			if ( ! empty( $_GET ) ) {

				$pagination_args['add_args'] = array();

				foreach ( wp_unslash( $_GET ) as $key => $value ) {

					if ( is_array( $value ) ) {
						continue;
					}

					$pagination_args['add_args'][ sanitize_key( $key ) ] =
						sanitize_text_field( $value );
				}
			}

			the_posts_pagination(
				$pagination_args
			);

			?>


		<?php else : ?>


			<!-- =================================================
			     EMPTY STATE
			================================================== -->

			<div class="empty empty--events">

				<div
					class="empty__icon"
					aria-hidden="true"
				>
					✦
				</div>

				<h2>
					<?php
					esc_html_e(
						'No upcoming events found',
						'eventnest'
					);
					?>
				</h2>

				<p>
					<?php
					esc_html_e(
						'Try another category, city or date range. You can also clear the filters to see all upcoming campus events.',
						'eventnest'
					);
					?>
				</p>

				<a
					class="btn btn--brand"
					href="<?php echo esc_url( $clear_url ); ?>"
				>
					<?php
					esc_html_e(
						'View all upcoming events',
						'eventnest'
					);
					?>
				</a>

			</div>


		<?php endif; ?>

	</div>

</section>


<?php get_footer(); ?>