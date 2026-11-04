<?php
/** Trial + newsletter submissions: REST endpoints, server-side validation, private storage, admin notification. */
defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	register_post_type( 'forma_lead', [
		'label' => 'FORMA Leads', 'public' => false, 'show_ui' => true, 'show_in_menu' => true, 'menu_icon' => 'dashicons-groups', 'menu_position' => 4,
		'supports' => [ 'title' ], 'capability_type' => 'post', 'capabilities' => [ 'create_posts' => 'do_not_allow' ], 'map_meta_cap' => true,
	] );
} );

add_action( 'rest_api_init', function () {
	foreach ( [ 'trial' => 'forma_rest_trial', 'newsletter' => 'forma_rest_news' ] as $route => $cb ) {
		register_rest_route( 'forma/v1', '/' . $route, [ 'methods' => 'POST', 'callback' => $cb, 'permission_callback' => '__return_true' ] );
	}
} );

function forma_rate_limited() {
	$key = 'forma_rl_' . md5( $_SERVER['REMOTE_ADDR'] ?? 'x' );
	$n   = (int) get_transient( $key );
	if ( $n >= 5 ) {
		return true;
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	return false;
}

function forma_rest_trial( WP_REST_Request $r ) {
	if ( ! empty( $r['website'] ) ) { // honeypot: pretend success
		return [ 'ok' => true ];
	}
	if ( forma_rate_limited() ) {
		return new WP_REST_Response( [ 'ok' => false, 'message' => 'Too many attempts. Please try again in a few minutes.' ], 429 );
	}
	$goals = forma_lines( forma_f( 'trial_goals' ) );
	$times = array_column( forma_rows( 'trial_times' ), 'label' );
	$d = [
		'first_name' => sanitize_text_field( (string) $r['first_name'] ),
		'last_name'  => sanitize_text_field( (string) $r['last_name'] ),
		'email'      => sanitize_email( (string) $r['email'] ),
		'phone'      => trim( sanitize_text_field( (string) $r['phone'] ) ),
		'goal'       => sanitize_text_field( (string) $r['goal'] ),
		'time'       => sanitize_text_field( (string) $r['time'] ),
	];
	$e = [];
	if ( mb_strlen( $d['first_name'] ) < 2 ) { $e['first_name'] = 'Enter your first name.'; }
	if ( mb_strlen( $d['last_name'] ) < 2 ) { $e['last_name'] = 'Enter your last name.'; }
	if ( ! is_email( $d['email'] ) ) { $e['email'] = 'Enter a valid email address.'; }
	$digits = preg_replace( '/\D/', '', $d['phone'] );
	if ( ! preg_match( '/^\+?[\d\s().\-]{7,20}$/', $d['phone'] ) || strlen( $digits ) < 7 || strlen( $digits ) > 15 ) { $e['phone'] = 'Enter a valid phone number.'; }
	if ( ! in_array( $d['goal'], $goals, true ) ) { $e['goal'] = 'Choose a goal.'; }
	if ( ! in_array( $d['time'], $times, true ) ) { $e['time'] = 'Choose a preferred time.'; }
	if ( $e ) {
		return new WP_REST_Response( [ 'ok' => false, 'errors' => $e ], 422 );
	}
	$id = wp_insert_post( [ 'post_type' => 'forma_lead', 'post_status' => 'private', 'post_title' => $d['first_name'] . ' ' . $d['last_name'] . ' · trial', 'meta_input' => array_merge( $d, [ 'lead_type' => 'trial' ] ) ] );
	if ( is_wp_error( $id ) ) {
		return new WP_REST_Response( [ 'ok' => false, 'message' => 'Something went wrong. Please try again.' ], 500 );
	}
	wp_mail( get_option( 'admin_email' ), 'New FORMA trial request: ' . $d['first_name'] . ' ' . $d['last_name'], implode( "\n", array_map( fn( $k, $v ) => "$k: $v", array_keys( $d ), $d ) ) );
	return [ 'ok' => true ];
}

function forma_rest_news( WP_REST_Request $r ) {
	if ( forma_rate_limited() ) {
		return new WP_REST_Response( [ 'ok' => false, 'message' => 'Too many attempts. Try again later.' ], 429 );
	}
	$email = sanitize_email( (string) $r['email'] );
	if ( ! is_email( $email ) ) {
		return new WP_REST_Response( [ 'ok' => false, 'errors' => [ 'email' => 'Enter a valid email address.' ] ], 422 );
	}
	wp_insert_post( [ 'post_type' => 'forma_lead', 'post_status' => 'private', 'post_title' => $email . ' · newsletter', 'meta_input' => [ 'email' => $email, 'lead_type' => 'newsletter' ] ] );
	return [ 'ok' => true ];
}
