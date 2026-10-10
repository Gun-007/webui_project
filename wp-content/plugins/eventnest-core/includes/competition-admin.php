<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=event', 'Competitions', 'Competitions', 'edit_en_events', 'enc-competitions', 'enc_render_competition_admin' );
} );
add_action( 'pre_get_posts', 'enc_hide_competitions_from_event_admin_list' );
add_action( 'admin_post_enc_create_competition', 'enc_handle_create_competition' );

function enc_hide_competitions_from_event_admin_list( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || $query->get( 'post_type' ) !== 'event' || $query->get( 'page' ) ) return;
	if ( ! taxonomy_exists( 'event_type' ) ) return;
	$tax_query = (array) $query->get( 'tax_query' );
	$exclude = array( 'taxonomy' => 'event_type', 'field' => 'slug', 'terms' => array( 'competition' ), 'operator' => 'NOT IN' );
	if ( isset( $tax_query['relation'] ) && strtoupper( $tax_query['relation'] ) === 'OR' ) {
		$tax_query = array( 'relation' => 'AND', $tax_query, $exclude );
	} else {
		$tax_query[] = $exclude;
	}
	$query->set( 'tax_query', $tax_query );
}

function enc_handle_create_competition() {
	if ( ! current_user_can( 'create_en_events' ) ) wp_die( esc_html__( 'You cannot create competitions.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	$nonce = isset( $_POST['enc_create_competition_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_create_competition_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_create_competition' ) ) wp_die( esc_html__( 'That action could not be verified.', 'eventnest-core' ), '', array( 'response' => 400 ) );
	$term = get_term_by( 'slug', 'competition', 'event_type' );
	if ( ! $term ) wp_die( esc_html__( 'The Competition event type is missing. Ask an administrator to refresh EventNest categories.', 'eventnest-core' ), '', array( 'response' => 500 ) );
	$post_id = wp_insert_post( array( 'post_type' => 'event', 'post_status' => 'draft', 'post_title' => '', 'post_author' => get_current_user_id() ), true );
	if ( is_wp_error( $post_id ) ) wp_die( esc_html__( 'The competition draft could not be created.', 'eventnest-core' ), '', array( 'response' => 500 ) );
	wp_set_object_terms( $post_id, array( (int) $term->term_id ), 'event_type' );
	update_post_meta( $post_id, '_en_scope', 'intra' );
	enc_log_admin_action( 'competition_draft_created', 0, $post_id, 'Created from the Competitions admin workspace.' );
	wp_safe_redirect( add_query_arg( 'enc_competition_created', '1', get_edit_post_link( $post_id, 'url' ) ) );
	exit;
}

function enc_render_competition_admin() {
	if ( ! current_user_can( 'edit_en_events' ) ) return;
	$args = array( 'post_type' => 'event', 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 300, 'orderby' => 'date', 'order' => 'DESC', 'tax_query' => array( array( 'taxonomy' => 'event_type', 'field' => 'slug', 'terms' => array( 'competition' ) ) ) );
	if ( in_array( 'en_faculty_head', (array) wp_get_current_user()->roles, true ) && ! current_user_can( 'manage_options' ) ) {
		$clubs = get_posts( array( 'post_type' => 'club', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_en_club_faculty', 'meta_value' => get_current_user_id() ) );
		$args['meta_query'] = array( $clubs ? array( 'key' => '_en_club', 'value' => array_map( 'absint', $clubs ), 'compare' => 'IN' ) : array( 'key' => '_en_club', 'value' => '-1' ) );
	}
	$posts = get_posts( $args );
	echo '<div class="wrap"><h1>Competitions</h1><p>Competitions have their own admin workspace and continue to use EventNest event records, registration history, and public URLs.</p>';
	if ( isset( $_GET['enc_competition_created'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Competition draft created with the Competition type and Intra-College scope. Complete its title and event details, or change the scope, before publishing.</p></div>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_create_competition">' . wp_nonce_field( 'enc_create_competition', 'enc_create_competition_nonce', true, false ) . '<button class="page-title-action" type="submit">Add Competition</button></form><table class="widefat striped" style="margin-top:1rem"><thead><tr><th>Competition</th><th>Status</th><th>Date</th><th>Scope</th><th>Venue</th></tr></thead><tbody>';
	if ( ! $posts ) echo '<tr><td colspan="5">No competitions yet.</td></tr>';
	foreach ( $posts as $post ) {
		$date = get_post_meta( $post->ID, '_en_date', true );
		$scope = get_post_meta( $post->ID, '_en_scope', true );
		$scope_label = array( 'intra' => 'Intra-College', 'inter' => 'Inter-College' );
		$title = $post->post_title ? $post->post_title : '(Draft without title)';
		echo '<tr><td><strong><a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">' . esc_html( $title ) . '</a></strong></td><td>' . esc_html( ucfirst( $post->post_status ) ) . '</td><td>' . esc_html( $date ? wp_date( get_option( 'date_format' ), strtotime( $date ) ) : 'Not set' ) . '</td><td>' . esc_html( isset( $scope_label[ $scope ] ) ? $scope_label[ $scope ] : 'Not set' ) . '</td><td>' . esc_html( get_post_meta( $post->ID, '_en_venue', true ) ?: 'Not set' ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}
