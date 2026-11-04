<?php
defined( 'ABSPATH' ) || exit;

/** Post that holds the landing-page SCF fields (the static front page). */
function forma_pid() {
	return (int) get_option( 'page_on_front' );
}

function forma_f( $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$v = get_field( $name, forma_pid() );
	return ( null === $v || false === $v || '' === $v ) ? $default : $v;
}

function forma_rows( $name ) {
	$v = forma_f( $name, [] );
	return is_array( $v ) ? $v : [];
}

function forma_opt( $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$v = get_field( $name, 'option' );
	return ( null === $v || false === $v || '' === $v ) ? $default : $v;
}

/** Responsive <img> from attachment id. $eager = hero (fetchpriority high), otherwise lazy. */
function forma_img( $id, $size = 'large', $alt = '', $eager = false, $sizes = '100vw', $class = '' ) {
	if ( ! $id ) {
		return '<div class="ph" aria-hidden="true"></div>';
	}
	$attr = [
		'class'    => 'forma-img ' . $class,
		'sizes'    => $sizes,
		'decoding' => 'async',
		'loading'  => $eager ? 'eager' : 'lazy',
	];
	if ( $eager ) {
		$attr['fetchpriority'] = 'high';
	}
	if ( '' !== $alt ) {
		$attr['alt'] = $alt;
	}
	return wp_get_attachment_image( (int) $id, $size, false, $attr );
}

function forma_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\R/', (string) $text ) ) ) );
}

function forma_e( $s ) {
	return esc_html( $s );
}

/** Heading with the last word(s) wrapped: text containing {mark}...{/mark} or {em}...{/em}. */
function forma_rich( $text ) {
	$t = esc_html( $text );
	$t = preg_replace( '/\{mark\}(.*?)\{\/mark\}/s', '<span class="mark">$1</span>', $t );
	$t = preg_replace( '/\{em\}(.*?)\{\/em\}/s', '<em>$1</em>', $t );
	return nl2br( $t );
}

/** Class availability status derived from data, never from presentation. */
function forma_status( $row ) {
	$max   = (int) ( $row['max_spots'] ?? 0 );
	$avail = (int) ( $row['spots'] ?? 0 );
	if ( ! empty( $row['waitlist'] ) || $avail <= 0 ) {
		return 'full';
	}
	if ( $avail <= 3 ) {
		return 'almost';
	}
	return 'available';
}

function forma_days() {
	return [ 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ];
}

function forma_time_parts( $hm ) {
	$ts = strtotime( '2000-01-01 ' . $hm );
	return $ts ? [ 'time' => gmdate( 'g:i', $ts ), 'ampm' => gmdate( 'A', $ts ), 'ts' => $ts ] : [ 'time' => $hm, 'ampm' => '', 'ts' => 0 ];
}
