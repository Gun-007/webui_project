<?php
get_header();
while ( have_posts() ) : the_post();
	$id    = get_the_ID();
	$d     = en_card_data( $id );
	$addr  = en_meta( $id, 'address' );
	$tix   = en_meta( $id, 'ticket_url' );
	$org   = en_meta( $id, 'organizer' );
	$cal   = en_calendar_url( $id );
	$url   = get_permalink();
	$share = rawurlencode( get_the_title() . ' ' . $url );
	$past  = $d['date'] && $d['date'] < current_time( 'Y-m-d' );
	$map   = trim( $d['venue'] . ' ' . $addr . ' ' . $d['city'] );
?>
<article class="event">
	<div class="wrap">
		<div class="event__hero" style="--h:<?php echo (int) $d['hue']; ?>">
			<?php if ( has_post_thumbnail() ) the_post_thumbnail( 'en-hero', array( 'fetchpriority' => 'high' ) ); ?>
		</div>

		<div class="event__grid">
			<div class="event__main">
				<?php if ( $d['cat'] ) : ?><span class="chip"><?php echo esc_html( $d['cat'] ); ?></span><?php endif; ?>
				<h1 class="event__title"><?php the_title(); ?></h1>
				<?php if ( $org ) : ?><p class="event__by"><?php printf( esc_html__( 'Hosted by %s', 'eventnest' ), '<b>' . esc_html( $org ) . '</b>' ); ?></p><?php endif; ?>

				<ul class="facts">
					<?php if ( $d['date'] ) : ?><li><span><?php esc_html_e( 'When', 'eventnest' ); ?></span><?php echo esc_html( en_fmt_date( $d['date'], 'l, j F Y' ) . ( $d['time'] ? ' at ' . en_fmt_time( $d['time'] ) : '' ) ); ?></li><?php endif; ?>
					<?php if ( $d['venue'] || $addr ) : ?><li><span><?php esc_html_e( 'Where', 'eventnest' ); ?></span><?php echo esc_html( trim( $d['venue'] . ( $addr ? ', ' . $addr : '' ) ) ); ?></li><?php endif; ?>
				</ul>

				<div class="prose"><?php the_content(); ?></div>

				<?php if ( $map ) : ?>
					<p><a class="link-more" target="_blank" rel="noopener" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $map ) ); ?>"><?php esc_html_e( 'Open in Google Maps', 'eventnest' ); ?></a></p>
				<?php endif; ?>
			</div>

			<aside class="event__aside">
				<div class="ticket-box">
					<p class="ticket-box__price"><?php echo esc_html( $d['price'] ); ?></p>
					<?php if ( $past ) : ?>
						<p class="ticket-box__note"><?php esc_html_e( 'This event has ended.', 'eventnest' ); ?></p>
						<a class="btn btn--ghost btn--block" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Find upcoming events', 'eventnest' ); ?></a>
					<?php elseif ( $tix ) : ?>
						<a class="btn btn--brand btn--block" href="<?php echo esc_url( $tix ); ?>" target="_blank" rel="noopener"><?php echo $d['price'] === __( 'Free', 'eventnest' ) ? esc_html__( 'Register for free', 'eventnest' ) : esc_html__( 'Get tickets', 'eventnest' ); ?></a>
					<?php else : ?>
						<p class="ticket-box__note"><?php esc_html_e( 'Tickets are not open yet. Check back soon.', 'eventnest' ); ?></p>
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
