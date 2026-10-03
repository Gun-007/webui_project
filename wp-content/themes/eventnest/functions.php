<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EN_VERSION', '1.1.0' );

require_once get_template_directory() . '/inc/events.php';
require_once get_template_directory() . '/inc/customizer.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'eventnest', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 48, 'width' => 160, 'flex-width' => true, 'flex-height' => true ) );
	add_image_size( 'en-card', 720, 480, true );
	add_image_size( 'en-hero', 1400, 720, true );
	register_nav_menus( array(
		'primary' => __( 'Primary menu', 'eventnest' ),
		'footer'  => __( 'Footer menu', 'eventnest' ),
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'eventnest-fonts', 'https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=DM+Sans:wght@400;500;700&display=swap', array(), null );
	wp_enqueue_style( 'eventnest', get_template_directory_uri() . '/assets/css/main.css', array( 'eventnest-fonts' ), EN_VERSION );
	wp_enqueue_script( 'eventnest', get_template_directory_uri() . '/assets/js/main.js', array(), EN_VERSION, true );
} );

// Apply saved theme (light/dark) before paint to avoid a flash.
add_action( 'wp_head', function () {
	echo "<script>(function(){try{var t=localStorage.getItem('en-theme')||'dark';document.documentElement.setAttribute('data-theme',t)}catch(e){document.documentElement.setAttribute('data-theme','dark')}})();</script>\n";
}, 1 );

// Customizer accent colour (light mode only; dark mode keeps its own tuned brand colour).
add_action( 'wp_head', function () {
	$c = sanitize_hex_color( get_theme_mod( 'en_accent', '' ) );
	if ( $c ) echo '<style>:root:not([data-theme="dark"]){--brand:' . esc_html( $c ) . '}</style>' . "\n";
}, 20 );

function en_fallback_menu() {
	echo '<ul class="menu">';
	$links = array(
		__( 'Home', 'eventnest' )         => home_url( '/' ),
		__( 'Events', 'eventnest' )       => get_post_type_archive_link( 'event' ),
		__( 'Competitions', 'eventnest' ) => home_url( '/competitions/' ),
		__( 'Clubs', 'eventnest' )        => home_url( '/clubs/' ),
		__( 'Proposals', 'eventnest' )    => home_url( '/proposals/' ),
		__( 'About', 'eventnest' )        => home_url( '/about/' ),
	);
	foreach ( $links as $label => $url ) {
		echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
	}
	echo '</ul>';
}

function en_logo_mark() {
	return '<svg class="brand__mark" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="9" fill="var(--brand)"/><path d="M21.8 10.2a9 9 0 1 0 0 11.6 7.2 7.2 0 0 1 0-11.6Z" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
}
