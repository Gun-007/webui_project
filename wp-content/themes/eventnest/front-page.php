<?php
get_header();

$q = en_upcoming( 6 );
$cards = array();
if ( $q->have_posts() ) {
	while ( $q->have_posts() ) { $q->the_post(); $cards[] = en_card_data( get_the_ID() ); }
	wp_reset_postdata();
}
$is_sample = empty( $cards );
if ( $is_sample ) $cards = en_sample_events();
$hero    = $cards[0];
$archive = get_post_type_archive_link( 'event' );
$cities  = get_terms( array( 'taxonomy' => 'event_city', 'hide_empty' => false, 'number' => 30 ) );
$cats    = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => false, 'number' => 30 ) );
$cities  = is_wp_error( $cities ) ? array() : $cities;
$cats    = is_wp_error( $cats ) ? array() : $cats;
?>

<section class="hero">
	<div class="wrap hero__grid">
		<div class="hero__copy">
			<h1 class="hero__title"><?php echo esc_html( en_opt( 'hero_title' ) ); ?></h1>
			<p class="hero__sub"><?php echo esc_html( en_opt( 'hero_sub' ) ); ?></p>

			<form class="search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
				<input type="hidden" name="post_type" value="event">
				<label class="search__field search__field--grow">
					<span><?php esc_html_e( 'What', 'eventnest' ); ?></span>
					<input type="search" name="s" placeholder="<?php esc_attr_e( 'Music, workshop, fest…', 'eventnest' ); ?>">
				</label>
				<?php if ( $cities ) : ?>
				<label class="search__field">
					<span><?php esc_html_e( 'Where', 'eventnest' ); ?></span>
					<select name="event_city">
						<option value=""><?php esc_html_e( 'All cities', 'eventnest' ); ?></option>
						<?php foreach ( $cities as $c ) echo '<option value="' . esc_attr( $c->slug ) . '">' . esc_html( $c->name ) . '</option>'; ?>
					</select>
				</label>
				<?php endif; ?>
				<button class="btn btn--brand search__go" type="submit"><?php esc_html_e( 'Find events', 'eventnest' ); ?></button>
			</form>
		</div>

		<div class="hero__art" aria-hidden="<?php echo $is_sample ? 'true' : 'false'; ?>">
			<span class="hero__sun"></span>
			<a class="big-ticket" href="<?php echo esc_url( $hero['url'] ); ?>" tabindex="<?php echo $is_sample ? '-1' : '0'; ?>">
				<span class="big-ticket__top">
					<span class="big-ticket__date"><b><?php echo esc_html( en_fmt_date( $hero['date'], 'j' ) ); ?></b><?php echo esc_html( en_fmt_date( $hero['date'], 'F' ) ); ?></span>
					<span class="big-ticket__price"><?php echo esc_html( $hero['price'] ); ?></span>
				</span>
				<span class="big-ticket__title"><?php echo esc_html( $hero['title'] ); ?></span>
				<span class="big-ticket__tear"></span>
				<span class="big-ticket__bottom">
					<span><?php echo esc_html( $hero['venue'] ); ?><br><small><?php echo esc_html( en_fmt_time( $hero['time'] ) ); ?></small></span>
					<span class="barcode"></span>
				</span>
			</a>
		</div>
	</div>
</section>

<section class="section section--tight">
	<div class="wrap">
		<ul class="chips" aria-label="<?php esc_attr_e( 'Browse by category', 'eventnest' ); ?>">
			<?php
			if ( $cats ) {
				foreach ( $cats as $c ) echo '<li><a class="chip" href="' . esc_url( get_term_link( $c ) ) . '">' . esc_html( $c->name ) . '</a></li>';
			} else {
				foreach ( array( 'Music', 'College fests', 'Workshops', 'Meetups', 'Markets', 'Comedy', 'Sports' ) as $n ) echo '<li><a class="chip" href="' . esc_url( $archive ) . '">' . esc_html( $n ) . '</a></li>';
			}
			?>
		</ul>
	</div>
</section>

<section class="section">
	<div class="wrap">
		<div class="section__head">
			<h2><?php esc_html_e( 'Coming up', 'eventnest' ); ?></h2>
			<a class="link-more" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'See all events', 'eventnest' ); ?></a>
		</div>
		<?php if ( $is_sample && current_user_can( 'edit_posts' ) ) : ?>
			<p class="notice"><?php esc_html_e( 'These are sample events. Go to Events → Add new in your dashboard to publish real ones. Visitors see this notice only if you are logged in.', 'eventnest' ); ?></p>
		<?php endif; ?>
		<div class="grid">
			<?php foreach ( $cards as $c ) en_card( $c ); ?>
		</div>
	</div>
</section>

<section class="section" id="how">
	<div class="wrap">
		<div class="band">
			<div class="band__copy">
				<h2><?php esc_html_e( 'Running an event? Be live in ten minutes.', 'eventnest' ); ?></h2>
				<p><?php esc_html_e( 'Whether it is a college fest, a workshop or a weekend market, you get an event page people can share, ticket sales and a simple way to check guests in.', 'eventnest' ); ?></p>
				<a class="btn btn--sun" href="<?php echo esc_url( en_opt( 'cta_url' ) ); ?>"><?php echo esc_html( en_opt( 'cta_text' ) ); ?></a>
			</div>
			<ol class="steps">
				<li><b><?php esc_html_e( 'Create your event page', 'eventnest' ); ?></b><span><?php esc_html_e( 'Add the date, venue, photos and ticket price.', 'eventnest' ); ?></span></li>
				<li><b><?php esc_html_e( 'Share it and sell tickets', 'eventnest' ); ?></b><span><?php esc_html_e( 'Post the link on WhatsApp and Instagram. Guests pay by UPI or card.', 'eventnest' ); ?></span></li>
				<li><b><?php esc_html_e( 'Check guests in at the door', 'eventnest' ); ?></b><span><?php esc_html_e( 'Scan QR tickets and see sales in one dashboard.', 'eventnest' ); ?></span></li>
			</ol>
		</div>
	</div>
</section>

<?php get_footer(); ?>
