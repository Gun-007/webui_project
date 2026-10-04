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
		'pending_account' => array( 'Your faculty account is awaiting administrator approval.', 'error' ),
		'invalid' => array( 'Please check the form and try again. Passwords must match and PRN / faculty ID must be unique.', 'error' ),
		'exists' => array( 'An account already uses that email address.', 'error' ),
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
			$matches = get_users( array( 'meta_key' => '_en_prn', 'meta_value' => $identity, 'number' => 1, 'fields' => 'all' ) );
			if ( $matches ) $username = $matches[0]->user_login;
		}
		$user = wp_signon( array( 'user_login' => $username, 'user_password' => $password, 'remember' => ! empty( $_POST['remember'] ) ), is_ssl() );
		if ( is_wp_error( $user ) ) enc_auth_redirect( 'login', 'login_failed' );
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
	if ( $name === '' || ! is_email( $email ) || email_exists( $email ) || strlen( $password ) < 10 || $password !== $confirm ) enc_auth_redirect( $redirect_page, email_exists( $email ) ? 'exists' : 'invalid' );

	$meta = array( '_en_full_name' => $name, '_en_mobile' => $mobile );
	if ( $is_student ) {
		$prn = isset( $_POST['prn'] ) ? sanitize_text_field( wp_unslash( $_POST['prn'] ) ) : '';
		$batch = isset( $_POST['batch'] ) ? sanitize_text_field( wp_unslash( $_POST['batch'] ) ) : '';
		$course = isset( $_POST['course'] ) ? sanitize_text_field( wp_unslash( $_POST['course'] ) ) : '';
		if ( $prn === '' || get_users( array( 'meta_key' => '_en_prn', 'meta_value' => $prn, 'number' => 1, 'fields' => 'ids' ) ) ) enc_auth_redirect( $redirect_page, 'invalid' );
		$meta['_en_prn'] = $prn;
		$meta['_en_batch'] = $batch;
		$meta['_en_course'] = $course;
	} else {
		$faculty_id = isset( $_POST['faculty_id'] ) ? sanitize_text_field( wp_unslash( $_POST['faculty_id'] ) ) : '';
		$post = isset( $_POST['faculty_post'] ) ? sanitize_text_field( wp_unslash( $_POST['faculty_post'] ) ) : '';
		if ( $faculty_id === '' || get_users( array( 'meta_key' => '_en_faculty_id', 'meta_value' => $faculty_id, 'number' => 1, 'fields' => 'ids' ) ) ) enc_auth_redirect( $redirect_page, 'invalid' );
		$meta['_en_faculty_id'] = $faculty_id;
		$meta['_en_faculty_post'] = $post;
	}

	$email_parts = explode( '@', $email );
	$username_seed = $is_student ? 'student_' . $meta['_en_prn'] : $email_parts[0];
	$user_id = wp_create_user( enc_auth_unique_username( $username_seed ), $password, $email );
	if ( is_wp_error( $user_id ) ) enc_auth_redirect( $redirect_page, 'invalid' );
	wp_update_user( array( 'ID' => $user_id, 'display_name' => $name, 'first_name' => $name ) );
	$user = new WP_User( $user_id );
	$user->set_role( $is_student ? 'en_student' : 'subscriber' );
	foreach ( $meta as $key => $value ) update_user_meta( $user_id, $key, $value );
	if ( ! $is_student ) update_user_meta( $user_id, '_en_account_status', 'pending' );
	enc_auth_redirect( $redirect_page, $is_student ? 'created' : 'pending' );
}, 20 );

/** Assigning a verified EventNest staff role in Users completes the pending signup. */
add_action( 'set_user_role', function ( $user_id, $role ) {
	if ( in_array( $role, array( 'en_faculty', 'en_club_head', 'en_deputy_director', 'en_director', 'administrator' ), true ) ) {
		delete_user_meta( $user_id, '_en_account_status' );
	}
}, 10, 2 );

/** A small review queue for faculty and club-head signup requests. */
add_action( 'admin_menu', function () {
	add_users_page( 'Faculty Requests', 'Faculty Requests', 'promote_users', 'enc-faculty-requests', 'enc_render_faculty_requests' );
} );

add_action( 'admin_post_enc_approve_faculty', function () {
	if ( ! current_user_can( 'promote_users' ) ) wp_die( esc_html__( 'You are not allowed to review account requests.', 'eventnest-core' ) );
	$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
	$role = isset( $_POST['eventnest_role'] ) ? sanitize_key( wp_unslash( $_POST['eventnest_role'] ) ) : '';
	$nonce = isset( $_POST['enc_approval_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_approval_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_approve_faculty_' . $user_id ) || ! in_array( $role, array( 'en_faculty', 'en_club_head' ), true ) || get_user_meta( $user_id, '_en_account_status', true ) !== 'pending' ) {
		wp_safe_redirect( add_query_arg( 'request_result', 'invalid', admin_url( 'users.php?page=enc-faculty-requests' ) ) );
		exit;
	}
	$user = get_user_by( 'id', $user_id );
	if ( ! $user ) {
		wp_safe_redirect( add_query_arg( 'request_result', 'invalid', admin_url( 'users.php?page=enc-faculty-requests' ) ) );
		exit;
	}
	$user->set_role( $role );
	update_user_meta( $user_id, '_en_account_status', 'approved' );
	wp_safe_redirect( add_query_arg( 'request_result', 'approved', admin_url( 'users.php?page=enc-faculty-requests' ) ) );
	exit;
} );

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
		echo '<select name="eventnest_role" aria-label="Role for ' . esc_attr( $user->display_name ) . '"><option value="en_faculty">Faculty</option><option value="en_club_head">Club Head</option></select> ';
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

function enc_dashboard_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to continue</h1>' . enc_auth_message( 'login_required' ) . '<a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></section>';
	$user = wp_get_current_user();
	$role = enc_user_role( $user->ID );
	$labels = array( 'en_student' => 'Student Dashboard', 'en_faculty' => 'Faculty Dashboard', 'en_club_head' => 'Club Head Dashboard', 'en_deputy_director' => 'Deputy Director Dashboard', 'en_director' => 'Director Dashboard', 'administrator' => 'Admin Dashboard', 'subscriber' => 'Account awaiting approval' );
	$title = isset( $labels[ $role ] ) ? $labels[ $role ] : 'EventNest Dashboard';
	$out = '<section class="en-auth-card en-dashboard"><p class="eyebrow eyebrow--small">YOUR EVENTNEST</p><h1>' . esc_html( $title ) . '</h1><p>Welcome, ' . esc_html( $user->display_name ) . '.</p>';
	if ( get_user_meta( $user->ID, '_en_account_status', true ) === 'pending' ) $out .= '<p class="en-auth__notice en-auth__notice--error">Your faculty access is pending administrator approval.</p>';
	if ( $role === 'en_student' ) {
		$out .= '<div class="en-profile"><p><strong>PRN</strong><span>' . esc_html( get_user_meta( $user->ID, '_en_prn', true ) ) . '</span></p><p><strong>Email</strong><span>' . esc_html( $user->user_email ) . '</span></p><p><strong>Course / batch</strong><span>' . esc_html( trim( get_user_meta( $user->ID, '_en_course', true ) . ' · ' . get_user_meta( $user->ID, '_en_batch', true ), ' ·' ) ) . '</span></p></div>';
		if ( function_exists( 'enc_student_stats' ) ) {
			$stats = enc_student_stats( $user->ID );
			$out .= '<div class="en-student-stats"><a href="' . esc_url( home_url( '/my-registrations/' ) ) . '"><strong>' . esc_html( number_format_i18n( $stats['registered'] ) ) . '</strong><span>Registrations</span></a><a href="' . esc_url( home_url( '/my-registrations/' ) ) . '"><strong>' . esc_html( number_format_i18n( $stats['upcoming'] ) ) . '</strong><span>Upcoming</span></a><a href="' . esc_url( home_url( '/my-proposals/' ) ) . '"><strong>' . esc_html( number_format_i18n( $stats['proposals'] ) ) . '</strong><span>Proposals</span></a></div><p class="en-dashboard__links"><a href="' . esc_url( home_url( '/my-registrations/' ) ) . '">My Registrations</a><a href="' . esc_url( home_url( '/my-proposals/' ) ) . '">My Proposals</a><a href="' . esc_url( home_url( '/submit-proposal/' ) ) . '">Propose an event</a></p>';
		}
	}
	if ( current_user_can( 'en_review_faculty' ) || current_user_can( 'en_review_deputy' ) || current_user_can( 'en_review_override' ) ) {
		$out .= '<p class="en-dashboard__links"><a href="' . esc_url( home_url( '/review-proposals/' ) ) . '">Review Proposals</a></p>';
	}
	$out .= '<div class="en-dashboard__actions"><a class="btn btn--brand" href="' . esc_url( get_post_type_archive_link( 'event' ) ) . '">Browse events</a> <a class="btn btn--ghost" href="' . esc_url( wp_logout_url( home_url( '/' ) ) ) . '">Log out</a></div></section>';
	return $out;
}
