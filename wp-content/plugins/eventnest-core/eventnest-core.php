<?php
/**
 * Plugin Name: EventNest Core
 * Description: Data model, roles, proposal approval workflow and event registration for the EventNest college portal.
 * Version: 0.3.0
 * Requires PHP: 7.4
 * Text Domain: eventnest-core
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'ENC_VERSION', '0.3.0' );
define( 'ENC_DB_VERSION', '1' );
define( 'ENC_DIR', plugin_dir_path( __FILE__ ) );

require_once ENC_DIR . 'includes/roles.php';
require_once ENC_DIR . 'includes/post-types.php';
require_once ENC_DIR . 'includes/database.php';
require_once ENC_DIR . 'includes/registrations.php';
require_once ENC_DIR . 'includes/proposals.php';
require_once ENC_DIR . 'includes/auth.php';
require_once ENC_DIR . 'includes/student-portal.php';
require_once ENC_DIR . 'includes/review-portal.php';

register_activation_hook( __FILE__, 'enc_activate' );

function enc_activate() {
	enc_register_post_types();
	enc_create_tables();
	enc_sync_roles();
	enc_seed_terms();
	update_option( 'enc_db_version', ENC_DB_VERSION );
	flush_rewrite_rules();
}

/** Re-sync roles/tables after a plugin update without needing re-activation. */
add_action( 'plugins_loaded', function () {
	if ( get_option( 'enc_db_version' ) !== ENC_DB_VERSION ) {
		enc_create_tables();
		enc_sync_roles();
		update_option( 'enc_db_version', ENC_DB_VERSION );
	}
} );
