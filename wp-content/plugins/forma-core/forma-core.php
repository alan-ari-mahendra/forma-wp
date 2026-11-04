<?php
/**
 * Plugin Name: FORMA Core
 * Description: SCF field groups, section renderers, trial form REST endpoint, JSON-LD and assets for the FORMA Athletic Club landing page.
 * Version: 1.0.0
 * Requires PHP: 8.0
 * Text Domain: forma
 */
defined( 'ABSPATH' ) || exit;

define( 'FORMA_VERSION', '1.0.0' );
define( 'FORMA_DIR', plugin_dir_path( __FILE__ ) );
define( 'FORMA_URL', plugin_dir_url( __FILE__ ) );

require_once FORMA_DIR . 'inc/helpers.php';
require_once FORMA_DIR . 'inc/fields.php';
require_once FORMA_DIR . 'inc/render.php';
require_once FORMA_DIR . 'inc/shell.php';
require_once FORMA_DIR . 'inc/trial.php';
require_once FORMA_DIR . 'inc/schema.php';
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once FORMA_DIR . 'inc/seed.php';
}

/** Assets: CSS in head (render-blocking is minimal, single small file), JS deferred, not loaded inside Elementor editor. */
add_action( 'wp_enqueue_scripts', function () {
	$deps = array_values( array_filter( [ 'hello-elementor', 'hello-elementor-theme-style', 'hello-elementor-child' ], 'wp_style_is' ) );
	wp_enqueue_style( 'forma-fonts', 'https://fonts.googleapis.com/css2?family=Barlow+Condensed:ital,wght@0,600;0,700;0,800;1,800&family=Barlow:wght@400;500;600&display=swap', [], null );
	wp_enqueue_style( 'forma', FORMA_URL . 'assets/forma.css', $deps, filemtime( FORMA_DIR . 'assets/forma.css' ) );
	$editing = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode();
	if ( ! $editing ) {
		wp_enqueue_script( 'forma', FORMA_URL . 'assets/forma.js', [], filemtime( FORMA_DIR . 'assets/forma.js' ), [ 'strategy' => 'defer', 'in_footer' => true ] );
		wp_localize_script( 'forma', 'FORMA', [
			'rest'  => esc_url_raw( rest_url( 'forma/v1/trial' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		] );
	}
}, 30 );

/** Preconnect for Google Fonts. */
add_filter( 'wp_resource_hints', function ( $urls, $type ) {
	if ( 'preconnect' === $type ) {
		$urls[] = 'https://fonts.googleapis.com';
		$urls[] = [ 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' ];
	}
	return $urls;
}, 10, 2 );

/** Menus. */
add_action( 'after_setup_theme', function () {
	register_nav_menus( [
		'forma-primary' => __( 'FORMA Primary', 'forma' ),
		'forma-club'    => __( 'FORMA Footer: Club', 'forma' ),
		'forma-members' => __( 'FORMA Footer: Members', 'forma' ),
	] );
	add_image_size( 'forma-hero', 1920, 1080, true );
	add_image_size( 'forma-card', 900, 1200, true );
	add_image_size( 'forma-wide', 1200, 800, true );
} );
