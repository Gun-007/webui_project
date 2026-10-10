<?php
get_header();
while ( have_posts() ) : the_post();
	$club_id = get_the_ID();
	$faculty = en_club_person_name( '_en_club_faculty' );
	$head = en_club_person_name( '_en_club_head' );
	$events = new WP_Query( array(
		'post_type' => 'event', 'post_status' => 'publish', 'posts_per_page' => 6,
		'meta_key' => '_en_date', 'orderby' => 'meta_value', 'order' => 'ASC',
		'meta_query' => array(
			'relation' => 'AND',
			array( 'key' => '_en_club', 'value' => $club_id, 'compare' => '=' ),
			array( 'key' => '_en_date', 'value' => current_time( 'Y-m-d' ), 'compare' => '>=', 'type' => 'DATE' ),
		),
	) );
?>
<section class="page-head en-club-head">
	<div class="wrap">
		<p class="eyebrow eyebrow--small">EVENTNEST CLUB</p>
		<h1><?php the_title(); ?></h1>
		<p class="section__sub">Explore this campus community and its upcoming activities.</p>
	</div>
</section>
<section class="section section--tight">
	<div class="wrap">
		<?php if ( has_post_thumbnail() ) : ?><div class="en-club-featured"><?php the_post_thumbnail( 'en-hero', array( 'fetchpriority' => 'high' ) ); ?></div><?php endif; ?>
		<div class="en-club-detail-grid">
			<div class="prose en-club-description"><?php the_content(); ?></div>
			<?php if ( $faculty || $head ) : ?>
				<aside class="event-panel en-club-people"><h2>Club leadership</h2><dl>
					<?php if ( $faculty ) : ?><div><dt>Faculty in-charge</dt><dd><?php echo esc_html( $faculty ); ?></dd></div><?php endif; ?>
					<?php if ( $head ) : ?><div><dt>Club head</dt><dd><?php echo esc_html( $head ); ?></dd></div><?php endif; ?>
				</dl></aside>
			<?php endif; ?>
		</div>
	</div>
</section>
<?php if ( function_exists( 'enc_club_membership_panel' ) ) : ?>
<section class="section section--tight"><div class="wrap"><?php echo enc_club_membership_panel( $club_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div></section>
<?php endif; ?>
<section class="section section--tight">
	<div class="wrap">
		<div class="section__head"><div><p class="eyebrow eyebrow--small">WHAT'S HAPPENING</p><h2>Upcoming club events</h2></div></div>
		<?php if ( $events->have_posts() ) : ?><div class="grid event-grid">
			<?php while ( $events->have_posts() ) : $events->the_post(); en_card( en_card_data( get_the_ID() ) ); endwhile; ?>
		</div><?php else : ?><div class="empty"><h3>No upcoming activities yet</h3><p>Check back soon for events from this club.</p></div><?php endif; ?>
	</div>
</section>
<?php wp_reset_postdata(); endwhile; get_footer(); ?>
