<?php
get_header();
$cities = get_terms( array( 'taxonomy' => 'event_city', 'hide_empty' => false ) );
$cats   = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => false ) );
$cities = is_wp_error( $cities ) ? array() : $cities;
$cats   = is_wp_error( $cats ) ? array() : $cats;
$cur_city = get_query_var( 'event_city' );
$cur_cat  = get_query_var( 'event_category' );
$when     = isset( $_GET['when'] ) ? sanitize_key( wp_unslash( $_GET['when'] ) ) : '';
if ( is_tax( 'event_city' ) )     $cur_city = get_queried_object()->slug;
if ( is_tax( 'event_category' ) ) $cur_cat  = get_queried_object()->slug;

if ( is_search() )      $title = sprintf( __( 'Results for “%s”', 'eventnest' ), get_search_query() );
elseif ( is_tax() )     $title = single_term_title( '', false );
else                    $title = __( 'Explore events', 'eventnest' );
?>
<section class="page-head">
	<div class="wrap">
		<p class="eyebrow eyebrow--small"><?php esc_html_e( 'Find your next campus moment', 'eventnest' ); ?></p>
		<h1><?php echo esc_html( $title ); ?></h1>
		<p class="section__sub"><?php esc_html_e( 'Explore workshops, competitions, club activities and more.', 'eventnest' ); ?></p>
		<form class="filters" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<input type="hidden" name="post_type" value="event">
			<input class="filters__q" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search events', 'eventnest' ); ?>" aria-label="<?php esc_attr_e( 'Search events', 'eventnest' ); ?>">
			<?php if ( $cities ) : ?>
			<select name="event_city" aria-label="<?php esc_attr_e( 'City', 'eventnest' ); ?>">
				<option value=""><?php esc_html_e( 'All cities', 'eventnest' ); ?></option>
				<?php foreach ( $cities as $c ) printf( '<option value="%s"%s>%s</option>', esc_attr( $c->slug ), selected( $cur_city, $c->slug, false ), esc_html( $c->name ) ); ?>
			</select>
			<?php endif; ?>
			<?php if ( $cats ) : ?>
			<select name="event_category" aria-label="<?php esc_attr_e( 'Category', 'eventnest' ); ?>">
				<option value=""><?php esc_html_e( 'All categories', 'eventnest' ); ?></option>
				<?php foreach ( $cats as $c ) printf( '<option value="%s"%s>%s</option>', esc_attr( $c->slug ), selected( $cur_cat, $c->slug, false ), esc_html( $c->name ) ); ?>
			</select>
			<?php endif; ?>
			<select name="when" aria-label="<?php esc_attr_e( 'Date range', 'eventnest' ); ?>">
				<option value="" <?php selected( $when, '' ); ?>><?php esc_html_e( 'Any upcoming date', 'eventnest' ); ?></option>
				<option value="week" <?php selected( $when, 'week' ); ?>><?php esc_html_e( 'Next 7 days', 'eventnest' ); ?></option>
				<option value="month" <?php selected( $when, 'month' ); ?>><?php esc_html_e( 'This month', 'eventnest' ); ?></option>
			</select>
			<label class="check"><input type="checkbox" name="free" value="1" <?php checked( ! empty( $_GET['free'] ) ); ?>> <?php esc_html_e( 'Free only', 'eventnest' ); ?></label>
			<button class="btn btn--brand btn--sm" type="submit"><?php esc_html_e( 'Show events', 'eventnest' ); ?></button>
		</form>
	</div>
</section>

<section class="section section--tight">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<p class="results-count"><?php printf( esc_html( _n( '%s event found', '%s events found', (int) $wp_query->found_posts, 'eventnest' ) ), esc_html( number_format_i18n( $wp_query->found_posts ) ) ); ?></p>
			<div class="grid event-grid">
				<?php while ( have_posts() ) : the_post(); en_card( en_card_data( get_the_ID() ) ); endwhile; ?>
			</div>
			<?php the_posts_pagination( array( 'mid_size' => 1, 'prev_text' => __( 'Previous', 'eventnest' ), 'next_text' => __( 'Next', 'eventnest' ) ) ); ?>
		<?php else : ?>
			<div class="empty">
				<h2><?php esc_html_e( 'No upcoming events match these filters', 'eventnest' ); ?></h2>
				<p><?php esc_html_e( 'Try another city or category, or clear the filters to see everything.', 'eventnest' ); ?></p>
				<a class="btn btn--brand" href="<?php echo esc_url( get_post_type_archive_link( 'event' ) ); ?>"><?php esc_html_e( 'Clear filters', 'eventnest' ); ?></a>
			</div>
		<?php endif; ?>
	</div>
</section>
<?php get_footer(); ?>
