<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=club', 'Club Leadership', 'Club Leadership', 'manage_options', 'enc-club-leadership', 'enc_render_club_leadership_admin' );
} );
add_action( 'admin_post_enc_change_club_head', 'enc_handle_club_head_change' );

function enc_handle_club_head_change() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Administrator access required.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
	$nonce = isset( $_POST['enc_club_head_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_club_head_nonce'] ) ) : '';
	if ( get_post_type( $club_id ) !== 'club' || ! wp_verify_nonce( $nonce, 'enc_change_club_head_' . $club_id ) ) wp_die( esc_html__( 'That club assignment could not be verified.', 'eventnest-core' ), '', array( 'response' => 400 ) );
	$new_head = isset( $_POST['new_head'] ) ? absint( $_POST['new_head'] ) : 0;
	$new_faculty = isset( $_POST['new_faculty_head'] ) ? absint( $_POST['new_faculty_head'] ) : 0;
	$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
	$old_head = absint( get_post_meta( $club_id, '_en_club_head', true ) );
	$old_faculty = absint( get_post_meta( $club_id, '_en_club_faculty', true ) );
	if ( strlen( $reason ) < 5 || strlen( $reason ) > 1000 ) enc_club_admin_redirect( 'invalid' );
	if ( $new_head === $old_head && $new_faculty === $old_faculty ) enc_club_admin_redirect( 'unchanged' );
	if ( $new_faculty ) {
		$faculty = get_userdata( $new_faculty );
		$department = absint( get_post_meta( $club_id, '_en_department', true ) );
		if ( ! $faculty || ! in_array( 'en_faculty_head', (array) $faculty->roles, true ) || enc_is_account_disabled( $new_faculty ) || ( $department && enc_user_department_id( $new_faculty ) !== $department ) ) enc_club_admin_redirect( 'invalid' );
	}
	if ( $new_head ) {
		$candidate = get_userdata( $new_head );
		if ( ! $candidate || enc_is_account_disabled( $new_head ) || in_array( 'administrator', (array) $candidate->roles, true ) ) enc_club_admin_redirect( 'invalid' );
		$candidate->set_role( 'en_club_head' );
	}
	update_post_meta( $club_id, '_en_club_head', $new_head );
	update_post_meta( $club_id, '_en_club_faculty', $new_faculty );
	update_post_meta( $club_id, '_en_club_head_assigned_at', current_time( 'mysql', true ) );
	$old_user = $old_head ? get_userdata( $old_head ) : false;
	$new_user = $new_head ? get_userdata( $new_head ) : false;
	$detail = 'From ' . ( $old_user ? $old_user->display_name : 'Vacant' ) . ' to ' . ( $new_user ? $new_user->display_name : 'Vacant' );
	$faculty_user = $new_faculty ? get_userdata( $new_faculty ) : false;
	enc_log_admin_action( 'club_leadership_updated', $old_head, $club_id, $reason, $detail . '; Faculty Head: ' . ( $faculty_user ? $faculty_user->display_name : 'Vacant' ) );
	if ( $old_head && ! $new_head ) enc_club_head_demote_if_unassigned( $old_head );
	if ( $old_head && $new_head && $old_head !== $new_head ) enc_club_head_demote_if_unassigned( $old_head );
	enc_club_admin_redirect( 'saved' );
}

function enc_club_head_demote_if_unassigned( $user_id ) {
	$user = get_userdata( $user_id );
	if ( ! $user || ! in_array( 'en_club_head', (array) $user->roles, true ) ) return;
	$clubs = get_posts( array( 'post_type' => 'club', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'meta_key' => '_en_club_head', 'meta_value' => $user_id, 'fields' => 'ids', 'posts_per_page' => 1 ) );
	if ( ! $clubs ) {
		$user->remove_role( 'en_club_head' );
		if ( ! $user->roles ) $user->add_role( 'subscriber' );
	}
}

function enc_club_admin_redirect( $result ) {
	wp_safe_redirect( add_query_arg( 'enc_club_admin_result', $result, admin_url( 'edit.php?post_type=club&page=enc-club-leadership' ) ) );
	exit;
}

function enc_render_club_leadership_admin() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$clubs = get_posts( array( 'post_type' => 'club', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC' ) );
	$result = isset( $_GET['enc_club_admin_result'] ) ? sanitize_key( wp_unslash( $_GET['enc_club_admin_result'] ) ) : '';
	$messages = array( 'saved' => 'Club leadership updated. Pending membership reviews are now visible to the assigned reviewers.', 'invalid' => 'Choose valid assigned heads and provide a reason of at least 5 characters.', 'unchanged' => 'Those heads are already assigned.' );
	echo '<div class="wrap"><h1>Club Leadership</h1><p>Assign a successor or mark a club vacant. Membership, events, registrations, and historical authorship are preserved. The former head keeps the role if still assigned to another club.</p>';
	if ( isset( $messages[ $result ] ) ) echo '<div class="notice ' . ( 'saved' === $result ? 'notice-success' : 'notice-warning' ) . ' is-dismissible"><p>' . esc_html( $messages[ $result ] ) . '</p></div>';
	$candidates = get_users( array( 'role__in' => array( 'en_club_head', 'en_faculty', 'en_faculty_head', 'en_student', 'subscriber' ), 'number' => 1000, 'orderby' => 'display_name', 'order' => 'ASC' ) );
	$faculty_heads = get_users( array( 'role' => 'en_faculty_head', 'number' => 1000, 'orderby' => 'display_name', 'order' => 'ASC' ) );
	foreach ( $clubs as $club ) {
		$current_id = absint( get_post_meta( $club->ID, '_en_club_head', true ) );
		$current = $current_id ? get_userdata( $current_id ) : false;
		echo '<section class="postbox" style="max-width:1000px;padding:1rem 1.2rem;margin:1rem 0"><h2>' . esc_html( $club->post_title ) . '</h2><p>Current Club Head: <strong>' . esc_html( $current ? $current->display_name : 'Vacant' ) . '</strong>';
		$assigned_at = get_post_meta( $club->ID, '_en_club_head_assigned_at', true );
		if ( $assigned_at ) echo ' · Assigned ' . esc_html( get_date_from_gmt( $assigned_at, 'j M Y' ) );
		$faculty_id = absint( get_post_meta( $club->ID, '_en_club_faculty', true ) );
		echo '</p><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_change_club_head"><input type="hidden" name="club_id" value="' . esc_attr( $club->ID ) . '">' . wp_nonce_field( 'enc_change_club_head_' . $club->ID, 'enc_club_head_nonce', true, false );
		echo '<p><label>Successor or vacancy<br><select name="new_head"><option value="0">Mark vacant</option>';
		foreach ( $candidates as $candidate ) echo '<option value="' . esc_attr( $candidate->ID ) . '"' . selected( $current_id, $candidate->ID, false ) . '>' . esc_html( $candidate->display_name . ' (' . $candidate->user_email . ')' ) . '</option>';
		echo '</select></label></p><p><label>Faculty Head<br><select name="new_faculty_head"><option value="0">Unassigned</option>';
		foreach ( $faculty_heads as $faculty_head ) { $department = absint( get_post_meta( $club->ID, '_en_department', true ) ); if ( $department && enc_user_department_id( $faculty_head->ID ) !== $department ) continue; echo '<option value="' . esc_attr( $faculty_head->ID ) . '"' . selected( $faculty_id, $faculty_head->ID, false ) . '>' . esc_html( $faculty_head->display_name . ' (' . $faculty_head->user_email . ')' ) . '</option>'; }
		echo '</select></label></p><p><label>Reason for assignment changes<br><input required minlength="5" maxlength="1000" class="large-text" name="reason"></label></p><button class="button button-primary" type="submit">Save club leadership</button></form></section>';
	}
	if ( ! $clubs ) echo '<p>No clubs are available.</p>';
	enc_render_audit_log( 30 );
	echo '</div>';
}
