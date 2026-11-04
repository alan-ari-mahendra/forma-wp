<?php
// Disable Hello's own page title (the landing page has its own H1) and keep the theme a thin shell.
add_filter( 'hello_elementor_page_title', '__return_false' );
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'hello-elementor-child', get_stylesheet_uri(), [ 'hello-elementor' ], wp_get_theme()->get( 'Version' ) );
}, 20 );
