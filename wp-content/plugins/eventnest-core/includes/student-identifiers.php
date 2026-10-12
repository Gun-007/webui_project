<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enc_prn_key( $prn ) {
	$normalized = strtoupper( trim( sanitize_text_field( (string) $prn ) ) );
	return $normalized === '' ? '' : hash( 'sha256', $normalized );
}

/** Backfill unique legacy PRNs once; ambiguous legacy identifiers remain email/username-only. */
function enc_migrate_student_identifiers() {
	if ( get_option( 'enc_prn_migration_version' ) === '1' ) return;
	$users = get_users( array( 'role' => 'en_student', 'fields' => 'ids', 'number' => -1 ) );
	$by_key = array();
	foreach ( $users as $user_id ) {
		$prn = get_user_meta( $user_id, '_en_prn', true );
		$key = enc_prn_key( $prn );
		if ( $key ) $by_key[ $key ][] = array( (int) $user_id, trim( (string) $prn ) );
	}
	global $wpdb;
	$table = enc_table( 'student_identifiers' );
	foreach ( $by_key as $key => $matches ) {
		if ( count( $matches ) !== 1 ) continue;
		$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO $table (prn_hash, prn_value, user_id, created_at) VALUES (%s, %s, %d, %s)", $key, $matches[0][1], $matches[0][0], current_time( 'mysql', true ) ) );
	}
	update_option( 'enc_prn_migration_version', '1', false );
}

function enc_reserve_student_prn( $prn, $exclude_user_id = 0 ) {
	$key = enc_prn_key( $prn );
	$value = trim( sanitize_text_field( (string) $prn ) );
	if ( ! $key || $value === '' ) return new WP_Error( 'prn_invalid', 'Enter a valid PRN.' );
	// Protect against duplicates in legacy data that was excluded from the index.
	$legacy_students = get_users( array( 'role' => 'en_student', 'meta_key' => '_en_prn', 'number' => -1, 'fields' => 'ids' ) );
	foreach ( $legacy_students as $legacy_id ) {
		if ( absint( $legacy_id ) === absint( $exclude_user_id ) ) continue;
		if ( enc_prn_key( get_user_meta( $legacy_id, '_en_prn', true ) ) === $key ) return new WP_Error( 'prn_exists', 'An account already uses this PRN.' );
	}
	global $wpdb;
	$table = enc_table( 'student_identifiers' );
	$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE user_id IS NULL AND created_at < %s", gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS ) ) );
	$inserted = $wpdb->query( $wpdb->prepare( "INSERT INTO $table (prn_hash, prn_value, user_id, created_at) VALUES (%s, %s, NULL, %s)", $key, $value, current_time( 'mysql', true ) ) );
	if ( ! $inserted ) return new WP_Error( 'prn_exists', 'An account already uses this PRN.' );
	return $key;
}

function enc_release_student_prn_reservation( $key ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . enc_table( 'student_identifiers' ) . ' WHERE prn_hash = %s AND user_id IS NULL', $key ) );
}

function enc_claim_student_prn( $key, $user_id ) {
	global $wpdb;
	return $wpdb->update( enc_table( 'student_identifiers' ), array( 'user_id' => absint( $user_id ) ), array( 'prn_hash' => $key ), array( '%d' ), array( '%s' ) );
}

function enc_find_student_by_prn( $prn ) {
	$key = enc_prn_key( $prn );
	if ( ! $key ) return false;
	global $wpdb;
	$user_id = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . enc_table( 'student_identifiers' ) . ' WHERE prn_hash = %s AND user_id IS NOT NULL LIMIT 1', $key ) );
	if ( ! $user_id || enc_prn_key( get_user_meta( $user_id, '_en_prn', true ) ) !== $key ) return false;
	return get_user_by( 'id', $user_id );
}
