<?php
/**
 * EventNest Contact Us page.
 *
 * Used automatically for a page with the slug "contact".
 */
get_header();

$team = array(
	array( 'name' => 'Aashriya Chauhan', 'email' => 'aac@sicsr.ac.in' ),
	array( 'name' => 'Daksh Arora', 'email' => 'daa@sicsr.ac.in' ),
	array( 'name' => 'Vedant Choudhary', 'email' => 'vec@sicsr.ac.in' ),
	array( 'name' => 'Nishit Gupta', 'email' => 'nig@sicsr.ac.in' ),
);
?>

<section class="en-contact-hero">
	<div class="wrap">
		<p class="eyebrow eyebrow--small"><?php esc_html_e( 'WE WOULD LOVE TO HEAR FROM YOU', 'eventnest' ); ?></p>
		<h1><?php esc_html_e( 'Contact Us', 'eventnest' ); ?></h1>
		<p class="en-contact-hero__lead"><?php esc_html_e( 'Have a question or feedback about EventNest? Get in touch with our team.', 'eventnest' ); ?></p>
	</div>
</section>

<section class="section en-contact-section">
	<div class="wrap">
		<div class="section__head">
			<div>
				<p class="eyebrow eyebrow--small"><?php esc_html_e( 'THE EVENTNEST TEAM', 'eventnest' ); ?></p>
				<h2><?php esc_html_e( 'Reach out to us', 'eventnest' ); ?></h2>
			</div>
		</div>
		<div class="en-contact-grid">
			<?php foreach ( $team as $member ) : ?>
				<article class="en-contact-card">
					<span class="en-contact-card__number" aria-hidden="true"><?php echo esc_html( strtoupper( substr( $member['name'], 0, 1 ) ) ); ?></span>
					<div>
						<h3><?php echo esc_html( $member['name'] ); ?></h3>
						<a class="en-contact-card__email" href="mailto:<?php echo esc_attr( $member['email'] ); ?>"><?php echo esc_html( $member['email'] ); ?></a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
