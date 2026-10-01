<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function en_opt( $key ) {
	$defaults = array(
		'hero_title'  => 'Find your next great night out.',
		'hero_sub'    => 'Concerts, college fests, workshops and meetups, all in one place. Book in a minute, show up with a QR code.',
		'cta_text'    => 'Host an event',
		'cta_url'     => home_url( '/host-an-event/' ),
		'whatsapp'    => '',
		'footer_text' => 'Discover events and sell tickets without the hassle.',
	);
	return get_theme_mod( 'en_' . $key, $defaults[ $key ] );
}

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'en_options', array( 'title' => __( 'EventNest settings', 'eventnest' ), 'priority' => 30 ) );
	$fields = array(
		'hero_title'  => array( 'Homepage headline', 'text' ),
		'hero_sub'    => array( 'Homepage subtitle', 'textarea' ),
		'cta_text'    => array( 'Header button text', 'text' ),
		'cta_url'     => array( 'Header button link', 'url' ),
		'whatsapp'    => array( 'WhatsApp number (country code, digits only)', 'text' ),
		'footer_text' => array( 'Footer tagline', 'text' ),
	);
	foreach ( $fields as $key => $f ) {
		$wp->add_setting( 'en_' . $key, array( 'default' => en_opt( $key ), 'sanitize_callback' => $f[1] === 'url' ? 'esc_url_raw' : 'sanitize_textarea_field' ) );
		$wp->add_control( 'en_' . $key, array( 'label' => $f[0], 'section' => 'en_options', 'type' => $f[1] ) );
	}
	$wp->add_setting( 'en_accent', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp->add_control( new WP_Customize_Color_Control( $wp, 'en_accent', array( 'label' => __( 'Brand colour (light mode)', 'eventnest' ), 'section' => 'en_options' ) ) );
} );
