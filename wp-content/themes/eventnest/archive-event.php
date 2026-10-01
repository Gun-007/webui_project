<?php
get_header();
$cities = get_terms( array( 'taxonomy' => 'event_city', 'hide_empty' => false ) );
$cats   = get_terms( array( 'taxonomy' => 'event_category', 'hide_empty' => false ) );
$cities = is_wp_error( $cities ) ? array() : $cities;
$cats   = is_wp_error( $cats ) ? array() : $cats;
$cur_city = get_query_var( 'event_city' );
$cur_cat  = get_query_var( 'event_category' );
if ( is_tax( 'event_city' ) )     $cur_city = get_queried_object()->slug;
if ( is_tax( 'event_category' ) ) $cur_cat  = get_queried_object()->slug;

if ( is_search() )      $title = sprintf( __( 'Results for “%s”', 'eventnest' ), get_search_query() );
elseif ( is_tax() )     $title = single_term_title( '', false );
else                    $title = __( 'Explore events', 'eventnest' );
?>
<section class="page-head">
	<div class="wrap">
		<h1><?php echo esc_html( $title ); ?></h1>
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
			<label class="check"><input type="checkbox" name="free" value="1" <?php checked( ! empty( $_GET['free'] ) ); ?>> <?php esc_html_e( 'Free only', 'eventnest' ); ?></label>
			<button class="btn btn--brand btn--sm" type="submit"><?php esc_html_e( 'Apply filters', 'eventnest' ); ?></button>
		</form>
	</div>
</section>

<section class="section section--tight">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="grid">
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
