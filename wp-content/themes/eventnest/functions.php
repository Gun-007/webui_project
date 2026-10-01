<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'EN_VERSION', '1.0.0' );

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
	echo "<script>(function(){try{var t=localStorage.getItem('en-theme');if(!t){t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'}document.documentElement.setAttribute('data-theme',t)}catch(e){}})();</script>\n";
}, 1 );

// Customizer accent colour (light mode only; dark mode keeps its own tuned brand colour).
add_action( 'wp_head', function () {
	$c = sanitize_hex_color( get_theme_mod( 'en_accent', '' ) );
	if ( $c ) echo '<style>:root:not([data-theme="dark"]){--brand:' . esc_html( $c ) . '}</style>' . "\n";
}, 20 );

function en_fallback_menu() {
	echo '<ul class="menu">';
	echo '<li><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'eventnest' ) . '</a></li>';
	echo '<li><a href="' . esc_url( get_post_type_archive_link( 'event' ) ) . '">' . esc_html__( 'Explore events', 'eventnest' ) . '</a></li>';
	echo '<li><a href="' . esc_url( home_url( '/#how' ) ) . '">' . esc_html__( 'For organizers', 'eventnest' ) . '</a></li>';
	echo '</ul>';
}

function en_logo_mark() {
	return '<svg class="brand__mark" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="10" fill="var(--brand)"/><path d="M8 11h16v3.5a2.5 2.5 0 0 0 0 5V23H8v-3.5a2.5 2.5 0 0 0 0-5z" fill="#FFC233"/></svg>';
}
