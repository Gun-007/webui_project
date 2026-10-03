<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------- Post type + taxonomies ---------- */
function en_register_content() {
	register_post_type( 'event', array(
		'labels' => array(
			'name' => __( 'Events', 'eventnest' ), 'singular_name' => __( 'Event', 'eventnest' ),
			'add_new_item' => __( 'Add new event', 'eventnest' ), 'edit_item' => __( 'Edit event', 'eventnest' ),
			'all_items' => __( 'All events', 'eventnest' ), 'menu_name' => __( 'Events', 'eventnest' ),
		),
		'public' => true, 'has_archive' => 'events', 'rewrite' => array( 'slug' => 'event' ),
		'menu_icon' => 'dashicons-tickets-alt', 'menu_position' => 5, 'show_in_rest' => true,
		'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
	) );
	register_taxonomy( 'event_category', 'event', array(
		'labels' => array( 'name' => __( 'Categories', 'eventnest' ), 'singular_name' => __( 'Category', 'eventnest' ) ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'query_var' => true,
		'rewrite' => array( 'slug' => 'event-category' ),
	) );
	register_taxonomy( 'event_city', 'event', array(
		'labels' => array( 'name' => __( 'Cities', 'eventnest' ), 'singular_name' => __( 'City', 'eventnest' ) ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true, 'query_var' => true,
		'rewrite' => array( 'slug' => 'event-city' ),
	) );
}
add_action( 'init', 'en_register_content' );

add_action( 'after_switch_theme', function () {
	en_register_content();
	flush_rewrite_rules();
} );

/* ---------- Event details meta box ---------- */
function en_meta_fields() {
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

function en_meta( $id, $key ) {
	return get_post_meta( $id, '_en_' . $key, true );
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
		update_post_meta( $post_id, '_en_' . $key, $val );
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
	return new WP_Query( array_merge( array(
		'post_type' => 'event', 'posts_per_page' => $n, 'ignore_sticky_posts' => true,
		'meta_key' => '_en_date', 'orderby' => 'meta_value', 'order' => 'ASC',
		'meta_query' => array( array( 'key' => '_en_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ),
	), $extra ) );
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
			<h3 class="ev-card__title"><a href="<?php echo esc_url( $e['url'] ); ?>"><?php echo esc_html( $e['title'] ); ?></a></h3>
			<?php if ( $where ) : ?><p class="ev-card__where"><?php echo esc_html( $where ); ?></p><?php endif; ?>
			<div class="ev-card__foot">
				<span class="ev-card__time"><?php echo esc_html( en_fmt_time( $e['time'] ) ); ?></span>
				<span class="chip chip--price"><?php echo esc_html( $e['price'] ); ?></span>
			</div>
		</div>
	</article>
	<?php
}

/* ---------- Archive filtering (search box, city, category, free) ---------- */
add_action( 'pre_get_posts', function ( $q ) {
	if ( is_admin() || ! $q->is_main_query() ) return;
	$is_events = $q->is_post_type_archive( 'event' ) || $q->is_tax( array( 'event_category', 'event_city' ) )
		|| ( $q->is_search() && $q->get( 'post_type' ) === 'event' );
	if ( ! $is_events ) return;

	$today = current_time( 'Y-m-d' );
	$mq    = array( array( 'key' => '_en_date', 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ) );
	$when  = isset( $_GET['when'] ) ? sanitize_key( wp_unslash( $_GET['when'] ) ) : '';
	if ( 'week' === $when || 'month' === $when ) {
		$last_day = 'week' === $when
			? date( 'Y-m-d', current_time( 'timestamp' ) + 7 * DAY_IN_SECONDS )
			: date( 'Y-m-t', current_time( 'timestamp' ) );
		$mq[0] = array( 'key' => '_en_date', 'value' => array( $today, $last_day ), 'compare' => 'BETWEEN', 'type' => 'DATE' );
	}
	if ( isset( $_GET['free'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['free'] ) ) ) {
		$mq[] = array( 'relation' => 'OR',
			array( 'key' => '_en_price', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_en_price', 'value' => array( '', '0' ), 'compare' => 'IN' ) );
	}
	$q->set( 'post_type', 'event' );
	$q->set( 'posts_per_page', 12 );
	$q->set( 'meta_key', '_en_date' );
	$q->set( 'orderby', 'meta_value' );
	$q->set( 'order', 'ASC' );
	$q->set( 'meta_query', $mq );
} );

add_filter( 'template_include', function ( $t ) {
	if ( is_search() && get_query_var( 'post_type' ) === 'event' ) {
		$n = locate_template( 'archive-event.php' );
		if ( $n ) return $n;
	}
	return $t;
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
