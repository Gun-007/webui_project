<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'enc_register_post_types' );

function enc_register_post_types() {
	$common = array( 'show_in_rest' => true, 'map_meta_cap' => true );

	register_post_type( 'event', array_merge( $common, array(
		'labels'          => array( 'name' => 'Events', 'singular_name' => 'Event', 'add_new_item' => 'Add new event', 'edit_item' => 'Edit event' ),
		'public'          => true,
		'has_archive'     => 'events',
		'rewrite'         => array( 'slug' => 'events' ),
		'menu_icon'       => 'dashicons-calendar-alt',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
		'capability_type' => array( 'en_event', 'en_events' ),
		'capabilities'    => array( 'create_posts' => 'create_en_events' ),
	) ) );

	register_post_type( 'club', array_merge( $common, array(
		'labels'          => array( 'name' => 'Clubs', 'singular_name' => 'Club', 'add_new_item' => 'Add new club' ),
		'public'          => true,
		'has_archive'     => 'clubs',
		'rewrite'         => array( 'slug' => 'clubs' ),
		'menu_icon'       => 'dashicons-groups',
		'supports'        => array( 'title', 'editor', 'thumbnail' ),
		'capability_type' => array( 'en_club', 'en_clubs' ),
		'capabilities'    => array( 'create_posts' => 'create_en_clubs' ),
	) ) );

	// Proposals are private data: no front-end URLs, not exposed over REST. Students submit through our own code.
	register_post_type( 'proposal', array(
		'labels'          => array( 'name' => 'Proposals', 'singular_name' => 'Proposal' ),
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-lightbulb',
		'supports'        => array( 'title', 'editor', 'author' ),
		'map_meta_cap'    => true,
		'capability_type' => array( 'en_proposal', 'en_proposals' ),
		'capabilities'    => array( 'create_posts' => 'create_en_proposals' ),
	) );

	register_post_type( 'announcement', array_merge( $common, array(
		'labels'          => array( 'name' => 'Announcements', 'singular_name' => 'Announcement', 'add_new_item' => 'Add announcement' ),
		'public'          => true,
		'has_archive'     => 'announcements',
		'rewrite'         => array( 'slug' => 'announcements' ),
		'menu_icon'       => 'dashicons-megaphone',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
		'capability_type' => array( 'en_announcement', 'en_announcements' ),
		'capabilities'    => array( 'create_posts' => 'create_en_announcements' ),
	) ) );

	register_taxonomy( 'event_type', 'event', array(
		'labels' => array( 'name' => 'Event types', 'singular_name' => 'Event type' ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
		'rewrite' => array( 'slug' => 'event-type' ),
		'capabilities' => array( 'manage_terms' => 'en_manage_platform', 'edit_terms' => 'en_manage_platform', 'delete_terms' => 'en_manage_platform', 'assign_terms' => 'edit_en_events' ),
	) );
	register_taxonomy( 'event_category', 'event', array(
		'labels' => array( 'name' => 'Categories', 'singular_name' => 'Category' ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
		'rewrite' => array( 'slug' => 'event-category' ),
		'capabilities' => array( 'manage_terms' => 'en_manage_platform', 'edit_terms' => 'en_manage_platform', 'delete_terms' => 'en_manage_platform', 'assign_terms' => 'edit_en_events' ),
	) );
}

function enc_seed_terms() {
	$types = array( 'Competition', 'Club Event', 'Workshop', 'Trial', 'Audition', 'Fest', 'Seminar', 'Webinar', 'Other' );
	$cats  = array( 'Cultural', 'Technical', 'Sports', 'Management', 'Academic', 'Other' );
	foreach ( $types as $t ) if ( ! term_exists( $t, 'event_type' ) ) wp_insert_term( $t, 'event_type' );
	foreach ( $cats as $c )  if ( ! term_exists( $c, 'event_category' ) ) wp_insert_term( $c, 'event_category' );
}

/** Event meta fields: key => label, type. Used for the editor box and for saving. */
function enc_event_fields() {
	return array(
		'_en_date'      => array( 'Event date', 'date' ),
		'_en_start'     => array( 'Start time', 'time' ),
		'_en_end'       => array( 'End time', 'time' ),
		'_en_venue'     => array( 'Venue', 'text' ),
		'_en_deadline'  => array( 'Registration deadline', 'date' ),
		'_en_max'       => array( 'Max participants (0 = unlimited)', 'number' ),
		'_en_fee'       => array( 'Registration fee in ₹ (0 = free)', 'number' ),
		'_en_scope'     => array( 'Competition scope', 'select', array( '' => 'Not a competition', 'intra' => 'Intra-College', 'inter' => 'Inter-College' ) ),
		'_en_club'      => array( 'Organising club', 'club' ),
		'_en_incharge'  => array( 'Faculty incharge', 'text' ),
		'_en_team_min'  => array( 'Team size min (0 = individual only)', 'number' ),
		'_en_team_max'  => array( 'Team size max', 'number' ),
		'_en_cancelled' => array( 'Event cancelled', 'checkbox' ),
	);
}

function enc_sanitize_field( $type, $value ) {
	switch ( $type ) {
		case 'date':     return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? $value : '';
		case 'time':     return preg_match( '/^\d{2}:\d{2}$/', (string) $value ) ? $value : '';
		case 'number':   return (string) max( 0, absint( $value ) );
		case 'club':     return (string) absint( $value );
		case 'checkbox': return $value ? '1' : '';
		default:         return sanitize_text_field( (string) $value );
	}
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'enc_event', 'Event details', 'enc_event_box', 'event', 'normal', 'high' );
} );

function enc_event_box( $post ) {
	wp_nonce_field( 'enc_event_save', 'enc_event_nonce' );
	echo '<table class="form-table"><tbody>';
	foreach ( enc_event_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( $f[1] === 'select' ) {
			echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
			foreach ( $f[2] as $v => $l ) echo '<option value="' . esc_attr( $v ) . '"' . selected( $val, $v, false ) . '>' . esc_html( $l ) . '</option>';
			echo '</select>';
		} elseif ( $f[1] === 'club' ) {
			wp_dropdown_pages( array( 'post_type' => 'club', 'name' => $key, 'id' => $key, 'selected' => (int) $val, 'show_option_none' => 'None', 'option_none_value' => '0' ) );
		} elseif ( $f[1] === 'checkbox' ) {
			echo '<input type="checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="1"' . checked( $val, '1', false ) . '>';
		} else {
			echo '<input type="' . esc_attr( $f[1] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '"' . ( $f[1] === 'number' ? ' min="0"' : '' ) . ' class="regular-text">';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action( 'save_post_event', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['enc_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enc_event_nonce'] ) ), 'enc_event_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	foreach ( enc_event_fields() as $key => $f ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		if ( $f[1] === 'select' && ! isset( $f[2][ $raw ] ) ) $raw = '';
		update_post_meta( $post_id, $key, enc_sanitize_field( $f[1], $raw ) );
	}
} );

/** Club meta: faculty incharge and club head are real user accounts. */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'enc_club', 'Club people', 'enc_club_box', 'club', 'side' );
} );

function enc_club_box( $post ) {
	wp_nonce_field( 'enc_club_save', 'enc_club_nonce' );
	echo '<p><label>Faculty incharge</label><br>';
	wp_dropdown_users( array( 'role__in' => array( 'en_faculty' ), 'name' => '_en_club_faculty', 'selected' => (int) get_post_meta( $post->ID, '_en_club_faculty', true ), 'show_option_none' => 'None', 'option_none_value' => '0' ) );
	echo '</p><p><label>Club head</label><br>';
	wp_dropdown_users( array( 'role__in' => array( 'en_club_head' ), 'name' => '_en_club_head', 'selected' => (int) get_post_meta( $post->ID, '_en_club_head', true ), 'show_option_none' => 'None', 'option_none_value' => '0' ) );
	echo '</p>';
}

add_action( 'save_post_club', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['enc_club_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enc_club_nonce'] ) ), 'enc_club_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	foreach ( array( '_en_club_faculty', '_en_club_head' ) as $k ) update_post_meta( $post_id, $k, isset( $_POST[ $k ] ) ? absint( $_POST[ $k ] ) : 0 );
} );

/** Derived event state: cancelled / completed / ongoing / registration_closed / registration_open. */
function enc_event_state( $event_id ) {
	if ( get_post_meta( $event_id, '_en_cancelled', true ) ) return 'cancelled';
	$today = wp_date( 'Y-m-d' );
	$date  = (string) get_post_meta( $event_id, '_en_date', true );
	$dead  = (string) get_post_meta( $event_id, '_en_deadline', true );
	if ( $date && $date < $today ) return 'completed';
	if ( $date && $date === $today ) return 'ongoing';
	if ( $dead && $dead < $today ) return 'registration_closed';
	return 'registration_open';
}
