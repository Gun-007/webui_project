<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Capability names WordPress needs for a custom post type with capability_type array( x, plural ). */
function enc_cpt_caps( $plural, $own_only = false ) {
	$all = array( 'edit_', 'edit_others_', 'publish_', 'read_private_', 'delete_', 'delete_private_', 'delete_published_', 'delete_others_', 'edit_private_', 'edit_published_', 'create_' );
	if ( $own_only ) $all = array( 'edit_', 'publish_', 'delete_', 'delete_published_', 'edit_published_', 'create_' );
	$out = array();
	foreach ( $all as $p ) $out[] = $p . $plural;
	return $out;
}

/** Our own (non post-type) capabilities. */
function enc_custom_caps() {
	return array(
		'en_register_event',   // sign up for events and competitions
		'en_submit_proposal',  // submit event proposals
		'en_submit_story',     // submit student stories
		'en_review_faculty',   // review stage: faculty / club head
		'en_review_deputy',    // review stage: deputy director
		'en_review_override',  // may act on any stage (director)
		'en_view_registrations',
		'en_view_analytics',
		'en_approve_accounts', // approve faculty / club head signups
		'en_manage_platform',  // users, clubs, settings
	);
}

function enc_role_map() {
	$events_full = enc_cpt_caps( 'en_events' );
	$events_own  = enc_cpt_caps( 'en_events', true );
	$clubs_full  = enc_cpt_caps( 'en_clubs' );
	$ann_full    = enc_cpt_caps( 'en_announcements' );
	$ann_own     = enc_cpt_caps( 'en_announcements', true );
	$departments = enc_cpt_caps( 'en_departments' );

	return array(
		'en_student' => array(
			'label' => 'Student',
			'caps'  => array( 'read', 'en_register_event', 'en_submit_proposal', 'en_submit_story' ),
		),
		'en_faculty' => array(
			'label' => 'Faculty',
			'caps'  => array_merge( array( 'read', 'upload_files', 'en_register_event', 'en_submit_proposal', 'en_submit_story' ), $ann_own ),
		),
		'en_faculty_head' => array(
			'label' => 'Faculty Head',
			'caps'  => array_merge( array( 'read', 'upload_files', 'en_review_faculty', 'en_view_registrations', 'en_view_analytics' ), $events_full, $ann_own ),
		),
		'en_club_head' => array(
			'label' => 'Club Head',
			'caps'  => array_merge( array( 'read', 'upload_files', 'en_register_event', 'en_submit_proposal', 'en_submit_story', 'en_view_registrations' ), $events_own, $ann_own ),
		),
		'en_deputy_director' => array(
			'label' => 'Deputy Director',
			'caps'  => array_merge( array( 'read', 'en_review_deputy', 'en_view_registrations', 'en_view_analytics' ), $events_full, $clubs_full, $ann_full ),
		),
		'en_director' => array(
			'label' => 'Director',
			'caps'  => array_merge( array( 'read', 'en_review_deputy', 'en_review_override', 'en_view_registrations', 'en_view_analytics' ), $events_full, $clubs_full, $ann_full ),
		),
	);
}

/** Create roles if missing and make sure each has exactly the capabilities above (idempotent). */
function enc_sync_roles() {
	$departments = enc_cpt_caps( 'en_departments' );
	foreach ( enc_role_map() as $slug => $def ) {
		$role = get_role( $slug );
		if ( ! $role ) $role = add_role( $slug, $def['label'], array() );
		if ( ! $role ) continue;
		$wanted = array_fill_keys( $def['caps'], true );
		foreach ( $wanted as $cap => $v ) $role->add_cap( $cap );
		// Remove capabilities we manage but no longer grant.
		$managed = array_merge( enc_custom_caps(), enc_cpt_caps( 'en_events' ), enc_cpt_caps( 'en_announcements' ), enc_cpt_caps( 'en_clubs' ), enc_cpt_caps( 'en_proposals' ), enc_cpt_caps( 'en_departments' ) );
		foreach ( $managed as $cap ) if ( ! isset( $wanted[ $cap ] ) && $role->has_cap( $cap ) ) $role->remove_cap( $cap );
	}
	// WordPress Administrator = EventNest Admin: gets everything.
	$admin = get_role( 'administrator' );
	if ( $admin ) {
		$all = array_merge( enc_custom_caps(), enc_cpt_caps( 'en_events' ), enc_cpt_caps( 'en_clubs' ), enc_cpt_caps( 'en_proposals' ), enc_cpt_caps( 'en_announcements' ), $departments );
		foreach ( $all as $cap ) $admin->add_cap( $cap );
	}
}

/** Short role helper for dashboards: returns the highest EventNest role slug of a user, or ''. */
function enc_user_role( $user_id = 0 ) {
	$user = $user_id ? get_userdata( $user_id ) : wp_get_current_user();
	if ( ! $user || ! $user->exists() ) return '';
	if ( in_array( 'administrator', (array) $user->roles, true ) ) return 'administrator';
	foreach ( array( 'en_director', 'en_deputy_director', 'en_faculty_head', 'en_faculty', 'en_club_head', 'en_student' ) as $r ) {
		if ( in_array( $r, (array) $user->roles, true ) ) return $r;
	}
	if ( in_array( 'subscriber', (array) $user->roles, true ) ) return 'subscriber';
	return '';
}
