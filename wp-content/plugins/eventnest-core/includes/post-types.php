<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', 'enc_register_post_types' );

/** Add the blueprint's starter clubs once, without replacing clubs already entered by the team. */
function enc_seed_default_clubs() {
	if ( get_option( 'enc_default_clubs_seeded' ) || ! post_type_exists( 'club' ) ) return;
	$clubs = array(
		'tech-club' => array( 'Tech Club', 'A campus community for students interested in technology, workshops, and collaborative projects.' ),
		'cultural-club' => array( 'Cultural Club', 'A campus community for students interested in culture, creative expression, and college celebrations.' ),
		'dance-club' => array( 'Dance Club', 'A campus community for students interested in dance, choreography, practice, and performances.' ),
		'music-club' => array( 'Music Club', 'A campus community for students interested in music, collaboration, and live performance.' ),
		'sports-club' => array( 'Sports Club', 'A campus community for students interested in sports, fitness, and friendly competition.' ),
		'photography-club' => array( 'Photography Club', 'A campus community for students interested in photography, visual storytelling, and photo walks.' ),
		'coding-club' => array( 'Coding Club', 'A campus community for students interested in programming, problem-solving, and building software.' ),
		'management-club' => array( 'Management Club', 'A campus community for students interested in leadership, entrepreneurship, and business activities.' ),
	);
	$complete = true;
	foreach ( $clubs as $slug => $club ) {
		$existing = get_posts( array( 'post_type' => 'club', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $existing ) continue;
		$result = wp_insert_post( array(
			'post_type' => 'club', 'post_status' => 'publish', 'post_name' => $slug,
			'post_title' => $club[0], 'post_content' => $club[1],
		), true );
		if ( is_wp_error( $result ) ) $complete = false;
	}
	if ( $complete ) update_option( 'enc_default_clubs_seeded', 1, false );
}

/** Add one editable, future-dated sample event to each blueprint club once. */
function enc_seed_default_club_events() {
	if ( get_option( 'enc_default_club_events_seeded' ) || ! post_type_exists( 'event' ) || ! post_type_exists( 'club' ) || ! taxonomy_exists( 'event_type' ) || ! taxonomy_exists( 'event_category' ) ) return;
	$events = array(
		'ai-web-workshop' => array( 'club' => 'tech-club', 'title' => 'AI & Web Development Workshop', 'description' => 'A sample hands-on campus session exploring artificial intelligence and modern web development. Replace the schedule, venue, and description with confirmed event information.', 'days' => 10, 'start' => '10:00', 'end' => '13:00', 'venue' => 'Computer Lab', 'type' => 'Workshop', 'category' => 'Technical', 'scope' => '' ),
		'campus-culture-night' => array( 'club' => 'cultural-club', 'title' => 'Campus Culture Night', 'description' => 'A sample evening celebrating student creativity and campus culture. Replace the schedule, venue, and description with confirmed event information.', 'days' => 14, 'start' => '17:00', 'end' => '20:00', 'venue' => 'Main Auditorium', 'type' => 'Fest', 'category' => 'Cultural', 'scope' => '' ),
		'dance-crew-auditions' => array( 'club' => 'dance-club', 'title' => 'Dance Crew Open Auditions', 'description' => 'A sample open audition for students interested in joining the campus dance community. Replace the schedule, venue, and details with confirmed information.', 'days' => 18, 'start' => '15:00', 'end' => '17:00', 'venue' => 'Dance Studio', 'type' => 'Audition', 'category' => 'Cultural', 'scope' => '' ),
		'acoustic-open-mic' => array( 'club' => 'music-club', 'title' => 'Acoustic Open Mic', 'description' => 'A sample open-mic session for campus singers and instrumentalists. Replace the schedule, venue, and details with confirmed information.', 'days' => 22, 'start' => '16:00', 'end' => '19:00', 'venue' => 'Seminar Hall', 'type' => 'Club Event', 'category' => 'Cultural', 'scope' => '' ),
		'inter-club-badminton' => array( 'club' => 'sports-club', 'title' => 'Inter-Club Badminton Doubles', 'description' => 'A sample intra-college doubles competition for campus clubs. Replace the schedule, venue, and rules with confirmed event information.', 'days' => 26, 'start' => '09:00', 'end' => '15:00', 'venue' => 'Indoor Sports Hall', 'type' => 'Competition', 'category' => 'Sports', 'scope' => 'intra' ),
		'campus-photo-walk' => array( 'club' => 'photography-club', 'title' => 'Campus Photo Walk', 'description' => 'A sample guided photo walk for students interested in campus photography and visual storytelling. Replace the schedule and details with confirmed information.', 'days' => 30, 'start' => '08:00', 'end' => '10:00', 'venue' => 'Main Gate', 'type' => 'Club Event', 'category' => 'Cultural', 'scope' => '' ),
		'campus-coding-challenge' => array( 'club' => 'coding-club', 'title' => 'Campus Coding Challenge', 'description' => 'A sample programming challenge for student teams. Replace the schedule, venue, and rules with confirmed event information.', 'days' => 34, 'start' => '09:00', 'end' => '16:00', 'venue' => 'Innovation Lab', 'type' => 'Competition', 'category' => 'Technical', 'scope' => 'intra' ),
		'startup-pitch-challenge' => array( 'club' => 'management-club', 'title' => 'Startup Pitch & Case Challenge', 'description' => 'A sample student challenge focused on business ideas and case analysis. Replace the schedule, venue, and rules with confirmed event information.', 'days' => 38, 'start' => '10:00', 'end' => '15:00', 'venue' => 'Seminar Hall', 'type' => 'Competition', 'category' => 'Management', 'scope' => 'intra' ),
	);
	$admin_ids = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ids' ) );
	if ( ! $admin_ids ) return;
	$now = current_time( 'timestamp', true );
	$complete = true;
	foreach ( $events as $slug => $event ) {
		$existing = get_posts( array( 'post_type' => 'event', 'name' => $slug, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( $existing ) continue;
		$clubs = get_posts( array( 'post_type' => 'club', 'name' => $event['club'], 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
		if ( ! $clubs ) { $complete = false; continue; }
		$date = wp_date( 'Y-m-d', $now + (int) $event['days'] * DAY_IN_SECONDS );
		$deadline = wp_date( 'Y-m-d', $now + max( 1, (int) $event['days'] - 2 ) * DAY_IN_SECONDS );
		$post_id = wp_insert_post( array(
			'post_type' => 'event', 'post_status' => 'publish', 'post_name' => $slug,
			'post_title' => $event['title'], 'post_content' => $event['description'], 'post_author' => (int) $admin_ids[0],
			'meta_input' => array(
				'_en_date' => $date, '_en_start' => $event['start'], '_en_end' => $event['end'],
				'_en_venue' => $event['venue'], '_en_deadline' => $deadline, '_en_max' => '100',
				'_en_fee' => '0', '_en_scope' => $event['scope'], '_en_club' => (int) $clubs[0],
			),
		), true );
		if ( is_wp_error( $post_id ) ) { $complete = false; continue; }
		foreach ( array( 'event_type' => $event['type'], 'event_category' => $event['category'] ) as $taxonomy => $name ) {
			$term = term_exists( $name, $taxonomy );
			if ( ! $term ) $term = wp_insert_term( $name, $taxonomy );
			if ( ! is_wp_error( $term ) ) wp_set_object_terms( $post_id, (int) ( is_array( $term ) ? $term['term_id'] : $term ), $taxonomy );
		}
	}
	if ( $complete ) update_option( 'enc_default_club_events_seeded', 1, false );
}

function enc_register_post_types() {
	$common = array( 'show_in_rest' => true, 'map_meta_cap' => true );

	register_post_type( 'event', array_merge( $common, array(
		'labels'          => array( 'name' => 'Events', 'singular_name' => 'Event', 'add_new_item' => 'Add new event', 'edit_item' => 'Edit event' ),
		'public'          => true,
		'has_archive'     => 'events',
		'rewrite'         => array( 'slug' => 'events' ),
		'menu_icon'       => 'dashicons-calendar-alt',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
		'capability_type' => array( 'en_event', 'en_events' ),
		'capabilities'    => array( 'create_posts' => 'create_en_events' ),
	) ) );

	register_post_type( 'club', array_merge( $common, array(
		'labels'          => array( 'name' => 'Clubs', 'singular_name' => 'Club', 'add_new_item' => 'Add new club' ),
		'public'          => true,
		'has_archive'     => 'clubs',
		'rewrite'         => array( 'slug' => 'clubs' ),
		'menu_icon'       => 'dashicons-groups',
		'supports'        => array( 'title', 'editor', 'thumbnail' ),
		'capability_type' => array( 'en_club', 'en_clubs' ),
		'capabilities'    => array( 'create_posts' => 'create_en_clubs' ),
	) ) );

	// Proposals are private data: no front-end URLs, not exposed over REST. Students submit through our own code.
	register_post_type( 'proposal', array(
		'labels'          => array( 'name' => 'Proposals', 'singular_name' => 'Proposal' ),
		'public'          => false,
		'show_ui'         => true,
		'show_in_rest'    => false,
		'menu_icon'       => 'dashicons-lightbulb',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
		'map_meta_cap'    => true,
		'capability_type' => array( 'en_proposal', 'en_proposals' ),
		'capabilities'    => array( 'create_posts' => 'create_en_proposals' ),
	) );

	register_post_type( 'announcement', array_merge( $common, array(
		'labels'          => array( 'name' => 'Announcements', 'singular_name' => 'Announcement', 'add_new_item' => 'Add announcement' ),
		'public'          => true,
		'has_archive'     => 'announcements',
		'rewrite'         => array( 'slug' => 'announcements' ),
		'menu_icon'       => 'dashicons-megaphone',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'author' ),
		'capability_type' => array( 'en_announcement', 'en_announcements' ),
		'capabilities'    => array( 'create_posts' => 'create_en_announcements' ),
	) ) );

	register_post_type( 'student_story', array_merge( $common, array(
		'labels'          => array( 'name' => 'Student Stories', 'singular_name' => 'Student Story', 'add_new_item' => 'Add student story', 'edit_item' => 'Edit student story', 'view_item' => 'View student story', 'all_items' => 'All student stories' ),
		'public'          => true,
		'has_archive'     => 'student-stories',
		'rewrite'         => array( 'slug' => 'student-stories' ),
		'menu_icon'       => 'dashicons-format-quote',
		'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt', 'author' ),
		'capability_type' => 'post',
		'map_meta_cap'    => true,
	) ) );

	register_post_type( 'faculty_department', array(
		'labels' => array( 'name' => 'Departments', 'singular_name' => 'Department', 'add_new_item' => 'Add department', 'edit_item' => 'Edit department', 'menu_name' => 'Departments' ),
		'public' => false, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_rest' => false,
		'show_in_menu' => 'users.php', 'supports' => array( 'title' ), 'map_meta_cap' => true,
		'capability_type' => array( 'en_department', 'en_departments' ),
		'capabilities' => array( 'create_posts' => 'create_en_departments' ),
	) );

	register_taxonomy( 'event_type', 'event', array(
		'labels' => array( 'name' => 'Event types', 'singular_name' => 'Event type' ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
		'rewrite' => array( 'slug' => 'event-type' ),
		'capabilities' => array( 'manage_terms' => 'en_manage_platform', 'edit_terms' => 'en_manage_platform', 'delete_terms' => 'en_manage_platform', 'assign_terms' => 'edit_en_events' ),
	) );
	register_taxonomy( 'event_category', 'event', array(
		'labels' => array( 'name' => 'Categories', 'singular_name' => 'Category' ),
		'hierarchical' => true, 'show_in_rest' => true, 'show_admin_column' => true,
		'rewrite' => array( 'slug' => 'event-category' ),
		'capabilities' => array( 'manage_terms' => 'en_manage_platform', 'edit_terms' => 'en_manage_platform', 'delete_terms' => 'en_manage_platform', 'assign_terms' => 'edit_en_events' ),
	) );
}

function enc_seed_terms() {
	$types = array( 'Competition', 'Club Event', 'Workshop', 'Trial', 'Audition', 'Fest', 'Seminar', 'Webinar', 'Other' );
	$cats  = array( 'Cultural', 'Technical', 'Sports', 'Management', 'Academic', 'Other' );
	foreach ( $types as $t ) if ( ! term_exists( $t, 'event_type' ) ) wp_insert_term( $t, 'event_type' );
	foreach ( $cats as $c )  if ( ! term_exists( $c, 'event_category' ) ) wp_insert_term( $c, 'event_category' );
}

/** Event meta fields: key => label, type. Used for the editor box and for saving. */
function enc_event_fields() {
	return array(
		'_en_date'      => array( 'Event date', 'date' ),
		'_en_start'     => array( 'Start time', 'time' ),
		'_en_end'       => array( 'End time', 'time' ),
		'_en_venue'     => array( 'Venue', 'text' ),
		'_en_deadline'  => array( 'Registration deadline', 'date' ),
		'_en_max'       => array( 'Max participants (0 = unlimited)', 'number' ),
		'_en_fee'       => array( 'Registration fee in ₹ (0 = free)', 'number' ),
		'_en_scope'     => array( 'Competition scope', 'select', array( '' => 'Not a competition', 'intra' => 'Intra-College', 'inter' => 'Inter-College' ) ),
		'_en_club'      => array( 'Organising club', 'club' ),
		'_en_incharge'  => array( 'Faculty incharge', 'text' ),
		'_en_team_min'  => array( 'Team size min (0 = individual only)', 'number' ),
		'_en_team_max'  => array( 'Team size max', 'number' ),
		'_en_cancelled' => array( 'Event cancelled', 'checkbox' ),
	);
}

function enc_sanitize_field( $type, $value ) {
	switch ( $type ) {
		case 'date':     return preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $value ) ? $value : '';
		case 'time':     return preg_match( '/^\d{2}:\d{2}$/', (string) $value ) ? $value : '';
		case 'number':   return (string) max( 0, absint( $value ) );
		case 'club':     return (string) absint( $value );
		case 'checkbox': return $value ? '1' : '';
		default:         return sanitize_text_field( (string) $value );
	}
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'enc_event', 'Event details', 'enc_event_box', 'event', 'normal', 'high' );
} );

function enc_event_box( $post ) {
	wp_nonce_field( 'enc_event_save', 'enc_event_nonce' );
	echo '<table class="form-table"><tbody>';
	foreach ( enc_event_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( $f[1] === 'select' ) {
			echo '<select name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '">';
			foreach ( $f[2] as $v => $l ) echo '<option value="' . esc_attr( $v ) . '"' . selected( $val, $v, false ) . '>' . esc_html( $l ) . '</option>';
			echo '</select>';
		} elseif ( $f[1] === 'club' ) {
			wp_dropdown_pages( array( 'post_type' => 'club', 'name' => $key, 'id' => $key, 'selected' => (int) $val, 'show_option_none' => 'None', 'option_none_value' => '0' ) );
		} elseif ( $f[1] === 'checkbox' ) {
			echo '<input type="checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="1"' . checked( $val, '1', false ) . '>';
		} else {
			echo '<input type="' . esc_attr( $f[1] ) . '" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '"' . ( $f[1] === 'number' ? ' min="0"' : '' ) . ' class="regular-text">';
		}
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}

add_action( 'save_post_event', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['enc_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enc_event_nonce'] ) ), 'enc_event_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	foreach ( enc_event_fields() as $key => $f ) {
		$raw = isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '';
		if ( $f[1] === 'select' && ! isset( $f[2][ $raw ] ) ) $raw = '';
		if ( $key === '_en_club' && ! current_user_can( 'manage_options' ) && function_exists( 'enc_user_is_faculty_scope_role' ) && enc_user_is_faculty_scope_role( get_current_user_id() ) ) {
			$user_department = enc_user_department_id( get_current_user_id() );
			$target_club = absint( $raw );
			$target_department = $target_club ? absint( get_post_meta( $target_club, '_en_department', true ) ) : 0;
			if ( ! $user_department || ( $target_department && $target_department !== $user_department ) ) $raw = get_post_meta( $post_id, '_en_club', true );
		}
		update_post_meta( $post_id, $key, enc_sanitize_field( $f[1], $raw ) );
	}
} );

/** Club meta: faculty incharge and club head are real user accounts. */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'enc_club', 'Club people', 'enc_club_box', 'club', 'side' );
} );

function enc_club_box( $post ) {
	wp_nonce_field( 'enc_club_save', 'enc_club_nonce' );
	echo '<p><label>Faculty incharge</label><br>';
	$club_department = absint( get_post_meta( $post->ID, '_en_department', true ) );
	$staff = get_users( array( 'role__in' => array( 'en_faculty', 'en_faculty_head' ), 'number' => 500, 'orderby' => 'display_name', 'order' => 'ASC' ) );
	echo '<select name="_en_club_faculty" class="widefat"><option value="0">None</option>';
	foreach ( $staff as $faculty ) {
		if ( $club_department && enc_user_department_id( $faculty->ID ) !== $club_department ) continue;
		echo '<option value="' . esc_attr( $faculty->ID ) . '"' . selected( (int) get_post_meta( $post->ID, '_en_club_faculty', true ), (int) $faculty->ID, false ) . '>' . esc_html( $faculty->display_name ) . '</option>';
	}
	echo '</select>';
	$head = get_userdata( (int) get_post_meta( $post->ID, '_en_club_head', true ) );
	echo '</p><p><strong>Club Head</strong><br>' . esc_html( $head ? $head->display_name : 'Vacant' ) . '</p><p><a href="' . esc_url( admin_url( 'edit.php?post_type=club&page=enc-club-leadership' ) ) . '">Change Club Head</a></p>';
}

add_action( 'save_post_club', function ( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( ! isset( $_POST['enc_club_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enc_club_nonce'] ) ), 'enc_club_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	if ( isset( $_POST['_en_club_faculty'] ) ) {
		$faculty_id = absint( $_POST['_en_club_faculty'] );
		$faculty = $faculty_id ? get_userdata( $faculty_id ) : false;
		$department_id = absint( get_post_meta( $post_id, '_en_department', true ) );
		if ( ! $faculty || ( ! in_array( 'en_faculty', (array) $faculty->roles, true ) && ! in_array( 'en_faculty_head', (array) $faculty->roles, true ) ) || ( $department_id && enc_user_department_id( $faculty_id ) !== $department_id ) ) $faculty_id = 0;
		update_post_meta( $post_id, '_en_club_faculty', $faculty_id );
	}
} );

/** Derived event state: cancelled / completed / ongoing / registration_closed / registration_open. */
function enc_event_state( $event_id ) {
	if ( get_post_meta( $event_id, '_en_cancelled', true ) ) return 'cancelled';
	$today = wp_date( 'Y-m-d' );
	$date  = (string) get_post_meta( $event_id, '_en_date', true );
	$dead  = (string) get_post_meta( $event_id, '_en_deadline', true );
	if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts ) || ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) return 'unscheduled';
	if ( $date && $date < $today ) return 'completed';
	if ( $date && $date === $today ) return 'ongoing';
	if ( $dead && $dead < $today ) return 'registration_closed';
	return 'registration_open';
}
