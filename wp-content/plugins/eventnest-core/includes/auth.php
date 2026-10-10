<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Add EventNest authentication and account shortcodes to normal WordPress pages. */
add_action( 'init', function () {
	add_shortcode( 'eventnest_login', 'enc_login_shortcode' );
	add_shortcode( 'eventnest_register_student', 'enc_student_registration_shortcode' );
	add_shortcode( 'eventnest_register_faculty', 'enc_faculty_registration_shortcode' );
	add_shortcode( 'eventnest_dashboard', 'enc_dashboard_shortcode' );
} );

function enc_auth_form_result( $key ) {
	return isset( $_GET[ $key ] ) ? sanitize_key( wp_unslash( $_GET[ $key ] ) ) : '';
}

function enc_auth_message( $code ) {
	$messages = array(
		'created' => array( 'Your account is ready. You can log in now.', 'success' ),
		'pending' => array( 'Your faculty account request was submitted. An administrator must approve your access.', 'success' ),
		'login_required' => array( 'Please log in to view your dashboard.', 'error' ),
		'login_failed' => array( 'Those login details were not recognized. Check them and try again.', 'error' ),
		'account_disabled' => array( 'This account has been disabled. Contact an administrator for help.', 'error' ),
		'pending_account' => array( 'Your faculty account is awaiting administrator approval.', 'error' ),
		'invalid' => array( 'Please check the required fields and try again.', 'error' ),
		'exists' => array( 'An account on this WordPress site already uses that email address. Try logging in or use a different email.', 'error' ),
		'password' => array( 'Passwords must match and be at least 10 characters long.', 'error' ),
		'prn_exists' => array( 'An account on this WordPress site already uses that PRN. Try logging in or check the PRN.', 'error' ),
		'faculty_id_exists' => array( 'A faculty request on this WordPress site already uses that Faculty ID.', 'error' ),
	);
	if ( ! isset( $messages[ $code ] ) ) return '';
	return '<p class="en-auth__notice en-auth__notice--' . esc_attr( $messages[ $code ][1] ) . '" role="status">' . esc_html( $messages[ $code ][0] ) . '</p>';
}

function enc_auth_field( $name, $label, $type = 'text', $required = true, $autocomplete = '' ) {
	$attrs = $required ? ' required' : '';
	if ( $autocomplete ) $attrs .= ' autocomplete="' . esc_attr( $autocomplete ) . '"';
	return '<label class="en-auth__field"><span>' . esc_html( $label ) . ( $required ? ' <b aria-hidden="true">*</b>' : '' ) . '</span><input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '"' . $attrs . '></label>';
}

function enc_auth_form_start( $action ) {
	return '<form class="en-auth" method="post"><input type="hidden" name="enc_auth_action" value="' . esc_attr( $action ) . '">' . wp_nonce_field( 'enc_auth_' . $action, 'enc_auth_nonce', true, false );
}

function enc_auth_unique_username( $seed ) {
	$base = sanitize_user( $seed, true );
	if ( $base === '' ) $base = 'eventnest_user';
	$name = substr( $base, 0, 50 );
	$suffix = 1;
	while ( username_exists( $name ) ) {
		$name = substr( $base, 0, 45 ) . '_' . $suffix;
		$suffix++;
	}
	return $name;
}

function enc_auth_redirect( $page, $result ) {
	$url = add_query_arg( 'en_result', $result, home_url( '/' . trim( $page, '/' ) . '/' ) );
	wp_safe_redirect( $url );
	exit;
}

/** Process only the auth forms rendered by our shortcodes. */
add_action( 'init', function () {
	if ( empty( $_POST['enc_auth_action'] ) ) return;
	$action = sanitize_key( wp_unslash( $_POST['enc_auth_action'] ) );
	$allowed = array( 'login', 'student_register', 'faculty_register' );
	if ( ! in_array( $action, $allowed, true ) ) return;
	$nonce = isset( $_POST['enc_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_auth_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_auth_' . $action ) ) {
		$page = $action === 'login' ? 'login' : ( $action === 'student_register' ? 'register-student' : 'register-faculty' );
		enc_auth_redirect( $page, 'invalid' );
	}

	if ( $action === 'login' ) {
		$identity = isset( $_POST['identity'] ) ? sanitize_text_field( wp_unslash( $_POST['identity'] ) ) : '';
		$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
		$username = $identity;
		if ( is_email( $identity ) ) {
			$user = get_user_by( 'email', $identity );
			if ( $user ) $username = $user->user_login;
		} else {
			$user = enc_find_student_by_prn( $identity );
			if ( $user ) $username = $user->user_login;
		}
		$user = wp_signon( array( 'user_login' => $username, 'user_password' => $password, 'remember' => ! empty( $_POST['remember'] ) ), is_ssl() );
		if ( is_wp_error( $user ) ) enc_auth_redirect( 'login', $user->get_error_code() === 'en_account_disabled' ? 'account_disabled' : 'login_failed' );
		$requested = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : home_url( '/dashboard/' );
		wp_safe_redirect( wp_validate_redirect( $requested, home_url( '/dashboard/' ) ) );
		exit;
	}

	$is_student = $action === 'student_register';
	$redirect_page = $is_student ? 'register-student' : 'register-faculty';
	$name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$mobile = isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '';
	$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
	$confirm = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';
	if ( $name === '' || ! is_email( $email ) ) enc_auth_redirect( $redirect_page, 'invalid' );
	if ( email_exists( $email ) ) enc_auth_redirect( $redirect_page, 'exists' );
	if ( strlen( $password ) < 10 || $password !== $confirm ) enc_auth_redirect( $redirect_page, 'password' );

	$meta = array( '_en_full_name' => $name, '_en_mobile' => $mobile );
	$prn_reservation = '';
	if ( $is_student ) {
		$prn = isset( $_POST['prn'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['prn'] ) ) ) : '';
		$batch = isset( $_POST['batch'] ) ? sanitize_text_field( wp_unslash( $_POST['batch'] ) ) : '';
		$course = isset( $_POST['course'] ) ? sanitize_text_field( wp_unslash( $_POST['course'] ) ) : '';
		if ( $prn === '' ) enc_auth_redirect( $redirect_page, 'invalid' );
		$prn_reservation = enc_reserve_student_prn( $prn );
		if ( is_wp_error( $prn_reservation ) ) enc_auth_redirect( $redirect_page, 'prn_exists' );
		$meta['_en_prn'] = $prn;
		$meta['_en_batch'] = $batch;
		$meta['_en_course'] = $course;
	} else {
		$faculty_id = isset( $_POST['faculty_id'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['faculty_id'] ) ) ) : '';
		$post = isset( $_POST['faculty_post'] ) ? sanitize_text_field( wp_unslash( $_POST['faculty_post'] ) ) : '';
		if ( $faculty_id === '' ) enc_auth_redirect( $redirect_page, 'invalid' );
		if ( get_users( array( 'meta_key' => '_en_faculty_id', 'meta_value' => $faculty_id, 'number' => 1, 'fields' => 'ids' ) ) ) enc_auth_redirect( $redirect_page, 'faculty_id_exists' );
		$meta['_en_faculty_id'] = $faculty_id;
		$meta['_en_faculty_post'] = $post;
	}

	$email_parts = explode( '@', $email );
	$username_seed = $is_student ? 'student_' . $meta['_en_prn'] : $email_parts[0];
	$user_id = wp_create_user( enc_auth_unique_username( $username_seed ), $password, $email );
	if ( is_wp_error( $user_id ) ) {
		if ( $prn_reservation ) enc_release_student_prn_reservation( $prn_reservation );
		enc_auth_redirect( $redirect_page, 'invalid' );
	}
	if ( $prn_reservation && ! enc_claim_student_prn( $prn_reservation, $user_id ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user_id );
		enc_release_student_prn_reservation( $prn_reservation );
		enc_auth_redirect( $redirect_page, 'prn_exists' );
	}
	wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );
	$user = new WP_User( $user_id );
	$user->set_role( $is_student ? 'en_student' : 'subscriber' );
	foreach ( $meta as $key => $value ) update_user_meta( $user_id, $key, $value );
	if ( ! $is_student ) update_user_meta( $user_id, '_en_account_status', 'pending' );
	enc_auth_redirect( $redirect_page, $is_student ? 'created' : 'pending' );
}, 20 );

/** Assigning a verified EventNest staff role in Users completes the pending signup. */
add_action( 'set_user_role', function ( $user_id, $role ) {
	if ( get_user_meta( $user_id, '_en_account_status', true ) !== 'disabled' && in_array( $role, array( 'en_faculty', 'en_faculty_head', 'en_club_head', 'en_deputy_director', 'en_director', 'administrator' ), true ) ) {
		delete_user_meta( $user_id, '_en_account_status' );
	}
}, 10, 2 );

/** A small review queue for faculty and club-head signup requests. */
add_action( 'admin_menu', function () {
	add_users_page( 'Faculty Requests', 'Faculty Requests', 'promote_users', 'enc-faculty-requests', 'enc_render_faculty_requests' );
} );

add_action( 'admin_post_enc_approve_faculty', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Only an administrator can review account requests.', 'eventnest-core' ) );
	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$role = isset( $_POST['eventnest_role'] ) ? sanitize_key( wp_unslash( $_POST['eventnest_role'] ) ) : '';
	$nonce = isset( $_POST['enc_approval_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_approval_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_approve_faculty_' . $user_id ) || ! in_array( $role, array( 'en_faculty', 'en_faculty_head', 'en_club_head', 'en_director', 'en_deputy_director' ), true ) || get_user_meta( $user_id, '_en_account_status', true ) !== 'pending' ) {
		enc_staff_approval_redirect( 'invalid' );
	}
	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		enc_staff_approval_redirect( 'invalid' );
	}
	$department_id = isset( $_POST['department_id'] ) ? absint( $_POST['department_id'] ) : 0;
	$club_id = isset( $_POST['club_id'] ) ? absint( $_POST['club_id'] ) : 0;
	if ( 'en_faculty_head' === $role && ! $department_id ) {
		enc_staff_approval_redirect( 'department_required' );
	}
	if ( in_array( $role, array( 'en_faculty_head', 'en_club_head' ), true ) && ( ! $club_id || get_post_type( $club_id ) !== 'club' ) ) enc_staff_approval_redirect( 'club_required' );
	if ( $department_id && ( get_post_type( $department_id ) !== 'faculty_department' || get_post_status( $department_id ) !== 'publish' ) ) {
		enc_staff_approval_redirect( 'invalid' );
	}
	$user->set_role( $role );
	if ( $department_id && in_array( $role, array( 'en_faculty', 'en_faculty_head' ), true ) ) update_user_meta( $user_id, '_en_department', $department_id );
	else delete_user_meta( $user_id, '_en_department' );
	update_user_meta( $user_id, '_en_account_status', 'approved' );
	if ( 'en_faculty_head' === $role ) update_post_meta( $club_id, '_en_club_faculty', $user_id );
	if ( 'en_club_head' === $role ) update_post_meta( $club_id, '_en_club_head', $user_id );
	enc_staff_approval_redirect( 'approved' );
} );

function enc_staff_approval_redirect( $result ) {
	$admin_queue = admin_url( 'users.php?page=enc-faculty-requests' );
	$return_to = isset( $_POST['return_to'] ) ? wp_validate_redirect( wp_unslash( $_POST['return_to'] ), $admin_queue ) : $admin_queue;
	wp_safe_redirect( add_query_arg( 'request_result', sanitize_key( $result ), $return_to ) );
	exit;
}

function enc_dashboard_staff_approval_queue() {
	if ( ! current_user_can( 'manage_options' ) ) return '';
	$requests = get_users( array( 'meta_key' => '_en_account_status', 'meta_value' => 'pending', 'orderby' => 'registered', 'order' => 'ASC', 'number' => 200 ) );
	$out = '<section class="en-dashboard-review en-staff-approvals" id="en-staff-approvals"><p class="eyebrow eyebrow--small">ACCOUNT ACCESS</p><h2>Staff account requests <span class="en-approval-count">' . esc_html( number_format_i18n( count( $requests ) ) ) . '</span></h2><p class="muted">Verify each request, assign the right role, and choose a department where applicable.</p>';
	$result = isset( $_GET['request_result'] ) ? sanitize_key( wp_unslash( $_GET['request_result'] ) ) : '';
	if ( 'approved' === $result ) $out .= '<p class="en-dashboard-note">Account approved and role assigned.</p>';
	elseif ( 'department_required' === $result ) $out .= '<p class="en-dashboard-note">Choose a department to approve someone as Faculty Head.</p>';
	elseif ( 'club_required' === $result ) $out .= '<p class="en-dashboard-note">Choose a club for this Faculty Head or Club Head.</p>';
	elseif ( 'invalid' === $result ) $out .= '<p class="en-dashboard-note">That request could not be processed. Refresh and try again.</p>';
	if ( ! $requests ) return $out . '<p class="en-dashboard-note">No staff requests are waiting for approval.</p></section>';
	foreach ( $requests as $user ) {
		$id = (int) $user->ID;
		$name = get_user_meta( $id, '_en_full_name', true ) ?: $user->display_name;
		$details = trim( get_user_meta( $id, '_en_faculty_id', true ) . ' · ' . get_user_meta( $id, '_en_faculty_post', true ), ' ·' );
		$out .= '<article class="en-approval-card"><div class="en-approval-card__identity"><span class="en-approval-avatar">' . esc_html( strtoupper( substr( $name, 0, 1 ) ) ) . '</span><div><h3>' . esc_html( $name ) . '</h3><a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a><p>' . esc_html( $details ?: 'Staff access request' ) . '</p></div></div>';
		$clubs = get_posts( array( 'post_type' => 'club', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
		$club_options = '<option value="0">Choose a club</option>';
		foreach ( $clubs as $club ) $club_options .= '<option value="' . (int) $club->ID . '">' . esc_html( $club->post_title ) . '</option>';
		$out .= '<form class="en-approval-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_approve_faculty"><input type="hidden" name="user_id" value="' . esc_attr( $id ) . '"><input type="hidden" name="return_to" value="' . esc_url( home_url( '/dashboard/#en-staff-approvals' ) ) . '">' . wp_nonce_field( 'enc_approve_faculty_' . $id, 'enc_approval_nonce', true, false ) . '<label><span>Approve as</span><select name="eventnest_role"><option value="en_faculty">Faculty</option><option value="en_faculty_head">Faculty Head</option><option value="en_club_head">Club Head</option><option value="en_director">Director</option><option value="en_deputy_director">Deputy Director</option></select></label><label><span>Department</span><select name="department_id">' . enc_department_choices() . '</select></label><label><span>Club assignment</span><select name="club_id">' . $club_options . '</select></label><button class="btn btn--brand" type="submit">Approve request</button></form></article>';
	}
	return $out . '</section>';
}

function enc_render_faculty_requests() {
	if ( ! current_user_can( 'promote_users' ) ) return;
	$requests = get_users( array(
		'role' => 'subscriber',
		'meta_key' => '_en_account_status',
		'meta_value' => 'pending',
		'orderby' => 'registered',
		'order' => 'ASC',
		'number' => 200,
	) );
	echo '<div class="wrap"><h1>Faculty Requests</h1><p>Review faculty and club-head account requests. Assign only the role that matches the verified person.</p>';
	$result = isset( $_GET['request_result'] ) ? sanitize_key( wp_unslash( $_GET['request_result'] ) ) : '';
	if ( $result === 'approved' ) echo '<div class="notice notice-success is-dismissible"><p>Request approved and role assigned.</p></div>';
	if ( $result === 'invalid' ) echo '<div class="notice notice-error"><p>Could not process that request. Refresh the list and try again.</p></div>';
	if ( $result === 'department_required' ) echo '<div class="notice notice-error"><p>Choose a department before approving a Faculty Head.</p></div>';
	if ( ! $requests ) {
		echo '<p>No pending requests.</p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>Name</th><th>Email</th><th>Faculty ID</th><th>Mobile</th><th>Designation</th><th>Requested</th><th>Approve as</th></tr></thead><tbody>';
	foreach ( $requests as $user ) {
		$user_id = (int) $user->ID;
		echo '<tr><td>' . esc_html( get_user_meta( $user_id, '_en_full_name', true ) ?: $user->display_name ) . '</td>';
		echo '<td><a href="mailto:' . esc_attr( $user->user_email ) . '">' . esc_html( $user->user_email ) . '</a></td>';
		echo '<td>' . esc_html( get_user_meta( $user_id, '_en_faculty_id', true ) ) . '</td>';
		echo '<td>' . esc_html( get_user_meta( $user_id, '_en_mobile', true ) ) . '</td>';
		echo '<td>' . esc_html( get_user_meta( $user_id, '_en_faculty_post', true ) ) . '</td>';
		echo '<td>' . esc_html( get_date_from_gmt( $user->user_registered, 'Y-m-d H:i' ) ) . '</td><td>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_approve_faculty"><input type="hidden" name="user_id" value="' . esc_attr( $user_id ) . '">';
		echo wp_nonce_field( 'enc_approve_faculty_' . $user_id, 'enc_approval_nonce', true, false );
		$clubs = get_posts( array( 'post_type' => 'club', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'title', 'order' => 'ASC' ) );
		$club_options = '<option value="0">Choose a club</option>';
		foreach ( $clubs as $club ) $club_options .= '<option value="' . (int) $club->ID . '">' . esc_html( $club->post_title ) . '</option>';
		echo '<select name="eventnest_role" aria-label="Role for ' . esc_attr( $user->display_name ) . '"><option value="en_faculty">Faculty</option><option value="en_faculty_head">Faculty Head</option><option value="en_club_head">Club Head</option><option value="en_director">Director</option><option value="en_deputy_director">Deputy Director</option></select> <select name="department_id" aria-label="Department for ' . esc_attr( $user->display_name ) . '">' . enc_department_choices() . '</select> <select name="club_id" aria-label="Club assignment">' . $club_options . '</select> ';
		submit_button( 'Approve', 'primary small', 'submit', false );
		echo '</form></td></tr>';
	}
	echo '</tbody></table></div>';
}

function enc_login_shortcode() {
	if ( is_user_logged_in() ) return '<section class="en-auth-card"><h1>You are logged in</h1><p><a class="btn btn--brand" href="' . esc_url( home_url( '/dashboard/' ) ) . '">Open dashboard</a></p></section>';
	$out = '<section class="en-auth-card"><p class="eyebrow eyebrow--small">EVENTNEST ACCOUNT</p><h1>Welcome back</h1><p class="muted">Log in with your username, email or student PRN.</p>' . enc_auth_message( enc_auth_form_result( 'en_result' ) );
	$out .= enc_auth_form_start( 'login' ) . enc_auth_field( 'identity', 'Username, email or PRN', 'text', true, 'username' ) . enc_auth_field( 'password', 'Password', 'password', true, 'current-password' );
	$redirect_to = isset( $_GET['redirect_to'] ) ? wp_validate_redirect( wp_unslash( $_GET['redirect_to'] ), home_url( '/dashboard/' ) ) : home_url( '/dashboard/' );
	$out .= '<input type="hidden" name="redirect_to" value="' . esc_url( $redirect_to ) . '"><label class="en-auth__check"><input type="checkbox" name="remember" value="1"> Remember me</label><button class="btn btn--brand btn--block" type="submit">Log in</button></form><p class="en-auth__links"><a href="' . esc_url( home_url( '/register-student/' ) ) . '">Create a student account</a> · <a href="' . esc_url( home_url( '/register-faculty/' ) ) . '">Faculty registration</a></p></section>';
	return $out;
}

function enc_student_registration_shortcode() {
	$out = '<section class="en-auth-card"><p class="eyebrow eyebrow--small">JOIN EVENTNEST</p><h1>Create a student account</h1><p class="muted">Use your college details so your registrations can be linked to you.</p>' . enc_auth_message( enc_auth_form_result( 'en_result' ) );
	$out .= enc_auth_form_start( 'student_register' ) . enc_auth_field( 'full_name', 'Full name', 'text', true, 'name' ) . enc_auth_field( 'prn', 'PRN', 'text' ) . enc_auth_field( 'email', 'College email', 'email', true, 'email' ) . enc_auth_field( 'mobile', 'Mobile number', 'tel', true, 'tel' );
	$out .= '<div class="en-auth__row">' . enc_auth_field( 'batch', 'Batch', 'text' ) . enc_auth_field( 'course', 'Course', 'text' ) . '</div>' . enc_auth_field( 'password', 'Password (at least 10 characters)', 'password', true, 'new-password' ) . enc_auth_field( 'confirm_password', 'Confirm password', 'password', true, 'new-password' ) . '<button class="btn btn--brand btn--block" type="submit">Create account</button></form><p class="en-auth__links">Already registered? <a href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></p></section>';
	return $out;
}

function enc_faculty_registration_shortcode() {
	$out = '<section class="en-auth-card"><p class="eyebrow eyebrow--small">FACULTY ACCESS</p><h1>Request a faculty account</h1><p class="muted">Access stays limited until an administrator verifies your request.</p>' . enc_auth_message( enc_auth_form_result( 'en_result' ) );
	$out .= enc_auth_form_start( 'faculty_register' ) . enc_auth_field( 'full_name', 'Full name', 'text', true, 'name' ) . enc_auth_field( 'faculty_id', 'Faculty ID', 'text' ) . enc_auth_field( 'email', 'College email', 'email', true, 'email' ) . enc_auth_field( 'mobile', 'Mobile number', 'tel', true, 'tel' ) . enc_auth_field( 'faculty_post', 'Post / designation', 'text' ) . enc_auth_field( 'password', 'Password (at least 10 characters)', 'password', true, 'new-password' ) . enc_auth_field( 'confirm_password', 'Confirm password', 'password', true, 'new-password' ) . '<button class="btn btn--brand btn--block" type="submit">Submit account request</button></form><p class="en-auth__links">Already registered? <a href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></p></section>';
	return $out;
}

function enc_dashboard_event_ids( $department_id = 0, $club_ids = null, $competitions = null ) {
	$args = array( 'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_en_date', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ) );
	if ( $department_id ) $args['meta_query'][] = array( 'key' => '_en_department', 'value' => absint( $department_id ), 'compare' => '=' );
	if ( is_array( $club_ids ) ) {
		if ( ! $club_ids ) return array();
		$args['meta_query'][] = array( 'key' => '_en_club', 'value' => array_map( 'absint', $club_ids ), 'compare' => 'IN' );
	}
	if ( null !== $competitions && taxonomy_exists( 'event_type' ) ) $args['tax_query'] = array( array( 'taxonomy' => 'event_type', 'field' => 'slug', 'terms' => 'competition', 'operator' => $competitions ? 'IN' : 'NOT IN' ) );
	return array_map( 'absint', get_posts( $args ) );
}

function enc_dashboard_registration_count( $event_ids = null ) {
	global $wpdb;
	$table = enc_table( 'registrations' );
	if ( null === $event_ids ) return (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE status = 'registered'" );
	$event_ids = array_values( array_filter( array_map( 'absint', $event_ids ) ) );
	if ( ! $event_ids ) return 0;
	$placeholders = implode( ',', array_fill( 0, count( $event_ids ), '%d' ) );
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE status = 'registered' AND event_id IN ($placeholders)", $event_ids ) );
}

function enc_dashboard_proposal_count( $department_id = 0, $club_ids = null ) {
	$args = array( 'post_type' => 'proposal', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_en_status', 'value' => array( 'submitted', 'under_review' ), 'compare' => 'IN' ) ) );
	if ( $department_id ) $args['meta_query'][] = array( 'key' => '_en_department', 'value' => absint( $department_id ), 'compare' => '=' );
	if ( is_array( $club_ids ) ) {
		if ( ! $club_ids ) return 0;
		$args['meta_query'][] = array( 'key' => '_en_p_club', 'value' => array_map( 'absint', $club_ids ), 'compare' => 'IN' );
	}
	return (int) ( new WP_Query( $args ) )->found_posts;
}

function enc_dashboard_metric_cards( $items ) {
	$out = '<div class="en-dashboard-metrics">';
	foreach ( $items as $item ) {
		$tag = ! empty( $item[2] ) ? 'a href="' . esc_url( $item[2] ) . '"' : 'article';
		$out .= '<' . $tag . ' class="en-dashboard-metric"><strong>' . esc_html( number_format_i18n( (int) $item[0] ) ) . '</strong><span>' . esc_html( $item[1] ) . '</span>' . ( ! empty( $item[2] ) ? '</a>' : '</article>' );
	}
	return $out . '</div>';
}

function enc_dashboard_action_links( $items ) {
	$out = '<div class="en-dashboard-tools">';
	foreach ( $items as $item ) $out .= '<a href="' . esc_url( $item[1] ) . '">' . esc_html( $item[0] ) . '<span aria-hidden="true">→</span></a>';
	return $out . '</div>';
}

function enc_dashboard_faculty_events() {
	if ( ! function_exists( 'enc_user_is_faculty' ) || ! enc_user_is_faculty() ) return '';
	$events = get_posts( array( 'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => 8, 'meta_query' => array( array( 'key' => '_en_audience', 'value' => 'faculty' ), array( 'key' => '_en_date', 'value' => wp_date( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ), 'meta_key' => '_en_date', 'orderby' => 'meta_value', 'order' => 'ASC' ) );
	$out = '<section class="en-dashboard-review"><h2>Faculty events</h2>';
	if ( ! $events ) return $out . '<p class="en-dashboard-note">No upcoming faculty events.</p></section>';
	foreach ( $events as $event ) $out .= '<p><a href="' . esc_url( get_permalink( $event ) ) . '">' . esc_html( $event->post_title ) . '</a> · ' . esc_html( get_post_meta( $event->ID, '_en_date', true ) ) . '</p>';
	return $out . '</section>';
}

function enc_dashboard_event_calendar() {
	$month = isset( $_GET['calendar_month'] ) ? sanitize_text_field( wp_unslash( $_GET['calendar_month'] ) ) : wp_date( 'Y-m' );
	if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) || ! strtotime( $month . '-01' ) ) $month = wp_date( 'Y-m' );
	$start = $month . '-01';
	$end = wp_date( 'Y-m-t', strtotime( $start ) );
	$calendar_meta = array( array( 'key' => '_en_date', 'value' => array( $start, $end ), 'compare' => 'BETWEEN', 'type' => 'DATE' ) );
	if ( ! function_exists( 'enc_user_is_faculty' ) || ! enc_user_is_faculty() ) $calendar_meta[] = array( 'relation' => 'OR', array( 'key' => '_en_audience', 'compare' => 'NOT EXISTS' ), array( 'key' => '_en_audience', 'value' => 'faculty', 'compare' => '!=' ) );
	$events = get_posts( array( 'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => 500, 'meta_query' => $calendar_meta, 'meta_key' => '_en_date', 'orderby' => 'meta_value', 'order' => 'ASC' ) );
	$by_day = array();
	foreach ( $events as $event ) { $day = (int) substr( (string) get_post_meta( $event->ID, '_en_date', true ), 8, 2 ); $by_day[ $day ][] = $event; }
	$first = (int) wp_date( 'N', strtotime( $start ) );
	$days = (int) wp_date( 't', strtotime( $start ) );
	$out = '<section class="en-dashboard-review en-event-calendar"><div class="en-event-calendar__heading"><div><p class="eyebrow eyebrow--small">SCHEDULE</p><h2>All events calendar</h2><p class="muted">Events and competitions across campus</p></div><nav aria-label="Calendar month"><a class="en-calendar__nav" href="' . esc_url( add_query_arg( 'calendar_month', wp_date( 'Y-m', strtotime( $start . ' -1 month' ) ) ) ) . '">← Previous</a><strong>' . esc_html( wp_date( 'F Y', strtotime( $start ) ) ) . '</strong><a class="en-calendar__nav" href="' . esc_url( add_query_arg( 'calendar_month', wp_date( 'Y-m', strtotime( $start . ' +1 month' ) ) ) ) . '">Next →</a></nav></div><table class="en-calendar"><thead><tr><th scope="col">Mon</th><th scope="col">Tue</th><th scope="col">Wed</th><th scope="col">Thu</th><th scope="col">Fri</th><th scope="col">Sat</th><th scope="col">Sun</th></tr></thead><tbody>';
	$day = 1;
	for ( $week = 0; $week < 6 && $day <= $days; $week++ ) {
		$out .= '<tr>';
		for ( $weekday = 1; $weekday <= 7; $weekday++ ) {
			$index = $week * 7 + $weekday;
			if ( $index < $first || $day > $days ) { $out .= '<td></td>'; continue; }
			$date_key = substr( $start, 0, 8 ) . sprintf( '%02d', $day );
			$out .= '<td' . ( $date_key === wp_date( 'Y-m-d' ) ? ' class="is-today"' : '' ) . '><time datetime="' . esc_attr( $date_key ) . '">' . $day . '</time>';
			foreach ( $by_day[ $day ] ?? array() as $event ) {
				$terms = get_the_terms( $event, 'event_type' );
				$type = ! is_wp_error( $terms ) && $terms ? strtolower( $terms[0]->name ) : '';
				$is_competition = false !== strpos( $type, 'competition' );
				$out .= '<a class="en-calendar__event' . ( $is_competition ? ' is-competition' : '' ) . '" href="' . esc_url( get_permalink( $event ) ) . '"><span>' . esc_html( get_the_title( $event ) ) . '</span><small>' . ( $is_competition ? 'Competition' : 'Event' ) . '</small></a>';
			}
			$out .= '</td>';
			$day++;
		}
		$out .= '</tr>';
	}
	$out .= '</tbody></table>';
	if ( ! $events ) $out .= '<p class="en-dashboard-note">No events or competitions scheduled this month.</p>';
	return $out . '</section>';
}

function enc_dashboard_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to continue</h1>' . enc_auth_message( 'login_required' ) . '<a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></section>';
	$user = wp_get_current_user();
	$role = enc_user_role( $user->ID );
	$department_id = function_exists( 'enc_user_department_id' ) ? enc_user_department_id( $user->ID ) : 0;
	$club_ids = array();
	if ( 'en_club_head' === $role ) $club_ids = array_map( 'absint', get_posts( array( 'post_type' => 'club', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_en_club_head', 'meta_value' => $user->ID ) ) );
	$member_count = 0;
	if ( 'en_student' === $role ) {
		global $wpdb;
		$member_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_table( 'club_memberships' ) . " WHERE user_id = %d AND status = 'approved'", $user->ID ) );
	}
	$labels = array( 'en_student' => 'Student Dashboard', 'en_faculty' => 'Faculty Dashboard', 'en_faculty_head' => 'Faculty Head Dashboard', 'en_club_head' => 'Club Head Dashboard', 'en_deputy_director' => 'Deputy Director Dashboard', 'en_director' => 'Director Dashboard', 'administrator' => 'Admin Dashboard', 'subscriber' => 'Account awaiting approval' );
	$title = isset( $labels[ $role ] ) ? $labels[ $role ] : 'EventNest Dashboard';
	$out = '<section class="en-dashboard-page"><header class="en-dashboard-heading"><p class="eyebrow eyebrow--small">EVENTNEST WORKSPACE</p><h1>' . esc_html( $title ) . '</h1><p>Welcome, ' . esc_html( $user->display_name ) . '.</p></header>';
	$out .= enc_dashboard_action_links( array( array( 'Edit profile', home_url( '/my-profile/' ) ) ) );
	if ( 'pending' === get_user_meta( $user->ID, '_en_account_status', true ) ) $out .= '<p class="en-auth__notice en-auth__notice--error">Your faculty access is pending administrator approval.</p>';
	if ( in_array( $role, array( 'en_student', 'en_faculty' ), true ) ) {
		$stats = function_exists( 'enc_student_stats' ) ? enc_student_stats( $user->ID ) : array( 'registered' => 0, 'upcoming' => 0, 'proposals' => 0 );
		$out .= '<div class="en-profile"><p><strong>PRN</strong><span>' . esc_html( get_user_meta( $user->ID, '_en_prn', true ) ) . '</span></p><p><strong>Email</strong><span>' . esc_html( $user->user_email ) . '</span></p><p><strong>Course / batch</strong><span>' . esc_html( trim( get_user_meta( $user->ID, '_en_course', true ) . ' · ' . get_user_meta( $user->ID, '_en_batch', true ), ' ·' ) ) . '</span></p></div>';
		$out .= enc_dashboard_metric_cards( array( array( $stats['registered'], 'Registrations', home_url( '/my-registrations/' ) ), array( $stats['upcoming'], 'Upcoming events', get_post_type_archive_link( 'event' ) ), array( $stats['proposals'], 'Proposals', home_url( '/my-proposals/' ) ), array( $member_count, 'Club memberships', home_url( '/my-clubs/' ) ) ) );
		$out .= enc_dashboard_action_links( array( array( 'My registrations', home_url( '/my-registrations/' ) ), array( 'My proposals', home_url( '/my-proposals/' ) ), array( 'My clubs', home_url( '/my-clubs/' ) ), array( 'Propose an event', home_url( '/submit-proposal/' ) ), array( 'My stories', home_url( '/my-stories/' ) ) ) );
	} elseif ( in_array( $role, array( 'administrator', 'en_director', 'en_deputy_director', 'en_faculty_head', 'en_faculty', 'en_club_head' ), true ) ) {
		$all_access = in_array( $role, array( 'administrator', 'en_director', 'en_deputy_director' ), true );
		$department_scope = in_array( $role, array( 'en_faculty', 'en_faculty_head' ), true ) ? $department_id : 0;
		$missing_department = in_array( $role, array( 'en_faculty', 'en_faculty_head' ), true ) && ! $department_id;
		$event_ids = $missing_department ? array() : enc_dashboard_event_ids( $department_scope, 'en_club_head' === $role ? $club_ids : null, false );
		$competition_ids = $missing_department ? array() : enc_dashboard_event_ids( $department_scope, 'en_club_head' === $role ? $club_ids : null, true );
		$registrations = $all_access ? enc_dashboard_registration_count() : enc_dashboard_registration_count( array_merge( $event_ids, $competition_ids ) );
		$proposals = $missing_department ? 0 : enc_dashboard_proposal_count( $department_scope, 'en_club_head' === $role ? $club_ids : null );
		$registration_url = function_exists( 'enc_registration_scope' ) && enc_registration_scope() ? home_url( '/event-registrations/' ) : '';
		$proposal_url = 'en_club_head' !== $role && ( current_user_can( 'en_review_faculty' ) || current_user_can( 'en_review_deputy' ) || current_user_can( 'en_review_override' ) ) ? home_url( '/review-proposals/' ) : '';
		$metrics = array( array( count( $event_ids ), 'Live events', get_post_type_archive_link( 'event' ) ), array( count( $competition_ids ), 'Live competitions', home_url( '/competitions/' ) ), array( $registrations, 'Registrations in my scope', $registration_url ), array( $proposals, 'Proposals to review', $proposal_url ) );
		if ( 'en_club_head' === $role && function_exists( 'enc_student_stats' ) ) { $mine = enc_student_stats( $user->ID ); $metrics[] = array( $mine['registered'], 'My registrations', home_url( '/my-registrations/' ) ); }
		if ( 'administrator' === $role || 'en_director' === $role ) {
			$metrics[] = array( wp_count_posts( 'club' )->publish, 'Published clubs', get_post_type_archive_link( 'club' ) );
			if ( 'administrator' === $role ) $metrics[] = array( count_users()['total_users'], 'Accounts', admin_url( 'users.php' ) );
		}
	if ( in_array( $role, array( 'administrator', 'en_director', 'en_deputy_director' ), true ) ) $out .= enc_dashboard_event_calendar();
		$out .= enc_dashboard_metric_cards( $metrics );
		if ( $missing_department ) $out .= '<p class="en-dashboard-note">Ask an administrator to assign your department to see its events, registrations, and proposals.</p>';
		$links = array();
		$links[] = array( 'Edit profile', home_url( '/my-profile/' ) );
		if ( 'administrator' === $role ) {
			$links = array( array( 'Manage events · edit or delete', admin_url( 'edit.php?post_type=event' ) ), array( 'Manage competitions · edit or delete', admin_url( 'edit.php?post_type=event&page=enc-competitions' ) ), array( 'Manage clubs · edit or delete', admin_url( 'edit.php?post_type=club' ) ), array( 'Club leadership', admin_url( 'edit.php?post_type=club&page=enc-club-leadership' ) ), array( 'Review proposals', home_url( '/review-proposals/' ) ), array( 'Event registrations', home_url( '/event-registrations/' ) ), array( 'Student stories', admin_url( 'edit.php?post_type=student_story' ) ), array( 'Faculty requests', home_url( '/dashboard/#en-staff-approvals' ) ), array( 'Manage / audit accounts', admin_url( 'users.php?page=enc-accounts' ) ), array( 'All users', admin_url( 'users.php' ) ) );
		} else {
			if ( 'en_club_head' !== $role && ( current_user_can( 'en_review_faculty' ) || current_user_can( 'en_review_deputy' ) || current_user_can( 'en_review_override' ) ) ) $links[] = array( 'Review proposals', home_url( '/review-proposals/' ) );
			if ( function_exists( 'enc_registration_scope' ) && enc_registration_scope() ) $links[] = array( 'View event registrations', home_url( '/event-registrations/' ) );
			if ( 'administrator' === $role ) $links[] = array( 'Staff account requests', home_url( '/dashboard/#en-staff-approvals' ) );
			if ( 'en_club_head' === $role ) {
				$links[] = array( 'My registrations', home_url( '/my-registrations/' ) );
				$links[] = array( 'My proposals', home_url( '/my-proposals/' ) );
				$links[] = array( 'My stories', home_url( '/my-stories/' ) );
				$links[] = array( 'Edit profile', home_url( '/my-profile/' ) );
				$links[] = array( 'My clubs and applications', home_url( '/my-clubs/' ) );
			}
			if ( current_user_can( 'edit_en_events' ) ) $links[] = array( 'en_club_head' === $role ? 'Manage my club events' : 'Manage department events', admin_url( 'edit.php?post_type=event' ) );
			if ( in_array( $role, array( 'en_deputy_director', 'en_director' ), true ) ) {
				$links[] = array( 'Manage events', admin_url( 'edit.php?post_type=event' ) );
				$links[] = array( 'Manage competitions', admin_url( 'edit.php?post_type=event&page=enc-competitions' ) );
				$links[] = array( 'Manage clubs', admin_url( 'edit.php?post_type=club' ) );
			}
		}
		if ( $links ) $out .= enc_dashboard_action_links( $links );
		$out .= enc_dashboard_staff_approval_queue();
		if ( 'administrator' === $role && function_exists( 'enc_dashboard_story_review_queue' ) ) $out .= enc_dashboard_story_review_queue();
		if ( 'en_club_head' === $role && $club_ids ) {
			global $wpdb;
			$holders = implode( ',', array_fill( 0, count( $club_ids ), '%d' ) );
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT m.id, m.club_id, m.message, m.created_at, u.display_name, u.user_email, p.post_title FROM ' . enc_table( 'club_memberships' ) . ' m INNER JOIN ' . $wpdb->users . ' u ON u.ID = m.user_id LEFT JOIN ' . $wpdb->posts . " p ON p.ID = m.club_id WHERE m.status = 'pending' AND m.club_id IN ($holders) ORDER BY m.created_at ASC", $club_ids ) );
			$out .= '<section class="en-dashboard-review"><h2>Pending club applications (' . esc_html( number_format_i18n( count( $rows ) ) ) . ')</h2>';
			if ( $rows ) {
				foreach ( $rows as $row ) {
					if ( ! enc_can_review_club_membership( (int) $row->club_id, $user->ID ) ) continue;
					$out .= '<article class="en-club-application"><h3>' . esc_html( $row->display_name ) . ' · ' . esc_html( $row->post_title ) . '</h3><p class="muted"><a href="mailto:' . esc_attr( $row->user_email ) . '">' . esc_html( $row->user_email ) . '</a> · ' . esc_html( get_date_from_gmt( $row->created_at, 'j M Y' ) ) . '</p>';
					if ( $row->message ) $out .= '<p>' . nl2br( esc_html( $row->message ) ) . '</p>';
					$out .= '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="en-club-review__form"><input type="hidden" name="action" value="enc_review_club_membership"><input type="hidden" name="club_id" value="' . esc_attr( $row->club_id ) . '"><input type="hidden" name="application_id" value="' . esc_attr( $row->id ) . '">' . wp_nonce_field( 'enc_review_club_membership_' . $row->id, 'enc_review_club_nonce', true, false ) . '<label class="en-auth__field"><span>Decision note (optional)</span><textarea name="review_note" rows="2" maxlength="1000"></textarea></label><button class="btn btn--brand" name="decision" value="approved" type="submit">Approve</button> <button class="btn btn--ghost" name="decision" value="rejected" type="submit">Reject</button></form></article>';
				}
			} else $out .= '<p class="en-dashboard-note">No applications are waiting for review.</p>';
			$out .= '</section>';
		}
	}
	if ( function_exists( 'enc_user_is_faculty' ) && enc_user_is_faculty( $user->ID ) ) $out .= enc_dashboard_faculty_events();
	$out .= '<div class="en-dashboard__actions"><a class="btn btn--brand" href="' . esc_url( get_post_type_archive_link( 'event' ) ) . '">Browse events</a><a class="btn btn--ghost" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Log out</a></div></section>';
	return $out;
}
