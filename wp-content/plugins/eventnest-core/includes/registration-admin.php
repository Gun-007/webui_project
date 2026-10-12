<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	add_shortcode( 'eventnest_all_registrations', 'enc_all_registrations_shortcode' );
} );

function enc_registration_scope( $event_id = 0 ) {
	$user = wp_get_current_user();
	$roles = (array) $user->roles;
	if ( current_user_can( 'manage_options' ) || in_array( 'en_deputy_director', $roles, true ) || in_array( 'en_director', $roles, true ) ) return true;
	if ( ! current_user_can( 'en_view_registrations' ) ) return false;
	$department = enc_user_department_id( $user->ID );
	$event_department = $event_id ? enc_department_for_object( $event_id ) : 0;
	if ( in_array( 'en_faculty_head', $roles, true ) && ( ! $department || ( $event_id && $event_department !== $department ) ) ) return false;
	if ( in_array( 'en_faculty', $roles, true ) && $department && $event_id && $event_department !== $department ) return false;
	if ( in_array( 'en_club_head', $roles, true ) && $event_id ) {
		$club_id = absint( get_post_meta( $event_id, '_en_club', true ) );
		if ( ! $club_id || absint( get_post_meta( $club_id, '_en_club_head', true ) ) !== (int) $user->ID ) return false;
	}
	if ( $event_id && ! $department && ! in_array( 'en_club_head', $roles, true ) && in_array( 'en_faculty', $roles, true ) ) {
		$event = get_post( $event_id );
		if ( ! $event || (int) $event->post_author !== (int) $user->ID ) return false;
	}
	if ( ! $department && in_array( 'en_faculty_head', $roles, true ) ) return false;
	return true;
}

/** Role-scoped overview; faculty see their department (or own events if unassigned), club heads see their club, senior reviewers see all. */
function enc_all_registrations_shortcode() {
	if ( ! enc_registration_scope() ) {
		return '<section class="en-auth-card"><h2>Reviewer access required</h2><p>This page contains private student registration details.</p></section>';
	}
	global $wpdb;
	$event_id = isset( $_GET['registration_event'] ) ? absint( $_GET['registration_event'] ) : 0;
	$status = isset( $_GET['registration_status'] ) ? sanitize_key( wp_unslash( $_GET['registration_status'] ) ) : '';
	$allowed_statuses = array( 'registered', 'cancelled', 'pending', 'completed' );
	if ( ! in_array( $status, $allowed_statuses, true ) ) $status = '';
	$search = isset( $_GET['registration_search'] ) ? sanitize_text_field( wp_unslash( $_GET['registration_search'] ) ) : '';
	$page = isset( $_GET['rpage'] ) ? max( 1, absint( $_GET['rpage'] ) ) : 1;
	$per_page = 25;
	$offset = ( $page - 1 ) * $per_page;
	$registrations = enc_table( 'registrations' );
	$teams = enc_table( 'teams' );
	$where = array( 'p.post_type = %s', 'p.post_status NOT IN (%s, %s)' );
	$params = array( 'event', 'trash', 'auto-draft' );
	if ( $event_id ) { $where[] = 'r.event_id = %d'; $params[] = $event_id; }
	$user = wp_get_current_user();
	$roles = (array) $user->roles;
	$global = current_user_can( 'manage_options' ) || in_array( 'en_deputy_director', $roles, true ) || in_array( 'en_director', $roles, true );
	if ( ! $global ) {
		$scopes = array();
		$scope_params = array();
		$department = enc_user_department_id( $user->ID );
		if ( $department && ( in_array( 'en_faculty_head', $roles, true ) || in_array( 'en_faculty', $roles, true ) ) ) {
			$scopes[] = "COALESCE(NULLIF(ed.meta_value, ''), NULLIF(cd.meta_value, '')) = %s";
			$scope_params[] = (string) $department;
		}
		if ( in_array( 'en_faculty', $roles, true ) && ! $department ) { $scopes[] = 'p.post_author = %d'; $scope_params[] = (int) $user->ID; }
		if ( in_array( 'en_club_head', $roles, true ) ) {
			$club_ids = get_posts( array( 'post_type' => 'club', 'post_status' => array( 'publish', 'draft', 'private' ), 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_en_club_head', 'meta_value' => $user->ID ) );
			if ( $club_ids ) $scopes[] = 'CAST(ec.meta_value AS UNSIGNED) IN (' . implode( ',', array_map( 'absint', $club_ids ) ) . ')';
		}
		if ( ! $scopes ) return '<section class="en-auth-card"><h2>No department assigned</h2><p>Ask an administrator to assign your department or event scope before viewing registration details.</p></section>';
		$where[] = '(' . implode( ' OR ', $scopes ) . ')';
		$params = array_merge( $params, $scope_params );
	}
	if ( $status ) { $where[] = 'r.status = %s'; $params[] = $status; }
	if ( $search !== '' ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s OR um.meta_value LIKE %s)';
		array_push( $params, $like, $like, $like );
	}
	$where_sql = implode( ' AND ', $where );
	$from_sql = ' FROM ' . $registrations . ' r INNER JOIN ' . $wpdb->posts . ' p ON p.ID = r.event_id INNER JOIN ' . $wpdb->users . ' u ON u.ID = r.user_id LEFT JOIN ' . $wpdb->usermeta . " um ON um.user_id = u.ID AND um.meta_key = '_en_prn' LEFT JOIN " . $teams . ' t ON t.id = r.team_id LEFT JOIN ' . $wpdb->postmeta . " ed ON ed.post_id = p.ID AND ed.meta_key = '_en_department' LEFT JOIN " . $wpdb->postmeta . " ec ON ec.post_id = p.ID AND ec.meta_key = '_en_club' LEFT JOIN " . $wpdb->postmeta . " cd ON cd.post_id = CAST(ec.meta_value AS UNSIGNED) AND cd.meta_key = '_en_department' " . ' WHERE ' . $where_sql;
	$count_sql = $wpdb->prepare( 'SELECT COUNT(*)' . $from_sql, $params );
	$total = (int) $wpdb->get_var( $count_sql );
	$rows_sql = 'SELECT r.status, r.created_at, r.team_id, p.ID AS event_id, p.post_title AS event_title, u.display_name, u.user_email, um.meta_value AS prn, t.name AS team_name' . $from_sql . ' ORDER BY r.created_at DESC LIMIT %d OFFSET %d';
	$row_params = $params;
	$row_params[] = $per_page;
	$row_params[] = $offset;
	$rows = $wpdb->get_results( $wpdb->prepare( $rows_sql, $row_params ) );
	$events = get_posts( array( 'post_type' => 'event', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'posts_per_page' => 500, 'orderby' => 'title', 'order' => 'ASC' ) );
	$events = array_values( array_filter( $events, function ( $event ) { return enc_registration_scope( $event->ID ); } ) );
	$out = '<section class="en-registration-admin"><p class="eyebrow eyebrow--small">EVENTNEST ADMIN</p><h2>Event registrations</h2><p class="section__sub">Search participants and review registration status across events.</p>';
	$out .= '<form class="filters en-registration-filters" method="get" action="' . esc_url( get_permalink() ) . '">';
	$out .= '<input class="filters__q" type="search" name="registration_search" value="' . esc_attr( $search ) . '" placeholder="Search name, email or PRN" aria-label="Search name, email or PRN">';
	$out .= '<select name="registration_event" aria-label="Filter by event"><option value="0">All events</option>';
	foreach ( $events as $event ) $out .= '<option value="' . esc_attr( $event->ID ) . '"' . selected( $event_id, $event->ID, false ) . '>' . esc_html( $event->post_title ) . '</option>';
	$out .= '</select><select name="registration_status" aria-label="Filter by registration status"><option value="">All statuses</option>';
	foreach ( $allowed_statuses as $value ) $out .= '<option value="' . esc_attr( $value ) . '"' . selected( $status, $value, false ) . '>' . esc_html( ucwords( str_replace( '_', ' ', $value ) ) ) . '</option>';
	$out .= '</select><button class="btn btn--brand btn--sm" type="submit">Filter</button></form>';
	$out .= '<p class="results-count">' . esc_html( sprintf( _n( '%s registration', '%s registrations', $total, 'eventnest-core' ), number_format_i18n( $total ) ) ) . '</p>';
	if ( $rows ) {
		$out .= '<div class="en-registration-table-wrap"><table class="en-registration-table"><thead><tr><th>Participant</th><th>PRN</th><th>Email</th><th>Event</th><th>Team</th><th>Status</th><th>Registered</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			$out .= '<tr><td>' . esc_html( $row->display_name ) . '</td><td>' . esc_html( $row->prn ?: '—' ) . '</td><td><a href="mailto:' . esc_attr( $row->user_email ) . '">' . esc_html( $row->user_email ) . '</a></td><td><a href="' . esc_url( get_permalink( $row->event_id ) ) . '">' . esc_html( $row->event_title ) . '</a></td><td>' . esc_html( $row->team_name ?: 'Individual' ) . '</td><td><span class="en-registration-status en-registration-status--' . esc_attr( sanitize_html_class( $row->status ) ) . '">' . esc_html( ucwords( str_replace( '_', ' ', $row->status ) ) ) . '</span></td><td>' . esc_html( get_date_from_gmt( $row->created_at, 'j M Y, g:i a' ) ) . '</td></tr>';
		}
		$out .= '</tbody></table></div>';
		$pages = (int) ceil( $total / $per_page );
		if ( $pages > 1 ) {
			$base = remove_query_arg( 'rpage' );
			$out .= '<nav class="en-competition-pagination" aria-label="Registration pages">';
			if ( $page > 1 ) $out .= '<a class="btn btn--ghost" href="' . esc_url( add_query_arg( 'rpage', $page - 1, $base ) ) . '">Previous</a>';
			if ( $page < $pages ) $out .= '<a class="btn btn--ghost" href="' . esc_url( add_query_arg( 'rpage', $page + 1, $base ) ) . '">Next</a>';
			$out .= '</nav>';
		}
	} else {
		$out .= '<div class="empty"><h3>No registrations found</h3><p>Try changing the filters, or check again after students register for an event.</p></div>';
	}
	return $out . '</section>';
}
