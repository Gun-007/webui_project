<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function en_opt( $key ) {
	$defaults = array(
		'hero_sub'    => 'Find workshops, competitions, club activities and campus events—all in one place.',
		'cta_text'    => 'Login',
		'cta_url'     => home_url( '/login/' ),
		'whatsapp'    => '',
		'footer_text' => 'Discover campus events, meet your community and make something happen together.',
	);
	return get_theme_mod( 'en_' . $key, $defaults[ $key ] );
}

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'en_options', array( 'title' => __( 'EventNest settings', 'eventnest' ), 'priority' => 30 ) );
	$fields = array(
		'hero_sub'    => array( 'Homepage subtitle', 'textarea' ),
		'cta_text'    => array( 'Header action text', 'text' ),
		'cta_url'     => array( 'Header action link', 'url' ),
		'whatsapp'    => array( 'WhatsApp number (country code, digits only)', 'text' ),
		'footer_text' => array( 'Footer tagline', 'text' ),
	);
	foreach ( $fields as $key => $f ) {
		$wp->add_setting( 'en_' . $key, array( 'default' => en_opt( $key ), 'sanitize_callback' => $f[1] === 'url' ? 'esc_url_raw' : 'sanitize_textarea_field' ) );
		$wp->add_control( 'en_' . $key, array( 'label' => $f[0], 'section' => 'en_options', 'type' => $f[1] ) );
	}
	$wp->add_setting( 'en_hero_image', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp->add_control( new WP_Customize_Image_Control( $wp, 'en_hero_image', array(
		'label'   => __( 'Homepage hero image', 'eventnest' ),
		'section' => 'en_options',
	) ) );
	$wp->add_setting( 'en_accent', array( 'default' => '', 'sanitize_callback' => 'sanitize_hex_color' ) );
	$wp->add_control( new WP_Customize_Color_Control( $wp, 'en_accent', array( 'label' => __( 'Brand colour (light mode)', 'eventnest' ), 'section' => 'en_options' ) ) );
} );
