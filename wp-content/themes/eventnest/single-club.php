<?php
get_header();
while ( have_posts() ) :
	the_post();
	$club_id = get_the_ID();
	$faculty = en_club_person_name( '_en_club_faculty' );
	$head = en_club_person_name( '_en_club_head' );
	$summary = has_excerpt() ? get_the_excerpt() : wp_trim_words( wp_strip_all_tags( get_the_content() ), 28 );
	$member_count = 0;
	if ( function_exists( 'enc_club_membership_table' ) ) {
		global $wpdb;
		$member_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_club_membership_table() . " WHERE club_id = %d AND status = 'approved'", $club_id ) );
	}
	$events = new WP_Query( array(
		'post_type'      => 'event',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'meta_key'       => '_en_date',
		'orderby'        => 'meta_value',
		'order'          => 'ASC',
		'meta_query'     => array(
			'relation' => 'AND',
			array( 'key' => '_en_club', 'value' => $club_id, 'compare' => '=' ),
			array( 'key' => '_en_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
		),
	) );
	$upcoming_count = (int) $events->found_posts;
	$related = new WP_Query( array(
		'post_type'      => 'club',
		'post_status'    => 'publish',
		'posts_per_page' => 3,
		'post__not_in'   => array( $club_id ),
		'orderby'        => 'rand',
	) );
	$hero_style = has_post_thumbnail() ? ' style="--club-hero-image:url(' . esc_url( get_the_post_thumbnail_url( $club_id, 'en-hero' ) ) . ')"' : '';
?>
<section class="en-club-hero"<?php echo $hero_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wrap en-club-hero__inner">
		<div class="en-club-hero__copy">
			<p class="eyebrow eyebrow--small"><?php esc_html_e( 'EVENTNEST CLUB', 'eventnest' ); ?></p>
			<h1><?php the_title(); ?></h1>
			<p class="en-club-hero__summary"><?php echo esc_html( $summary ?: __( 'Explore this campus community, meet its members, and follow its upcoming activities.', 'eventnest' ) ); ?></p>
			<div class="en-club-hero__actions">
				<a class="btn btn--brand" href="#club-membership"><?php esc_html_e( 'Join or manage', 'eventnest' ); ?></a>
				<a class="btn btn--ghost" href="#club-events"><?php esc_html_e( 'View events', 'eventnest' ); ?></a>
			</div>
		</div>
		<div class="en-club-hero__stats" aria-label="<?php esc_attr_e( 'Club highlights', 'eventnest' ); ?>">
			<div><strong><?php echo esc_html( number_format_i18n( $member_count ) ); ?></strong><span><?php esc_html_e( 'Members', 'eventnest' ); ?></span></div>
			<div><strong><?php echo esc_html( number_format_i18n( $upcoming_count ) ); ?></strong><span><?php esc_html_e( 'Upcoming events', 'eventnest' ); ?></span></div>
			<div><strong><?php echo esc_html( $head ? $head : __( 'Assigned soon', 'eventnest' ) ); ?></strong><span><?php esc_html_e( 'Club head', 'eventnest' ); ?></span></div>
		</div>
	</div>
</section>

<section class="section section--tight">
	<div class="wrap en-club-profile">
		<main class="en-club-profile__main">
			<section class="en-club-block">
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'ABOUT THE CLUB', 'eventnest' ); ?></p>
				<h2><?php esc_html_e( 'What this community is about', 'eventnest' ); ?></h2>
				<div class="prose en-club-description">
					<?php if ( trim( get_the_content() ) ) : ?>
						<?php the_content(); ?>
					<?php else : ?>
						<p><?php esc_html_e( 'This club is ready for its introduction. Add details about activities, member expectations, meeting rhythm, and the kind of students who should apply.', 'eventnest' ); ?></p>
					<?php endif; ?>
				</div>
			</section>

			<section class="en-club-block">
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'CLUB LIFE', 'eventnest' ); ?></p>
				<h2><?php esc_html_e( 'What members can expect', 'eventnest' ); ?></h2>
				<div class="en-club-perks">
					<article><span aria-hidden="true">01</span><h3><?php esc_html_e( 'Practice and learn', 'eventnest' ); ?></h3><p><?php esc_html_e( 'Regular sessions, peer learning, and chances to build confidence through participation.', 'eventnest' ); ?></p></article>
					<article><span aria-hidden="true">02</span><h3><?php esc_html_e( 'Create campus moments', 'eventnest' ); ?></h3><p><?php esc_html_e( 'Plan events, auditions, workshops, showcases, or competitions with your club team.', 'eventnest' ); ?></p></article>
					<article><span aria-hidden="true">03</span><h3><?php esc_html_e( 'Grow your circle', 'eventnest' ); ?></h3><p><?php esc_html_e( 'Meet students with shared interests and collaborate across batches and departments.', 'eventnest' ); ?></p></article>
				</div>
			</section>
		</main>

		<aside class="en-club-profile__side">
			<div class="event-panel en-club-snapshot">
				<h2><?php esc_html_e( 'Club snapshot', 'eventnest' ); ?></h2>
				<dl>
					<div><dt><?php esc_html_e( 'Members', 'eventnest' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $member_count ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Upcoming events', 'eventnest' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $upcoming_count ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Faculty in-charge', 'eventnest' ); ?></dt><dd><?php echo esc_html( $faculty ?: __( 'To be assigned', 'eventnest' ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Club head', 'eventnest' ); ?></dt><dd><?php echo esc_html( $head ?: __( 'To be assigned', 'eventnest' ) ); ?></dd></div>
				</dl>
			</div>
			<div id="club-membership">
				<?php if ( function_exists( 'enc_club_membership_panel' ) ) echo enc_club_membership_panel( $club_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</aside>
	</div>
</section>

<section class="section section--tight" id="club-events">
	<div class="wrap">
		<div class="section__head"><div><p class="eyebrow eyebrow--small"><?php esc_html_e( "WHAT'S HAPPENING", 'eventnest' ); ?></p><h2><?php esc_html_e( 'Upcoming club events', 'eventnest' ); ?></h2><p class="section__sub"><?php esc_html_e( 'Activities, auditions, workshops, and competitions connected to this club.', 'eventnest' ); ?></p></div></div>
		<?php if ( $events->have_posts() ) : ?><div class="grid event-grid">
			<?php while ( $events->have_posts() ) : $events->the_post(); en_card( en_card_data( get_the_ID() ) ); endwhile; ?>
		</div><?php else : ?><div class="empty en-club-empty"><h3><?php esc_html_e( 'No upcoming club events yet', 'eventnest' ); ?></h3><p><?php esc_html_e( 'This club has no published upcoming events right now. Explore all events or propose a new campus activity.', 'eventnest' ); ?></p><div class="en-club-empty__actions"><a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Explore events', 'eventnest' ); ?></a><a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/submit-proposal/' ) ); ?>"><?php esc_html_e( 'Propose an event', 'eventnest' ); ?></a></div></div><?php endif; ?>
	</div>
</section>
<?php wp_reset_postdata(); ?>

<?php if ( $related->have_posts() ) : ?>
<section class="section section--tight">
	<div class="wrap">
		<div class="section__head"><div><p class="eyebrow eyebrow--small"><?php esc_html_e( 'KEEP EXPLORING', 'eventnest' ); ?></p><h2><?php esc_html_e( 'More clubs to discover', 'eventnest' ); ?></h2></div><a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'club' ) ); ?>"><?php esc_html_e( 'View all clubs', 'eventnest' ); ?><span aria-hidden="true">→</span></a></div>
		<div class="en-club-grid">
			<?php while ( $related->have_posts() ) : $related->the_post(); ?>
				<article <?php post_class( 'en-club-card' ); ?>>
					<a class="en-club-card__media" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'eventnest' ), get_the_title() ) ); ?>">
						<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'en-card', array( 'loading' => 'lazy' ) ); else : ?><span aria-hidden="true">✦</span><?php endif; ?>
					</a>
					<div class="en-club-card__body">
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<?php if ( has_excerpt() || get_the_content() ) : ?><p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p><?php endif; ?>
						<a class="link-more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'View club', 'eventnest' ); ?> <span aria-hidden="true">→</span></a>
					</div>
				</article>
			<?php endwhile; ?>
		</div>
	</div>
</section>
<?php endif; wp_reset_postdata(); ?>

<?php endwhile; get_footer(); ?>
