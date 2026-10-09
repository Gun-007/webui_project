<?php
/**
 * EventNest About page.
 *
 * Used automatically for a page with the slug "about".
 */
get_header();
?>

<section class="en-about-hero">
	<div class="wrap en-about-hero__grid">
		<div>
			<p class="eyebrow eyebrow--small"><?php esc_html_e( 'BUILT FOR CAMPUS LIFE', 'eventnest' ); ?></p>
			<h1><?php esc_html_e( 'One place for every campus moment.', 'eventnest' ); ?></h1>
			<p class="en-about-hero__lead"><?php esc_html_e( 'EventNest helps students discover events, clubs plan activities, and reviewers move ideas from proposal to published campus experience.', 'eventnest' ); ?></p>
			<div class="en-about-hero__actions">
				<a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Explore events', 'eventnest' ); ?></a>
				<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/submit-proposal/' ) ); ?>"><?php esc_html_e( 'Propose an event', 'eventnest' ); ?></a>
			</div>
		</div>
		<div class="en-about-snapshot" aria-label="<?php esc_attr_e( 'EventNest platform highlights', 'eventnest' ); ?>">
			<div>
				<span><?php esc_html_e( 'Events', 'eventnest' ); ?></span>
				<strong><?php esc_html_e( 'Discover', 'eventnest' ); ?></strong>
			</div>
			<div>
				<span><?php esc_html_e( 'Clubs', 'eventnest' ); ?></span>
				<strong><?php esc_html_e( 'Join', 'eventnest' ); ?></strong>
			</div>
			<div>
				<span><?php esc_html_e( 'Ideas', 'eventnest' ); ?></span>
				<strong><?php esc_html_e( 'Propose', 'eventnest' ); ?></strong>
			</div>
			<div>
				<span><?php esc_html_e( 'Reviews', 'eventnest' ); ?></span>
				<strong><?php esc_html_e( 'Approve', 'eventnest' ); ?></strong>
			</div>
		</div>
	</div>
</section>

<section class="section en-about-section">
	<div class="wrap">
		<div class="section__head">
			<div>
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'WHY EVENTNEST', 'eventnest' ); ?></p>
				<h2><?php esc_html_e( 'Campus activity should be easy to find and easier to run.', 'eventnest' ); ?></h2>
				<p class="section__sub"><?php esc_html_e( 'The platform connects the public event experience with the behind-the-scenes approval and registration work teams need every day.', 'eventnest' ); ?></p>
			</div>
		</div>
		<div class="en-about-values">
			<article>
				<span aria-hidden="true">01</span>
				<h3><?php esc_html_e( 'Find what is happening', 'eventnest' ); ?></h3>
				<p><?php esc_html_e( 'Students can browse upcoming events, competitions, and club activities without chasing scattered notices.', 'eventnest' ); ?></p>
			</article>
			<article>
				<span aria-hidden="true">02</span>
				<h3><?php esc_html_e( 'Participate with confidence', 'eventnest' ); ?></h3>
				<p><?php esc_html_e( 'Registrations, deadlines, venues, capacities, and club memberships stay linked to real student accounts.', 'eventnest' ); ?></p>
			</article>
			<article>
				<span aria-hidden="true">03</span>
				<h3><?php esc_html_e( 'Move ideas forward', 'eventnest' ); ?></h3>
				<p><?php esc_html_e( 'Student proposals can be reviewed by faculty, club heads, and higher authorities with a visible decision history.', 'eventnest' ); ?></p>
			</article>
		</div>
	</div>
</section>

<section class="section section--tight en-about-band">
	<div class="wrap en-about-flow">
		<div>
			<p class="eyebrow eyebrow--small"><?php esc_html_e( 'HOW IT WORKS', 'eventnest' ); ?></p>
			<h2><?php esc_html_e( 'From campus idea to published event.', 'eventnest' ); ?></h2>
		</div>
		<ol>
			<li><strong><?php esc_html_e( 'Students submit proposals', 'eventnest' ); ?></strong><span><?php esc_html_e( 'Event ideas include category, event type, date, venue, and expected participation.', 'eventnest' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Reviewers make decisions', 'eventnest' ); ?></strong><span><?php esc_html_e( 'Faculty, club heads, and deputy reviewers approve, reject, or request changes with comments.', 'eventnest' ); ?></span></li>
			<li><strong><?php esc_html_e( 'Approved proposals become events', 'eventnest' ); ?></strong><span><?php esc_html_e( 'After all required reviewers approve, the event appears in Events and opens for registrations.', 'eventnest' ); ?></span></li>
		</ol>
	</div>
</section>

<section class="section en-about-section">
	<div class="wrap">
		<div class="section__head">
			<div>
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'WHO USES IT', 'eventnest' ); ?></p>
				<h2><?php esc_html_e( 'Designed around real campus roles.', 'eventnest' ); ?></h2>
			</div>
		</div>
		<div class="en-about-roles">
			<article><h3><?php esc_html_e( 'Students', 'eventnest' ); ?></h3><p><?php esc_html_e( 'Register for events, track participation, join clubs, and propose new activities.', 'eventnest' ); ?></p></article>
			<article><h3><?php esc_html_e( 'Faculty and club heads', 'eventnest' ); ?></h3><p><?php esc_html_e( 'Review proposals, manage club applications, and support activity planning.', 'eventnest' ); ?></p></article>
			<article><h3><?php esc_html_e( 'Administrators', 'eventnest' ); ?></h3><p><?php esc_html_e( 'Approve staff access, monitor registrations, manage event records, and publish final event pages.', 'eventnest' ); ?></p></article>
		</div>
	</div>
</section>

<section class="section section--tight en-about-cta">
	<div class="wrap en-about-cta__inner">
		<div>
			<p class="eyebrow eyebrow--small"><?php esc_html_e( 'READY WHEN YOU ARE', 'eventnest' ); ?></p>
			<h2><?php esc_html_e( 'Start with the next campus activity.', 'eventnest' ); ?></h2>
			<p><?php esc_html_e( 'Browse what is already live, or sign in to manage your registrations, proposals, and club activity.', 'eventnest' ); ?></p>
		</div>
		<div class="en-about-cta__actions">
			<a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'View events', 'eventnest' ); ?></a>
			<a class="btn btn--ghost" href="<?php echo esc_url( home_url( '/dashboard/' ) ); ?>"><?php esc_html_e( 'Open dashboard', 'eventnest' ); ?></a>
		</div>
	</div>
</section>

<?php
while ( have_posts() ) :
	the_post();
	if ( trim( get_the_content() ) ) :
		?>
		<section class="section section--tight">
			<div class="wrap wrap--narrow">
				<div class="prose"><?php the_content(); ?></div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
