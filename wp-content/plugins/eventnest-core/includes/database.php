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
  UNIQUE KEY event_user (event_id,user_id),
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
}
