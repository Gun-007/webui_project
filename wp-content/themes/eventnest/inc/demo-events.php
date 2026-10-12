<?php
/**
 * EventNest Demo Events
 *
 * Creates sample events directly in the WordPress database.
 *
 * Safe to run multiple times:
 * - Existing demo events are not duplicated.
 * - Missing demo events are created.
 * - Demo events are marked with _en_demo = 1.
 *
 * Also provides:
 * Events -> Event Check
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * ADMIN SUBMENU
 * ========================================================= */

add_action( 'admin_menu', function () {

	add_submenu_page(
		'edit.php?post_type=event',
		__( 'Event Check', 'eventnest' ),
		__( 'Event Check', 'eventnest' ),
		'manage_options',
		'en-demo-events',
		'en_demo_page'
	);

} );


/* =========================================================
 * CREATE / GET TERM
 * ========================================================= */

function en_demo_term( $name, $taxonomy ) {

	$name = sanitize_text_field( $name );

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return 0;
	}

	$existing = term_exists( $name, $taxonomy );

	if ( $existing ) {

		if ( is_array( $existing ) && isset( $existing['term_id'] ) ) {
			return (int) $existing['term_id'];
		}

		return (int) $existing;
	}

	$created = wp_insert_term(
		$name,
		$taxonomy
	);

	if ( is_wp_error( $created ) ) {
		return 0;
	}

	return isset( $created['term_id'] )
		? (int) $created['term_id']
		: 0;
}


/* =========================================================
 * EVENT META KEY HELPER
 * ========================================================= */

function en_demo_meta_key( $key ) {

	/*
	 * Use the existing EventNest helper whenever available.
	 * This keeps the demo events compatible with your
	 * EventNest Core event fields.
	 */
	if ( function_exists( 'en_meta_key' ) ) {
		return en_meta_key( $key );
	}

	return '_en_' . sanitize_key( $key );
}


/* =========================================================
 * FIND EXISTING DEMO EVENT
 * ========================================================= */

function en_demo_existing_event( $title ) {

	$posts = get_posts(
		array(
			'post_type'      => 'event',
			'post_status'    => 'any',
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	return ! empty( $posts )
		? (int) $posts[0]
		: 0;
}


/* =========================================================
 * CREATE DEMO EVENTS
 * ========================================================= */

function en_demo_create() {

	if ( ! post_type_exists( 'event' ) ) {
		return array(
			'created' => 0,
			'existing' => 0,
			'errors' => array(
				'The event post type is not registered yet.'
			),
		);
	}


	/*
	 * Event dates are calculated from today.
	 * This guarantees the demo events remain upcoming.
	 */
	$day = function ( $days ) {

		return wp_date(
			'Y-m-d',
			current_time( 'timestamp' ) + ( $days * DAY_IN_SECONDS )
		);

	};


	$rules = implode(
		"\n",
		array(
			'Carry your college ID card.',
			'Arrive 15 minutes before the start.',
			'Follow the instructions of the organizers.',
		)
	);


	$highlights = implode(
		"\n",
		array(
			'Hands-on activities',
			'Meet students from other departments',
			'Certificates for participants',
			'Refreshments available',
		)
	);


	/*
	 * title,
	 * category,
	 * days from today,
	 * start,
	 * end,
	 * venue,
	 * registration deadline in days,
	 * price,
	 * organizer,
	 * description
	 */
	$events = array(

		array(
			'Open Mic Night',
			'Club Event',
			4,
			'18:00',
			'20:30',
			'Amphitheatre',
			2,
			0,
			'Music Club',
			'Sing, play or just listen. Sign up at the door and take the stage.',
		),

		array(
			'AI & ML Workshop',
			'Technical',
			6,
			'10:00',
			'13:00',
			'Main Lab',
			3,
			0,
			'Tech Club',
			'An introduction to machine learning with live demos. No experience needed.',
		),

		array(
			'Dance Crew Trials',
			'Club Event',
			8,
			'15:00',
			'18:00',
			'Dance Studio',
			5,
			0,
			'Dance Club',
			'Auditions for the new Dance Club crew. Bring your best moves.',
		),

		array(
			'Campus Quiz League',
			'Competition',
			9,
			'14:00',
			'16:30',
			'Seminar Hall',
			6,
			0,
			'Student Council',
			'A knowledge showdown between departments. Teams of three.',
		),

		array(
			'Inter-College Dance Competition',
			'Competition',
			11,
			'16:00',
			'20:00',
			'Main Auditorium',
			8,
			0,
			'Dance Club',
			'Crews from colleges across the city compete on one stage.',
		),

		array(
			'Startup Pitch Evening',
			'Workshop',
			13,
			'17:00',
			'19:30',
			'Seminar Hall',
			10,
			0,
			'Entrepreneurship Cell',
			'Student founders pitch their ideas to mentors and get live feedback.',
		),

		array(
			'Tech Club Workshop',
			'Workshop',
			14,
			'11:00',
			'15:00',
			'Computer Lab',
			12,
			0,
			'Tech Club',
			'Build your first web app in one afternoon with help from senior students.',
		),

		array(
			'Photography Walk',
			'Club Event',
			17,
			'16:30',
			'18:30',
			'Campus Gate',
			12,
			0,
			'Photography Club',
			'A relaxed golden-hour walk around campus with your camera or phone.',
		),

		array(
			'Cultural Fest 2026',
			'Cultural',
			21,
			'10:00',
			'21:00',
			'College Ground',
			19,
			99,
			'Cultural Club',
			'A full day of music, dance, food stalls and fun on the college ground.',
		),

		array(
			'Inter-College Coding Challenge',
			'Competition',
			25,
			'10:00',
			'15:00',
			'Innovation Lab',
			18,
			50,
			'Coding Club',
			'Solve problems against the clock and compete with the best teams in the city.',
		),

	);


	/* =====================================================
	 * CREATE CAMPUS TERM
	 * ===================================================== */

	$city = en_demo_term(
		'Campus',
		'event_city'
	);


	$created = 0;
	$existing = 0;
	$errors = array();


	/* =====================================================
	 * CREATE EACH EVENT
	 * ===================================================== */

	foreach ( $events as $event ) {

		$title       = $event[0];
		$category    = $event[1];
		$days        = (int) $event[2];
		$start       = $event[3];
		$end         = $event[4];
		$venue       = $event[5];
		$deadline    = (int) $event[6];
		$price       = $event[7];
		$organizer   = $event[8];
		$description = $event[9];


		/*
		 * Do not create duplicates.
		 */
		$existing_id = en_demo_existing_event(
			$title
		);


		if ( $existing_id ) {

			/*
			 * Mark it as a demo event in case it was created
			 * by an earlier version of the demo importer.
			 */
			update_post_meta(
				$existing_id,
				'_en_demo',
				1
			);

			$existing++;

			continue;
		}


		/* =================================================
		 * INSERT EVENT
		 * ================================================= */

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'event',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_excerpt' => $description,
				'post_content' => '<p>' . esc_html( $description ) . '</p>',
				'post_author'  => get_current_user_id(),
			),
			true
		);


		if ( is_wp_error( $post_id ) ) {

			$errors[] = $title . ': ' . $post_id->get_error_message();

			continue;
		}


		/* =================================================
		 * EVENT META
		 * ================================================= */

		$meta = array(

			'date' => $day( $days ),

			'time' => $start,

			'end_time' => $end,

			'venue' => $venue,

			'registration_deadline' => $day(
				max( 0, $deadline )
			),

			'price' => (string) $price,

			'organizer' => $organizer,

			'rules' => $rules,

			'highlights' => $highlights,

		);


		foreach ( $meta as $key => $value ) {

			update_post_meta(
				$post_id,
				en_demo_meta_key( $key ),
				$value
			);

		}


		/*
		 * Mark this event as created by the demo system.
		 */
		update_post_meta(
			$post_id,
			'_en_demo',
			1
		);


		/* =================================================
		 * CATEGORY
		 * ================================================= */

		$category_id = en_demo_term(
			$category,
			'event_category'
		);


		if ( $category_id ) {

			wp_set_object_terms(
				$post_id,
				array( $category_id ),
				'event_category',
				false
			);

		}


		/* =================================================
		 * CITY
		 * ================================================= */

		if ( $city ) {

			wp_set_object_terms(
				$post_id,
				array( $city ),
				'event_city',
				false
			);

		}


		$created++;

	}


	/*
	 * Mark the seeding process as completed.
	 */
	update_option(
		'en_demo_made',
		1
	);


	return array(
		'created'  => $created,
		'existing' => $existing,
		'errors'   => $errors,
	);

}


/* =========================================================
 * REMOVE DEMO EVENTS
 * ========================================================= */

function en_demo_remove() {

	$ids = get_posts(
		array(
			'post_type'      => 'event',
			'post_status'    => 'any',
			'numberposts'    => -1,
			'fields'         => 'ids',
			'meta_key'       => '_en_demo',
			'meta_value'     => 1,
			'suppress_filters' => true,
		)
	);


	$removed = 0;


	foreach ( $ids as $id ) {

		if ( wp_delete_post( $id, true ) ) {
			$removed++;
		}

	}


	/*
	 * Allow demo events to be created again.
	 */
	delete_option(
		'en_demo_made'
	);


	return $removed;

}


/* =========================================================
 * ADMIN ACTION
 * ========================================================= */

add_action(
	'admin_post_en_demo_events',
	function () {

		if ( ! current_user_can( 'manage_options' ) ) {

			wp_die(
				'Not allowed.',
				'',
				array(
					'response' => 403,
				)
			);

		}


		check_admin_referer(
			'en_demo_events'
		);


		$action = isset( $_POST['do'] )
			? sanitize_key(
				wp_unslash( $_POST['do'] )
			)
			: 'add';


		if ( 'remove' === $action ) {

			$removed = en_demo_remove();

			$url = add_query_arg(
				array(
					'page'    => 'en-demo-events',
					'done'    => 'removed',
					'removed' => $removed,
				),
				admin_url(
					'edit.php?post_type=event'
				)
			);

		} else {

			$result = en_demo_create();

			$url = add_query_arg(
				array(
					'page'    => 'en-demo-events',
					'done'    => 'added',
					'created' => $result['created'],
					'existing' => $result['existing'],
				),
				admin_url(
					'edit.php?post_type=event'
				)
			);

		}


		wp_safe_redirect(
			$url
		);

		exit;

	}
);


/* =========================================================
 * EVENT CHECK PAGE
 * ========================================================= */

function en_demo_page() {

	global $wpdb;


	$demo = (bool) get_option(
		'en_demo_made'
	);


	$done = isset( $_GET['done'] )
		? sanitize_key(
			wp_unslash( $_GET['done'] )
		)
		: '';


	$date_key = en_demo_meta_key(
		'date'
	);


	/* =====================================================
	 * DATABASE COUNTS
	 * ===================================================== */

	$counts = wp_count_posts(
		'event'
	);


	$published = isset( $counts->publish )
		? (int) $counts->publish
		: 0;


	$drafts =
		( isset( $counts->draft )
			? (int) $counts->draft
			: 0 )
		+
		( isset( $counts->pending )
			? (int) $counts->pending
			: 0 );


	$with_date = (int) $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT COUNT(DISTINCT p.ID)

			FROM {$wpdb->posts} p

			INNER JOIN {$wpdb->postmeta} m
				ON m.post_id = p.ID

			WHERE p.post_type = 'event'

			AND p.post_status = 'publish'

			AND m.meta_key = %s

			AND m.meta_value <> ''
			",
			$date_key
		)
	);


	/* =====================================================
	 * UPCOMING COUNT
	 * ===================================================== */

	$upcoming = function_exists( 'en_upcoming' )
		? en_upcoming( 100 )
		: new WP_Query(
			array(
				'post_type'      => 'event',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
				'meta_key'       => $date_key,
				'orderby'        => 'meta_value',
				'order'          => 'ASC',
				'meta_query'     => array(
					array(
						'key'     => $date_key,
						'value'   => current_time( 'Y-m-d' ),
						'compare' => '>=',
						'type'    => 'DATE',
					),
				),
			)
		);


	$shown = (int) $upcoming->found_posts;


	/* =====================================================
	 * DEMO EVENT COUNT
	 * ===================================================== */

	$demo_count = new WP_Query(
		array(
			'post_type'      => 'event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_en_demo',
			'meta_value'     => 1,
		)
	);


	$demo_total = count(
		$demo_count->posts
	);


	?>

	<div class="wrap">

		<h1>
			<?php esc_html_e( 'Event Check', 'eventnest' ); ?>
		</h1>


		<?php if ( 'added' === $done ) : ?>

			<div class="notice notice-success is-dismissible">

				<p>

					<strong>
						Demo events processed successfully.
					</strong>

					<?php

					$created = isset( $_GET['created'] )
						? absint( $_GET['created'] )
						: 0;

					$existing = isset( $_GET['existing'] )
						? absint( $_GET['existing'] )
						: 0;

					printf(
						' Created: %d. Already existed: %d.',
						$created,
						$existing
					);

					?>

				</p>

			</div>

		<?php endif; ?>


		<?php if ( 'removed' === $done ) : ?>

			<div class="notice notice-success is-dismissible">

				<p>
					<?php

					$removed = isset( $_GET['removed'] )
						? absint( $_GET['removed'] )
						: 0;

					printf(
						'Removed %d demo event(s).',
						$removed
					);

					?>
				</p>

			</div>

		<?php endif; ?>


		<h2>
			<?php esc_html_e( 'What the Events page reads from the database', 'eventnest' ); ?>
		</h2>


		<p>

			<?php esc_html_e(
				'The Events page uses the event custom post type and the event date stored in WordPress post meta.',
				'eventnest'
			); ?>

		</p>


		<table
			class="widefat striped"
			style="max-width:650px"
		>

			<tbody>

				<tr>

					<td>
						<strong>Published events</strong>
					</td>

					<td>
						<strong>
							<?php echo esc_html( $published ); ?>
						</strong>
					</td>

				</tr>


				<tr>

					<td>
						<strong>Drafts / pending</strong>
					</td>

					<td>
						<strong>
							<?php echo esc_html( $drafts ); ?>
						</strong>
					</td>

				</tr>


				<tr>

					<td>
						<strong>Published events with a date</strong>
					</td>

					<td>
						<strong>
							<?php echo esc_html( $with_date ); ?>
						</strong>
					</td>

				</tr>


				<tr>

					<td>
						<strong>Upcoming events</strong>
					</td>

					<td>
						<strong>
							<?php echo esc_html( $shown ); ?>
						</strong>
					</td>

				</tr>


				<tr>

					<td>
						<strong>Demo events</strong>
					</td>

					<td>
						<strong>
							<?php echo esc_html( $demo_total ); ?>
						</strong>
					</td>

				</tr>

			</tbody>

		</table>


		<?php if ( ! $published ) : ?>

			<div class="notice notice-warning inline">

				<p>
					There are currently no published events.
				</p>

			</div>


		<?php elseif ( $published > $with_date ) : ?>

			<div class="notice notice-warning inline">

				<p>
					Some published events do not have an event date.
					Those events will not appear in the upcoming Events listing.
				</p>

			</div>


		<?php elseif ( ! $shown ) : ?>

			<div class="notice notice-warning inline">

				<p>
					Your published events have dates, but none are upcoming.
				</p>

			</div>


		<?php else : ?>

			<div class="notice notice-success inline">

				<p>
					Your database contains upcoming events that can be displayed.
				</p>

			</div>

		<?php endif; ?>


		<p style="margin-top:20px">

			<a
				class="button button-primary"
				href="<?php echo esc_url(
					get_post_type_archive_link( 'event' )
				); ?>"
				target="_blank"
			>
				Open Events Page
			</a>


			<a
				class="button"
				href="<?php echo esc_url(
					admin_url(
						'post-new.php?post_type=event'
					)
				); ?>"
			>
				Add New Event
			</a>

		</p>


		<hr style="margin:30px 0;">


		<h2>
			Demo Events
		</h2>


		<p>
			This creates 10 sample EventNest events directly in the WordPress database.
			Running the action again will not create duplicates.
		</p>


		<form
			method="post"
			action="<?php echo esc_url(
				admin_url( 'admin-post.php' )
			); ?>"
		>

			<input
				type="hidden"
				name="action"
				value="en_demo_events"
			>


			<input
				type="hidden"
				name="do"
				value="<?php echo $demo ? 'remove' : 'add'; ?>"
			>


			<?php wp_nonce_field( 'en_demo_events' ); ?>


			<?php if ( $demo ) : ?>

				<button
					type="submit"
					class="button"
				>
					Remove Demo Events
				</button>

			<?php else : ?>

				<button
					type="submit"
					class="button button-primary"
				>
					Add Demo Events
				</button>

			<?php endif; ?>

		</form>

	</div>

	<?php
}