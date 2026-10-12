<?php
/**
 * EventNest Contact page for pages with the "contact" slug.
 */
get_header();
?>
<section class="page-head">
	<div class="wrap">
		<p class="eyebrow eyebrow--small"><?php esc_html_e( 'EVENTNEST SUPPORT', 'eventnest' ); ?></p>
		<h1><?php esc_html_e( 'Contact Us', 'eventnest' ); ?></h1>
		<p class="section__sub"><?php esc_html_e( 'Find the right place to get help with your campus account, events, or clubs.', 'eventnest' ); ?></p>
	</div>
</section>
<section class="section">
	<div class="wrap grid event-grid">
		<article class="event-panel">
			<h2><?php esc_html_e( 'Account help', 'eventnest' ); ?></h2>
			<p><?php esc_html_e( 'For account access, registration, or profile questions, contact your department or the site administrator.', 'eventnest' ); ?></p>
			<a class="link-more" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'Go to your dashboard', 'eventnest' ); ?> <span aria-hidden="true">→</span></a>
		</article>
		<article class="event-panel">
			<h2><?php esc_html_e( 'Event and club questions', 'eventnest' ); ?></h2>
			<p><?php esc_html_e( 'Open an event or club page for its current details and organizer information.', 'eventnest' ); ?></p>
			<a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Browse events', 'eventnest' ); ?> <span aria-hidden="true">→</span></a>
		</article>
	</div>
</section>
<?php get_footer(); ?>
