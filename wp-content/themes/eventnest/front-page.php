<?php
get_header();

$events_query = en_upcoming( 8 );
$events       = array();
if ( $events_query->have_posts() ) {
	while ( $events_query->have_posts() ) {
		$events_query->the_post();
		$events[] = en_card_data( get_the_ID() );
	}
	wp_reset_postdata();
}

$has_events = ! empty( $events );
if ( ! $has_events ) {
	$events = en_sample_events();
}

$closing_query = new WP_Query( array(
	'post_type'      => 'event',
	'post_status'    => 'publish',
	'posts_per_page' => 3,
	'meta_key'       => en_meta_key( 'registration_deadline' ),
	'orderby'        => 'meta_value',
	'order'          => 'ASC',
	'meta_query'     => array(
		'relation' => 'AND',
		array(
			'key'     => en_meta_key( 'registration_deadline' ),
			'value'   => array( current_time( 'Y-m-d' ), date( 'Y-m-d', current_time( 'timestamp' ) + 7 * DAY_IN_SECONDS ) ),
			'compare' => 'BETWEEN',
			'type'    => 'DATE',
		),
		array(
			'key'     => '_en_date',
			'value'   => current_time( 'Y-m-d' ),
			'compare' => '>=',
			'type'    => 'DATE',
		),
	),
) );
$closing = array();
if ( $closing_query->have_posts() ) {
	while ( $closing_query->have_posts() ) {
		$closing_query->the_post();
		$closing[] = en_card_data( get_the_ID() );
	}
	wp_reset_postdata();
}
if ( ! $has_events ) {
	$closing = array_slice( $events, 0, 3 );
}

$hero_image = get_theme_mod( 'en_hero_image' );
$archive    = get_post_type_archive_link( 'event' );
?>

<section class="hero" <?php if ( $hero_image ) : ?>style="--hero-image:url('<?php echo esc_url( $hero_image ); ?>')"<?php endif; ?>>
	<div class="wrap hero__inner">
		<p class="eyebrow"><span class="eyebrow__dot"></span><?php esc_html_e( 'Your campus. Your events. Your platform.', 'eventnest' ); ?></p>
		<h1 class="hero__title">
			<span><?php esc_html_e( 'Discover.', 'eventnest' ); ?></span>
			<span><?php esc_html_e( 'Participate.', 'eventnest' ); ?></span>
			<span><?php esc_html_e( 'Create.', 'eventnest' ); ?> <em><?php esc_html_e( 'Together.', 'eventnest' ); ?></em></span>
		</h1>
		<p class="hero__sub"><?php echo esc_html( en_opt( 'hero_sub' ) ); ?></p>
		<a class="btn btn--brand" href="<?php echo esc_url( $archive ); ?>">
			<?php esc_html_e( 'Explore events', 'eventnest' ); ?>
			<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
		</a>
		<div class="hero__meta"><span><?php esc_html_e( 'Made for your campus', 'eventnest' ); ?></span><span><?php esc_html_e( 'Discover what’s happening', 'eventnest' ); ?></span></div>
	</div>
	<div class="hero__glow hero__glow--one" aria-hidden="true"></div>
	<div class="hero__glow hero__glow--two" aria-hidden="true"></div>
</section>

<section class="section whats-new" aria-labelledby="whats-new-title">
	<div class="wrap">
		<div class="section__head">
			<div>
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'Happening around campus', 'eventnest' ); ?></p>
				<h2 id="whats-new-title"><?php esc_html_e( 'What’s New', 'eventnest' ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'The latest events, workshops and activities from across campus.', 'eventnest' ); ?></p>
			</div>
			<a class="link-more" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'View all', 'eventnest' ); ?><span aria-hidden="true">→</span></a>
		</div>
		<?php if ( ! $has_events && current_user_can( 'edit_posts' ) ) : ?>
			<p class="notice"><?php esc_html_e( 'These are preview cards. Add published events in WordPress and they will appear here automatically.', 'eventnest' ); ?></p>
		<?php endif; ?>
		<div class="grid event-grid">
			<?php foreach ( array_slice( $events, 0, 4 ) as $event ) : ?>
				<?php en_card( $event ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section closing-soon" aria-labelledby="closing-title">
	<div class="wrap">
		<div class="section__head">
			<div>
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'Save your spot', 'eventnest' ); ?></p>
				<h2 id="closing-title"><?php esc_html_e( 'Don’t Miss Out!', 'eventnest' ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'Registration is closing soon for these campus events.', 'eventnest' ); ?></p>
			</div>
			<a class="link-more" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'View all', 'eventnest' ); ?><span aria-hidden="true">→</span></a>
		</div>
		<div class="closing-grid">
			<?php foreach ( $closing as $event ) :
				$deadline = isset( $event['deadline'] ) ? $event['deadline'] : '';
				$days_left = $deadline ? (int) floor( ( strtotime( $deadline ) - current_time( 'timestamp' ) ) / DAY_IN_SECONDS ) : 0;
				$deadline_label = $days_left <= 0 ? __( 'Today', 'eventnest' ) : ( 1 === $days_left ? __( 'Tomorrow', 'eventnest' ) : sprintf( __( 'In %d days', 'eventnest' ), $days_left ) );
			?>
				<article class="closing-card">
					<div class="closing-card__icon" aria-hidden="true"><span>✦</span></div>
					<div class="closing-card__content">
						<?php if ( $event['cat'] ) : ?><span class="closing-card__category"><?php echo esc_html( $event['cat'] ); ?></span><?php endif; ?>
						<h3><a href="<?php echo esc_url( $event['url'] ); ?>"><?php echo esc_html( $event['title'] ); ?></a></h3>
						<p><?php esc_html_e( 'Registration closes', 'eventnest' ); ?> <strong><?php echo esc_html( $deadline_label ); ?></strong></p>
					</div>
					<a class="btn btn--brand btn--sm closing-card__action" href="<?php echo esc_url( $event['url'] ); ?>"><?php esc_html_e( 'Register', 'eventnest' ); ?><span aria-hidden="true">→</span></a>
				</article>
			<?php endforeach; ?>
			<?php if ( empty( $closing ) ) : ?>
				<div class="empty"><h3><?php esc_html_e( 'You’re all caught up', 'eventnest' ); ?></h3><p><?php esc_html_e( 'There are no registration deadlines coming up this week.', 'eventnest' ); ?></p></div>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
