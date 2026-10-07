<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/** Receive a small raster image for a student-submitted post. Returns 0 when omitted. */
function enc_receive_student_image( $field_name ) {
	if ( ! isset( $_FILES[ $field_name ] ) || ! is_array( $_FILES[ $field_name ] ) ) return 0;
	$file = $_FILES[ $field_name ];
	if ( empty( $file['name'] ) || ( isset( $file['error'] ) && (int) $file['error'] === UPLOAD_ERR_NO_FILE ) ) return 0;
	if ( ! isset( $file['error'] ) || (int) $file['error'] !== UPLOAD_ERR_OK ) return new WP_Error( 'image_upload', 'The image could not be uploaded.' );
	if ( empty( $file['size'] ) || (int) $file['size'] > 5 * MB_IN_BYTES ) return new WP_Error( 'image_size', 'Choose an image smaller than 5 MB.' );

	$allowed_mimes = array( 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp' );
	$checked = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ), $allowed_mimes );
	if ( empty( $checked['type'] ) || ! in_array( $checked['type'], $allowed_mimes, true ) ) return new WP_Error( 'image_type', 'Use a JPG, PNG, GIF, or WebP image.' );

	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	return media_handle_upload( $field_name, 0, array(), array( 'test_form' => false, 'mimes' => $allowed_mimes ) );
}

function enc_attach_student_image( $attachment_id, $post_id ) {
	$attachment_id = absint( $attachment_id );
	$post_id = absint( $post_id );
	if ( ! $attachment_id || ! $post_id ) return;
	wp_update_post( array( 'ID' => $attachment_id, 'post_parent' => $post_id ) );
	set_post_thumbnail( $post_id, $attachment_id );
}
