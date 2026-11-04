<?php
/**
 * ExerciseGym + FAQPage structured data, generated from the same SCF data that is visible on the page.
 * With Rank Math active the entities are merged into its @graph (no duplicate blocks); otherwise printed as JSON-LD.
 */
defined( 'ABSPATH' ) || exit;

function forma_schema_entities() {
	$url   = home_url( '/' );
	$hours = [];
	foreach ( (array) forma_opt( 'biz_hours', [] ) as $h ) {
		$days = array_values( array_filter( array_map( 'trim', explode( ',', (string) $h['days'] ) ) ) );
		if ( $days && $h['opens'] && $h['closes'] ) {
			$hours[] = [ '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days, 'opens' => $h['opens'], 'closes' => $h['closes'] ];
		}
	}
	$same = array_values( array_filter( array_column( (array) forma_opt( 'socials', [] ), 'url' ) ) );
	$img  = forma_f( 'hero_image' ) ? wp_get_attachment_image_url( forma_f( 'hero_image' ), 'forma-hero' ) : '';
	$gym  = array_filter( [
		'@type'     => [ 'ExerciseGym', 'LocalBusiness' ],
		'@id'       => $url . '#gym',
		'name'      => 'FORMA Athletic Club',
		'description' => forma_f( 'hero_description' ),
		'url'       => $url,
		'image'     => $img,
		'telephone' => forma_opt( 'biz_phone' ),
		'email'     => forma_opt( 'biz_email' ),
		'priceRange' => forma_opt( 'biz_price_range' ),
		'address'   => array_filter( [
			'@type' => 'PostalAddress', 'streetAddress' => forma_opt( 'biz_street' ), 'addressLocality' => forma_opt( 'biz_city' ),
			'addressRegion' => forma_opt( 'biz_region' ), 'postalCode' => forma_opt( 'biz_zip' ), 'addressCountry' => forma_opt( 'biz_country' ),
		] ),
		'openingHoursSpecification' => $hours,
		'sameAs'    => $same,
	] );
	$out = [ 'gym' => $gym ];

	$q = [];
	foreach ( forma_rows( 'faqs' ) as $f ) {
		$q[] = [ '@type' => 'Question', 'name' => $f['question'], 'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $f['answer'] ] ];
	}
	if ( $q ) {
		$out['faq'] = [ '@type' => 'FAQPage', '@id' => $url . '#faq', 'url' => $url, 'mainEntity' => $q ];
	}
	return $out;
}

add_filter( 'rank_math/json_ld', function ( $data ) {
	if ( ! is_front_page() ) {
		return $data;
	}
	foreach ( forma_schema_entities() as $k => $e ) {
		$data[ 'forma_' . $k ] = $e;
	}
	return $data;
}, 99 );

add_action( 'wp_head', function () {
	if ( ! is_front_page() || class_exists( 'RankMath' ) ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( [ '@context' => 'https://schema.org', '@graph' => array_values( forma_schema_entities() ) ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
} );
