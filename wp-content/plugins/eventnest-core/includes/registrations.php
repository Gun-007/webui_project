<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enc_registration_count( $event_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_table( 'registrations' ) . " WHERE event_id = %d AND status IN ('registered','pending','completed')", $event_id ) );
}

function enc_is_registered( $event_id, $user_id ) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . enc_table( 'registrations' ) . " WHERE event_id = %d AND user_id = %d AND status <> 'cancelled'", $event_id, $user_id ) );
}

/** Checks shared by individual and team sign-ups. Returns WP_Error or true. */
function enc_can_register( $event_id, $user_id ) {
	$event = get_post( $event_id );
	if ( ! $event || $event->post_type !== 'event' || $event->post_status !== 'publish' ) return new WP_Error( 'no_event', 'Event not found.' );
	if ( ! user_can( $user_id, 'en_register_event' ) ) return new WP_Error( 'forbidden', 'Your account cannot register for events.' );
	if ( enc_event_state( $event_id ) !== 'registration_open' ) return new WP_Error( 'closed', 'Registration is closed for this event.' );
	if ( enc_is_registered( $event_id, $user_id ) ) return new WP_Error( 'duplicate', 'Already registered.' );
	return true;
}

/** Register one student. Capacity is re-checked after insert so two simultaneous sign-ups cannot exceed the limit. */
function enc_register( $event_id, $user_id, $team_id = 0 ) {
	global $wpdb;
	$ok = enc_can_register( $event_id, $user_id );
	if ( is_wp_error( $ok ) ) return $ok;

	$table = enc_table( 'registrations' );
	// A cancelled row for the same person is reused rather than hitting the unique key.
	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE event_id = %d AND user_id = %d AND status = 'cancelled'", $event_id, $user_id ) );
	$inserted = $wpdb->insert( $table, array( 'event_id' => $event_id, 'user_id' => $user_id, 'team_id' => (int) $team_id, 'status' => 'registered', 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%d', '%s', '%s' ) );
	if ( ! $inserted ) return new WP_Error( 'db', 'Could not save your registration.' );
	$id = (int) $wpdb->insert_id;

	$max = (int) get_post_meta( $event_id, '_en_max', true );
	if ( $max > 0 ) {
		$rank = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE event_id = %d AND status IN ('registered','pending','completed') AND id <= %d", $event_id, $id ) );
		if ( $rank > $max ) {
			$wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
			return new WP_Error( 'full', 'This event is full.' );
		}
	}
	do_action( 'enc_registered', $event_id, $user_id, $id );
	return $id;
}

function enc_cancel_registration( $event_id, $user_id ) {
	global $wpdb;
	$n = $wpdb->update( enc_table( 'registrations' ), array( 'status' => 'cancelled' ), array( 'event_id' => $event_id, 'user_id' => $user_id ), array( '%s' ), array( '%d', '%d' ) );
	if ( $n ) do_action( 'enc_registration_cancelled', $event_id, $user_id );
	return (bool) $n;
}

/** Team sign-up. The leader is added automatically; $member_ids are the other students. All-or-nothing. */
function enc_register_team( $event_id, $leader_id, $team_name, array $member_ids ) {
	global $wpdb;
	$min = (int) get_post_meta( $event_id, '_en_team_min', true );
	$max = (int) get_post_meta( $event_id, '_en_team_max', true );
	if ( $max < 1 ) return new WP_Error( 'individual', 'This event is for individual entries only.' );

	$team_name = sanitize_text_field( $team_name );
	if ( $team_name === '' ) return new WP_Error( 'name', 'Please give your team a name.' );

	$people = array_values( array_unique( array_map( 'absint', array_merge( array( $leader_id ), $member_ids ) ) ) );
	if ( count( $people ) < max( 1, $min ) || count( $people ) > $max ) return new WP_Error( 'size', sprintf( 'Team size must be between %d and %d.', max( 1, $min ), $max ) );

	foreach ( $people as $uid ) {
		$ok = enc_can_register( $event_id, $uid );
		if ( is_wp_error( $ok ) ) { $u = get_userdata( $uid ); return new WP_Error( $ok->get_error_code(), ( $u ? $u->display_name : 'A member' ) . ': ' . $ok->get_error_message() ); }
	}

	$wpdb->insert( enc_table( 'teams' ), array( 'event_id' => $event_id, 'name' => $team_name, 'leader_id' => $leader_id, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%s', '%d', '%s' ) );
	$team_id = (int) $wpdb->insert_id;
	if ( ! $team_id ) return new WP_Error( 'db', 'Could not create the team.' );

	$done = array();
	foreach ( $people as $uid ) {
		$r = enc_register( $event_id, $uid, $team_id );
		if ( is_wp_error( $r ) ) { // roll back everything
			foreach ( $done as $d ) $wpdb->delete( enc_table( 'registrations' ), array( 'id' => $d ), array( '%d' ) );
			$wpdb->delete( enc_table( 'teams' ), array( 'id' => $team_id ), array( '%d' ) );
			return $r;
		}
		$done[] = $r;
	}
	return $team_id;
}
