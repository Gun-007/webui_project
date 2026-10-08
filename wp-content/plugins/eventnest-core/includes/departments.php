<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function enc_department_choices( $selected = 0 ) {
	$items = get_posts( array( 'post_type' => 'faculty_department', 'post_status' => 'publish', 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC' ) );
	$out = '<option value="0">' . esc_html__( 'No department', 'eventnest-core' ) . '</option>';
	foreach ( $items as $item ) $out .= '<option value="' . esc_attr( $item->ID ) . '"' . selected( (int) $selected, (int) $item->ID, false ) . '>' . esc_html( $item->post_title ) . '</option>';
	return $out;
}

function enc_required_department_choices( $selected = 0 ) {
	return str_replace( '<option value="0">No department</option>', '<option value="">Choose a department</option>', enc_department_choices( $selected ) );
}

function enc_user_department_id( $user_id ) {
	return absint( get_user_meta( absint( $user_id ), '_en_department', true ) );
}

function enc_department_for_object( $post_id ) {
	$post_id = absint( $post_id );
	$department = absint( get_post_meta( $post_id, '_en_department', true ) );
	if ( $department ) return $department;
	$type = get_post_type( $post_id );
	$club_id = 0;
	if ( 'club' === $type ) return 0;
	if ( 'event' === $type ) $club_id = absint( get_post_meta( $post_id, '_en_club', true ) );
	if ( 'proposal' === $type ) $club_id = absint( get_post_meta( $post_id, '_en_p_club', true ) );
	return $club_id ? absint( get_post_meta( $club_id, '_en_department', true ) ) : 0;
}

function enc_user_is_faculty_scope_role( $user_id ) {
	$user = get_userdata( absint( $user_id ) );
	return $user && ( in_array( 'en_faculty', (array) $user->roles, true ) || in_array( 'en_faculty_head', (array) $user->roles, true ) );
}

/** Assign departments only through trusted administrator user-edit screens. */
function enc_department_user_field( $user ) {
	if ( ! current_user_can( 'manage_options' ) || ! enc_user_is_faculty_scope_role( $user->ID ) ) return;
	echo '<h2>EventNest department</h2><table class="form-table"><tr><th><label for="_en_department">Department</label></th><td><select name="_en_department" id="_en_department">' . enc_department_choices( enc_user_department_id( $user->ID ) ) . '</select><p class="description">Faculty and Faculty Head access to events, proposals, and registration reports follows this department assignment.</p>' . wp_nonce_field( 'enc_save_user_department_' . $user->ID, 'enc_department_nonce', true, false ) . '</td></tr></table>';
}
add_action( 'show_user_profile', 'enc_department_user_field' );
add_action( 'edit_user_profile', 'enc_department_user_field' );

function enc_save_user_department( $user_id ) {
	if ( ! current_user_can( 'manage_options' ) || ! enc_user_is_faculty_scope_role( $user_id ) ) return;
	$nonce = isset( $_POST['enc_department_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_department_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_save_user_department_' . $user_id ) ) return;
	$department_id = isset( $_POST['_en_department'] ) ? absint( $_POST['_en_department'] ) : 0;
	if ( $department_id && ( get_post_type( $department_id ) !== 'faculty_department' || get_post_status( $department_id ) !== 'publish' ) ) return;
	if ( $department_id ) update_user_meta( $user_id, '_en_department', $department_id );
	else delete_user_meta( $user_id, '_en_department' );
}
add_action( 'personal_options_update', 'enc_save_user_department' );
add_action( 'edit_user_profile_update', 'enc_save_user_department' );

/** Admin-side department assignments for clubs and direct events. */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'enc_club_department', 'Department', 'enc_club_department_box', 'club', 'side', 'default' );
	add_meta_box( 'enc_event_department', 'Department', 'enc_event_department_box', 'event', 'side', 'default' );
} );

function enc_club_department_box( $post ) {
	wp_nonce_field( 'enc_save_club_department_' . $post->ID, 'enc_club_department_nonce' );
	echo '<label for="_en_department">Owning department</label><select class="widefat" name="_en_department" id="_en_department">' . enc_department_choices( get_post_meta( $post->ID, '_en_department', true ) ) . '</select><p class="description">Events and proposals assigned to this club inherit its department.</p>';
}

function enc_event_department_box( $post ) {
	$club_id = absint( get_post_meta( $post->ID, '_en_club', true ) );
	$club_department = $club_id ? absint( get_post_meta( $club_id, '_en_department', true ) ) : 0;
	wp_nonce_field( 'enc_save_event_department_' . $post->ID, 'enc_event_department_nonce' );
	if ( $club_department ) {
		echo '<p>Department inherited from <strong>' . esc_html( get_the_title( $club_id ) ) . '</strong>.</p><input type="hidden" name="_en_department" value="' . esc_attr( $club_department ) . '">';
	} else {
		echo '<label for="_en_department">Owning department</label><select class="widefat" name="_en_department" id="_en_department">' . enc_department_choices( get_post_meta( $post->ID, '_en_department', true ) ) . '</select>';
	}
}

function enc_save_club_department( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	$nonce = isset( $_POST['enc_club_department_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_club_department_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_save_club_department_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) return;
	$department_id = isset( $_POST['_en_department'] ) ? absint( $_POST['_en_department'] ) : 0;
	if ( $department_id && ( get_post_type( $department_id ) !== 'faculty_department' || get_post_status( $department_id ) !== 'publish' ) ) $department_id = 0;
	if ( $department_id ) update_post_meta( $post_id, '_en_department', $department_id ); else delete_post_meta( $post_id, '_en_department' );
	enc_sync_club_department( $post_id, $department_id );
}
add_action( 'save_post_club', 'enc_save_club_department', 5 );

function enc_save_event_department( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	$nonce = isset( $_POST['enc_event_department_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_event_department_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_save_event_department_' . $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) return;
	$club_id = absint( get_post_meta( $post_id, '_en_club', true ) );
	$department_id = $club_id ? absint( get_post_meta( $club_id, '_en_department', true ) ) : ( isset( $_POST['_en_department'] ) ? absint( $_POST['_en_department'] ) : 0 );
	if ( ! current_user_can( 'manage_options' ) && enc_user_is_faculty_scope_role( get_current_user_id() ) ) $department_id = enc_user_department_id( get_current_user_id() ) ?: absint( get_post_meta( $post_id, '_en_department', true ) );
	if ( $department_id && ( get_post_type( $department_id ) !== 'faculty_department' || get_post_status( $department_id ) !== 'publish' ) ) $department_id = 0;
	if ( $department_id ) update_post_meta( $post_id, '_en_department', $department_id ); else delete_post_meta( $post_id, '_en_department' );
}
add_action( 'save_post_event', 'enc_save_event_department', 20 );

function enc_sync_club_department( $club_id, $department_id ) {
	foreach ( array( 'event' => '_en_club', 'proposal' => '_en_p_club' ) as $type => $club_meta ) {
		$items = get_posts( array( 'post_type' => $type, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ), 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => $club_meta, 'meta_value' => $club_id ) );
		foreach ( $items as $item_id ) {
			if ( $department_id ) update_post_meta( $item_id, '_en_department', $department_id ); else delete_post_meta( $item_id, '_en_department' );
		}
	}
}

/** Department scope is enforced on direct post edits and in their admin lists. */
add_filter( 'map_meta_cap', function ( $caps, $cap, $user_id, $args ) {
	if ( ! in_array( $cap, array( 'edit_post', 'delete_post', 'read_post' ), true ) || empty( $args[0] ) || user_can( $user_id, 'manage_options' ) || ! enc_user_is_faculty_scope_role( $user_id ) ) return $caps;
	$post_id = absint( $args[0] );
	if ( ! in_array( get_post_type( $post_id ), array( 'event', 'proposal' ), true ) ) return $caps;
	$department = enc_user_department_id( $user_id );
	$user = get_userdata( $user_id );
	$is_head = $user && in_array( 'en_faculty_head', (array) $user->roles, true );
	$object_department = enc_department_for_object( $post_id );
	$post = get_post( $post_id );
	$is_new_own_draft = $post && (int) $post->post_author === (int) $user_id && in_array( $post->post_status, array( 'auto-draft', 'draft', 'pending' ), true );
	if ( ( $is_head && ! $department ) || ( $department && $object_department !== $department && ! ( ! $object_department && $is_new_own_draft ) ) ) return array( 'do_not_allow' );
	return $caps;
}, 20, 4 );

add_action( 'pre_get_posts', function ( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || ! enc_user_is_faculty_scope_role( get_current_user_id() ) || current_user_can( 'manage_options' ) ) return;
	$type = $query->get( 'post_type' );
	if ( ! in_array( $type, array( 'event', 'proposal' ), true ) ) return;
	$department = enc_user_department_id( get_current_user_id() );
	$user = wp_get_current_user();
	if ( ! $department && in_array( 'en_faculty_head', (array) $user->roles, true ) ) {
		$query->set( 'post__in', array( 0 ) );
		return;
	}
	if ( $department ) $query->set( 'meta_query', array_merge( (array) $query->get( 'meta_query' ), array( array( 'key' => '_en_department', 'value' => $department, 'compare' => '=' ) ) ) );
} );
