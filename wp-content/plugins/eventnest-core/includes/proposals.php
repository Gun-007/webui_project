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
	return $s ? $s : 'submitted';
}

function enc_log_approval( $proposal_id, $stage, $reviewer_id, $decision, $comment = '' ) {
	global $wpdb;
	$wpdb->insert( enc_table( 'approvals' ), array( 'proposal_id' => $proposal_id, 'stage' => $stage, 'reviewer_id' => $reviewer_id, 'decision' => $decision, 'comment' => sanitize_textarea_field( $comment ), 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%s', '%d', '%s', '%s', '%s' ) );
}

function enc_proposal_history( $proposal_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . enc_table( 'approvals' ) . ' WHERE proposal_id = %d ORDER BY id ASC', $proposal_id ) );
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
	);
	foreach ( array( 'title' => 'a title', 'description' => 'a description', 'date' => 'a proposed date', 'venue' => 'a venue' ) as $k => $label ) {
		if ( $out[ $k ] === '' ) return new WP_Error( 'missing', 'Please enter ' . $label . '.' );
	}
	if ( ! $out['type'] || ! term_exists( $out['type'], 'event_type' ) ) return new WP_Error( 'type', 'Please choose an event type.' );
	if ( ! $out['category'] || ! term_exists( $out['category'], 'event_category' ) ) return new WP_Error( 'category', 'Please choose a category.' );
	if ( $out['date'] < wp_date( 'Y-m-d' ) ) return new WP_Error( 'date', 'The proposed date must be in the future.' );
	return $out;
}

function enc_save_proposal_meta( $id, array $c ) {
	update_post_meta( $id, '_en_p_type', $c['type'] );
	update_post_meta( $id, '_en_p_category', $c['category'] );
	update_post_meta( $id, '_en_p_date', $c['date'] );
	update_post_meta( $id, '_en_p_participants', $c['participants'] );
	update_post_meta( $id, '_en_p_venue', $c['venue'] );
	update_post_meta( $id, '_en_p_club', $c['club'] );
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
	$p = get_post( $proposal_id );
	if ( ! $p || $p->post_type !== 'proposal' || (int) $p->post_author !== (int) $user_id ) return new WP_Error( 'forbidden', 'This is not your proposal.' );
	if ( enc_proposal_status( $proposal_id ) !== 'needs_changes' ) return new WP_Error( 'state', 'This proposal is not waiting for changes.' );
	$c = enc_clean_proposal( $data );
	if ( is_wp_error( $c ) ) return $c;
	wp_update_post( array( 'ID' => $proposal_id, 'post_title' => $c['title'], 'post_content' => $c['description'] ) );
	enc_save_proposal_meta( $proposal_id, $c );
	update_post_meta( $proposal_id, '_en_status', 'submitted' );
	update_post_meta( $proposal_id, '_en_stage', 0 );
	enc_log_approval( $proposal_id, 'student', $user_id, 'resubmitted' );
	do_action( 'enc_proposal_status_changed', $proposal_id, 'submitted', $user_id, '' );
	return true;
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
	return user_can( $user_id, 'en_review_override' ) || user_can( $user_id, 'en_review_' . $stage );
}

/** Reviewer decides: 'approve', 'reject' or 'changes'. Reject and changes need a comment. */
function enc_proposal_decide( $proposal_id, $reviewer_id, $decision, $comment = '' ) {
	$p = get_post( $proposal_id );
	if ( ! $p || $p->post_type !== 'proposal' ) return new WP_Error( 'no_proposal', 'Proposal not found.' );
	if ( ! enc_user_can_review( $reviewer_id, $proposal_id ) ) return new WP_Error( 'forbidden', 'You cannot review this proposal right now.' );
	if ( ! in_array( $decision, array( 'approve', 'reject', 'changes' ), true ) ) return new WP_Error( 'decision', 'Unknown decision.' );
	$comment = sanitize_textarea_field( $comment );
	if ( $decision !== 'approve' && $comment === '' ) return new WP_Error( 'comment', 'Please add a comment explaining your decision.' );

	$stage = enc_proposal_current_stage( $proposal_id );
	$chain = enc_approval_chain();
	$i     = (int) get_post_meta( $proposal_id, '_en_stage', true );

	if ( $decision === 'reject' )  { $status = 'rejected'; }
	elseif ( $decision === 'changes' ) { $status = 'needs_changes'; }
	elseif ( $i + 1 < count( $chain ) ) { $status = 'under_review'; update_post_meta( $proposal_id, '_en_stage', $i + 1 ); }
	else { $status = 'approved'; }

	update_post_meta( $proposal_id, '_en_status', $status );
	enc_log_approval( $proposal_id, $stage, $reviewer_id, $decision === 'approve' ? 'approved' : ( $decision === 'reject' ? 'rejected' : 'needs_changes' ), $comment );

	if ( $status === 'approved' ) enc_proposal_make_event( $proposal_id );
	do_action( 'enc_proposal_status_changed', $proposal_id, $status, $reviewer_id, $comment );
	return $status;
}

/** Final approval creates a draft event with the proposal's details; staff then complete and publish it. */
function enc_proposal_make_event( $proposal_id ) {
	if ( get_post_meta( $proposal_id, '_en_event_id', true ) ) return;
	$p = get_post( $proposal_id );
	$event_id = wp_insert_post( array( 'post_type' => 'event', 'post_status' => 'draft', 'post_title' => $p->post_title, 'post_content' => $p->post_content, 'post_author' => $p->post_author ), true );
	if ( is_wp_error( $event_id ) ) return;
	wp_set_object_terms( $event_id, array( (int) get_post_meta( $proposal_id, '_en_p_type', true ) ), 'event_type' );
	wp_set_object_terms( $event_id, array( (int) get_post_meta( $proposal_id, '_en_p_category', true ) ), 'event_category' );
	update_post_meta( $event_id, '_en_date', get_post_meta( $proposal_id, '_en_p_date', true ) );
	update_post_meta( $event_id, '_en_venue', get_post_meta( $proposal_id, '_en_p_venue', true ) );
	update_post_meta( $event_id, '_en_max', get_post_meta( $proposal_id, '_en_p_participants', true ) );
	update_post_meta( $event_id, '_en_club', get_post_meta( $proposal_id, '_en_p_club', true ) );
	update_post_meta( $event_id, '_en_proposal', $proposal_id );
	update_post_meta( $proposal_id, '_en_event_id', $event_id );
}

/** When staff publish the event created from a proposal, mark the proposal Published. */
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $post->post_type !== 'event' || $new !== 'publish' || $old === 'publish' ) return;
	$pid = (int) get_post_meta( $post->ID, '_en_proposal', true );
	if ( $pid && enc_proposal_status( $pid ) === 'approved' ) {
		update_post_meta( $pid, '_en_status', 'published' );
		enc_log_approval( $pid, 'event', get_current_user_id(), 'published' );
		do_action( 'enc_proposal_status_changed', $pid, 'published', get_current_user_id(), '' );
	}
}, 10, 3 );
