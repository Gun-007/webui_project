<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'club' ) ) return;
	$query->set( 'posts_per_page', 12 );
	$query->set( 'orderby', 'title' );
	$query->set( 'order', 'ASC' );
	$search = isset( $_GET['club_search'] ) ? sanitize_text_field( wp_unslash( $_GET['club_search'] ) ) : '';
	if ( $search !== '' ) $query->set( 's', $search );
} );

function en_club_person_name( $meta_key ) {
	$user_id = absint( get_post_meta( get_the_ID(), $meta_key, true ) );
	$user = $user_id ? get_userdata( $user_id ) : false;
	return $user ? $user->display_name : '';
}
