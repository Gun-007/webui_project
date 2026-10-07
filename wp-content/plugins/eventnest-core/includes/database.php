<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enc_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'en_' . $name;
}

function enc_create_tables() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();

	// One row per person per event. Team members share a team_id.
	dbDelta( "CREATE TABLE " . enc_table( 'registrations' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  team_id bigint(20) unsigned NOT NULL DEFAULT 0,
  status varchar(20) NOT NULL DEFAULT 'registered',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY event_user (event_id,user_id),
  KEY user_id (user_id),
  KEY team_id (team_id)
) $c;" );

	dbDelta( "CREATE TABLE " . enc_table( 'teams' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  event_id bigint(20) unsigned NOT NULL,
  name varchar(100) NOT NULL,
  leader_id bigint(20) unsigned NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY event_id (event_id)
) $c;" );

	// Audit trail of every proposal action.
	dbDelta( "CREATE TABLE " . enc_table( 'approvals' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  proposal_id bigint(20) unsigned NOT NULL,
  stage varchar(30) NOT NULL,
  reviewer_id bigint(20) unsigned NOT NULL,
  decision varchar(20) NOT NULL,
  comment text NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY proposal_id (proposal_id)
) $c;" );

	// Club membership applications and their decision history summary.
	dbDelta( "CREATE TABLE " . enc_table( 'club_memberships' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  club_id bigint(20) unsigned NOT NULL,
  user_id bigint(20) unsigned NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  message text NULL,
  review_note text NULL,
  created_at datetime NOT NULL,
  reviewed_by bigint(20) unsigned NOT NULL DEFAULT 0,
  reviewed_at datetime NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY club_user (club_id,user_id),
  KEY user_id (user_id),
  KEY membership_status (status)
) $c;" );
	// A re-registration is a new record; retain the cancelled row as history.
	$registration_table = enc_table( 'registrations' );
	$event_user_indexes = $wpdb->get_results( $wpdb->prepare( "SHOW INDEX FROM `$registration_table` WHERE Key_name = %s", 'event_user' ) );
	foreach ( (array) $event_user_indexes as $index ) {
		if ( isset( $index->Non_unique ) && (int) $index->Non_unique === 0 ) {
			$wpdb->query( "ALTER TABLE `$registration_table` DROP INDEX event_user, ADD KEY event_user (event_id,user_id)" );
			break;
		}
	}

	dbDelta( "CREATE TABLE " . enc_table( 'audit_log' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
  target_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
  object_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(40) NOT NULL,
  reason text NULL,
  details text NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY target_user_id (target_user_id),
  KEY object_id (object_id),
  KEY action (action)
	) $c;" );

	dbDelta( "CREATE TABLE " . enc_table( 'student_identifiers' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  prn_hash char(64) NOT NULL,
  prn_value varchar(100) NOT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY prn_hash (prn_hash),
  UNIQUE KEY user_id (user_id)
) $c;" );

	dbDelta( "CREATE TABLE " . enc_table( 'club_application_history' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  membership_id bigint(20) unsigned NOT NULL,
  actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(30) NOT NULL,
  status varchar(20) NOT NULL,
  message text NULL,
  review_note text NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY membership_id (membership_id),
  KEY actor_id (actor_id)
) $c;" );

	dbDelta( "CREATE TABLE " . enc_table( 'proposal_revisions' ) . " (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  proposal_id bigint(20) unsigned NOT NULL,
  revision int(10) unsigned NOT NULL DEFAULT 1,
  actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(30) NOT NULL,
  snapshot longtext NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY proposal_id (proposal_id),
  KEY actor_id (actor_id)
) $c;" );
}

/** Append an administrative action to the EventNest audit log. */
function enc_log_admin_action( $action, $target_user_id = 0, $object_id = 0, $reason = '', $details = '' ) {
	global $wpdb;
	return $wpdb->insert( enc_table( 'audit_log' ), array(
		'actor_id' => get_current_user_id(),
		'target_user_id' => absint( $target_user_id ),
		'object_id' => absint( $object_id ),
		'action' => sanitize_key( $action ),
		'reason' => sanitize_textarea_field( $reason ),
		'details' => sanitize_textarea_field( $details ),
		'created_at' => current_time( 'mysql', true ),
	), array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' ) );
}
