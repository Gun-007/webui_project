<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	add_shortcode( 'eventnest_my_profile', 'enc_my_profile_shortcode' );
} );

add_action( 'init', function () {
	if ( get_page_by_path( 'my-profile', OBJECT, 'page' ) ) return;
	wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => 'my-profile',
		'post_title'   => 'My Profile',
		'post_content' => '[eventnest_my_profile]',
	) );
}, 40 );

add_action( 'admin_post_enc_update_profile', 'enc_handle_profile_update' );
add_action( 'admin_post_nopriv_enc_update_profile', 'enc_handle_profile_update' );

function enc_profile_redirect( $result ) {
	wp_safe_redirect( add_query_arg( 'profile_result', $result, home_url( '/my-profile/' ) ) );
	exit;
}

function enc_handle_profile_update() {
	if ( ! is_user_logged_in() ) {
		wp_die( esc_html__( 'Sign in to update your profile.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	}

	$user_id = get_current_user_id();
	$nonce = isset( $_POST['enc_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_profile_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_update_profile_' . $user_id ) ) enc_profile_redirect( 'error' );

	$name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$mobile = isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '';
	$course = isset( $_POST['course'] ) ? sanitize_text_field( wp_unslash( $_POST['course'] ) ) : '';
	$batch = isset( $_POST['batch'] ) ? sanitize_text_field( wp_unslash( $_POST['batch'] ) ) : '';
	$email_owner = email_exists( $email );
	if ( '' === $name || ! is_email( $email ) || ( $email_owner && (int) $email_owner !== $user_id ) ) enc_profile_redirect( 'invalid' );

	$updated = wp_update_user( array(
		'ID'           => $user_id,
		'user_email'   => $email,
		'display_name' => $name,
		'first_name'   => $name,
	) );
	if ( is_wp_error( $updated ) ) enc_profile_redirect( 'invalid' );

	update_user_meta( $user_id, '_en_full_name', $name );
	update_user_meta( $user_id, '_en_mobile', $mobile );
	update_user_meta( $user_id, '_en_course', $course );
	update_user_meta( $user_id, '_en_batch', $batch );
	enc_profile_redirect( 'saved' );
}

function enc_my_profile_shortcode() {
	if ( ! is_user_logged_in() ) {
		$login_url = add_query_arg( 'redirect_to', home_url( '/my-profile/' ), home_url( '/login/' ) );
		return '<section class="en-auth-card"><h1>Log in to view your profile</h1><a class="btn btn--brand" href="' . esc_url( $login_url ) . '">Log in</a></section>';
	}
	$user_id = get_current_user_id();
	$user = get_userdata( $user_id );
	$result = isset( $_GET['profile_result'] ) ? sanitize_key( wp_unslash( $_GET['profile_result'] ) ) : '';
	$out = '<section class="en-auth-card en-profile-editor"><p class="eyebrow eyebrow--small">YOUR EVENTNEST ACCOUNT</p><h1>My Profile</h1><p class="muted">Keep your contact and study details up to date.</p>';
	if ( 'saved' === $result ) $out .= '<p class="en-auth__notice en-auth__notice--success" role="status">Your profile was updated.</p>';
	if ( 'invalid' === $result || 'error' === $result ) $out .= '<p class="en-auth__notice en-auth__notice--error" role="status">We could not save those changes. Check your name and email, then try again.</p>';
	$out .= '<form class="en-auth" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_update_profile">' . wp_nonce_field( 'enc_update_profile_' . $user_id, 'enc_profile_nonce', true, false );
	$out .= '<label class="en-auth__field"><span>Full name *</span><input required maxlength="120" name="full_name" autocomplete="name" value="' . esc_attr( get_user_meta( $user_id, '_en_full_name', true ) ?: $user->display_name ) . '"></label>';
	$out .= '<label class="en-auth__field"><span>College email *</span><input required type="email" maxlength="100" name="email" autocomplete="email" value="' . esc_attr( $user->user_email ) . '"></label>';
	$out .= '<div class="en-auth__row"><label class="en-auth__field"><span>Mobile number</span><input type="tel" maxlength="40" name="mobile" autocomplete="tel" value="' . esc_attr( get_user_meta( $user_id, '_en_mobile', true ) ) . '"></label><label class="en-auth__field"><span>PRN (account ID)</span><input value="' . esc_attr( get_user_meta( $user_id, '_en_prn', true ) ) . '" readonly></label></div>';
	$out .= '<div class="en-auth__row"><label class="en-auth__field"><span>Course</span><input maxlength="100" name="course" value="' . esc_attr( get_user_meta( $user_id, '_en_course', true ) ) . '"></label><label class="en-auth__field"><span>Batch / year</span><input maxlength="60" name="batch" value="' . esc_attr( get_user_meta( $user_id, '_en_batch', true ) ) . '"></label></div>';
	$out .= '<p class="muted en-profile-editor__note">Your PRN identifies your account and registration history, so it can’t be changed here. Contact an administrator if it is incorrect.</p><button class="btn btn--brand" type="submit">Save profile</button></form></section>';
	return $out;
}
