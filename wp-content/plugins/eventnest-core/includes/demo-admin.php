<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=event', 'Starter Demo Content', 'Starter Demo Content', 'manage_options', 'enc-demo-content', 'enc_render_demo_content_admin' );
} );

add_action( 'admin_post_enc_seed_demo_content', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'Administrator access required.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	check_admin_referer( 'enc_seed_demo_content' );
	enc_seed_default_clubs();
	enc_seed_default_club_events();
	wp_safe_redirect( add_query_arg( 'seeded', '1', admin_url( 'edit.php?post_type=event&page=enc-demo-content' ) ) );
	exit;
} );

function enc_render_demo_content_admin() {
	if ( ! current_user_can( 'manage_options' ) ) return;
	echo '<div class="wrap"><h1>Starter Demo Content</h1><p>Optionally add sample clubs and sample events for a fresh demo site. Existing records are not replaced. This action is never run automatically on normal page loads.</p>';
	if ( isset( $_GET['seeded'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['seeded'] ) ) ) echo '<div class="notice notice-success is-dismissible"><p>Starter content seeding was requested. Review Events and Clubs to confirm the sample records.</p></div>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_seed_demo_content">' . wp_nonce_field( 'enc_seed_demo_content', '_wpnonce', true, false ) . '<p><button class="button button-primary" type="submit">Add starter clubs and events</button></p></form></div>';
}
