<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	add_shortcode( 'eventnest_my_clubs', 'enc_my_clubs_shortcode' );
} );

add_action( 'init', function () {
	if ( get_page_by_path( 'my-clubs', OBJECT, 'page' ) ) return;
	wp_insert_post( array(
		'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'my-clubs',
		'post_title' => 'My Clubs', 'post_content' => '[eventnest_my_clubs]',
	) );
}, 40 );

add_action( 'admin_post_enc_apply_club', 'enc_handle_club_application' );
add_action( 'admin_post_nopriv_enc_apply_club', 'enc_handle_club_application' );
add_action( 'admin_post_enc_edit_club_application', 'enc_handle_club_application_edit' );
add_action( 'admin_post_enc_review_club_membership', 'enc_handle_club_membership_review' );

function enc_club_membership_table() {
	return enc_table( 'club_memberships' );
}

function enc_log_club_application_history( $membership_id, $actor_id, $action, $status, $message = '', $review_note = '' ) {
	global $wpdb;
	return $wpdb->insert( enc_table( 'club_application_history' ), array(
		'membership_id' => absint( $membership_id ), 'actor_id' => absint( $actor_id ),
		'action' => sanitize_key( $action ), 'status' => sanitize_key( $status ),
		'message' => sanitize_textarea_field( $message ), 'review_note' => sanitize_textarea_field( $review_note ),
		'created_at' => current_time( 'mysql', true ),
	), array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' ) );
}

function enc_club_application_history( $membership_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . enc_table( 'club_application_history' ) . ' WHERE membership_id = %d ORDER BY id ASC', absint( $membership_id ) ) );
}

function enc_is_club_student( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	$user = $user_id ? get_userdata( $user_id ) : false;
	return $user && in_array( 'en_student', (array) $user->roles, true );
}

function enc_can_review_club_membership( $club_id, $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id || get_post_type( $club_id ) !== 'club' ) return false;
	if ( user_can( $user_id, 'manage_options' ) ) return true;
	$user = get_userdata( $user_id );
	if ( ! $user ) return false;
	$roles = (array) $user->roles;
	if ( in_array( 'en_club_head', $roles, true ) && absint( get_post_meta( $club_id, '_en_club_head', true ) ) === $user_id ) return true;
	$club_department = absint( get_post_meta( $club_id, '_en_department', true ) );
	$user_department = enc_user_department_id( $user_id );
	if ( in_array( 'en_faculty_head', $roles, true ) ) return $club_department && $club_department === $user_department;
	if ( in_array( 'en_faculty', $roles, true ) && absint( get_post_meta( $club_id, '_en_club_faculty', true ) ) === $user_id ) {
		return ! $club_department || $club_department === $user_department;
	}
	return false;
}

function enc_club_membership_redirect( $club_id, $result ) {
	$url = get_permalink( $club_id );
	wp_safe_redirect( add_query_arg( 'club_result', $result, $url ? $url : home_url( '/clubs/' ) ) );
	exit;
}

function enc_handle_club_application() {
	$club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
	if ( ! is_user_logged_in() ) {
		$redirect = $club_id ? get_permalink( $club_id ) : home_url( '/clubs/' );
		wp_safe_redirect( add_query_arg( 'redirect_to', $redirect, home_url( '/login/' ) ) );
		exit;
	}
	if ( ! enc_is_club_student() || get_post_type( $club_id ) !== 'club' || get_post_status( $club_id ) !== 'publish' ) {
		wp_die( esc_html__( 'Only students can apply to join a published club.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	}
	$nonce = isset( $_POST['enc_club_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_club_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_apply_club_' . $club_id ) ) enc_club_membership_redirect( $club_id, 'error' );
	$message = isset( $_POST['application_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['application_message'] ) ) : '';
	if ( function_exists( 'mb_substr' ) ) $message = mb_substr( $message, 0, 1500 ); else $message = substr( $message, 0, 1500 );
	global $wpdb;
	$table = enc_club_membership_table();
	$user_id = get_current_user_id();
	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, status, message, review_note FROM $table WHERE club_id = %d AND user_id = %d LIMIT 1", $club_id, $user_id ) );
	if ( $existing && $existing->status === 'approved' ) enc_club_membership_redirect( $club_id, 'already_member' );
	if ( $existing && $existing->status === 'pending' ) enc_club_membership_redirect( $club_id, 'already_pending' );
	if ( $existing ) {
		$saved = $wpdb->update( $table, array( 'status' => 'pending', 'message' => $message, 'review_note' => null, 'created_at' => current_time( 'mysql', true ), 'reviewed_by' => 0, 'reviewed_at' => null ), array( 'id' => (int) $existing->id, 'user_id' => $user_id ), array( '%s', '%s', '%s', '%s', '%d', '%s' ), array( '%d', '%d' ) );
		if ( $saved !== false ) enc_log_club_application_history( $existing->id, $user_id, 'resubmitted', 'pending', $message );
	} else {
		$saved = $wpdb->insert( $table, array( 'club_id' => $club_id, 'user_id' => $user_id, 'status' => 'pending', 'message' => $message, 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%d', '%s', '%s', '%s' ) );
		if ( $saved ) enc_log_club_application_history( $wpdb->insert_id, $user_id, 'submitted', 'pending', $message );
	}
	enc_club_membership_redirect( $club_id, $saved ? 'applied' : 'error' );
}

function enc_handle_club_application_edit() {
	$membership_id = isset( $_POST['application_id'] ) ? absint( $_POST['application_id'] ) : 0;
	$user_id = get_current_user_id();
	global $wpdb;
	$table = enc_club_membership_table();
	$application = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d AND user_id = %d LIMIT 1", $membership_id, $user_id ) );
	if ( ! $application || ! enc_is_club_student( $user_id ) || $application->status !== 'pending' ) wp_die( esc_html__( 'Only your pending club application can be edited.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$nonce = isset( $_POST['enc_edit_club_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_edit_club_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_edit_club_application_' . $membership_id ) ) enc_club_membership_redirect( $application->club_id, 'error' );
	$message = isset( $_POST['application_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['application_message'] ) ) : '';
	$message = function_exists( 'mb_substr' ) ? mb_substr( $message, 0, 1500 ) : substr( $message, 0, 1500 );
	$saved = $wpdb->update( $table, array( 'message' => $message ), array( 'id' => $membership_id, 'user_id' => $user_id, 'status' => 'pending' ), array( '%s' ), array( '%d', '%d', '%s' ) );
	if ( $saved !== false ) enc_log_club_application_history( $membership_id, $user_id, 'edited', 'pending', $message );
	enc_club_membership_redirect( $application->club_id, $saved !== false ? 'application_updated' : 'error' );
}

function enc_handle_club_membership_review() {
	$club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
	$application_id = isset( $_POST['application_id'] ) ? absint( $_POST['application_id'] ) : 0;
	if ( ! enc_can_review_club_membership( $club_id ) ) wp_die( esc_html__( 'You cannot review applications for this club.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$nonce = isset( $_POST['enc_review_club_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_review_club_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_review_club_membership_' . $application_id ) ) enc_club_membership_redirect( $club_id, 'review_error' );
	$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
	if ( ! in_array( $decision, array( 'approved', 'rejected' ), true ) ) enc_club_membership_redirect( $club_id, 'review_error' );
	$note = isset( $_POST['review_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_note'] ) ) : '';
	global $wpdb;
	$saved = $wpdb->update( enc_club_membership_table(), array( 'status' => $decision, 'review_note' => $note, 'reviewed_by' => get_current_user_id(), 'reviewed_at' => current_time( 'mysql', true ) ), array( 'id' => $application_id, 'club_id' => $club_id, 'status' => 'pending' ), array( '%s', '%s', '%d', '%s' ), array( '%d', '%d', '%s' ) );
	if ( $saved ) {
		$application = $wpdb->get_row( $wpdb->prepare( 'SELECT message FROM ' . enc_club_membership_table() . ' WHERE id = %d', $application_id ) );
		enc_log_club_application_history( $application_id, get_current_user_id(), $decision, $decision, $application ? $application->message : '', $note );
	}
	enc_club_membership_redirect( $club_id, $saved ? 'reviewed' : 'review_error' );
}

function enc_club_membership_notices( $application_status = '' ) {
	$code = isset( $_GET['club_result'] ) ? sanitize_key( wp_unslash( $_GET['club_result'] ) ) : '';
	$messages = array(
		'applied' => array( 'Your application was sent to the club for review.', 'success' ),
		'already_pending' => array( 'Your application is already waiting for review.', 'info' ),
		'already_member' => array( 'You are already a member of this club.', 'info' ),
		'application_updated' => array( 'Your application was updated and is still waiting for review.', 'success' ),
		'reviewed' => array( 'The application decision was saved.', 'success' ),
		'review_error' => array( 'We could not save that decision. The application may already have been reviewed.', 'error' ),
		'error' => array( 'We could not submit the application. Please try again.', 'error' ),
	);
	if ( $code === 'applied' && $application_status === 'approved' ) {
		$messages['applied'][0] = 'Your application was approved. You are a member of this club.';
	}
	if ( $code === 'applied' && $application_status === 'rejected' ) {
		$messages['applied'] = array( 'Your application has been reviewed. See the reviewer note below before applying again.', 'info' );
	}
	if ( ! isset( $messages[ $code ] ) ) return '';
	return '<p class="en-auth__notice en-auth__notice--' . esc_attr( $messages[ $code ][1] ) . '" role="status">' . esc_html( $messages[ $code ][0] ) . '</p>';
}

function enc_club_membership_panel( $club_id ) {
	global $wpdb;
	$user_id = get_current_user_id();
	$is_reviewer = $user_id && enc_can_review_club_membership( $club_id, $user_id ) && ! enc_is_club_student( $user_id );
	$heading = $is_reviewer ? 'Club applications' : 'Join this club';
	$application = $user_id && enc_is_club_student( $user_id ) ? $wpdb->get_row( $wpdb->prepare( 'SELECT id, status, message, review_note FROM ' . enc_club_membership_table() . ' WHERE club_id = %d AND user_id = %d LIMIT 1', $club_id, $user_id ) ) : false;
	$out = '<section class="en-club-membership"><h2>' . esc_html( $heading ) . '</h2>' . enc_club_membership_notices( $application ? $application->status : '' );
	if ( $user_id && enc_is_club_student( $user_id ) ) {
		if ( $application && $application->status === 'approved' ) {
			$out .= '<p>You are a member of this club.</p>';
		} elseif ( $application && $application->status === 'pending' ) {
			$out .= '<p>Your application is <strong>pending review</strong>.</p><form class="en-auth en-club-apply" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_edit_club_application"><input type="hidden" name="application_id" value="' . esc_attr( $application->id ) . '">' . wp_nonce_field( 'enc_edit_club_application_' . $application->id, 'enc_edit_club_nonce', true, false );
			$out .= '<label class="en-auth__field"><span>Edit your note while the application is pending</span><textarea name="application_message" rows="3" maxlength="1500">' . esc_textarea( $application->message ) . '</textarea></label><button class="btn btn--ghost" type="submit">Save changes</button></form>';
		} else {
			if ( $application && $application->review_note ) $out .= '<p class="muted">Reviewer note: ' . esc_html( $application->review_note ) . '</p>';
			$out .= '<p class="muted">Send a short note to the club head or faculty in-charge.</p><form class="en-auth en-club-apply" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			$out .= '<input type="hidden" name="action" value="enc_apply_club"><input type="hidden" name="club_id" value="' . esc_attr( $club_id ) . '">' . wp_nonce_field( 'enc_apply_club_' . $club_id, 'enc_club_nonce', true, false );
			$out .= '<label class="en-auth__field"><span>Why would you like to join? <small>(optional)</small></span><textarea name="application_message" rows="3" maxlength="1500"></textarea></label><button class="btn btn--brand" type="submit">Apply to join</button></form>';
		}
	} elseif ( ! is_user_logged_in() ) {
		$out .= '<p class="muted">Log in with your student account to apply.</p><a class="btn btn--brand" href="' . esc_url( add_query_arg( 'redirect_to', get_permalink( $club_id ), home_url( '/login/' ) ) ) . '">Log in to apply</a>';
	} else {
		$out .= enc_can_review_club_membership( $club_id ) ? '<p class="muted">Review applications for this club below.</p>' : '<p class="muted">Club applications are available to student accounts.</p>';
	}

	$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_club_membership_table() . " WHERE club_id = %d AND status = 'approved'", $club_id ) );
	$out .= '<p class="en-club-member-count">' . esc_html( sprintf( _n( '%s member', '%s members', $count, 'eventnest-core' ), number_format_i18n( $count ) ) ) . '</p>';
	if ( enc_can_review_club_membership( $club_id ) ) {
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT m.id, m.message, m.created_at, u.display_name, u.user_email, um.meta_value AS prn FROM ' . enc_club_membership_table() . ' m INNER JOIN ' . $wpdb->users . ' u ON u.ID = m.user_id LEFT JOIN ' . $wpdb->usermeta . " um ON um.user_id = u.ID AND um.meta_key = '_en_prn' WHERE m.club_id = %d AND m.status = 'pending' ORDER BY m.created_at ASC", $club_id ) );
		$out .= '<div class="en-club-review"><h3>Pending applications</h3>';
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$out .= '<article class="en-club-application"><h4>' . esc_html( $row->display_name ) . '</h4><p class="muted">' . esc_html( $row->prn ?: 'Student' ) . ' · <a href="mailto:' . esc_attr( $row->user_email ) . '">' . esc_html( $row->user_email ) . '</a></p>';
				if ( $row->message ) $out .= '<p>' . nl2br( esc_html( $row->message ) ) . '</p>';
				$application_history = enc_club_application_history( $row->id );
				if ( $application_history ) {
					$out .= '<details class="en-proposal-history"><summary>Application history</summary><ol>';
					foreach ( $application_history as $entry ) {
						$actor = get_userdata( (int) $entry->actor_id );
						$out .= '<li><strong>' . esc_html( ucwords( str_replace( '_', ' ', $entry->action ) ) ) . '</strong> · ' . esc_html( $actor ? $actor->display_name : 'EventNest' ) . ' · ' . esc_html( get_date_from_gmt( $entry->created_at, 'j M Y, g:i a' ) );
						if ( $entry->message ) $out .= '<p>' . nl2br( esc_html( $entry->message ) ) . '</p>';
						$out .= '</li>';
					}
					$out .= '</ol></details>';
				}
				$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="en-club-review__form"><input type="hidden" name="action" value="enc_review_club_membership"><input type="hidden" name="club_id" value="' . esc_attr( $club_id ) . '"><input type="hidden" name="application_id" value="' . esc_attr( $row->id ) . '">' . wp_nonce_field( 'enc_review_club_membership_' . $row->id, 'enc_review_club_nonce', true, false );
				$out .= '<label class="en-auth__field"><span>Decision note <small>(optional)</small></span><textarea name="review_note" rows="2" maxlength="1000"></textarea></label><button class="btn btn--brand" name="decision" value="approved" type="submit">Approve</button> <button class="btn btn--ghost" name="decision" value="rejected" type="submit">Decline</button></form></article>';
			}
		} else {
			$out .= '<p class="muted">There are no applications waiting for review.</p>';
		}
		$out .= '</div>';
	}
	return $out . '</section>';
}

function enc_my_clubs_shortcode() {
	if ( ! is_user_logged_in() ) return '<div class="empty"><h2>Log in as a student to view club applications</h2><a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></div>';
	if ( in_array( 'en_club_head', (array) wp_get_current_user()->roles, true ) ) {
		$user_id = get_current_user_id();
		$club_ids = get_posts( array( 'post_type' => 'club', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_en_club_head', 'meta_value' => $user_id ) );
		$out = '<section class="section"><div class="wrap"><div class="page-head"><p class="eyebrow eyebrow--small">CLUB MANAGEMENT</p><h1>My clubs</h1><p class="section__sub">Only clubs assigned to you, with their events and registration management.</p></div>';
		if ( ! $club_ids ) return $out . '<div class="empty"><h2>No club assigned</h2><p>Ask an administrator to assign you as a Club Head.</p></div></div></section>';
		foreach ( $club_ids as $club_id ) {
			$out .= '<article class="en-club-application"><h2><a href="' . esc_url( get_permalink( $club_id ) ) . '">' . esc_html( get_the_title( $club_id ) ) . '</a></h2>';
			$events = get_posts( array( 'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => 100, 'meta_key' => '_en_club', 'meta_value' => $club_id, 'orderby' => 'title', 'order' => 'ASC' ) );
			if ( $events ) {
				$out .= '<ul>';
				foreach ( $events as $event ) $out .= '<li><a href="' . esc_url( get_permalink( $event ) ) . '">' . esc_html( $event->post_title ) . '</a> · <a href="' . esc_url( add_query_arg( 'registration_event', $event->ID, home_url( '/event-registrations/' ) ) ) . '">View registrations</a> · <a href="' . esc_url( get_edit_post_link( $event->ID ) ) . '">Manage</a></li>';
				$out .= '</ul>';
			} else $out .= '<p class="muted">No published club events yet.</p>';
			$out .= '<p><a class="btn btn--ghost" href="' . esc_url( admin_url( 'post-new.php?post_type=event' ) ) . '">Create club event</a></p></article>';
		}
		return $out . '</div></section>';
	}
	if ( ! enc_is_club_student() ) return '<div class="empty"><h2>My Clubs is for student accounts</h2><p>Sign in with a student account to see your applications.</p></div>';
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT m.*, p.post_title FROM ' . enc_club_membership_table() . ' m LEFT JOIN ' . $wpdb->posts . ' p ON p.ID = m.club_id WHERE m.user_id = %d ORDER BY m.created_at DESC', get_current_user_id() ) );
	$out = '<section class="section"><div class="wrap"><div class="page-head"><p class="eyebrow eyebrow--small">YOUR EVENTNEST</p><h1>My Clubs</h1><p class="section__sub">Club applications and membership status.</p></div>';
	if ( ! $rows ) return $out . '<div class="empty"><h2>No club applications yet</h2><p>Explore clubs and apply to join a community.</p><a class="btn btn--brand" href="' . esc_url( get_post_type_archive_link( 'club' ) ) . '">Explore clubs</a></div></div></section>';
	$out .= '<div class="en-my-clubs">';
	foreach ( $rows as $row ) {
		$status = sanitize_html_class( $row->status );
		$out .= '<article class="en-club-application"><p class="en-registration-status en-registration-status--' . esc_attr( $status ) . '">' . esc_html( ucfirst( $row->status ) ) . '</p><h2><a href="' . esc_url( get_permalink( $row->club_id ) ) . '">' . esc_html( $row->post_title ?: 'Club unavailable' ) . '</a></h2><p class="muted">Applied ' . esc_html( get_date_from_gmt( $row->created_at, 'j M Y' ) ) . '</p>';
		if ( $row->review_note ) $out .= '<p class="muted">Reviewer note: ' . esc_html( $row->review_note ) . '</p>';
		$history = enc_club_application_history( $row->id );
		if ( $history ) {
			$out .= '<details class="en-proposal-history"><summary>Application history</summary><ol>';
			foreach ( $history as $entry ) {
				$actor = get_userdata( (int) $entry->actor_id );
				$out .= '<li><strong>' . esc_html( ucwords( str_replace( '_', ' ', $entry->action ) ) ) . '</strong> · ' . esc_html( $actor ? $actor->display_name : 'EventNest' ) . ' · ' . esc_html( get_date_from_gmt( $entry->created_at, 'j M Y, g:i a' ) );
				if ( $entry->message ) $out .= '<p>' . nl2br( esc_html( $entry->message ) ) . '</p>';
				if ( $entry->review_note ) $out .= '<p>Reviewer note: ' . nl2br( esc_html( $entry->review_note ) ) . '</p>';
				$out .= '</li>';
			}
			$out .= '</ol></details>';
		}
		$out .= '</article>';
	}
	return $out . '</div></div></section>';
}
