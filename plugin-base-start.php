<?php
/**
 * Plugin Name: Custom Code for PLO
 * Plugin URI: https://courior.alloxesinfotech.com/
 * Description: Custom Code for PLO
 * Author: Rohan Vyas
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Nothing' );
}

// Plugin Basename
define( 'PLO_PLUGIN_BASENAME', basename( dirname( __FILE__ ) ) . '/' . basename( __FILE__ ) );

// Plugin Path
define( 'PLO_PATH', dirname( __FILE__ ) );

// Plugin URL
define( 'PLO_URL', plugins_url( '', PLO_PLUGIN_BASENAME ) );

// Plugin CSS URL
define( 'PLO_CSS_URL', plugins_url( 'css', PLO_PLUGIN_BASENAME ) );

// Plugin JS URL
define( 'PLO_JS_URL', plugins_url( 'js', PLO_PLUGIN_BASENAME ) );

// Plugin IMG URL
define( 'PLO_IMG_URL', plugins_url( 'image', PLO_PLUGIN_BASENAME ) );

add_action('wp_enqueue_scripts','plo_scripts');
function plo_scripts(){
	wp_enqueue_style( 'plo-gf', 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap', false, '0.1', 'all' );
	wp_enqueue_style( 'plo-fa', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css', false, '4.7.0', 'all' );
	wp_enqueue_style( 'plo-bs', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', false, '5.0.2', 'all' );
	wp_enqueue_style( 'plo', PLO_CSS_URL . '/style.css', false, '1.0', 'all' );
	wp_enqueue_style( 'plo-responsive', PLO_CSS_URL . '/responsive.css', false, '1.0', 'all' );
	
	wp_enqueue_script('plo-bs', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js', array(), '5.0.2', true);
	wp_enqueue_script('plo-bs-bundle', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array(), '5.0.2', true);
	wp_enqueue_script('plo-popper', 'https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js', array(), '2.9.2', true);
	wp_enqueue_script('plo-custom', PLO_JS_URL . '/plo-script.js', array(), '3.6.1', true);
}
