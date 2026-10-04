<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	add_shortcode( 'eventnest_my_registrations', 'enc_my_registrations_shortcode' );
	add_shortcode( 'eventnest_submit_proposal', 'enc_submit_proposal_shortcode' );
	add_shortcode( 'eventnest_my_proposals', 'enc_my_proposals_shortcode' );
} );

function enc_student_notice( $code ) {
	$messages = array(
		'registered' => array( 'You are registered for this event.', 'success' ),
		'cancelled' => array( 'Your registration was cancelled.', 'success' ),
		'proposal_submitted' => array( 'Your proposal was submitted for review.', 'success' ),
		'proposal_resubmitted' => array( 'Your revised proposal was sent for review.', 'success' ),
		'proposal_error' => array( 'We could not submit that proposal. Check the required fields and try again.', 'error' ),
		'registration_error' => array( 'We could not complete that registration. It may be closed, full, or already registered.', 'error' ),
		'cancel_error' => array( 'We could not cancel that registration. It may already be closed or belong to another account.', 'error' ),
	);
	if ( ! isset( $messages[ $code ] ) ) return '';
	return '<p class="en-auth__notice en-auth__notice--' . esc_attr( $messages[ $code ][1] ) . '" role="status">' . esc_html( $messages[ $code ][0] ) . '</p>';
}

function enc_student_return( $url, $key, $value ) {
	wp_safe_redirect( add_query_arg( $key, $value, $url ) );
	exit;
}

function enc_student_stats( $user_id ) {
	global $wpdb;
	$registered = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_table( 'registrations' ) . " WHERE user_id = %d AND status = 'registered'", $user_id ) );
	$upcoming = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_table( 'registrations' ) . ' r INNER JOIN ' . $wpdb->posts . ' p ON p.ID = r.event_id AND p.post_status = %s LEFT JOIN ' . $wpdb->postmeta . " pm ON pm.post_id = r.event_id AND pm.meta_key = '_en_date' WHERE r.user_id = %d AND r.status = 'registered' AND pm.meta_value >= %s", 'publish', $user_id, wp_date( 'Y-m-d' ) ) );
	$proposals = count( get_posts( array( 'post_type' => 'proposal', 'post_status' => 'publish', 'author' => $user_id, 'posts_per_page' => -1, 'fields' => 'ids' ) ) );
	return array( 'registered' => $registered, 'upcoming' => $upcoming, 'proposals' => $proposals );
}

function enc_require_student_action( $login_return ) {
	if ( ! is_user_logged_in() ) {
		$login = add_query_arg( 'redirect_to', $login_return, home_url( '/login/' ) );
		wp_safe_redirect( $login );
		exit;
	}
	if ( ! current_user_can( 'en_register_event' ) && ! current_user_can( 'en_submit_proposal' ) ) wp_die( esc_html__( 'Your account cannot perform this action.', 'eventnest-core' ), '', array( 'response' => 403 ) );
}

add_action( 'admin_post_enc_register_event', 'enc_handle_event_registration' );
add_action( 'admin_post_nopriv_enc_register_event', 'enc_handle_event_registration' );
function enc_handle_event_registration() {
	$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
	$event_url = $event_id ? get_permalink( $event_id ) : home_url( '/events/' );
	enc_require_student_action( $event_url );
	$nonce = isset( $_POST['enc_registration_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_registration_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_register_event_' . $event_id ) ) enc_student_return( $event_url, 'en_registration', 'registration_error' );
	$result = enc_register( $event_id, get_current_user_id() );
	if ( is_wp_error( $result ) ) enc_student_return( $event_url, 'en_registration', 'registration_error' );
	enc_student_return( $event_url, 'en_registration', 'registered' );
}

add_action( 'admin_post_enc_cancel_registration', function () {
	$event_id = isset( $_POST['event_id'] ) ? absint( $_POST['event_id'] ) : 0;
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_safe_redirect( home_url( '/login/' ) );
		exit;
	}
	$nonce = isset( $_POST['enc_cancel_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_cancel_nonce'] ) ) : '';
	$event = get_post( $event_id );
	$date = (string) get_post_meta( $event_id, '_en_date', true );
	$may_cancel = $event && $event->post_type === 'event' && $date >= wp_date( 'Y-m-d' ) && ! get_post_meta( $event_id, '_en_cancelled', true );
	if ( ! wp_verify_nonce( $nonce, 'enc_cancel_registration_' . $event_id ) || ! $may_cancel || ! enc_is_registered( $event_id, $user_id ) ) enc_student_return( home_url( '/my-registrations/' ), 'en_result', 'cancel_error' );
	if ( enc_cancel_registration( $event_id, $user_id ) ) enc_student_return( home_url( '/my-registrations/' ), 'en_result', 'cancelled' );
	enc_student_return( home_url( '/my-registrations/' ), 'en_result', 'cancel_error' );
} );

function enc_my_registrations_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to view your registrations</h1><a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></section>';
	global $wpdb;
	$user_id = get_current_user_id();
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT r.*, p.post_title, t.name AS team_name FROM ' . enc_table( 'registrations' ) . ' r LEFT JOIN ' . $wpdb->posts . ' p ON p.ID = r.event_id LEFT JOIN ' . enc_table( 'teams' ) . ' t ON t.id = r.team_id WHERE r.user_id = %d ORDER BY r.created_at DESC', $user_id ) );
	$out = '<section class="section"><div class="wrap"><div class="page-head"><p class="eyebrow eyebrow--small">YOUR EVENTNEST</p><h1>My Registrations</h1><p class="section__sub">Events and competitions you have joined.</p>' . enc_student_notice( isset( $_GET['en_result'] ) ? sanitize_key( wp_unslash( $_GET['en_result'] ) ) : '' ) . '</div>';
	if ( ! $rows ) return $out . '<div class="empty"><h2>No registrations yet</h2><p>Browse events and register to see them here.</p><a class="btn btn--brand" href="' . esc_url( get_post_type_archive_link( 'event' ) ) . '">Browse events</a></div></div></section>';
	$out .= '<div class="en-student-list">';
	foreach ( $rows as $row ) {
		$event_id = (int) $row->event_id;
		$date = (string) get_post_meta( $event_id, '_en_date', true );
		$venue = (string) get_post_meta( $event_id, '_en_venue', true );
		$status = sanitize_key( $row->status );
		$status_label = ucwords( str_replace( '_', ' ', $status ) );
		$out .= '<article class="en-student-card"><div class="en-student-card__main"><p class="en-student-card__status en-student-card__status--' . esc_attr( $status ) . '">' . esc_html( $status_label ) . '</p><h2><a href="' . esc_url( get_permalink( $event_id ) ) . '">' . esc_html( $row->post_title ?: 'Event unavailable' ) . '</a></h2><p class="muted">' . esc_html( $date ? wp_date( 'j M Y', strtotime( $date ) ) : 'Date to be announced' ) . ( $venue ? ' · ' . esc_html( $venue ) : '' ) . '</p>';
		if ( $row->team_name ) $out .= '<p class="muted">Team: ' . esc_html( $row->team_name ) . '</p>';
		$out .= '<p class="muted">Registered ' . esc_html( get_date_from_gmt( $row->created_at, 'j M Y, g:i a' ) ) . '</p></div>';
		if ( $status === 'registered' && $date >= wp_date( 'Y-m-d' ) && ! get_post_meta( $event_id, '_en_cancelled', true ) ) {
			$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="en-student-card__action"><input type="hidden" name="action" value="enc_cancel_registration"><input type="hidden" name="event_id" value="' . esc_attr( $event_id ) . '">' . wp_nonce_field( 'enc_cancel_registration_' . $event_id, 'enc_cancel_nonce', true, false ) . '<button class="btn btn--ghost" type="submit">Cancel registration</button></form>';
		}
		$out .= '</article>';
	}
	return $out . '</div></div></section>';
}

add_action( 'admin_post_enc_submit_proposal', 'enc_handle_proposal_submission' );
add_action( 'admin_post_nopriv_enc_submit_proposal', 'enc_handle_proposal_submission' );
add_action( 'admin_post_enc_resubmit_proposal', 'enc_handle_proposal_resubmission' );
function enc_proposal_form_data() {
	return array(
		'title' => isset( $_POST['proposal_title'] ) ? wp_unslash( $_POST['proposal_title'] ) : '',
		'description' => isset( $_POST['proposal_description'] ) ? wp_unslash( $_POST['proposal_description'] ) : '',
		'type' => isset( $_POST['proposal_type'] ) ? wp_unslash( $_POST['proposal_type'] ) : 0,
		'category' => isset( $_POST['proposal_category'] ) ? wp_unslash( $_POST['proposal_category'] ) : 0,
		'date' => isset( $_POST['proposal_date'] ) ? wp_unslash( $_POST['proposal_date'] ) : '',
		'participants' => isset( $_POST['proposal_participants'] ) ? wp_unslash( $_POST['proposal_participants'] ) : 0,
		'venue' => isset( $_POST['proposal_venue'] ) ? wp_unslash( $_POST['proposal_venue'] ) : '',
		'club' => isset( $_POST['proposal_club'] ) ? wp_unslash( $_POST['proposal_club'] ) : 0,
	);
}

function enc_handle_proposal_submission() {
	enc_require_student_action( home_url( '/submit-proposal/' ) );
	if ( ! current_user_can( 'en_submit_proposal' ) ) wp_die( esc_html__( 'Your account cannot submit proposals.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$nonce = isset( $_POST['enc_proposal_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_proposal_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_submit_proposal' ) ) enc_student_return( home_url( '/submit-proposal/' ), 'en_result', 'proposal_error' );
	$result = enc_proposal_create( get_current_user_id(), enc_proposal_form_data() );
	if ( is_wp_error( $result ) ) enc_student_return( home_url( '/submit-proposal/' ), 'en_result', 'proposal_error' );
	enc_student_return( home_url( '/my-proposals/' ), 'en_result', 'proposal_submitted' );
}

function enc_handle_proposal_resubmission() {
	$user_id = get_current_user_id();
	$proposal_id = isset( $_POST['proposal_id'] ) ? absint( $_POST['proposal_id'] ) : 0;
	if ( ! $user_id || ! current_user_can( 'en_submit_proposal' ) ) wp_die( esc_html__( 'Your account cannot resubmit proposals.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$nonce = isset( $_POST['enc_proposal_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_proposal_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_resubmit_proposal_' . $proposal_id ) ) enc_student_return( home_url( '/my-proposals/' ), 'en_result', 'proposal_error' );
	$result = enc_proposal_resubmit( $proposal_id, $user_id, enc_proposal_form_data() );
	if ( is_wp_error( $result ) ) enc_student_return( home_url( '/my-proposals/' ), 'en_result', 'proposal_error' );
	enc_student_return( home_url( '/my-proposals/' ), 'en_result', 'proposal_resubmitted' );
}

function enc_proposal_form( $proposal = null ) {
	$id = $proposal ? (int) $proposal->ID : 0;
	$action = $proposal ? 'enc_resubmit_proposal' : 'enc_submit_proposal';
	$submit_label = $proposal ? 'Resubmit proposal' : 'Submit proposal';
	$value = function ( $key, $meta = '' ) use ( $proposal ) {
		if ( ! $proposal ) return '';
		return $meta ? get_post_meta( $proposal->ID, $meta, true ) : $proposal->$key;
	};
	$out = '<form class="en-auth en-proposal-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="' . esc_attr( $action ) . '">';
	if ( $proposal ) $out .= '<input type="hidden" name="proposal_id" value="' . esc_attr( $id ) . '">';
	$out .= wp_nonce_field( $proposal ? 'enc_resubmit_proposal_' . $id : 'enc_submit_proposal', 'enc_proposal_nonce', true, false );
	$out .= '<label class="en-auth__field"><span>Event title *</span><input required name="proposal_title" maxlength="180" value="' . esc_attr( $value( 'post_title' ) ) . '"></label>';
	$out .= '<label class="en-auth__field"><span>Description *</span><textarea required name="proposal_description" rows="5">' . esc_textarea( $value( 'post_content' ) ) . '</textarea></label>';
	$out .= '<div class="en-auth__row">';
	foreach ( array( 'event_type' => array( 'Event type', 'proposal_type', '_en_p_type' ), 'event_category' => array( 'Category', 'proposal_category', '_en_p_category' ) ) as $taxonomy => $field ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		$out .= '<label class="en-auth__field"><span>' . esc_html( $field[0] ) . ' *</span><select required name="' . esc_attr( $field[1] ) . '"><option value="">Choose…</option>';
		if ( ! is_wp_error( $terms ) ) foreach ( $terms as $term ) $out .= '<option value="' . esc_attr( $term->term_id ) . '"' . selected( (int) $value( '', $field[2] ), (int) $term->term_id, false ) . '>' . esc_html( $term->name ) . '</option>';
		$out .= '</select></label>';
	}
	$out .= '</div><div class="en-auth__row"><label class="en-auth__field"><span>Proposed date *</span><input type="date" required name="proposal_date" min="' . esc_attr( wp_date( 'Y-m-d' ) ) . '" value="' . esc_attr( $value( '', '_en_p_date' ) ) . '"></label><label class="en-auth__field"><span>Expected participants</span><input type="number" min="0" name="proposal_participants" value="' . esc_attr( $value( '', '_en_p_participants' ) ) . '"></label></div>';
	$out .= '<div class="en-auth__row"><label class="en-auth__field"><span>Venue *</span><input required name="proposal_venue" value="' . esc_attr( $value( '', '_en_p_venue' ) ) . '"></label><label class="en-auth__field"><span>Organizing club</span><select name="proposal_club"><option value="0">No club selected</option>';
	$clubs = get_posts( array( 'post_type' => 'club', 'post_status' => 'publish', 'numberposts' => 100, 'orderby' => 'title', 'order' => 'ASC' ) );
	foreach ( $clubs as $club ) $out .= '<option value="' . esc_attr( $club->ID ) . '"' . selected( (int) $value( '', '_en_p_club' ), (int) $club->ID, false ) . '>' . esc_html( $club->post_title ) . '</option>';
	$out .= '</select></label></div><button class="btn btn--brand" type="submit">' . esc_html( $submit_label ) . '</button></form>';
	return $out;
}

function enc_submit_proposal_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to propose an event</h1><a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></section>';
	if ( ! current_user_can( 'en_submit_proposal' ) ) return '<section class="en-auth-card"><h1>Student access required</h1><p>Your account cannot submit proposals.</p></section>';
	return '<section class="en-auth-card"><p class="eyebrow eyebrow--small">SHAPE CAMPUS LIFE</p><h1>Submit an event proposal</h1><p class="muted">Approved proposals become draft events for staff to complete and publish.</p>' . enc_student_notice( isset( $_GET['en_result'] ) ? sanitize_key( wp_unslash( $_GET['en_result'] ) ) : '' ) . enc_proposal_form() . '</section>';
}

function enc_my_proposals_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to view your proposals</h1><a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></section>';
	$items = get_posts( array( 'post_type' => 'proposal', 'post_status' => 'publish', 'author' => get_current_user_id(), 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'DESC' ) );
	$out = '<section class="section"><div class="wrap"><div class="page-head"><p class="eyebrow eyebrow--small">YOUR EVENTNEST</p><h1>My Proposals</h1><p class="section__sub">Track decisions and respond to reviewer feedback.</p>' . enc_student_notice( isset( $_GET['en_result'] ) ? sanitize_key( wp_unslash( $_GET['en_result'] ) ) : '' ) . '<p><a class="btn btn--brand" href="' . esc_url( home_url( '/submit-proposal/' ) ) . '">New proposal</a></p></div>';
	if ( ! $items ) return $out . '<div class="empty"><h2>No proposals yet</h2><p>Have an idea for campus? Send it for review.</p><a class="btn btn--brand" href="' . esc_url( home_url( '/submit-proposal/' ) ) . '">Propose an event</a></div></div></section>';
	foreach ( $items as $item ) {
		$id = (int) $item->ID;
		$status = enc_proposal_status( $id );
		$labels = enc_proposal_statuses();
		$out .= '<article class="en-student-card"><div class="en-student-card__main"><p class="en-student-card__status en-student-card__status--' . esc_attr( $status ) . '">' . esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status ) . '</p><h2>' . esc_html( $item->post_title ) . '</h2><p class="muted">Submitted ' . esc_html( get_the_date( 'j M Y', $item ) ) . ' · Proposed date ' . esc_html( get_post_meta( $id, '_en_p_date', true ) ) . '</p>';
		$history = enc_proposal_history( $id );
		if ( $history ) {
			$out .= '<details class="en-proposal-history"><summary>Review history</summary><ol>';
			foreach ( $history as $entry ) {
				$reviewer = get_userdata( (int) $entry->reviewer_id );
				$out .= '<li><strong>' . esc_html( ucwords( str_replace( '_', ' ', $entry->decision ) ) ) . '</strong> · ' . esc_html( $entry->stage ) . ' · ' . esc_html( $reviewer ? $reviewer->display_name : 'EventNest' ) . ' · ' . esc_html( get_date_from_gmt( $entry->created_at, 'j M Y' ) );
				if ( $entry->comment ) $out .= '<p>' . esc_html( $entry->comment ) . '</p>';
				$out .= '</li>';
			}
			$out .= '</ol></details>';
		}
		$event_id = (int) get_post_meta( $id, '_en_event_id', true );
		if ( $status === 'published' && $event_id ) $out .= '<p><a class="link-more" href="' . esc_url( get_permalink( $event_id ) ) . '">View published event →</a></p>';
		$out .= '</div></article>';
		if ( $status === 'needs_changes' ) $out .= '<div class="en-resubmit"><h3>Update your proposal</h3>' . enc_proposal_form( $item ) . '</div>';
	}
	return $out . '</div></section>';
}
