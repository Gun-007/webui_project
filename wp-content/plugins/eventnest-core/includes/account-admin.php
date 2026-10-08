<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enc_is_account_disabled( $user_id ) {
	return get_user_meta( absint( $user_id ), '_en_account_status', true ) === 'disabled';
}

add_filter( 'wp_authenticate_user', function ( $user ) {
	if ( ! is_wp_error( $user ) && $user instanceof WP_User && enc_is_account_disabled( $user->ID ) ) {
		return new WP_Error( 'en_account_disabled', __( 'This account has been disabled. Contact an administrator.', 'eventnest-core' ) );
	}
	return $user;
}, 30 );

// Force out sessions that were already open when an administrator disabled an account.
add_action( 'init', function () {
	if ( ! is_user_logged_in() || ! enc_is_account_disabled( get_current_user_id() ) ) return;
	wp_logout();
	wp_safe_redirect( add_query_arg( 'en_result', 'account_disabled', home_url( '/login/' ) ) );
	exit;
}, 1 );

add_action( 'admin_menu', function () {
	add_users_page( 'EventNest Accounts', 'EventNest Accounts', 'manage_options', 'enc-accounts', 'enc_render_account_admin' );
} );
add_action( 'admin_post_enc_set_account_status', 'enc_handle_account_status' );
add_action( 'admin_post_enc_change_student_prn', 'enc_handle_student_prn_change' );

function enc_handle_student_prn_change() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Administrator access required.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$nonce = isset( $_POST['enc_prn_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_prn_nonce'] ) ) : '';
	$user = get_userdata( $user_id );
	if ( ! $user || ! in_array( 'en_student', (array) $user->roles, true ) || ! wp_verify_nonce( $nonce, 'enc_change_student_prn_' . $user_id ) ) wp_die( esc_html__( 'That PRN update could not be verified.', 'eventnest-core' ), '', array( 'response' => 400 ) );
	$prn = isset( $_POST['student_prn'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['student_prn'] ) ) ) : '';
	$old_prn = get_user_meta( $user_id, '_en_prn', true );
	$old_key = enc_prn_key( $old_prn );
	$new_key = enc_prn_key( $prn );
	if ( ! $new_key ) enc_account_admin_redirect( 'prn_invalid' );
	if ( $new_key === $old_key ) enc_account_admin_redirect( 'prn_unchanged' );
	global $wpdb;
	$table = enc_table( 'student_identifiers' );
	$legacy_students = get_users( array( 'role' => 'en_student', 'meta_key' => '_en_prn', 'number' => -1, 'fields' => 'ids' ) );
	foreach ( $legacy_students as $legacy_id ) {
		if ( (int) $legacy_id !== $user_id && enc_prn_key( get_user_meta( $legacy_id, '_en_prn', true ) ) === $new_key ) enc_account_admin_redirect( 'prn_exists' );
	}
	$mapped = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE user_id = %d LIMIT 1", $user_id ) );
	if ( $mapped ) {
		$saved = $wpdb->update( $table, array( 'prn_hash' => $new_key, 'prn_value' => $prn ), array( 'user_id' => $user_id ), array( '%s', '%s' ), array( '%d' ) );
	} else {
		$saved = $wpdb->insert( $table, array( 'prn_hash' => $new_key, 'prn_value' => $prn, 'user_id' => $user_id, 'created_at' => current_time( 'mysql', true ) ), array( '%s', '%s', '%d', '%s' ) );
	}
	if ( $saved === false ) enc_account_admin_redirect( 'prn_exists' );
	update_user_meta( $user_id, '_en_prn', $prn );
	enc_log_admin_action( 'student_prn_corrected', $user_id, 0, 'PRN corrected by administrator.' );
	enc_account_admin_redirect( 'prn_saved' );
}

function enc_handle_account_status() {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Administrator access required.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$action = isset( $_POST['account_action'] ) ? sanitize_key( wp_unslash( $_POST['account_action'] ) ) : '';
	$nonce = isset( $_POST['enc_account_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_account_nonce'] ) ) : '';
	$user = get_userdata( $user_id );
	if ( ! $user || ! wp_verify_nonce( $nonce, 'enc_account_status_' . $user_id ) || ! in_array( $action, array( 'disable', 'reactivate' ), true ) ) wp_die( esc_html__( 'That account action could not be verified.', 'eventnest-core' ), '', array( 'response' => 400 ) );
	if ( $user_id === get_current_user_id() || in_array( 'administrator', (array) $user->roles, true ) ) wp_die( esc_html__( 'Administrator accounts and your own account cannot be changed here.', 'eventnest-core' ), '', array( 'response' => 400 ) );
	$reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';
	if ( strlen( $reason ) < 5 || strlen( $reason ) > 1000 ) enc_account_admin_redirect( 'invalid' );

	if ( 'disable' === $action ) {
		if ( enc_is_account_disabled( $user_id ) ) enc_account_admin_redirect( 'unchanged' );
		$previous = get_user_meta( $user_id, '_en_account_status', true );
		update_user_meta( $user_id, '_en_account_status_before_disabled', $previous ? $previous : 'active' );
		update_user_meta( $user_id, '_en_account_status', 'disabled' );
		update_user_meta( $user_id, '_en_account_status_reason', $reason );
		update_user_meta( $user_id, '_en_account_status_actor', get_current_user_id() );
		update_user_meta( $user_id, '_en_account_status_changed', current_time( 'mysql', true ) );
		WP_Session_Tokens::get_instance( $user_id )->destroy_all();
		enc_log_admin_action( 'account_disabled', $user_id, 0, $reason );
	} else {
		if ( ! enc_is_account_disabled( $user_id ) ) enc_account_admin_redirect( 'unchanged' );
		$previous = get_user_meta( $user_id, '_en_account_status_before_disabled', true );
		if ( $previous && 'active' !== $previous ) update_user_meta( $user_id, '_en_account_status', $previous );
		else delete_user_meta( $user_id, '_en_account_status' );
		delete_user_meta( $user_id, '_en_account_status_before_disabled' );
		update_user_meta( $user_id, '_en_account_status_reason', $reason );
		update_user_meta( $user_id, '_en_account_status_actor', get_current_user_id() );
		update_user_meta( $user_id, '_en_account_status_changed', current_time( 'mysql', true ) );
		enc_log_admin_action( 'account_reactivated', $user_id, 0, $reason );
	}
	enc_account_admin_redirect( 'saved' );
}

function enc_account_admin_redirect( $result ) {
	wp_safe_redirect( add_query_arg( 'enc_account_result', $result, admin_url( 'users.php?page=enc-accounts' ) ) );
	exit;
}

function enc_render_account_admin() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	$filter = isset( $_GET['account_state'] ) ? sanitize_key( wp_unslash( $_GET['account_state'] ) ) : 'all';
	if ( ! in_array( $filter, array( 'all', 'active', 'pending', 'disabled' ), true ) ) $filter = 'all';
	$users = get_users( array( 'role__in' => array( 'en_student', 'en_faculty', 'en_faculty_head', 'en_club_head', 'en_deputy_director', 'en_director', 'subscriber' ), 'number' => 500, 'orderby' => 'display_name', 'order' => 'ASC' ) );
	$messages = array( 'saved' => 'Account status updated. Existing records were preserved.', 'invalid' => 'Enter a reason between 5 and 1,000 characters.', 'unchanged' => 'That account already has the selected status.', 'prn_saved' => 'Student PRN updated.', 'prn_invalid' => 'Enter a valid PRN.', 'prn_exists' => 'That PRN is already assigned to another student.', 'prn_unchanged' => 'That student already has this PRN.', 'prn_error' => 'The PRN could not be updated. Refresh and try again.' );
	$result = isset( $_GET['enc_account_result'] ) ? sanitize_key( wp_unslash( $_GET['enc_account_result'] ) ) : '';
	echo '<div class="wrap"><h1>EventNest Accounts</h1><p>Disable access without deleting accounts, registrations, proposals, stories, or memberships. Reactivation restores the previous pending/active state.</p>';
	if ( isset( $messages[ $result ] ) ) echo '<div class="notice ' . ( 'saved' === $result ? 'notice-success' : 'notice-warning' ) . ' is-dismissible"><p>' . esc_html( $messages[ $result ] ) . '</p></div>';
	echo '<form method="get"><input type="hidden" name="page" value="enc-accounts"><label for="account-state">Show </label><select id="account-state" name="account_state">';
	foreach ( array( 'all' => 'All accounts', 'active' => 'Active', 'pending' => 'Pending', 'disabled' => 'Disabled' ) as $key => $label ) echo '<option value="' . esc_attr( $key ) . '"' . selected( $filter, $key, false ) . '>' . esc_html( $label ) . '</option>';
	echo '</select> <button class="button">Filter</button></form><table class="widefat striped"><thead><tr><th>Account</th><th>Role</th><th>Status</th><th>Last status change</th><th>Action</th></tr></thead><tbody>';
	$shown = 0;
	foreach ( $users as $user ) {
		$status = get_user_meta( $user->ID, '_en_account_status', true );
		if ( ! $status ) $status = 'active';
		if ( $filter !== 'all' && $filter !== $status ) continue;
		$shown++;
		$changed = get_user_meta( $user->ID, '_en_account_status_changed', true );
		$role = enc_user_role( $user->ID );
		if ( ! $role && in_array( 'subscriber', (array) $user->roles, true ) ) $role = 'subscriber';
		$role_map = enc_role_map();
		$role_label = isset( $role_map[ $role ]['label'] ) ? $role_map[ $role ]['label'] : ( 'subscriber' === $role ? 'Pending staff' : ucfirst( $role ) );
		$action = 'disabled' === $status ? 'reactivate' : 'disable';
		echo '<tr><td><strong>' . esc_html( $user->display_name ) . '</strong><br><a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a>';
		if ( in_array( 'en_student', (array) $user->roles, true ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:.5rem"><input type="hidden" name="action" value="enc_change_student_prn"><input type="hidden" name="user_id" value="' . esc_attr( $user->ID ) . '">' . wp_nonce_field( 'enc_change_student_prn_' . $user->ID, 'enc_prn_nonce', true, false );
			echo '<label class="screen-reader-text" for="student-prn-' . esc_attr( $user->ID ) . '">Student PRN</label><input id="student-prn-' . esc_attr( $user->ID ) . '" name="student_prn" value="' . esc_attr( get_user_meta( $user->ID, '_en_prn', true ) ) . '" placeholder="Student PRN" required> <button class="button" type="submit">Update PRN</button></form>';
		}
		echo '</td><td>' . esc_html( $role_label ) . '</td><td>' . esc_html( ucfirst( $status ) ) . '</td><td>' . esc_html( $changed ? get_date_from_gmt( $changed, 'j M Y, H:i' ) : '—' ) . '</td><td>';
		if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_set_account_status"><input type="hidden" name="user_id" value="' . esc_attr( $user->ID ) . '"><input type="hidden" name="account_action" value="' . esc_attr( $action ) . '">' . wp_nonce_field( 'enc_account_status_' . $user->ID, 'enc_account_nonce', true, false );
			echo '<label class="screen-reader-text" for="reason-' . esc_attr( $user->ID ) . '">Reason</label><input required minlength="5" maxlength="1000" id="reason-' . esc_attr( $user->ID ) . '" name="reason" placeholder="Reason for ' . esc_attr( $action ) . '" class="regular-text"> <button class="button ' . ( 'disable' === $action ? 'button-link-delete' : 'button-primary' ) . '" type="submit">' . esc_html( ucfirst( $action ) ) . '</button></form>';
		}
		echo '</td></tr>';
	}
	if ( ! $shown ) echo '<tr><td colspan="5">No accounts match this filter.</td></tr>';
	echo '</tbody></table>';
	enc_render_audit_log( 40 );
	echo '</div>';
}

function enc_render_audit_log( $limit = 40 ) {
	global $wpdb;
	$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . enc_table( 'audit_log' ) . ' ORDER BY id DESC LIMIT %d', absint( $limit ) ) );
	echo '<h2>Recent account and club administration history</h2><table class="widefat striped"><thead><tr><th>Time</th><th>Action</th><th>Account / record</th><th>Reason</th></tr></thead><tbody>';
	if ( ! $rows ) echo '<tr><td colspan="4">No actions recorded yet.</td></tr>';
	foreach ( $rows as $row ) {
		$actor = get_userdata( (int) $row->actor_id );
		$target = $row->target_user_id ? get_userdata( (int) $row->target_user_id ) : false;
		$object = $row->object_id ? get_the_title( (int) $row->object_id ) : '';
		$subject = $target ? $target->display_name : ( $object ? $object : '—' );
		echo '<tr><td>' . esc_html( get_date_from_gmt( $row->created_at, 'j M Y, H:i' ) ) . '</td><td>' . esc_html( ucwords( str_replace( '_', ' ', $row->action ) ) ) . '<br><small>By ' . esc_html( $actor ? $actor->display_name : 'Unknown' ) . '</small></td><td>' . esc_html( $subject ) . '</td><td>' . esc_html( $row->reason ) . '</td></tr>';
	}
	echo '</tbody></table>';
}
