<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Approval order. Default (option B): faculty / club head, then deputy director. Change via the option or filter. */
function enc_approval_chain() {
	$chain = get_option( 'enc_approval_chain', array( 'faculty', 'deputy' ) );
	$chain = array_values( array_intersect( (array) $chain, array( 'faculty', 'deputy' ) ) );
	if ( ! $chain ) $chain = array( 'faculty' );
	return apply_filters( 'enc_approval_chain', $chain );
}

function enc_proposal_statuses() {
	return array( 'submitted' => 'Submitted', 'under_review' => 'Under review', 'needs_changes' => 'Needs changes', 'approved' => 'Approved', 'rejected' => 'Rejected', 'published' => 'Published' );
}

function enc_proposal_status( $id ) {
	$s = get_post_meta( $id, '_en_status', true );
	$event_id = absint( get_post_meta( $id, '_en_event_id', true ) );
	if ( $event_id && get_post_type( $event_id ) === 'event' && get_post_status( $event_id ) === 'publish' && $s !== 'published' ) {
		enc_proposal_mark_published( $id, 0 );
		$s = 'published';
	}
	return $s ? $s : 'submitted';
}

/** Keep proposal status aligned when its event is already public or is published later. */
function enc_proposal_mark_published( $proposal_id, $actor_id = null ) {
	if ( get_post_meta( $proposal_id, '_en_status', true ) === 'published' ) return false;
	update_post_meta( $proposal_id, '_en_status', 'published' );
	$actor_id = null === $actor_id ? get_current_user_id() : absint( $actor_id );
	enc_log_approval( $proposal_id, 'event', $actor_id, 'published' );
	do_action( 'enc_proposal_status_changed', $proposal_id, 'published', $actor_id, '' );
	return true;
}

function enc_log_approval( $proposal_id, $stage, $reviewer_id, $decision, $comment = '' ) {
	global $wpdb;
	$wpdb->insert( enc_table( 'approvals' ), array( 'proposal_id' => $proposal_id, 'stage' => $stage, 'reviewer_id' => $reviewer_id, 'decision' => $decision, 'comment' => sanitize_textarea_field( $comment ), 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%s', '%d', '%s', '%s', '%s' ) );
}

function enc_proposal_history( $proposal_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . enc_table( 'approvals' ) . ' WHERE proposal_id = %d ORDER BY id ASC', $proposal_id ) );
}

function enc_proposal_lock_name( $proposal_id ) {
	return 'enc_prop_' . substr( hash( 'sha256', (string) absint( $proposal_id ) ), 0, 32 );
}

function enc_proposal_acquire_lock( $proposal_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', enc_proposal_lock_name( $proposal_id ) ) ) === 1;
}

function enc_proposal_release_lock( $proposal_id ) {
	global $wpdb;
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', enc_proposal_lock_name( $proposal_id ) ) );
}

function enc_log_proposal_revision( $proposal_id, $actor_id, $action, array $snapshot ) {
	global $wpdb;
	$table = enc_table( 'proposal_revisions' );
	$revision = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(revision) FROM $table WHERE proposal_id = %d", $proposal_id ) );
	return $wpdb->insert( $table, array(
		'proposal_id' => absint( $proposal_id ),
		'revision' => $revision,
		'actor_id' => absint( $actor_id ),
		'action' => sanitize_key( $action ),
		'snapshot' => wp_json_encode( $snapshot ),
		'created_at' => current_time( 'mysql', true ),
	), array( '%d', '%d', '%d', '%s', '%s', '%s' ) );
}

function enc_proposal_revisions( $proposal_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . enc_table( 'proposal_revisions' ) . ' WHERE proposal_id = %d ORDER BY revision ASC', $proposal_id ) );
}

function enc_proposal_snapshot( $proposal_id ) {
	$post = get_post( $proposal_id );
	if ( ! $post ) return array();
	return array(
		'title' => $post->post_title,
		'description' => $post->post_content,
		'type' => absint( get_post_meta( $proposal_id, '_en_p_type', true ) ),
		'category' => absint( get_post_meta( $proposal_id, '_en_p_category', true ) ),
		'date' => (string) get_post_meta( $proposal_id, '_en_p_date', true ),
		'participants' => absint( get_post_meta( $proposal_id, '_en_p_participants', true ) ),
		'venue' => (string) get_post_meta( $proposal_id, '_en_p_venue', true ),
		'club' => absint( get_post_meta( $proposal_id, '_en_p_club', true ) ),
		'department' => absint( get_post_meta( $proposal_id, '_en_department', true ) ),
		'image_id' => absint( get_post_thumbnail_id( $proposal_id ) ),
	);
}

/** The fields a student fills in. Returns clean data or WP_Error. */
function enc_clean_proposal( array $d ) {
	$out = array(
		'title'        => sanitize_text_field( $d['title'] ?? '' ),
		'description'  => sanitize_textarea_field( $d['description'] ?? '' ),
		'type'         => absint( $d['type'] ?? 0 ),       // event_type term ID
		'category'     => absint( $d['category'] ?? 0 ),   // event_category term ID
		'date'         => enc_sanitize_field( 'date', $d['date'] ?? '' ),
		'participants' => absint( $d['participants'] ?? 0 ),
		'venue'        => sanitize_text_field( $d['venue'] ?? '' ),
		'club'         => absint( $d['club'] ?? 0 ),
		'department'   => absint( $d['department'] ?? 0 ),
	);
	foreach ( array( 'title' => 'a title', 'description' => 'a description', 'date' => 'a proposed date', 'venue' => 'a venue' ) as $k => $label ) {
		if ( $out[ $k ] === '' ) return new WP_Error( 'missing', 'Please enter ' . $label . '.' );
	}
	if ( ! $out['type'] || ! term_exists( $out['type'], 'event_type' ) ) return new WP_Error( 'type', 'Please choose an event type.' );
	if ( ! $out['category'] || ! term_exists( $out['category'], 'event_category' ) ) return new WP_Error( 'category', 'Please choose a category.' );
	if ( $out['date'] < wp_date( 'Y-m-d' ) ) return new WP_Error( 'date', 'The proposed date must be in the future.' );
	if ( $out['club'] && ( get_post_type( $out['club'] ) !== 'club' || get_post_status( $out['club'] ) !== 'publish' ) ) return new WP_Error( 'club', 'Please choose an available club.' );
	if ( $out['club'] ) {
		$club_department = absint( get_post_meta( $out['club'], '_en_department', true ) );
		if ( $club_department ) $out['department'] = $club_department;
	}
	if ( $out['department'] && ( get_post_type( $out['department'] ) !== 'faculty_department' || get_post_status( $out['department'] ) !== 'publish' ) ) return new WP_Error( 'department', 'Please choose an available department.' );
	if ( ! $out['department'] ) return new WP_Error( 'department_required', 'Choose a department so the proposal reaches the right reviewers.' );
	return $out;
}

function enc_save_proposal_meta( $id, array $c ) {
	update_post_meta( $id, '_en_p_type', $c['type'] );
	update_post_meta( $id, '_en_p_category', $c['category'] );
	update_post_meta( $id, '_en_p_date', $c['date'] );
	update_post_meta( $id, '_en_p_participants', $c['participants'] );
	update_post_meta( $id, '_en_p_venue', $c['venue'] );
	update_post_meta( $id, '_en_p_club', $c['club'] );
	if ( ! empty( $c['department'] ) ) update_post_meta( $id, '_en_department', $c['department'] );
	else delete_post_meta( $id, '_en_department' );
}

/** Student submits a proposal. */
function enc_proposal_create( $user_id, array $data ) {
	if ( ! user_can( $user_id, 'en_submit_proposal' ) ) return new WP_Error( 'forbidden', 'Your account cannot submit proposals.' );
	$c = enc_clean_proposal( $data );
	if ( is_wp_error( $c ) ) return $c;
	$id = wp_insert_post( array( 'post_type' => 'proposal', 'post_status' => 'publish', 'post_title' => $c['title'], 'post_content' => $c['description'], 'post_author' => $user_id ), true );
	if ( is_wp_error( $id ) ) return $id;
	enc_save_proposal_meta( $id, $c );
	update_post_meta( $id, '_en_status', 'submitted' );
	update_post_meta( $id, '_en_stage', 0 );
	enc_log_approval( $id, 'student', $user_id, 'submitted' );
	do_action( 'enc_proposal_status_changed', $id, 'submitted', $user_id, '' );
	return $id;
}

/** Student edits and resubmits after "needs changes". Restarts the approval chain. */
function enc_proposal_resubmit( $proposal_id, $user_id, array $data ) {
	$c = enc_clean_proposal( $data );
	if ( is_wp_error( $c ) ) return $c;
	if ( ! enc_proposal_acquire_lock( $proposal_id ) ) return new WP_Error( 'busy', 'This proposal is being updated. Please try again.' );
	try {
		$p = get_post( $proposal_id );
		if ( ! $p || $p->post_type !== 'proposal' || (int) $p->post_author !== (int) $user_id ) return new WP_Error( 'forbidden', 'This is not your proposal.' );
		if ( enc_proposal_status( $proposal_id ) !== 'needs_changes' ) return new WP_Error( 'state', 'This proposal is not waiting for changes.' );
		wp_update_post( array( 'ID' => $proposal_id, 'post_title' => $c['title'], 'post_content' => $c['description'] ) );
		enc_save_proposal_meta( $proposal_id, $c );
		update_post_meta( $proposal_id, '_en_status', 'submitted' );
		update_post_meta( $proposal_id, '_en_stage', 0 );
		enc_log_approval( $proposal_id, 'student', $user_id, 'resubmitted' );
		do_action( 'enc_proposal_status_changed', $proposal_id, 'submitted', $user_id, '' );
		return true;
	} finally {
		enc_proposal_release_lock( $proposal_id );
	}
}

/** Which stage ('faculty' / 'deputy') is waiting on a decision, or '' when none. */
function enc_proposal_current_stage( $id ) {
	if ( ! in_array( enc_proposal_status( $id ), array( 'submitted', 'under_review' ), true ) ) return '';
	$chain = enc_approval_chain();
	$i = (int) get_post_meta( $id, '_en_stage', true );
	return isset( $chain[ $i ] ) ? $chain[ $i ] : '';
}

function enc_user_can_review( $user_id, $proposal_id ) {
	$stage = enc_proposal_current_stage( $proposal_id );
	if ( ! $stage ) return false;
	if ( user_can( $user_id, 'en_review_override' ) ) return true;
	if ( ! user_can( $user_id, 'en_review_' . $stage ) ) return false;
	$user = get_userdata( $user_id );
	if ( ! $user ) return false;
	$roles = (array) $user->roles;
	if ( 'faculty' === $stage && in_array( 'en_faculty_head', $roles, true ) ) {
		$department = enc_user_department_id( $user_id );
		return $department && enc_department_for_object( $proposal_id ) === $department;
	}
	if ( 'faculty' === $stage && in_array( 'en_faculty', $roles, true ) ) {
		$user_department = enc_user_department_id( $user_id );
		$proposal_department = enc_department_for_object( $proposal_id );
		if ( $user_department && $user_department !== $proposal_department ) return false;
	}
	$club_id = absint( get_post_meta( $proposal_id, '_en_p_club', true ) );
	if ( 'faculty' === $stage && $club_id && ! ( in_array( 'en_faculty', $roles, true ) && enc_user_department_id( $user_id ) && enc_user_department_id( $user_id ) === enc_department_for_object( $proposal_id ) ) ) {
		$is_head = in_array( 'en_club_head', $roles, true ) && absint( get_post_meta( $club_id, '_en_club_head', true ) ) === (int) $user_id;
		$is_faculty = in_array( 'en_faculty', $roles, true ) && absint( get_post_meta( $club_id, '_en_club_faculty', true ) ) === (int) $user_id;
		return $is_head || $is_faculty;
	}
	return true;
}

/** Reviewer decides: 'approve', 'reject' or 'changes'. Reject and changes need a comment. */
function enc_proposal_decide( $proposal_id, $reviewer_id, $decision, $comment = '' ) {
	if ( ! in_array( $decision, array( 'approve', 'reject', 'changes' ), true ) ) return new WP_Error( 'decision', 'Unknown decision.' );
	$comment = sanitize_textarea_field( $comment );
	if ( $decision !== 'approve' && $comment === '' ) return new WP_Error( 'comment', 'Please add a comment explaining your decision.' );
	if ( ! enc_proposal_acquire_lock( $proposal_id ) ) return new WP_Error( 'busy', 'This proposal is being reviewed. Please refresh and try again.' );
	try {
		$p = get_post( $proposal_id );
		if ( ! $p || $p->post_type !== 'proposal' ) return new WP_Error( 'no_proposal', 'Proposal not found.' );
		if ( ! enc_user_can_review( $reviewer_id, $proposal_id ) ) return new WP_Error( 'forbidden', 'You cannot review this proposal right now.' );
		$stage = enc_proposal_current_stage( $proposal_id );
		$chain = enc_approval_chain();
		$i     = (int) get_post_meta( $proposal_id, '_en_stage', true );
		if ( $decision === 'reject' ) $status = 'rejected';
		elseif ( $decision === 'changes' ) $status = 'needs_changes';
		elseif ( $i + 1 < count( $chain ) ) { $status = 'under_review'; update_post_meta( $proposal_id, '_en_stage', $i + 1 ); }
		else $status = 'approved';
		if ( $status === 'approved' ) {
			$event_id = enc_proposal_make_event( $proposal_id );
			if ( is_wp_error( $event_id ) ) return $event_id;
			$status = 'published';
		}
		$previous_status = get_post_meta( $proposal_id, '_en_status', true );
		if ( $previous_status !== $status ) update_post_meta( $proposal_id, '_en_status', $status );
		enc_log_approval( $proposal_id, $stage, $reviewer_id, $decision === 'approve' ? 'approved' : ( $decision === 'reject' ? 'rejected' : 'needs_changes' ), $comment );
		if ( $previous_status !== $status ) do_action( 'enc_proposal_status_changed', $proposal_id, $status, $reviewer_id, $comment );
		return $status;
	} finally {
		enc_proposal_release_lock( $proposal_id );
	}
}

/** Final approval publishes an event using the proposal's reviewed details. */
function enc_proposal_make_event( $proposal_id ) {
	$p = get_post( $proposal_id );
	if ( ! $p || $p->post_type !== 'proposal' ) return new WP_Error( 'no_proposal', 'Proposal not found.' );
	$existing_id = absint( get_post_meta( $proposal_id, '_en_event_id', true ) );
	$existing = $existing_id ? get_post( $existing_id ) : null;
	if ( $existing && $existing->post_type === 'event' && 'trash' !== $existing->post_status ) $event_id = $existing_id;
	else $event_id = 0;
	if ( $existing_id ) delete_post_meta( $proposal_id, '_en_event_id' );
	if ( ! $event_id ) {
		$event_id = wp_insert_post( array( 'post_type' => 'event', 'post_status' => 'draft', 'post_title' => $p->post_title, 'post_content' => $p->post_content, 'post_author' => $p->post_author ), true );
		if ( is_wp_error( $event_id ) ) return $event_id;
	}
	$thumbnail_id = get_post_thumbnail_id( $proposal_id );
	if ( $thumbnail_id ) set_post_thumbnail( $event_id, $thumbnail_id );
	wp_set_object_terms( $event_id, array( (int) get_post_meta( $proposal_id, '_en_p_type', true ) ), 'event_type' );
	wp_set_object_terms( $event_id, array( (int) get_post_meta( $proposal_id, '_en_p_category', true ) ), 'event_category' );
	update_post_meta( $event_id, '_en_date', get_post_meta( $proposal_id, '_en_p_date', true ) );
	update_post_meta( $event_id, '_en_venue', get_post_meta( $proposal_id, '_en_p_venue', true ) );
	update_post_meta( $event_id, '_en_max', get_post_meta( $proposal_id, '_en_p_participants', true ) );
	update_post_meta( $event_id, '_en_club', get_post_meta( $proposal_id, '_en_p_club', true ) );
	update_post_meta( $event_id, '_en_department', enc_department_for_object( $proposal_id ) );
	update_post_meta( $event_id, '_en_proposal', $proposal_id );
	update_post_meta( $proposal_id, '_en_event_id', $event_id );
	$published = wp_update_post( array( 'ID' => $event_id, 'post_status' => 'publish' ), true );
	if ( is_wp_error( $published ) ) return $published;
	if ( get_post_status( $event_id ) !== 'publish' ) return new WP_Error( 'event_not_published', 'The approved event could not be published.' );
	return $event_id;
}

/** When a linked event becomes public, reflect that in its proposal. */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $post->post_type !== 'event' || $new !== 'publish' || $old === 'publish' ) return;
	$pid = (int) get_post_meta( $post->ID, '_en_proposal', true );
	if ( $pid ) enc_proposal_mark_published( $pid, get_current_user_id() );
}, 10, 3 );
