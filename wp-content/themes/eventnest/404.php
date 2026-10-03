<?php get_header(); ?>
<section class="section"><div class="wrap wrap--narrow"><div class="empty">
	<h1><?php esc_html_e( 'This page has left the venue', 'eventnest' ); ?></h1>
	<p><?php esc_html_e( 'The link may be old or the event may have been removed. Browse what is on instead.', 'eventnest' ); ?></p>
	<a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Explore events', 'eventnest' ); ?></a>
</div></div></section>
<?php get_footer(); ?>
