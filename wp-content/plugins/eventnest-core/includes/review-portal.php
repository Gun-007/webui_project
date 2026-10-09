<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'init', function () {
	add_shortcode( 'eventnest_review_proposals', 'enc_review_proposals_shortcode' );
} );

add_action( 'admin_post_enc_review_proposal', 'enc_handle_proposal_review' );
add_action( 'admin_post_nopriv_enc_review_proposal', 'enc_handle_proposal_review' );
function enc_handle_proposal_review() {
	if ( ! is_user_logged_in() ) {
		wp_safe_redirect( add_query_arg( 'redirect_to', home_url( '/review-proposals/' ), home_url( '/login/' ) ) );
		exit;
	}
	$proposal_id = isset( $_POST['proposal_id'] ) ? absint( $_POST['proposal_id'] ) : 0;
	$nonce = isset( $_POST['enc_review_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['enc_review_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'enc_review_proposal_' . $proposal_id ) ) {
		wp_safe_redirect( add_query_arg( 'review_result', 'error', home_url( '/review-proposals/' ) ) );
		exit;
	}
	$decision = isset( $_POST['decision'] ) ? sanitize_key( wp_unslash( $_POST['decision'] ) ) : '';
	$comment = isset( $_POST['review_comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_comment'] ) ) : '';
	$result = enc_proposal_decide( $proposal_id, get_current_user_id(), $decision, $comment );
	if ( is_wp_error( $result ) ) {
		wp_safe_redirect( add_query_arg( 'review_result', 'error', home_url( '/review-proposals/' ) ) );
		exit;
	}
	wp_safe_redirect( add_query_arg( 'review_result', sanitize_key( $result ), home_url( '/review-proposals/' ) ) );
	exit;
}

function enc_review_proposals_shortcode() {
	if ( ! is_user_logged_in() ) return '<section class="en-auth-card"><h1>Log in to review proposals</h1><a class="btn btn--brand" href="' . esc_url( home_url( '/login/' ) ) . '">Log in</a></section>';
	$user_id = get_current_user_id();
	if ( ! current_user_can( 'en_review_faculty' ) && ! current_user_can( 'en_review_deputy' ) && ! current_user_can( 'en_review_override' ) ) return '<section class="en-auth-card"><h1>Reviewer access required</h1><p>Your account cannot review proposals.</p></section>';
	$items = get_posts( array( 'post_type' => 'proposal', 'post_status' => 'publish', 'posts_per_page' => 200, 'orderby' => 'date', 'order' => 'ASC' ) );
	$out = '<section class="section"><div class="wrap"><div class="page-head"><p class="eyebrow eyebrow--small">EVENTNEST WORKFLOW</p><h1>Review Proposals</h1><p class="section__sub">Review each submission at the approval stage assigned to you.</p>';
	$result = isset( $_GET['review_result'] ) ? sanitize_key( wp_unslash( $_GET['review_result'] ) ) : '';
	if ( $result === 'under_review' ) $out .= '<p class="en-auth__notice en-auth__notice--success" role="status">Approval recorded. The proposal was sent to the next reviewer; the event appears after final approval.</p>';
	if ( $result === 'published' ) $out .= '<p class="en-auth__notice en-auth__notice--success" role="status">Final approval recorded. The event is now published in Events.</p>';
	if ( in_array( $result, array( 'rejected', 'needs_changes' ), true ) ) $out .= '<p class="en-auth__notice en-auth__notice--success" role="status">Your decision has been recorded.</p>';
	if ( $result === 'error' ) $out .= '<p class="en-auth__notice en-auth__notice--error" role="status">The decision could not be saved. Refresh and check whether the proposal is still awaiting your review.</p>';
	$out .= '</div>';
	$shown = 0;
	foreach ( $items as $item ) {
		$id = (int) $item->ID;
		if ( ! enc_user_can_review( $user_id, $id ) ) continue;
		$shown++;
		$stage = enc_proposal_current_stage( $id );
		$type = get_term( (int) get_post_meta( $id, '_en_p_type', true ), 'event_type' );
		$category = get_term( (int) get_post_meta( $id, '_en_p_category', true ), 'event_category' );
		$club_id = (int) get_post_meta( $id, '_en_p_club', true );
		$author = get_userdata( (int) $item->post_author );
		$out .= '<article class="en-review-card"><div class="en-review-card__head"><div><p class="en-student-card__status">' . esc_html( ucwords( str_replace( '_', ' ', $stage ) . ' review' ) ) . '</p><h2>' . esc_html( $item->post_title ) . '</h2><p class="muted">Submitted by ' . esc_html( $author ? $author->display_name : 'Student' ) . ' · ' . esc_html( get_the_date( 'j M Y', $item ) ) . '</p></div></div>';
		$out .= '<div class="en-review-card__facts"><span><b>Type</b>' . esc_html( ! is_wp_error( $type ) && $type ? $type->name : '—' ) . '</span><span><b>Category</b>' . esc_html( ! is_wp_error( $category ) && $category ? $category->name : '—' ) . '</span><span><b>Proposed date</b>' . esc_html( get_post_meta( $id, '_en_p_date', true ) ) . '</span><span><b>Venue</b>' . esc_html( get_post_meta( $id, '_en_p_venue', true ) ) . '</span><span><b>Expected participants</b>' . esc_html( get_post_meta( $id, '_en_p_participants', true ) ?: 'Not specified' ) . '</span><span><b>Club</b>' . esc_html( $club_id ? get_the_title( $club_id ) : 'No club selected' ) . '</span></div>';
		if ( has_post_thumbnail( $id ) ) $out .= '<figure class="en-review-card__image">' . get_the_post_thumbnail( $id, 'large', array( 'loading' => 'lazy' ) ) . '</figure>';
		$out .= '<div class="event-panel en-review-card__description">' . wpautop( esc_html( $item->post_content ) ) . '</div>';
		$out .= '<form class="en-review-form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="enc_review_proposal"><input type="hidden" name="proposal_id" value="' . esc_attr( $id ) . '">' . wp_nonce_field( 'enc_review_proposal_' . $id, 'enc_review_nonce', true, false );
		$out .= '<label class="en-auth__field"><span>Reviewer comment (required for changes or rejection)</span><textarea name="review_comment" rows="3"></textarea></label><div class="en-review-form__actions"><button class="btn btn--brand" name="decision" value="approve" type="submit">Approve</button><button class="btn btn--ghost" name="decision" value="changes" type="submit">Request changes</button><button class="btn btn--danger" name="decision" value="reject" type="submit">Reject</button></div></form></article>';
	}
	if ( ! $shown ) $out .= '<div class="empty"><h2>No proposals waiting for you</h2><p>New submissions will appear here when they reach one of your approval stages.</p></div>';
	return $out . '</div></section>';
}
