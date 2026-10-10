<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	add_shortcode( 'eventnest_submit_story', 'enc_submit_story_shortcode' );
	add_shortcode( 'eventnest_my_stories', 'enc_my_stories_shortcode' );
} );

add_action( 'init', function () {
	$pages = array(
		'share-story' => array( 'Share Your Story', '[eventnest_submit_story]' ),
		'my-stories'  => array( 'My Stories', '[eventnest_my_stories]' ),
	);
	foreach ( $pages as $slug => $page ) {
		if ( get_page_by_path( $slug, OBJECT, 'page' ) ) continue;
		wp_insert_post( array(
			'post_type' => 'page', 'post_status' => 'publish', 'post_name' => $slug,
			'post_title' => $page[0], 'post_content' => $page[1],
		) );
	}
}, 40 );

add_action( 'admin_post_enc_submit_story', 'enc_handle_story_submission' );
add_action( 'admin_post_nopriv_enc_submit_story', 'enc_handle_story_submission' );

function enc_story_is_student( $user_id = 0 ) {
	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	return $user_id && user_can( $user_id, 'en_submit_story' );
}

add_action( 'admin_post_enc_review_story', function () {
	if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Administrator access required.', '', array( 'response' => 403 ) );
	$id = isset( $_POST['story_id'] ) ? absint( $_POST['story_id'] ) : 0;
	$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
	$nonce = isset( $_POST['enc_story_review_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_story_review_nonce'] ) ) : '';
	if ( ! $id || get_post_type( $id ) !== 'student_story' || ! wp_verify_nonce( $nonce, 'enc_review_story_' . $id ) || ! in_array( $decision, array( 'approve', 'reject' ), true ) ) wp_die( 'Invalid story review.', '', array( 'response' => 400 ) );
	update_post_meta( $id, '_en_story_review_status', 'approve' === $decision ? 'approved' : 'rejected' );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'approve' === $decision ? 'publish' : 'draft' ) );
	wp_safe_redirect( home_url( '/dashboard/#en-story-approvals' ) ); exit;
} );

function enc_dashboard_story_review_queue() {
	if ( ! current_user_can( 'manage_options' ) ) return '';
	$stories = get_posts( array( 'post_type' => 'student_story', 'post_status' => 'pending', 'posts_per_page' => 100, 'orderby' => 'date', 'order' => 'ASC' ) );
	$out = '<section class="en-dashboard-review en-story-approvals" id="en-story-approvals"><p class="eyebrow eyebrow--small">ADMIN REVIEW</p><h2>Student stories to review <span class="en-approval-count">' . esc_html( number_format_i18n( count( $stories ) ) ) . '</span></h2><p class="muted">Approve stories to publish them, or reject them to return them to the student.</p>';
	if ( ! $stories ) return $out . '<p class="en-dashboard-note">No stories are waiting for review.</p></section>';
	foreach ( $stories as $story ) {
		$author = get_userdata( (int) $story->post_author );
		$out .= '<article class="en-approval-card en-story-approval-card"><div><p class="eyebrow eyebrow--small">STUDENT STORY · ' . esc_html( get_the_date( 'j M Y', $story ) ) . '</p><h3>' . esc_html( $story->post_title ) . '</h3><p class="muted">By ' . esc_html( $author ? $author->display_name : 'Student' ) . '</p><p>' . esc_html( wp_trim_words( $story->post_content, 35 ) ) . '</p></div><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_review_story"><input type="hidden" name="story_id" value="' . (int) $story->ID . '">' . wp_nonce_field( 'enc_review_story_' . $story->ID, 'enc_story_review_nonce', true, false ) . '<button class="btn btn--brand" name="decision" value="approve">Approve story</button><button class="btn btn--ghost" name="decision" value="reject">Reject</button></form></article>';
	}
	return $out . '</section>';
}

add_filter( 'pre_trash_post', function ( $trash, $post ) {
	if ( $post && 'student_story' === $post->post_type && current_user_can( 'manage_options' ) ) {
		update_post_meta( $post->ID, '_en_story_review_status', 'rejected' );
		wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ) );
		return false;
	}
	return $trash;
}, 10, 2 );

function enc_story_return( $page, $result ) {
	$url = home_url( '/' . trim( $page, '/' ) . '/' );
	wp_safe_redirect( add_query_arg( 'story_result', $result, $url ) );
	exit;
}

function enc_story_notice() {
	$code = isset( $_GET['story_result'] ) ? sanitize_key( wp_unslash( $_GET['story_result'] ) ) : '';
	$messages = array(
		'submitted' => array( 'Your story was submitted and is waiting for review.', 'success' ),
		'error'     => array( 'We could not submit your story. Check the required fields and use a JPG, PNG, GIF, or WebP photo smaller than 5 MB.', 'error' ),
	);
	if ( ! isset( $messages[ $code ] ) ) return '';
	return '<p class="en-auth__notice en-auth__notice--' . esc_attr( $messages[ $code ][1] ) . '" role="status">' . esc_html( $messages[ $code ][0] ) . '</p>';
}

function enc_handle_story_submission() {
	if ( ! is_user_logged_in() || ! enc_story_is_student() ) {
		wp_die( esc_html__( 'Please sign in with a student account to share a story.', 'eventnest-core' ), '', array( 'response' => 403 ) );
	}
	$nonce = isset( $_POST['enc_story_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_story_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_submit_story' ) ) enc_story_return( 'share-story', 'error' );

	$title = isset( $_POST['story_title'] ) ? sanitize_text_field( wp_unslash( $_POST['story_title'] ) ) : '';
	$content = isset( $_POST['story_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['story_content'] ) ) : '';
	$event = isset( $_POST['story_event'] ) ? sanitize_text_field( wp_unslash( $_POST['story_event'] ) ) : '';
	$course = isset( $_POST['story_course'] ) ? sanitize_text_field( wp_unslash( $_POST['story_course'] ) ) : '';
	$year = isset( $_POST['story_year'] ) ? sanitize_text_field( wp_unslash( $_POST['story_year'] ) ) : '';
	$rating = isset( $_POST['story_rating'] ) ? absint( $_POST['story_rating'] ) : 0;
	$favourite = isset( $_POST['story_favourite'] ) ? sanitize_textarea_field( wp_unslash( $_POST['story_favourite'] ) ) : '';
	$learned = isset( $_POST['story_learned'] ) ? sanitize_textarea_field( wp_unslash( $_POST['story_learned'] ) ) : '';
	if ( ! $title || ! $content || ! $event || ! $course || ! $year || $rating < 1 || $rating > 5 ) enc_story_return( 'share-story', 'error' );
	$image_id = enc_receive_student_image( 'story_image' );
	if ( is_wp_error( $image_id ) ) enc_story_return( 'share-story', 'error' );

	$user_id = get_current_user_id();
	$story_id = wp_insert_post( array(
		'post_type' => 'student_story', 'post_status' => 'pending', 'post_title' => $title,
		'post_content' => $content, 'post_author' => $user_id,
	), true );
	if ( is_wp_error( $story_id ) ) {
		if ( $image_id ) wp_delete_attachment( $image_id, true );
		enc_story_return( 'share-story', 'error' );
	}
	if ( $image_id ) enc_attach_student_image( $image_id, $story_id );
	foreach ( array(
		'_en_story_event' => $event,
		'_en_story_course' => $course,
		'_en_story_year' => $year,
		'_en_story_rating' => $rating,
		'_en_story_favourite' => $favourite,
		'_en_story_learned' => $learned,
	) as $key => $value ) update_post_meta( $story_id, $key, $value );
	enc_story_return( 'my-stories', 'submitted' );
}

function enc_story_fields_meta_box( $post ) {
	wp_nonce_field( 'enc_story_fields_save', 'enc_story_fields_nonce' );
	$fields = array(
		'_en_story_event' => array( 'Event or experience', 'text' ),
		'_en_story_course' => array( 'Course', 'text' ),
		'_en_story_year' => array( 'Year of study', 'text' ),
		'_en_story_rating' => array( 'Rating (1–5)', 'number' ),
		'_en_story_favourite' => array( 'Favourite moment', 'textarea' ),
		'_en_story_learned' => array( 'What I learned', 'textarea' ),
	);
	echo '<table class="form-table"><tbody>';
	foreach ( $fields as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<tr><th><label for="' . esc_attr( $key ) . '">' . esc_html( $field[0] ) . '</label></th><td>';
		if ( $field[1] === 'textarea' ) echo '<textarea class="large-text" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
		else echo '<input class="regular-text" type="' . esc_attr( $field[1] ) . '" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '"' . ( $field[1] === 'number' ? ' min="1" max="5"' : '' ) . '>';
		echo '</td></tr>';
	}
	echo '</tbody></table>';
}
add_action( 'add_meta_boxes_student_story', function () {
	add_meta_box( 'enc_story_fields', 'Story details', 'enc_story_fields_meta_box', 'student_story', 'normal', 'high' );
} );

add_action( 'save_post_student_story', function ( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) return;
	if ( ! isset( $_POST['enc_story_fields_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enc_story_fields_nonce'] ) ), 'enc_story_fields_save' ) ) return;
	if ( ! current_user_can( 'edit_post', $post_id ) ) return;
	$fields = array( '_en_story_event', '_en_story_course', '_en_story_year' );
	foreach ( $fields as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, $value );
	}
	foreach ( array( '_en_story_favourite', '_en_story_learned' ) as $key ) {
		$value = isset( $_POST[ $key ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) ) : '';
		update_post_meta( $post_id, $key, $value );
	}
	$rating = isset( $_POST['_en_story_rating'] ) ? min( 5, max( 1, absint( $_POST['_en_story_rating'] ) ) ) : 5;
	update_post_meta( $post_id, '_en_story_rating', $rating );
} );

function enc_story_rating( $story_id, $class = '' ) {
	$rating = min( 5, max( 1, absint( get_post_meta( $story_id, '_en_story_rating', true ) ?: 5 ) ) );
	return '<span class="en-story-rating ' . esc_attr( $class ) . '" aria-label="' . esc_attr( sprintf( __( '%1$d out of 5 stars', 'eventnest-core' ), $rating ) ) . '"><span aria-hidden="true">' . str_repeat( '★', $rating ) . '</span> <small>' . esc_html( $rating . ' / 5' ) . '</small></span>';
}

function enc_submit_story_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to share your story</h1><p class="muted">Student stories are submitted by signed-in students and reviewed before publication.</p><a class="btn btn--brand" href="' . esc_url( add_query_arg( 'redirect_to', home_url( '/share-story/' ), home_url( '/login/' ) ) ) . '">Log in</a></section>';
	if ( ! enc_story_is_student() ) return '<section class="en-auth-card"><h1>Student access required</h1><p class="muted">Only student accounts can submit stories.</p></section>';
	$user_id = get_current_user_id();
	$course = get_user_meta( $user_id, '_en_course', true );
	$out = '<section class="en-auth-card en-story-form-card"><p class="eyebrow eyebrow--small">EVENTNEST STORIES</p><h1>Share your experience</h1><p class="muted">Tell other students about a campus event or moment. Stories are reviewed before they appear publicly.</p>' . enc_story_notice();
	$out .= '<form class="en-auth en-story-form" method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_submit_story">' . wp_nonce_field( 'enc_submit_story', 'enc_story_nonce', true, false );
	$out .= '<label class="en-auth__field"><span>Story title *</span><input required maxlength="180" name="story_title"></label>';
	$out .= '<label class="en-auth__field"><span>Your experience *</span><textarea required name="story_content" rows="9" maxlength="12000" placeholder="What happened? What made the experience meaningful?"></textarea></label>';
	$out .= '<div class="en-auth__row"><label class="en-auth__field"><span>Event or experience *</span><input required maxlength="160" name="story_event"></label><label class="en-auth__field"><span>Rating *</span><select required name="story_rating"><option value="">Choose a rating</option><option value="5">5 — Loved it</option><option value="4">4 — Great</option><option value="3">3 — Good</option><option value="2">2 — Fair</option><option value="1">1 — Needs improvement</option></select></label></div>';
	$out .= '<div class="en-auth__row"><label class="en-auth__field"><span>Course *</span><input required maxlength="100" name="story_course" value="' . esc_attr( $course ) . '"></label><label class="en-auth__field"><span>Year of study *</span><input required maxlength="60" name="story_year" placeholder="e.g. 4th Year"></label></div>';
	$out .= '<label class="en-auth__field"><span>Story photo <small>(optional, JPG, PNG, GIF, or WebP; max 5 MB)</small></span><input type="file" name="story_image" accept="image/jpeg,image/png,image/gif,image/webp"></label>';
	$out .= '<label class="en-auth__field"><span>Favourite moment <small>(optional)</small></span><textarea name="story_favourite" rows="3" maxlength="2000"></textarea></label><label class="en-auth__field"><span>What I learned <small>(optional)</small></span><textarea name="story_learned" rows="3" maxlength="2000"></textarea></label>';
	$out .= '<p class="muted en-story-privacy">Your name, course, year, rating, and story will be visible to visitors if the story is approved.</p><button class="btn btn--brand" type="submit">Submit story for review</button></form></section>';
	return $out;
}

function enc_my_stories_shortcode() {
	if ( ! is_user_logged_in() ) return '<div class="empty"><h2>Log in to view your stories</h2><a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></div>';
	if ( ! enc_story_is_student() ) return '<div class="empty"><h2>My Stories is for student accounts</h2><p>Sign in with a student account to view your submissions.</p></div>';
	$items = get_posts( array( 'post_type' => 'student_story', 'post_status' => array( 'publish', 'pending', 'draft' ), 'author' => get_current_user_id(), 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );
	$out = '<section class="section"><div class="wrap"><div class="page-head"><p class="eyebrow eyebrow--small">YOUR EVENTNEST</p><h1>My Stories</h1><p class="section__sub">Track the stories you have shared with campus.</p><p><a class="btn btn--brand" href="' . esc_url( home_url( '/share-story/' ) ) . '">Share a story</a></p>' . enc_story_notice() . '</div>';
	if ( ! $items ) return $out . '<div class="empty"><h2>No stories submitted yet</h2><p>Share a campus experience other students can learn from.</p><a class="btn btn--brand" href="' . esc_url( home_url( '/share-story/' ) ) . '">Share your story</a></div></div></section>';
	$out .= '<div class="en-my-stories">';
	foreach ( $items as $item ) {
		$status = get_post_status( $item );
		$review_status = get_post_meta( $item->ID, '_en_story_review_status', true );
		$label = 'rejected' === $review_status ? 'Rejected' : ( $status === 'publish' ? 'Published' : ( $status === 'pending' ? 'Waiting for review' : ucfirst( $status ) ) );
		$out .= '<article class="en-my-story"><span class="en-my-story__status en-my-story__status--' . esc_attr( sanitize_html_class( $status ) ) . '">' . esc_html( $label ) . '</span><h2>' . esc_html( get_the_title( $item ) ) . '</h2><p class="muted">Submitted ' . esc_html( get_the_date( 'j M Y', $item ) ) . '</p>';
		if ( 'rejected' === $review_status ) $out .= '<p class="muted">Your story was rejected by an administrator. Your account is unchanged.</p>';
		if ( $status === 'publish' ) $out .= '<a class="link-more" href="' . esc_url( get_permalink( $item ) ) . '">View story →</a>';
		$out .= '</article>';
	}
	return $out . '</div></div></section>';
}
