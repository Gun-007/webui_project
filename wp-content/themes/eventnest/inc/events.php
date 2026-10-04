<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- Post type + taxonomies ---------- */
function en_register_content() {
	if ( ! post_type_exists( 'event' ) ) register_post_type( 'event', array(
		'labels' => array(
			'name' => __( 'Events', 'eventnest' ), 'singular_name' => __( 'Event', 'eventnest' ),
			'add_new_item' => __( 'Add new event', 'eventnest' ), 'edit_item' => __( 'Edit event', 'eventnest' ),
			'all_items' => __( 'All events', 'eventnest' ), 'menu_name' => __( 'Events', 'eventnest' ),
		),
		'public' => true, 'has_archive' => 'events', 'rewrite' => array( 'slug' => 'events' ),
		'menu_icon' => 'dashicons-tickets-alt', 'menu_position' => 5, 'show_in_rest' => true,
		'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
	) );
	if ( ! taxonomy_exists( 'event_category' ) ) register_taxonomy( 'event_category', 'event', array(
		'labels' => array( 'name' => __( 'Categories', 'eventnest' ), 'singular_name' => __( 'Category', 'eventnest' ) ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'query_var' => true,
		'rewrite' => array( 'slug' => 'event-category' ),
	) );
	if ( ! taxonomy_exists( 'event_city' ) ) register_taxonomy( 'event_city', 'event', array(
		'labels' => array( 'name' => __( 'Cities', 'eventnest' ), 'singular_name' => __( 'City', 'eventnest' ) ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'query_var' => true,
		'rewrite' => array( 'slug' => 'event-city' ),
	) );
}
add_action( 'init', 'en_register_content', 20 );

add_action( 'after_switch_theme', function () {
	en_register_content();
	flush_rewrite_rules();
} );

/* ---------- Event details meta box ---------- */
function en_meta_fields() {
	if ( function_exists( 'enc_event_fields' ) ) {
		return array(
			'address'    => array( 'Room / building details (optional)', 'text' ),
			'rules'      => array( 'Rules & guidelines', 'textarea' ),
			'highlights' => array( 'Highlights (one per line)', 'textarea' ),
			'ticket_url' => array( 'External registration link (optional)', 'url' ),
		);
	}
	return array(
		'date'       => array( 'Date', 'date' ),
		'time'       => array( 'Start time', 'time' ),
		'end_time'   => array( 'End time', 'time' ),
		'venue'      => array( 'Venue name', 'text' ),
		'address'    => array( 'Full address', 'text' ),
		'registration_deadline' => array( 'Registration deadline', 'date' ),
		'max_participants' => array( 'Maximum participants (optional)', 'number' ),
		'rules'      => array( 'Rules & guidelines', 'textarea' ),
		'highlights' => array( 'Highlights (one per line)', 'textarea' ),
		'price'      => array( 'Ticket price in ₹ (0 or empty = free)', 'number' ),
		'ticket_url' => array( 'Ticket or registration link', 'url' ),
		'organizer'  => array( 'Organizer name', 'text' ),
	);
}

function en_meta_key( $key ) {
	if ( function_exists( 'enc_event_fields' ) ) {
		$plugin_keys = array(
			'date'                 => '_en_date',
			'time'                 => '_en_start',
			'end_time'             => '_en_end',
			'venue'                => '_en_venue',
			'registration_deadline' => '_en_deadline',
			'max_participants'     => '_en_max',
			'price'                => '_en_fee',
		);
		if ( isset( $plugin_keys[ $key ] ) ) return $plugin_keys[ $key ];
	}
	return '_en_' . $key;
}

function en_meta( $id, $key ) {
	if ( 'organizer' === $key && function_exists( 'enc_event_fields' ) ) {
		$club_id = absint( get_post_meta( $id, '_en_club', true ) );
		$club    = $club_id ? get_the_title( $club_id ) : '';
		return $club ?: get_post_meta( $id, '_en_incharge', true );
	}
	return get_post_meta( $id, en_meta_key( $key ), true );
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'en_details', __( 'Event details', 'eventnest' ), 'en_meta_box', 'event', 'normal', 'high' );
} );

function en_meta_box( $post ) {
	wp_nonce_field( 'en_save_event', 'en_nonce' );
	echo '<div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px">';
	foreach ( en_meta_fields() as $key => $f ) {
		$val  = esc_attr( en_meta( $post->ID, $key ) );
		$step = $f[1] === 'number' ? ' min="0" step="1"' : '';
		if ( 'textarea' === $f[1] ) {
			printf( '<p style="margin:0;grid-column:1/-1"><label for="en_%1$s"><strong>%2$s</strong></label><br><textarea style="width:100%%;min-height:90px" id="en_%1$s" name="en_%1$s">%3$s</textarea></p>', esc_attr( $key ), esc_html( $f[0] ), esc_textarea( en_meta( $post->ID, $key ) ) );
		} else {
			printf( '<p style="margin:0"><label for="en_%1$s"><strong>%2$s</strong></label><br><input style="width:100%%" type="%3$s" id="en_%1$s" name="en_%1$s" value="%4$s"%5$s></p>', esc_attr( $key ), esc_html( $f[0] ), esc_attr( $f[1] ), $val, $step );
		}
	}
	echo '</div>';
}

add_action( 'save_post_event', function ( $post_id ) {
	if ( ! isset( $_POST['en_nonce'] ) || ! wp_verify_nonce( $_POST['en_nonce'], 'en_save_event' ) ) return;
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	foreach ( en_meta_fields() as $key => $f ) {
		$raw = isset( $_POST[ 'en_' . $key ] ) ? wp_unslash( $_POST[ 'en_' . $key ] ) : '';
		if ( 'url' === $f[1] ) {
			$val = esc_url_raw( $raw );
		} elseif ( 'textarea' === $f[1] ) {
			$val = sanitize_textarea_field( $raw );
		} elseif ( 'number' === $f[1] ) {
			$val = max( 0, absint( $raw ) );
		} else {
			$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, en_meta_key( $key ), $val );
	}
} );

/* ---------- Helpers ---------- */
function en_fmt_date( $date, $fmt = 'j M' ) {
	return $date ? date_i18n( $fmt, strtotime( $date ) ) : '';
}
function en_fmt_time( $time ) {
	return $time ? date_i18n( 'g:i A', strtotime( $time ) ) : '';
}
function en_price_label( $price ) {
	if ( $price === '' || $price === null || (float) $price <= 0 ) return __( 'Free', 'eventnest' );
	return '₹' . number_format_i18n( (float) $price );
}
function en_first_term( $id, $tax ) {
	$t = get_the_terms( $id, $tax );
	return ( $t && ! is_wp_error( $t ) ) ? $t[0] : null;
}

function en_card_data( $id ) {
	$cat  = en_first_term( $id, 'event_category' );
	$city = en_first_term( $id, 'event_city' );
	return array(
		'url'    => get_permalink( $id ),
		'title'  => get_the_title( $id ),
		'img'    => has_post_thumbnail( $id ) ? get_the_post_thumbnail( $id, 'en-card', array( 'loading' => 'lazy' ) ) : '',
		'date'   => en_meta( $id, 'date' ),
		'deadline' => en_meta( $id, 'registration_deadline' ),
		'time'   => en_meta( $id, 'time' ),
		'end_time' => en_meta( $id, 'end_time' ),
		'venue'  => en_meta( $id, 'venue' ),
		'city'   => $city ? $city->name : '',
		'cat'    => $cat ? $cat->name : '',
		'price'  => en_price_label( en_meta( $id, 'price' ) ),
		'hue'    => ( $id * 47 ) % 360,
		'sample' => false,
	);
}

function en_sample_events() {
	$mk = function ( $title, $days, $time, $venue, $city, $cat, $price, $hue ) {
		return array( 'url' => '#', 'title' => $title, 'img' => '', 'date' => date( 'Y-m-d', strtotime( "+$days days", current_time( 'timestamp' ) ) ),
			'deadline' => date( 'Y-m-d', strtotime( '+' . max( 0, $days - 2 ) . ' days', current_time( 'timestamp' ) ) ),
			'time' => $time, 'venue' => $venue, 'city' => $city, 'cat' => $cat, 'price' => $price, 'hue' => $hue, 'sample' => true );
	};
	return array(
		$mk( 'Inter-College Dance Competition', 6, '15:00', 'Main Auditorium', 'Campus', 'Competition', 'Free', 330 ),
		$mk( 'Tech Club Workshop', 9, '10:00', 'Computer Lab', 'Campus', 'Workshop', 'Free', 205 ),
		$mk( 'Cultural Fest 2026', 12, '17:00', 'College Ground', 'Campus', 'Cultural', 'Free', 275 ),
		$mk( 'AI & ML Workshop', 15, '11:00', 'Seminar Hall', 'Campus', 'Technical', 'Free', 220 ),
		$mk( 'Photography Walk', 19, '08:00', 'Campus Gate', 'Campus', 'Club Event', 'Free', 25 ),
		$mk( 'Inter-College Coding Challenge', 24, '09:00', 'Innovation Lab', 'Campus', 'Competition', 'Free', 180 ),
	);
}

function en_upcoming( $n = 6, $extra = array() ) {

	$date_key = en_meta_key( 'date' );

	$defaults = array(
		'post_type'           => 'event',
		'post_status'         => 'publish',
		'posts_per_page'      => $n,
		'ignore_sticky_posts' => true,
		'meta_key'            => $date_key,
		'orderby'             => 'meta_value',
		'order'               => 'ASC',

		'meta_query' => array(
			array(
				'key'     => $date_key,
				'value'   => current_time( 'Y-m-d' ),
				'compare' => '>=',
				'type'    => 'DATE',
			),
		),
	);

	return new WP_Query(
		array_merge( $defaults, $extra )
	);
}

/* ---------- Competitions page ---------- */
add_action( 'init', function () {
	add_shortcode( 'eventnest_competitions', 'en_competitions_shortcode' );
} );

function en_competitions_shortcode() {
	if ( ! taxonomy_exists( 'event_type' ) ) {
		return '<div class="empty"><h2>Competitions are unavailable</h2><p>Activate EventNest Core to browse competitions.</p></div>';
	}

	$scope = isset( $_GET['scope'] ) ? sanitize_key( wp_unslash( $_GET['scope'] ) ) : '';
	if ( ! in_array( $scope, array( 'intra', 'inter' ), true ) ) $scope = '';
	$category = isset( $_GET['competition_category'] ) ? sanitize_title( wp_unslash( $_GET['competition_category'] ) ) : '';
	$when = isset( $_GET['competition_when'] ) ? sanitize_key( wp_unslash( $_GET['competition_when'] ) ) : '';
	if ( ! in_array( $when, array( 'week', 'month' ), true ) ) $when = '';
	$search = isset( $_GET['competition_q'] ) ? sanitize_text_field( wp_unslash( $_GET['competition_q'] ) ) : '';
	$tax_query = array( 'relation' => 'AND', array( 'taxonomy' => 'event_type', 'field' => 'slug', 'terms' => 'competition' ) );
	if ( $category && taxonomy_exists( 'event_category' ) ) $tax_query[] = array( 'taxonomy' => 'event_category', 'field' => 'slug', 'terms' => $category );
	$meta_query = array( 'relation' => 'AND', array( 'key' => '_en_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) );
	if ( $when ) {
		$last_day = 'week' === $when ? wp_date( 'Y-m-d', current_time( 'timestamp' ) + 7 * DAY_IN_SECONDS ) : wp_date( 'Y-m-t' );
		$meta_query[0] = array( 'key' => '_en_date', 'value' => array( current_time( 'Y-m-d' ), $last_day ), 'compare' => 'BETWEEN', 'type' => 'DATE' );
	}
	if ( $scope ) $meta_query[] = array( 'key' => '_en_scope', 'value' => $scope, 'compare' => '=' );
	$page = isset( $_GET['cpage'] ) ? max( 1, absint( $_GET['cpage'] ) ) : 1;
	$query = new WP_Query( array(
		'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => 9, 'paged' => $page,
		's' => $search, 'tax_query' => $tax_query, 'meta_query' => $meta_query,
		'meta_key' => '_en_date', 'orderby' => 'meta_value', 'order' => 'ASC',
	) );
	$categories = taxonomy_exists( 'event_category' ) ? get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => false ) ) : array();
	if ( is_wp_error( $categories ) ) $categories = array();
	$url = get_permalink();
	$out = '<section class="en-competitions"><div class="wrap">';
	$out .= '<p class="section__sub">Browse intra-college and inter-college competitions.</p>';
	$out .= '<form class="filters en-competition-filters" method="get" action="' . esc_url( $url ) . '">';
	$out .= '<input class="filters__q" type="search" name="competition_q" value="' . esc_attr( $search ) . '" placeholder="Search competitions" aria-label="Search competitions">';
	$out .= '<select name="scope" aria-label="Competition scope"><option value="">All competitions</option><option value="intra"' . selected( $scope, 'intra', false ) . '>Intra-College</option><option value="inter"' . selected( $scope, 'inter', false ) . '>Inter-College</option></select>';
	if ( $categories ) {
		$out .= '<select name="competition_category" aria-label="Category"><option value="">All categories</option>';
		foreach ( $categories as $term ) $out .= '<option value="' . esc_attr( $term->slug ) . '"' . selected( $category, $term->slug, false ) . '>' . esc_html( $term->name ) . '</option>';
		$out .= '</select>';
	}
	$out .= '<select name="competition_when" aria-label="Date range"><option value="">Any upcoming date</option><option value="week"' . selected( $when, 'week', false ) . '>Next 7 days</option><option value="month"' . selected( $when, 'month', false ) . '>This month</option></select>';
	$out .= '<button class="btn btn--brand btn--sm" type="submit">Show competitions</button></form>';
	if ( $query->have_posts() ) {
		$out .= '<p class="results-count">' . esc_html( sprintf( _n( '%s competition found', '%s competitions found', (int) $query->found_posts, 'eventnest' ), number_format_i18n( $query->found_posts ) ) ) . '</p><div class="grid event-grid">';
		while ( $query->have_posts() ) { $query->the_post(); ob_start(); en_card( en_card_data( get_the_ID() ) ); $out .= ob_get_clean(); }
		$out .= '</div>';
		if ( $query->max_num_pages > 1 ) {
			$clean_url = remove_query_arg( 'cpage' );
			$out .= '<nav class="en-competition-pagination" aria-label="Competition pages">';
			if ( $page > 1 ) $out .= '<a class="btn btn--ghost" href="' . esc_url( add_query_arg( 'cpage', $page - 1, $clean_url ) ) . '">Previous</a>';
			if ( $page < $query->max_num_pages ) $out .= '<a class="btn btn--ghost" href="' . esc_url( add_query_arg( 'cpage', $page + 1, $clean_url ) ) . '">Next</a>';
			$out .= '</nav>';
		}
	} else {
		$out .= '<div class="empty"><h2>No upcoming competitions match these filters</h2><p>Try another category or date range, or clear your search.</p><a class="btn btn--brand" href="' . esc_url( $url ) . '">Clear filters</a></div>';
	}
	wp_reset_postdata();
	return $out . '</div></section>';
}

/* ---------- Card renderer ---------- */
function en_card( array $e ) {
	$day = $e['date'] ? en_fmt_date( $e['date'], 'j' ) : '';
	$mon = $e['date'] ? en_fmt_date( $e['date'], 'M' ) : '';
	$where = trim( $e['venue'] . ( $e['venue'] && $e['city'] ? ', ' : '' ) . $e['city'] );
	?>
	<article class="ev-card">
		<a class="ev-card__media" href="<?php echo esc_url( $e['url'] ); ?>" style="--h:<?php echo (int) $e['hue']; ?>" tabindex="-1" aria-hidden="true">
			<?php echo $e['img']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php if ( $day ) : ?><span class="ev-card__date"><b><?php echo esc_html( $day ); ?></b><?php echo esc_html( $mon ); ?></span><?php endif; ?>
			<?php if ( $e['cat'] ) : ?><span class="ev-card__cat"><?php echo esc_html( $e['cat'] ); ?></span><?php endif; ?>
		</a>
		<div class="ev-card__body">

	<h3 class="ev-card__title">

		<a href="<?php echo esc_url( $e['url'] ); ?>">

			<?php echo esc_html( $e['title'] ); ?>

		</a>

	</h3>


	<?php if ( $where ) : ?>

		<p class="ev-card__where">

			📍 <?php echo esc_html( $where ); ?>

		</p>

	<?php endif; ?>


	<div class="ev-card__foot">

		<span class="ev-card__time">

			<?php echo esc_html( en_fmt_time( $e['time'] ) ); ?>

		</span>


		<span class="chip chip--price">

			<?php echo esc_html( $e['price'] ); ?>

		</span>

	</div>


	<a
		class="ev-card__action"
		href="<?php echo esc_url( $e['url'] ); ?>"
	>

		<?php esc_html_e( 'View event', 'eventnest' ); ?>

		<span aria-hidden="true">→</span>

	</a>

</div>
	</article>
	<?php
}

/* ---------- Archive filtering (search box, city, category, free) ---------- */
/* ---------- Event archive filtering ---------- */

/* ---------- Event archive filtering ---------- */

add_action( 'pre_get_posts', function ( $q ) {

	if ( is_admin() || ! $q->is_main_query() ) {
		return;
	}

	/*
	 * Detect the Event archive/search query.
	 */
	$post_type = $q->get( 'post_type' );

	$is_event_archive =
		$q->is_post_type_archive( 'event' )
		|| 'event' === $post_type
		|| (
			is_array( $post_type )
			&& in_array( 'event', $post_type, true )
		)
		|| $q->is_tax( array( 'event_category', 'event_city' ) );

	if ( ! $is_event_archive ) {
		return;
	}


	/*
	 * Make absolutely sure the query only contains Events.
	 */
	$q->set( 'post_type', 'event' );
	$q->set( 'post_status', 'publish' );
	$q->set( 'posts_per_page', 12 );


	/*
	 * Event date.
	 */
	$date_key = en_meta_key( 'date' );


	/*
	 * Build meta query.
	 *
	 * IMPORTANT:
	 * We do NOT require a date here initially.
	 * This prevents an event from disappearing just
	 * because the date field wasn't saved correctly.
	 */
	$meta_query = array();


	/*
	 * Date filter.
	 */
	$when = isset( $_GET['when'] )
		? sanitize_key(
			wp_unslash( $_GET['when'] )
		)
		: '';


	$today = current_time( 'Y-m-d' );


	if ( 'week' === $when ) {

		$last_day = wp_date(
			'Y-m-d',
			current_time( 'timestamp' ) + ( 7 * DAY_IN_SECONDS )
		);

		$meta_query[] = array(
			'key'     => $date_key,
			'value'   => array( $today, $last_day ),
			'compare' => 'BETWEEN',
			'type'    => 'DATE',
		);

	} elseif ( 'month' === $when ) {

		$last_day = wp_date(
			'Y-m-t',
			current_time( 'timestamp' )
		);

		$meta_query[] = array(
			'key'     => $date_key,
			'value'   => array( $today, $last_day ),
			'compare' => 'BETWEEN',
			'type'    => 'DATE',
		);

	} else {

		/*
		 * Default:
		 * show upcoming events.
		 *
		 * We use EXISTS instead of forcing a date value
		 * so events with other valid data don't disappear.
		 */
		$meta_query[] = array(
			'key'     => $date_key,
			'compare' => 'EXISTS',
		);

	}


	/*
	 * Free-only filter.
	 */
	$free = isset( $_GET['free'] )
		&& '1' === sanitize_text_field(
			wp_unslash( $_GET['free'] )
		);


	if ( $free ) {

		$price_key = en_meta_key( 'price' );

		$meta_query[] = array(
			'relation' => 'OR',

			array(
				'key'     => $price_key,
				'compare' => 'NOT EXISTS',
			),

			array(
				'key'     => $price_key,
				'value'   => array(
					'',
					'0',
					'0.00',
				),
				'compare' => 'IN',
			),

		);

	}


	/*
	 * Category filter.
	 */
	$tax_query = array();


	if ( ! empty( $_GET['event_category'] ) ) {

		$category = sanitize_title(
			wp_unslash(
				$_GET['event_category']
			)
		);

		if ( $category ) {

			$tax_query[] = array(
				'taxonomy' => 'event_category',
				'field'    => 'slug',
				'terms'    => $category,
			);

		}

	}


	/*
	 * City filter.
	 */
	if ( ! empty( $_GET['event_city'] ) ) {

		$city = sanitize_title(
			wp_unslash(
				$_GET['event_city']
			)
		);

		if ( $city ) {

			$tax_query[] = array(
				'taxonomy' => 'event_city',
				'field'    => 'slug',
				'terms'    => $city,
			);

		}

	}


	/*
	 * Apply taxonomy filtering.
	 */
	if ( ! empty( $tax_query ) ) {

		$tax_query['relation'] = 'AND';

		$q->set(
			'tax_query',
			$tax_query
		);

	}


	/*
	 * Apply meta filtering.
	 */
	if ( ! empty( $meta_query ) ) {

		$q->set(
			'meta_query',
			$meta_query
		);

	}


	/*
	 * Search.
	 */
	if ( ! empty( $_GET['s'] ) ) {

		$search = sanitize_text_field(
			wp_unslash(
				$_GET['s']
			)
		);

		$q->set(
			's',
			$search
		);

	}


	/*
	 * Sort events by event date.
	 */
	$q->set(
		'meta_key',
		$date_key
	);

	$q->set(
		'orderby',
		'meta_value'
	);

	$q->set(
		'order',
		'ASC'
	);

} );

/* ---------- Google Calendar link ---------- */
function en_calendar_url( $id ) {
	$date = en_meta( $id, 'date' );
	if ( ! $date ) return '';
	$time  = en_meta( $id, 'time' ) ?: '10:00';
	$start = strtotime( "$date $time" );
	$end   = $start + 3 * HOUR_IN_SECONDS;
	return add_query_arg( array(
		'action'   => 'TEMPLATE',
		'text'     => get_the_title( $id ),
		'dates'    => gmdate( 'Ymd\THis', $start ) . '/' . gmdate( 'Ymd\THis', $end ),
		'location' => trim( en_meta( $id, 'venue' ) . ' ' . en_meta( $id, 'address' ) ),
	), 'https://calendar.google.com/calendar/render' );
}
