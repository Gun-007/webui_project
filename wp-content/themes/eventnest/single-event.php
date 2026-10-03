<?php
get_header();
while ( have_posts() ) : the_post();
	$id    = get_the_ID();
	$d     = en_card_data( $id );
	$addr  = en_meta( $id, 'address' );
	$tix   = en_meta( $id, 'ticket_url' );
	$org   = en_meta( $id, 'organizer' );
	$rules = en_meta( $id, 'rules' );
	$highlights = array_filter( array_map( 'trim', preg_split( '/\R/', (string) en_meta( $id, 'highlights' ) ) ) );
	$deadline = $d['deadline'];
	$max_participants = en_meta( $id, 'max_participants' );
	$cal   = en_calendar_url( $id );
	$url   = get_permalink();
	$share = rawurlencode( get_the_title() . ' ' . $url );
	$past  = $d['date'] && $d['date'] < current_time( 'Y-m-d' );
	$registration_closed = $deadline && $deadline < current_time( 'Y-m-d' );
	$cancelled = get_post_meta( $id, '_en_cancelled', true );
	$registered_count = function_exists( 'enc_registration_count' ) ? enc_registration_count( $id ) : 0;
	$is_full = $max_participants && $registered_count >= (int) $max_participants;
	$map   = trim( $d['venue'] . ' ' . $addr . ' ' . $d['city'] );
?>
<article class="event">
	<div class="wrap">
			<div class="event__hero" style="--h:<?php echo (int) $d['hue']; ?>">
				<?php if ( has_post_thumbnail() ) : ?><?php the_post_thumbnail( 'en-hero', array( 'fetchpriority' => 'high' ) ); ?><?php else : ?><span class="event__hero-mark" aria-hidden="true">EVENTNEST</span><?php endif; ?>
			</div>

		<div class="event__grid">
			<div class="event__main">
				<?php if ( $d['cat'] ) : ?><span class="event-category"><?php echo esc_html( $d['cat'] ); ?></span><?php endif; ?>
				<h1 class="event__title"><?php the_title(); ?></h1>
				<?php if ( $org ) : ?><p class="event__by"><?php printf( esc_html__( 'Organized by %s', 'eventnest' ), '<b>' . esc_html( $org ) . '</b>' ); ?></p><?php endif; ?>

				<ul class="facts">
					<?php if ( $d['date'] ) : ?><li><span><?php esc_html_e( 'Date & time', 'eventnest' ); ?></span><?php echo esc_html( en_fmt_date( $d['date'], 'l, j F Y' ) . ( $d['time'] ? ' · ' . en_fmt_time( $d['time'] ) : '' ) . ( $d['end_time'] ? ' – ' . en_fmt_time( $d['end_time'] ) : '' ) ); ?></li><?php endif; ?>
					<?php if ( $d['venue'] || $addr ) : ?><li><span><?php esc_html_e( 'Where', 'eventnest' ); ?></span><?php echo esc_html( trim( $d['venue'] . ( $addr ? ', ' . $addr : '' ) ) ); ?></li><?php endif; ?>
					<?php if ( $deadline ) : ?><li><span><?php esc_html_e( 'Registration deadline', 'eventnest' ); ?></span><?php echo esc_html( en_fmt_date( $deadline, 'j F Y' ) ); ?></li><?php endif; ?>
					<?php if ( $max_participants ) : ?><li><span><?php esc_html_e( 'Capacity', 'eventnest' ); ?></span><?php if ( $is_full ) : ?><?php esc_html_e( 'Registration is full', 'eventnest' ); ?><?php else : ?><?php printf( esc_html__( '%1$s of %2$s places remaining', 'eventnest' ), esc_html( number_format_i18n( (int) $max_participants - $registered_count ) ), esc_html( number_format_i18n( (int) $max_participants ) ) ); ?><?php endif; ?></li><?php endif; ?>
				</ul>

				<section class="event-section" id="about">
					<h2><?php esc_html_e( 'About this event', 'eventnest' ); ?></h2>
					<div class="prose"><?php the_content(); ?></div>
				</section>
				<?php if ( $rules ) : ?>
					<section class="event-section" id="rules">
						<h2><?php esc_html_e( 'Rules & guidelines', 'eventnest' ); ?></h2>
						<div class="event-panel"><?php echo wpautop( esc_html( $rules ) ); ?></div>
					</section>
				<?php endif; ?>
				<?php if ( $highlights ) : ?>
					<section class="event-section" id="highlights">
						<h2><?php esc_html_e( 'Highlights', 'eventnest' ); ?></h2>
						<ul class="highlight-list">
							<?php foreach ( $highlights as $highlight ) : ?><li><?php echo esc_html( $highlight ); ?></li><?php endforeach; ?>
						</ul>
					</section>
				<?php endif; ?>
				<?php if ( $org ) : ?>
					<section class="event-section" id="organizer">
						<h2><?php esc_html_e( 'Organizer', 'eventnest' ); ?></h2>
						<div class="event-panel event-organizer"><span class="event-organizer__avatar" aria-hidden="true">✦</span><div><strong><?php echo esc_html( $org ); ?></strong><p><?php esc_html_e( 'Campus event organizer', 'eventnest' ); ?></p></div></div>
					</section>
				<?php endif; ?>

				<?php if ( $map ) : ?>
					<p><a class="link-more" target="_blank" rel="noopener" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $map ) ); ?>"><?php esc_html_e( 'Open in Google Maps', 'eventnest' ); ?></a></p>
				<?php endif; ?>
			</div>

			<aside class="event__aside">
				<div class="ticket-box">
					<p class="ticket-box__price"><?php echo esc_html( $d['price'] ); ?></p>
					<?php if ( $cancelled ) : ?>
						<p class="ticket-box__note ticket-box__note--alert"><?php esc_html_e( 'This event has been cancelled.', 'eventnest' ); ?></p>
					<?php elseif ( $past ) : ?>
						<p class="ticket-box__note"><?php esc_html_e( 'This event has ended.', 'eventnest' ); ?></p>
						<a class="btn btn--ghost btn--block" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Find upcoming events', 'eventnest' ); ?></a>
					<?php elseif ( $registration_closed || $is_full ) : ?>
						<?php if ( $is_full ) : ?><p class="ticket-box__note"><?php esc_html_e( 'This event has reached its participant limit.', 'eventnest' ); ?></p><?php else : ?>
						<p class="ticket-box__note"><?php esc_html_e( 'Registration for this event has closed.', 'eventnest' ); ?></p>
						<?php endif; ?>
					<?php elseif ( $tix ) : ?>
						<a class="btn btn--brand btn--block" href="<?php echo esc_url( $tix ); ?>" target="_blank" rel="noopener"><?php echo $d['price'] === __( 'Free', 'eventnest' ) ? esc_html__( 'Register for free', 'eventnest' ) : esc_html__( 'Get tickets', 'eventnest' ); ?></a>
					<?php else : ?>
						<p class="ticket-box__note"><?php esc_html_e( 'Registration details will be shared by the organizer.', 'eventnest' ); ?></p>
					<?php endif; ?>
					<?php if ( $cal && ! $past ) : ?><a class="btn btn--ghost btn--block" href="<?php echo esc_url( $cal ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Add to calendar', 'eventnest' ); ?></a><?php endif; ?>
					<div class="share">
						<a href="<?php echo esc_url( 'https://wa.me/?text=' . $share ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Share on WhatsApp', 'eventnest' ); ?></a>
						<button type="button" data-copy="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Copy link', 'eventnest' ); ?></button>
					</div>
				</div>
			</aside>
		</div>
	</div>
</article>

<?php
	$rel = en_upcoming( 3, array( 'post__not_in' => array( $id ) ) );
	if ( $rel->have_posts() ) : ?>
	<section class="section">
		<div class="wrap">
			<div class="section__head"><h2><?php esc_html_e( 'More events you may like', 'eventnest' ); ?></h2></div>
			<div class="grid">
				<?php while ( $rel->have_posts() ) : $rel->the_post(); en_card( en_card_data( get_the_ID() ) ); endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
<?php endif;
endwhile;
get_footer();
