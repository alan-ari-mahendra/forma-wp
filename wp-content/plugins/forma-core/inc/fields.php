<?php
/**
 * SCF/ACF field groups, registered in code (version-controlled, editable values in Dashboard).
 * All landing-page groups are shown on the static front page. Site-wide data lives on the "FORMA Settings" options page.
 */
defined( 'ABSPATH' ) || exit;

function forma_f_text( $name, $label, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'text' ], $extra );
}
function forma_f_area( $name, $label, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'textarea', 'rows' => 3, 'new_lines' => '' ], $extra );
}
function forma_f_url( $name, $label, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'url' ], $extra );
}
function forma_f_img( $name, $label, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'medium', 'instructions' => 'Set alt text in the Media Library.' ], $extra );
}
function forma_f_num( $name, $label, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'number', 'min' => 0 ], $extra );
}
function forma_f_bool( $name, $label, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'true_false', 'ui' => 1 ], $extra );
}
function forma_f_sel( $name, $label, $choices, $extra = [] ) {
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'select', 'choices' => $choices, 'return_format' => 'value' ], $extra );
}
function forma_f_rep( $name, $label, $subs, $extra = [] ) {
	foreach ( $subs as &$s ) {
		$s['key'] = 'field_forma_' . $name . '_' . $s['name'];
	}
	return array_merge( [ 'key' => 'field_forma_' . $name, 'name' => $name, 'label' => $label, 'type' => 'repeater', 'layout' => 'block', 'button_label' => 'Add row', 'sub_fields' => $subs ], $extra );
}
/** Sub-field builders (no key prefix; the repeater assigns keys). */
function forma_s( $type, $name, $label, $extra = [] ) {
	return array_merge( [ 'name' => $name, 'label' => $label, 'type' => $type ], $extra );
}

function forma_head_fields( $p, $headline = true ) {
	$f = [ forma_f_text( $p . '_eyebrow', 'Eyebrow' ) ];
	if ( $headline ) {
		$f[] = forma_f_area( $p . '_heading', 'Heading', [ 'rows' => 2, 'instructions' => 'New line = line break. Wrap words in {mark}…{/mark} for the volt highlight.' ] );
		$f[] = forma_f_area( $p . '_lead', 'Lead paragraph' );
	}
	return $f;
}

add_action( 'acf/init', function () {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	$loc = [ [ [ 'param' => 'page_type', 'operator' => '==', 'value' => 'front_page' ] ] ];
	$g   = function ( $key, $title, $fields, $order ) use ( $loc ) {
		acf_add_local_field_group( [
			'key' => 'group_forma_' . $key, 'title' => 'FORMA · ' . $title, 'fields' => $fields,
			'location' => $loc, 'menu_order' => $order, 'position' => 'normal', 'style' => 'default', 'active' => true,
		] );
	};

	$g( 'hero', '3 Hero', array_merge( [
		forma_f_text( 'hero_eyebrow', 'Eyebrow' ),
		forma_f_text( 'hero_heading', 'Heading (before accent)' ),
		forma_f_text( 'hero_heading_accent', 'Heading accent word' ),
		forma_f_area( 'hero_description', 'Description' ),
		forma_f_text( 'hero_cta1_text', 'Primary CTA text' ),
		forma_f_url( 'hero_cta1_url', 'Primary CTA URL' ),
		forma_f_text( 'hero_cta2_text', 'Secondary CTA text' ),
		forma_f_url( 'hero_cta2_url', 'Secondary CTA URL' ),
		forma_f_img( 'hero_image', 'Hero image' ),
		forma_f_rep( 'hero_stats', 'Statistics (4)', [
			forma_s( 'text', 'value', 'Value' ),
			forma_s( 'text', 'label', 'Label' ),
			forma_s( 'true_false', 'stars', 'Show stars', [ 'ui' => 1 ] ),
		], [ 'min' => 1, 'max' => 4 ] ),
	], [] ), 3 );

	$g( 'marquee', '4 Marquee', [
		forma_f_area( 'marquee_text', 'Marquee items', [ 'rows' => 6, 'instructions' => 'One item per line.' ] ),
	], 4 );

	$g( 'intro', '5 Introduction', array_merge( forma_head_fields( 'intro' ), [
		forma_f_img( 'intro_image', 'Image (4:5)' ),
		forma_f_rep( 'principles', 'Training principles (3)', [
			forma_s( 'text', 'title', 'Title' ),
			forma_s( 'textarea', 'text', 'Text', [ 'rows' => 3 ] ),
		], [ 'max' => 3 ] ),
	] ), 5 );

	$g( 'programs', '6 Programs', array_merge( forma_head_fields( 'programs' ), [
		forma_f_rep( 'programs', 'Programs (4)', [
			forma_s( 'text', 'name', 'Name' ),
			forma_s( 'textarea', 'description', 'Description', [ 'rows' => 3 ] ),
			forma_s( 'image', 'image', 'Image', [ 'return_format' => 'id', 'preview_size' => 'medium' ] ),
			forma_s( 'text', 'duration', 'Duration', [ 'instructions' => 'e.g. 60 min' ] ),
			forma_s( 'text', 'level', 'Level' ),
			forma_s( 'number', 'intensity', 'Intensity (1–5)', [ 'min' => 1, 'max' => 5, 'default_value' => 3 ] ),
			forma_s( 'textarea', 'bullets', 'What a session includes', [ 'rows' => 4, 'new_lines' => '', 'instructions' => 'One per line.' ] ),
			forma_s( 'text', 'cta_text', 'CTA text' ),
			forma_s( 'url', 'cta_url', 'CTA URL' ),
		], [ 'max' => 6 ] ),
	] ), 6 );

	$g( 'facilities', '7 Facilities', array_merge( forma_head_fields( 'facilities' ), [
		forma_f_rep( 'facilities', 'Facilities (4)', [
			forma_s( 'text', 'name', 'Name' ),
			forma_s( 'text', 'description', 'Description' ),
			forma_s( 'image', 'image', 'Image', [ 'return_format' => 'id', 'preview_size' => 'medium' ] ),
			forma_s( 'text', 'category', 'Category' ),
		], [ 'max' => 4, 'instructions' => 'Bento layout: item 1 large, item 2 wide, items 3–4 small.' ] ),
		forma_f_text( 'facilities_hours', 'Info strip: Open' ),
		forma_f_text( 'facilities_included', 'Info strip: Included' ),
		forma_f_text( 'facilities_parking', 'Info strip: Parking' ),
	] ), 7 );

	$g( 'schedule', '8 Class schedule', array_merge( forma_head_fields( 'schedule' ), [
		forma_f_text( 'schedule_footnote', 'Footnote' ),
		forma_f_text( 'schedule_download_text', 'Timetable link text' ),
		forma_f_url( 'schedule_download_url', 'Timetable link URL' ),
		forma_f_rep( 'classes', 'Classes', [
			forma_s( 'text', 'class_name', 'Class name' ),
			forma_s( 'select', 'day', 'Day', [ 'choices' => array_combine( forma_days(), forma_days() ), 'return_format' => 'value' ] ),
			forma_s( 'text', 'start_time', 'Start time (HH:MM, 24h)', [ 'placeholder' => '05:30' ] ),
			forma_s( 'text', 'end_time', 'End time (HH:MM, 24h)', [ 'placeholder' => '06:30' ] ),
			forma_s( 'text', 'program', 'Program', [ 'instructions' => 'Must match a program name (used by the filter).' ] ),
			forma_s( 'text', 'coach', 'Coach' ),
			forma_s( 'number', 'spots', 'Available spots', [ 'min' => 0 ] ),
			forma_s( 'number', 'max_spots', 'Maximum spots', [ 'min' => 0 ] ),
			forma_s( 'true_false', 'waitlist', 'Waitlist only', [ 'ui' => 1, 'instructions' => 'Forces "Full / Waitlist" status.' ] ),
			forma_s( 'url', 'booking_url', 'Booking URL' ),
		], [ 'layout' => 'table' ] ),
	] ), 8 );

	$g( 'coaches', '9 Coaches', array_merge( forma_head_fields( 'coaches' ), [
		forma_f_img( 'coaches_image', 'Coach hero image (wide)' ),
		forma_f_rep( 'coaches', 'Coaches', [
			forma_s( 'text', 'name', 'Name' ),
			forma_s( 'text', 'specialty', 'Specialty' ),
			forma_s( 'textarea', 'bio', 'Short biography', [ 'rows' => 3 ] ),
			forma_s( 'image', 'image', 'Profile image (3:4)', [ 'return_format' => 'id', 'preview_size' => 'medium' ] ),
			forma_s( 'text', 'credentials', 'Credentials', [ 'instructions' => 'Comma separated, e.g. NSCA-CSCS, 12 yrs' ] ),
		], [ 'max' => 8 ] ),
	] ), 9 );

	$g( 'pricing', '10 Pricing', array_merge( forma_head_fields( 'pricing' ), [
		forma_f_text( 'promo_title', 'Promo bar title', [ 'instructions' => 'Wrap accent in {mark}…{/mark}' ] ),
		forma_f_text( 'promo_text', 'Promo bar text' ),
		forma_f_text( 'promo_cta_text', 'Promo CTA text' ),
		forma_f_url( 'promo_cta_url', 'Promo CTA URL' ),
		forma_f_rep( 'plans', 'Plans (3)', [
			forma_s( 'text', 'name', 'Plan name' ),
			forma_s( 'text', 'description', 'Short description' ),
			forma_s( 'text', 'price', 'Price', [ 'instructions' => 'e.g. $219' ] ),
			forma_s( 'text', 'period', 'Billing period', [ 'instructions' => 'e.g. / month' ] ),
			forma_s( 'textarea', 'benefits', 'Benefits', [ 'rows' => 5, 'new_lines' => '', 'instructions' => 'One per line.' ] ),
			forma_s( 'text', 'cta_text', 'CTA text' ),
			forma_s( 'url', 'cta_url', 'CTA URL' ),
			forma_s( 'true_false', 'featured', 'Featured / recommended', [ 'ui' => 1 ] ),
			forma_s( 'text', 'badge', 'Badge text', [ 'default_value' => 'Most popular' ] ),
		], [ 'max' => 3 ] ),
		forma_f_text( 'pricing_footnote', 'Footnote' ),
	] ), 10 );

	$g( 'testimonials', '11 Testimonials & results', [
		forma_f_rep( 'testimonials', 'Testimonials (first = featured quote)', [
			forma_s( 'text', 'name', 'Member name' ),
			forma_s( 'textarea', 'quote', 'Testimonial', [ 'rows' => 3 ] ),
			forma_s( 'text', 'goal', 'Training goal / since' ),
			forma_s( 'number', 'rating', 'Rating (1–5)', [ 'min' => 1, 'max' => 5, 'default_value' => 5 ] ),
		] ),
		forma_f_rep( 'results', 'Result cards', [
			forma_s( 'text', 'value', 'Result value', [ 'instructions' => 'e.g. +45 KG' ] ),
			forma_s( 'text', 'label', 'Result label' ),
			forma_s( 'textarea', 'quote', 'Quote', [ 'rows' => 3 ] ),
			forma_s( 'text', 'name', 'Member name' ),
			forma_s( 'text', 'goal', 'Training goal' ),
		], [ 'max' => 6, 'instructions' => 'Only publish results members have agreed to share. Avoid medical or health-outcome claims.' ] ),
		forma_f_rep( 'proof_stats', 'Supporting statistics', [
			forma_s( 'text', 'value', 'Value' ),
			forma_s( 'text', 'label', 'Label' ),
		], [ 'max' => 4 ] ),
	], 11 );

	$g( 'faq', '12 FAQ', array_merge( forma_head_fields( 'faq' ), [
		forma_f_text( 'faq_cta_text', 'CTA text' ),
		forma_f_url( 'faq_cta_url', 'CTA URL' ),
		forma_f_rep( 'faqs', 'Questions', [
			forma_s( 'text', 'question', 'Question' ),
			forma_s( 'textarea', 'answer', 'Answer', [ 'rows' => 3, 'new_lines' => '' ] ),
		] ),
	] ), 12 );

	$g( 'trial', '13 Trial form', [
		forma_f_img( 'trial_image', 'Trial section image' ),
		forma_f_text( 'trial_eyebrow', 'Eyebrow' ),
		forma_f_area( 'trial_heading', 'Heading', [ 'rows' => 3 ] ),
		forma_f_area( 'trial_lead', 'Lead' ),
		forma_f_text( 'trial_form_title', 'Form title' ),
		forma_f_text( 'trial_form_note', 'Form note' ),
		forma_f_text( 'trial_submit', 'Submit button text' ),
		forma_f_area( 'trial_goals', 'Training goals', [ 'rows' => 4, 'new_lines' => '', 'instructions' => 'One per line.' ] ),
		forma_f_rep( 'trial_times', 'Preferred times', [
			forma_s( 'text', 'label', 'Label' ),
			forma_s( 'text', 'range', 'Range' ),
		] ),
		forma_f_area( 'trial_assurances', 'Assurances', [ 'rows' => 3, 'new_lines' => '', 'instructions' => 'One per line.' ] ),
		forma_f_text( 'trial_success_title', 'Success title' ),
		forma_f_area( 'trial_success_text', 'Success text' ),
	], 13 );

	// Site-wide settings (announcement, footer, contact, business schema).
	if ( function_exists( 'acf_add_options_page' ) ) {
		acf_add_options_page( [ 'page_title' => 'FORMA Settings', 'menu_title' => 'FORMA Settings', 'menu_slug' => 'forma-settings', 'capability' => 'edit_posts', 'icon_url' => 'dashicons-superhero', 'position' => 3 ] );
	}
	acf_add_local_field_group( [
		'key' => 'group_forma_settings', 'title' => 'FORMA · Site settings',
		'fields' => [
			forma_f_bool( 'announce_on', 'Show announcement bar', [ 'default_value' => 1 ] ),
			forma_f_text( 'announce_text', 'Announcement text' ),
			forma_f_text( 'announce_link_text', 'Announcement link text' ),
			forma_f_url( 'announce_link_url', 'Announcement link URL' ),
			forma_f_text( 'nav_login_text', 'Nav secondary link text' ),
			forma_f_url( 'nav_login_url', 'Nav secondary link URL' ),
			forma_f_text( 'nav_cta_text', 'Nav CTA text' ),
			forma_f_url( 'nav_cta_url', 'Nav CTA URL' ),
			forma_f_text( 'footer_newsletter_title', 'Footer newsletter title' ),
			forma_f_area( 'footer_newsletter_text', 'Footer newsletter text' ),
			forma_f_text( 'biz_street', 'Street address' ),
			forma_f_text( 'biz_city', 'City' ),
			forma_f_text( 'biz_region', 'State / region' ),
			forma_f_text( 'biz_zip', 'Postal code' ),
			forma_f_text( 'biz_country', 'Country code', [ 'default_value' => 'US' ] ),
			forma_f_text( 'biz_phone', 'Phone' ),
			forma_f_text( 'biz_email', 'Email' ),
			forma_f_text( 'biz_price_range', 'Price range', [ 'default_value' => '$$' ] ),
			forma_f_rep( 'biz_hours', 'Opening hours', [
				forma_s( 'text', 'label', 'Label', [ 'instructions' => 'e.g. Mon to Fri' ] ),
				forma_s( 'text', 'days', 'Schema days', [ 'instructions' => 'Comma separated: Monday,Tuesday' ] ),
				forma_s( 'text', 'opens', 'Opens (HH:MM)' ),
				forma_s( 'text', 'closes', 'Closes (HH:MM)' ),
				forma_s( 'text', 'display', 'Display text', [ 'instructions' => 'e.g. 5:30 AM to 10 PM' ] ),
			] ),
			forma_f_rep( 'socials', 'Social links', [
				forma_s( 'text', 'label', 'Label (e.g. IG)' ),
				forma_s( 'text', 'name', 'Accessible name (e.g. Instagram)' ),
				forma_s( 'url', 'url', 'URL' ),
			] ),
			forma_f_text( 'footer_copyright', 'Copyright line' ),
		],
		'location' => [ [ [ 'param' => 'options_page', 'operator' => '==', 'value' => 'forma-settings' ] ] ],
	] );
} );
