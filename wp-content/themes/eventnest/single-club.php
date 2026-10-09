<?php
get_header();
while ( have_posts() ) : the_post();
	$club_id = get_the_ID();
	$faculty_id = absint( get_post_meta( $club_id, '_en_club_faculty', true ) );
	$head_id = absint( get_post_meta( $club_id, '_en_club_head', true ) );
	$faculty = $faculty_id ? get_userdata( $faculty_id ) : false;
	$head = $head_id ? get_userdata( $head_id ) : false;
	$description = get_the_excerpt() ?: 'A campus community where students meet, learn, and make memorable things happen together.';
	$hero_image = get_the_post_thumbnail_url( $club_id, 'en-hero' ) ?: get_template_directory_uri() . '/assets/images/eventnest-club-cover.svg';
	$events = new WP_Query( array(
		'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => 6,
		'meta_key' => '_en_date', 'orderby' => 'meta_value', 'order' => 'ASC',
		'meta_query' => array( 'relation' => 'AND', array( 'key' => '_en_club', 'value' => $club_id, 'compare' => '=' ), array( 'key' => '_en_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ) ),
	) );
	global $wpdb;
	$member_count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . enc_table( 'club_memberships' ) . " WHERE club_id = %d AND status = 'approved'", $club_id ) );
	$event_count = (int) $events->found_posts;
	$other_clubs = new WP_Query( array( 'post_type' => 'club', 'post_status' => 'publish', 'posts_per_page' => 3, 'post__not_in' => array( $club_id ), 'orderby' => 'rand' ) );
?>
<section class="en-club-hero" style="--club-hero-image:url('<?php echo esc_url( $hero_image ); ?>')">
	<div class="wrap en-club-hero__inner">
		<div><p class="eyebrow eyebrow--small">EVENTNEST CLUB · CAMPUS COMMUNITY</p><h1><?php the_title(); ?></h1><p class="en-club-hero__summary"><?php echo esc_html( $description ); ?></p>
			<div class="en-club-hero__actions"><a class="btn btn--brand" href="#club-membership">Join this club <span aria-hidden="true">→</span></a><a class="btn btn--ghost" href="#club-events">View events</a><?php if ( function_exists( 'en_frontend_post_actions' ) ) en_frontend_post_actions( $club_id ); ?></div>
		</div>
		<div class="en-club-hero__stats" aria-label="Club at a glance"><div><strong><?php echo esc_html( number_format_i18n( $member_count ) ); ?></strong><span>Members</span></div><div><strong><?php echo esc_html( number_format_i18n( $event_count ) ); ?></strong><span>Upcoming events</span></div><div><strong><?php echo esc_html( $head ? $head->display_name : 'Open role' ); ?></strong><span>Club head</span></div></div>
	</div>
</section>
<section class="section section--tight"><div class="wrap en-club-profile">
	<div class="en-club-profile__main">
		<article class="en-club-block"><p class="eyebrow eyebrow--small">ABOUT THE CLUB</p><h2>What this community is about</h2><div class="prose"><?php if ( get_the_content() ) : the_content(); else : ?><p><?php echo esc_html( $description ); ?></p><?php endif; ?></div></article>
		<section class="en-club-block"><p class="eyebrow eyebrow--small">CLUB LIFE</p><h2>What members can expect</h2><div class="en-club-perks"><article><span>01</span><h3>Practice and learn</h3><p>Build skills through regular sessions, peer learning, and hands-on activities.</p></article><article><span>02</span><h3>Create campus moments</h3><p>Plan events, workshops, showcases, or competitions with your club team.</p></article><article><span>03</span><h3>Grow your circle</h3><p>Meet students with shared interests and collaborate across campus.</p></article></div></section>
	</div>
	<aside class="en-club-profile__side">
		<section class="en-club-block en-club-snapshot"><h2>Club snapshot</h2><dl><div><dt>Members</dt><dd><?php echo esc_html( number_format_i18n( $member_count ) ); ?></dd></div><div><dt>Upcoming events</dt><dd><?php echo esc_html( number_format_i18n( $event_count ) ); ?></dd></div><div><dt>Faculty in-charge</dt><dd><?php echo esc_html( $faculty ? $faculty->display_name : 'To be assigned' ); ?></dd></div><div><dt>Club head</dt><dd><?php echo esc_html( $head ? $head->display_name : 'To be assigned' ); ?></dd></div></dl></section>
		<section class="en-club-block en-club-leadership"><p class="eyebrow eyebrow--small">YOUR CLUB TEAM</p><h2>Club leadership</h2>
			<?php foreach ( array( array( 'Faculty in-charge', $faculty ), array( 'Club head', $head ) ) as $person ) : ?><div class="en-club-leader"><?php if ( $person[1] ) : ?><?php echo get_avatar( $person[1]->ID, 64, '', '', array( 'class' => 'en-club-leader__avatar' ) ); ?><?php else : ?><span class="en-club-leader__avatar en-club-leader__initial" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $person[0], 0, 1 ) ) ); ?></span><?php endif; ?><div><span><?php echo esc_html( $person[0] ); ?></span><strong><?php echo esc_html( $person[1] ? $person[1]->display_name : 'To be assigned' ); ?></strong><?php if ( $person[1] ) : ?><a href="mailto:<?php echo esc_attr( $person[1]->user_email ); ?>">Contact</a><?php endif; ?></div></div><?php endforeach; ?>
		</section>
		<div id="club-membership"><?php if ( function_exists( 'enc_club_membership_panel' ) ) echo enc_club_membership_panel( $club_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	</aside>
</div></section>
<section class="section section--tight" id="club-events"><div class="wrap"><div class="section__head"><div><p class="eyebrow eyebrow--small">WHAT'S HAPPENING</p><h2>Upcoming club events</h2><p class="section__sub">Activities, auditions, workshops, and competitions connected to this club.</p></div></div>
	<?php if ( $events->have_posts() ) : ?><div class="grid event-grid"><?php while ( $events->have_posts() ) : $events->the_post(); en_card( en_card_data( get_the_ID() ) ); endwhile; ?></div><?php else : ?><div class="empty en-club-empty"><h3>Your next club moment is waiting</h3><p>There are no upcoming activities listed yet. Check back soon for new sessions and events.</p></div><?php endif; ?>
</div></section>
<?php if ( $other_clubs->have_posts() ) : ?><section class="section section--tight"><div class="wrap"><div class="section__head"><div><p class="eyebrow eyebrow--small">KEEP EXPLORING</p><h2>More clubs to discover</h2></div><a class="link-more" href="<?php echo esc_url( get_post_type_archive_link( 'club' ) ); ?>">View all clubs <span aria-hidden="true">→</span></a></div><div class="en-club-grid"><?php while ( $other_clubs->have_posts() ) : $other_clubs->the_post(); ?><article class="en-club-card"><a class="en-club-card__media" href="<?php the_permalink(); ?>"><?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'en-card', array( 'loading' => 'lazy' ) ); else : ?><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/eventnest-club-cover.svg' ); ?>" alt="" loading="lazy"><?php endif; ?></a><div class="en-club-card__body"><h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3><p><?php echo esc_html( get_the_excerpt() ?: 'Find your people, explore new interests, and make campus memories.' ); ?></p><a class="link-more" href="<?php the_permalink(); ?>">View club <span aria-hidden="true">→</span></a></div></article><?php endwhile; ?></div></div></section><?php endif; ?>
<?php wp_reset_postdata(); endwhile; get_footer(); ?>
