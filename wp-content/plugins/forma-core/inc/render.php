<?php
/**
 * Section renderers. Each is a shortcode so the Elementor page keeps one flat Shortcode widget per section
 * while every word, image and number comes from SCF.
 */
defined( 'ABSPATH' ) || exit;

function forma_eyebrow( $text ) {
	return $text ? '<p class="eyebrow label">' . esc_html( $text ) . '</p>' : '';
}

function forma_sec_head( $p, $default_h2 = true ) {
	$h = forma_f( $p . '_heading' );
	$l = forma_f( $p . '_lead' );
	return '<div class="sec-head"><div>' . forma_eyebrow( forma_f( $p . '_eyebrow' ) ) . '<h2 class="disp">' . forma_rich( $h ) . '</h2></div>' . ( $l ? '<p>' . esc_html( $l ) . '</p>' : '' ) . '</div>';
}

function forma_btn( $text, $url, $class = 'btn-volt', $extra = '' ) {
	if ( ! $text ) {
		return '';
	}
	return '<a href="' . esc_url( $url ?: '#trial' ) . '" class="btn ' . esc_attr( $class ) . '" ' . $extra . '>' . esc_html( $text ) . '</a>';
}

/* ---------- 3 Hero ---------- */
add_shortcode( 'forma_hero', function () {
	$stats = '';
	foreach ( forma_rows( 'hero_stats' ) as $s ) {
		$stats .= '<div class="hero-stat"><b>' . esc_html( $s['value'] ) . '</b><small>' . ( ! empty( $s['stars'] ) ? '<span class="stars" aria-hidden="true">★★★★★</span> ' : '' ) . esc_html( $s['label'] ) . '</small></div>';
	}
	ob_start(); ?>
	<section class="hero dk" id="top" aria-labelledby="hero-title">
		<div class="hero-media"><?php echo forma_img( forma_f( 'hero_image' ), 'forma-hero', '', true, '100vw' ); ?></div>
		<div class="hero-shade" aria-hidden="true"></div>
		<div class="wrap hero-inner">
			<p class="label hero-eyebrow"><?php echo esc_html( forma_f( 'hero_eyebrow' ) ); ?></p>
			<h1 class="disp" id="hero-title"><?php echo esc_html( forma_f( 'hero_heading' ) ); ?> <em><?php echo esc_html( forma_f( 'hero_heading_accent' ) ); ?></em></h1>
			<div class="hero-row">
				<p class="hero-desc"><?php echo esc_html( forma_f( 'hero_description' ) ); ?></p>
				<div class="hero-cta">
					<?php echo forma_btn( forma_f( 'hero_cta1_text' ), forma_f( 'hero_cta1_url' ), 'btn-volt' ); // phpcs:ignore ?>
					<?php echo forma_btn( forma_f( 'hero_cta2_text' ), forma_f( 'hero_cta2_url' ), 'btn-line-l' ); // phpcs:ignore ?>
				</div>
			</div>
			<div class="hero-stats"><?php echo $stats; // phpcs:ignore ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 4 Marquee ---------- */
add_shortcode( 'forma_marquee', function () {
	$items = forma_lines( forma_f( 'marquee_text' ) );
	if ( ! $items ) {
		return '';
	}
	$html = '';
	foreach ( $items as $i ) {
		$html .= '<span>' . esc_html( $i ) . ' ◆</span>';
	}
	return '<div class="marquee" aria-hidden="true"><div class="marquee-track"><div class="marquee-set">' . $html . '</div><div class="marquee-set">' . $html . '</div></div></div>';
} );

/* ---------- 5 Intro ---------- */
add_shortcode( 'forma_intro', function () {
	$pr = '';
	$n  = 1;
	foreach ( forma_rows( 'principles' ) as $p ) {
		$pr .= '<li><i>' . sprintf( '%02d', $n++ ) . '</i><h3>' . esc_html( $p['title'] ) . '</h3><p>' . esc_html( $p['text'] ) . '</p></li>';
	}
	ob_start(); ?>
	<section class="sec intro" aria-labelledby="intro-title">
		<div class="wrap">
			<div class="intro-grid">
				<div>
					<?php echo forma_eyebrow( forma_f( 'intro_eyebrow' ) ); // phpcs:ignore ?>
					<h2 class="disp" id="intro-title"><?php echo forma_rich( forma_f( 'intro_heading' ) ); // phpcs:ignore ?></h2>
					<p class="intro-lead"><?php echo esc_html( forma_f( 'intro_lead' ) ); ?></p>
				</div>
				<div class="intro-img"><?php echo forma_img( forma_f( 'intro_image' ), 'forma-card', '', false, '(max-width:1080px) 90vw, 500px' ); // phpcs:ignore ?></div>
			</div>
			<ul class="principles"><?php echo $pr; // phpcs:ignore ?></ul>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 6 Programs (tabs) ---------- */
add_shortcode( 'forma_programs', function () {
	$progs = forma_rows( 'programs' );
	if ( ! $progs ) {
		return '';
	}
	$tabs = $panels = '';
	foreach ( $progs as $i => $p ) {
		$on    = 0 === $i;
		$slug  = sanitize_title( $p['name'] );
		$tabs .= sprintf( '<button type="button" role="tab" class="tab%s" id="tab-%s" aria-controls="panel-%s" aria-selected="%s" tabindex="%s">%s</button>', $on ? ' on' : '', esc_attr( $slug ), esc_attr( $slug ), $on ? 'true' : 'false', $on ? '0' : '-1', esc_html( $p['name'] ) );
		$bars = '';
		for ( $b = 1; $b <= 5; $b++ ) {
			$bars .= '<span class="bar' . ( $b <= (int) $p['intensity'] ? ' on' : '' ) . '"></span>';
		}
		$bul = '';
		foreach ( forma_lines( $p['bullets'] ) as $l ) {
			$bul .= '<li class="li">' . esc_html( $l ) . '</li>';
		}
		$panels .= '<div role="tabpanel" class="panel" id="panel-' . esc_attr( $slug ) . '" aria-labelledby="tab-' . esc_attr( $slug ) . '"' . ( $on ? '' : ' hidden' ) . '>'
			. '<div class="panel-img">' . forma_img( $p['image'], 'forma-wide', '', false, '(max-width:1080px) 100vw, 680px' ) . '</div>'
			. '<div class="panel-body"><h3 class="disp">' . esc_html( $p['name'] ) . '</h3>'
			. '<div class="meta"><div><small>Duration</small><b>' . esc_html( $p['duration'] ) . '</b></div><div><small>Level</small><b>' . esc_html( $p['level'] ) . '</b></div>'
			. '<div><small>Intensity</small><div class="bars" role="img" aria-label="Intensity ' . (int) $p['intensity'] . ' of 5">' . $bars . '</div></div></div>'
			. '<p>' . esc_html( $p['description'] ) . '</p><ul class="bullets">' . $bul . '</ul>'
			. forma_btn( $p['cta_text'], $p['cta_url'], 'btn-dark' ) . '</div></div>';
	}
	return '<section class="sec sec-white" id="programs" aria-labelledby="programs-title"><div class="wrap">' . str_replace( '<h2 class="disp">', '<h2 class="disp" id="programs-title">', forma_sec_head( 'programs' ) )
		. '<div class="tabs" data-forma-tabs><div class="tablist" role="tablist" aria-label="Programs">' . $tabs . '</div><div class="panels">' . $panels . '</div></div></div></section>';
} );

/* ---------- 7 Facilities (bento) ---------- */
add_shortcode( 'forma_facilities', function () {
	$tiles = '';
	foreach ( forma_rows( 'facilities' ) as $i => $f ) {
		$tiles .= '<figure class="tile tile-' . ( $i + 1 ) . '">' . forma_img( $f['image'], 'forma-wide', '', false, '(max-width:720px) 100vw, 50vw' )
			. '<figcaption class="cap"><div><h3>' . esc_html( $f['name'] ) . '</h3><small>' . esc_html( $f['description'] ) . '</small></div><span class="num">' . sprintf( '%02d', $i + 1 ) . '</span></figcaption></figure>';
	}
	$info = '';
	foreach ( [ 'Open' => 'facilities_hours', 'Included' => 'facilities_included', 'Parking' => 'facilities_parking' ] as $l => $k ) {
		if ( forma_f( $k ) ) {
			$info .= '<span><b>' . esc_html( $l ) . '</b> ' . esc_html( forma_f( $k ) ) . '</span>';
		}
	}
	return '<section class="sec sec-dark dk" aria-labelledby="facilities-title"><div class="wrap">' . str_replace( '<h2 class="disp">', '<h2 class="disp" id="facilities-title">', forma_sec_head( 'facilities' ) ) . '<div class="bento">' . $tiles . '</div><div class="infostrip">' . $info . '</div></div></section>';
} );

/* ---------- 8 Schedule (filterable) ---------- */
add_shortcode( 'forma_schedule', function () {
	$rows = forma_rows( 'classes' );
	$days = forma_days();
	$tz   = wp_timezone();
	$mon  = new DateTimeImmutable( 'monday this week', $tz );
	$today = (int) ( new DateTimeImmutable( 'now', $tz ) )->format( 'N' ) - 1;
	usort( $rows, function ( $a, $b ) use ( $days ) {
		return [ array_search( $a['day'], $days, true ), $a['start_time'] ] <=> [ array_search( $b['day'], $days, true ), $b['start_time'] ];
	} );
	$progs = [];
	foreach ( forma_rows( 'programs' ) as $p ) {
		$progs[] = $p['name'];
	}
	$day_btns = '';
	foreach ( $days as $i => $d ) {
		$day_btns .= '<button type="button" class="day' . ( $i === $today ? ' on' : '' ) . '" data-day="' . esc_attr( $d ) . '" aria-pressed="' . ( $i === $today ? 'true' : 'false' ) . '"><small>' . esc_html( $d ) . '</small><span>' . esc_html( $mon->modify( "+$i day" )->format( 'j' ) ) . '</span></button>';
	}
	$chips = '<button type="button" class="chip on" data-program="all" aria-pressed="true">All</button>';
	foreach ( $progs as $p ) {
		$chips .= '<button type="button" class="chip" data-program="' . esc_attr( sanitize_title( $p ) ) . '" aria-pressed="false">' . esc_html( $p ) . '</button>';
	}
	$out = '';
	foreach ( $rows as $r ) {
		$st    = forma_status( $r );
		$t     = forma_time_parts( $r['start_time'] );
		$e     = forma_time_parts( $r['end_time'] );
		$mins  = ( $t['ts'] && $e['ts'] ) ? max( 0, (int) round( ( $e['ts'] - $t['ts'] ) / 60 ) ) : 0;
		$left  = (int) $r['spots'];
		$label = 'full' === $st ? __( 'Full', 'forma' ) : sprintf( __( '%d left', 'forma' ), $left );
		$note  = 'full' === $st ? __( 'Waitlist only', 'forma' ) : ( 'almost' === $st ? __( 'Almost full', 'forma' ) : __( 'Spots available', 'forma' ) );
		$cta   = 'full' === $st ? __( 'Waitlist', 'forma' ) : __( 'Book', 'forma' );
		$url   = $r['booking_url'] ?: '#trial';
		$out  .= sprintf(
			'<li class="row" data-day="%s" data-program="%s" data-status="%s" data-spots="%d" data-max="%d"><div class="time">%s<small>%s%s</small></div><div class="cls"><b>%s</b><span class="tag">%s</span></div><div class="coach">%s</div><div class="spots %s"><b>%s</b><span class="spots-note"> %s</span></div><a href="%s" class="btn %s" aria-label="%s">%s</a></li>',
			esc_attr( $r['day'] ), esc_attr( sanitize_title( $r['program'] ) ), esc_attr( $st ), $left, (int) $r['max_spots'],
			esc_html( $t['time'] ), esc_html( $t['ampm'] ), $mins ? ' · ' . (int) $mins . ' min' : '',
			esc_html( $r['class_name'] ), esc_html( $r['program'] ), esc_html( $r['coach'] ),
			'almost' === $st ? 'low' : '', esc_html( $label ), esc_html( $note ),
			esc_url( $url ), 'full' === $st ? 'btn-ghost' : 'btn-volt',
			esc_attr( $cta . ': ' . $r['class_name'] . ' ' . $r['day'] . ' ' . $t['time'] . $t['ampm'] ), esc_html( $cta )
		);
	}
	ob_start(); ?>
	<section class="sec" id="schedule" aria-labelledby="schedule-title">
		<div class="wrap" data-forma-schedule>
			<?php echo str_replace( '<h2 class="disp">', '<h2 class="disp" id="schedule-title">', forma_sec_head( 'schedule' ) ); // phpcs:ignore ?>
			<div class="filters">
				<div class="days" role="group" aria-label="Filter by day"><?php echo $day_btns; // phpcs:ignore ?></div>
				<div class="chips" role="group" aria-label="Filter by program"><?php echo $chips; // phpcs:ignore ?></div>
			</div>
			<div class="sched">
				<div class="row row-head" aria-hidden="true"><span>Time</span><span>Class</span><span>Coach</span><span>Spots</span><span></span></div>
				<ul class="sched-list"><?php echo $out; // phpcs:ignore ?></ul>
				<p class="sched-empty" role="status" hidden>No classes match this day and program. Try another day.</p>
			</div>
			<div class="sched-foot"><span><?php echo esc_html( forma_f( 'schedule_footnote' ) ); ?></span>
				<?php if ( forma_f( 'schedule_download_text' ) ) : ?><a href="<?php echo esc_url( forma_f( 'schedule_download_url', '#trial' ) ); ?>"><?php echo esc_html( forma_f( 'schedule_download_text' ) ); ?></a><?php endif; ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 9 Coaches ---------- */
add_shortcode( 'forma_coaches', function () {
	$cards = '';
	foreach ( forma_rows( 'coaches' ) as $c ) {
		$cr = '';
		foreach ( array_filter( array_map( 'trim', explode( ',', (string) $c['credentials'] ) ) ) as $x ) {
			$cr .= '<li class="cred">' . esc_html( $x ) . '</li>';
		}
		$cards .= '<article class="ccard"><div class="ccard-img">' . forma_img( $c['image'], 'forma-card', '', false, '(max-width:720px) 90vw, (max-width:1080px) 45vw, 300px' ) . '</div><div class="ccard-body"><h3>' . esc_html( $c['name'] ) . '</h3><p class="role">' . esc_html( $c['specialty'] ) . '</p>'
			. ( $c['bio'] ? '<p class="bio">' . esc_html( $c['bio'] ) . '</p>' : '' ) . '<ul class="creds">' . $cr . '</ul></div></article>';
	}
	ob_start(); ?>
	<section class="coaches dk" id="coaches" aria-labelledby="coaches-title">
		<div class="coaches-hero">
			<div class="coaches-media"><?php echo forma_img( forma_f( 'coaches_image' ), 'forma-hero', '', false, '100vw' ); // phpcs:ignore ?></div>
			<div class="coaches-shade" aria-hidden="true"></div>
			<div class="wrap coaches-copy">
				<?php echo forma_eyebrow( forma_f( 'coaches_eyebrow' ) ); // phpcs:ignore ?>
				<h2 class="disp" id="coaches-title"><?php echo forma_rich( forma_f( 'coaches_heading' ) ); // phpcs:ignore ?></h2>
				<p><?php echo esc_html( forma_f( 'coaches_lead' ) ); ?></p>
			</div>
		</div>
		<div class="wrap coaches-grid"><?php echo $cards; // phpcs:ignore ?></div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 10 Pricing ---------- */
add_shortcode( 'forma_pricing', function () {
	$check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M5 12l5 5 9-10"/></svg>';
	$plans = '';
	foreach ( forma_rows( 'plans' ) as $p ) {
		$f  = ! empty( $p['featured'] );
		$li = '';
		foreach ( forma_lines( $p['benefits'] ) as $b ) {
			$li .= '<li>' . $check . esc_html( $b ) . '</li>';
		}
		$plans .= '<article class="plan' . ( $f ? ' plan-featured' : '' ) . '">' . ( $f && $p['badge'] ? '<span class="badge">' . esc_html( $p['badge'] ) . '</span>' : '' )
			. '<h3>' . esc_html( $p['name'] ) . '</h3><p class="desc">' . esc_html( $p['description'] ) . '</p><div class="price"><b>' . esc_html( $p['price'] ) . '</b><small>' . esc_html( $p['period'] ) . '</small></div>'
			. '<ul>' . $li . '</ul>' . forma_btn( $p['cta_text'], $p['cta_url'], $f ? 'btn-dark' : 'btn-line' ) . '</article>';
	}
	ob_start(); ?>
	<section class="sec" id="membership" aria-labelledby="pricing-title">
		<div class="wrap">
			<?php echo str_replace( '<h2 class="disp">', '<h2 class="disp" id="pricing-title">', forma_sec_head( 'pricing' ) ); // phpcs:ignore ?>
			<?php if ( forma_f( 'promo_title' ) ) : ?>
			<div class="promo dk"><div><h3><?php echo forma_rich( forma_f( 'promo_title' ) ); // phpcs:ignore ?></h3><p><?php echo esc_html( forma_f( 'promo_text' ) ); ?></p></div>
				<?php echo forma_btn( forma_f( 'promo_cta_text' ), forma_f( 'promo_cta_url' ), 'btn-volt' ); // phpcs:ignore ?></div>
			<?php endif; ?>
			<div class="plans"><?php echo $plans; // phpcs:ignore ?></div>
			<p class="foot-note"><?php echo esc_html( forma_f( 'pricing_footnote' ) ); ?></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 11 Testimonials & results ---------- */
add_shortcode( 'forma_testimonials', function () {
	$t   = forma_rows( 'testimonials' );
	$res = forma_rows( 'results' );
	$st  = forma_rows( 'proof_stats' );
	$feat = $t[0] ?? null;
	$stars = function ( $n ) {
		return '<span class="stars" role="img" aria-label="' . (int) $n . ' out of 5 stars">' . str_repeat( '★', max( 0, min( 5, (int) $n ) ) ) . '</span>';
	};
	ob_start(); ?>
	<section class="sec sec-dark dk testi" aria-label="Member testimonials and results">
		<div class="wrap">
			<?php if ( $feat ) : ?>
			<figure class="feature-quote">
				<?php echo $stars( $feat['rating'] ); // phpcs:ignore ?>
				<blockquote class="disp">“<?php echo esc_html( $feat['quote'] ); ?>”</blockquote>
				<figcaption><?php echo esc_html( $feat['name'] . ( $feat['goal'] ? ', ' . $feat['goal'] : '' ) ); ?></figcaption>
			</figure>
			<?php endif; ?>
			<div class="results">
				<?php foreach ( $res as $r ) : ?>
				<article class="res"><div class="stat"><b><?php echo esc_html( $r['value'] ); ?></b><span><?php echo esc_html( $r['label'] ); ?></span></div>
					<p><?php echo esc_html( $r['quote'] ); ?></p>
					<div class="who"><span class="avatar" aria-hidden="true"><?php echo esc_html( mb_substr( $r['name'], 0, 1 ) ); ?></span><div><b><?php echo esc_html( $r['name'] ); ?></b><small><?php echo esc_html( $r['goal'] ); ?></small></div></div></article>
				<?php endforeach; ?>
			</div>
			<?php if ( count( $t ) > 1 ) : ?>
			<ul class="more-quotes"><?php foreach ( array_slice( $t, 1 ) as $q ) : ?>
				<li><?php echo $stars( $q['rating'] ); // phpcs:ignore ?><p>“<?php echo esc_html( $q['quote'] ); ?>”</p><small><?php echo esc_html( $q['name'] . ( $q['goal'] ? ' · ' . $q['goal'] : '' ) ); ?></small></li>
			<?php endforeach; ?></ul>
			<?php endif; ?>
			<?php if ( $st ) : ?><dl class="proof"><?php foreach ( $st as $s ) : ?><div><dt class="label"><?php echo esc_html( $s['label'] ); ?></dt><dd><?php echo esc_html( $s['value'] ); ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 12 FAQ ---------- */
add_shortcode( 'forma_faq', function () {
	$items = '';
	foreach ( forma_rows( 'faqs' ) as $i => $f ) {
		$items .= '<div class="faq-item"><h3 class="faq-h"><button type="button" class="faq-q" id="faq-q-' . $i . '" aria-expanded="false" aria-controls="faq-a-' . $i . '"><span>' . esc_html( $f['question'] ) . '</span><i aria-hidden="true">+</i></button></h3>'
			. '<div class="faq-a" id="faq-a-' . $i . '" role="region" aria-labelledby="faq-q-' . $i . '" hidden><p>' . esc_html( $f['answer'] ) . '</p></div></div>';
	}
	ob_start(); ?>
	<section class="sec" id="faq" aria-labelledby="faq-title">
		<div class="wrap faq-grid">
			<div>
				<?php echo forma_eyebrow( forma_f( 'faq_eyebrow' ) ); // phpcs:ignore ?>
				<h2 class="disp" id="faq-title"><?php echo forma_rich( forma_f( 'faq_heading' ) ); // phpcs:ignore ?></h2>
				<p class="faq-lead"><?php echo esc_html( forma_f( 'faq_lead' ) ); ?></p>
				<?php echo forma_btn( forma_f( 'faq_cta_text' ), forma_f( 'faq_cta_url' ), 'btn-line' ); // phpcs:ignore ?>
			</div>
			<div class="faq-list" data-forma-faq><?php echo $items; // phpcs:ignore ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );

/* ---------- 13 Trial form ---------- */
add_shortcode( 'forma_trial', function () {
	$goals = forma_lines( forma_f( 'trial_goals' ) );
	$opts  = '<option value="">Select a goal</option>';
	foreach ( $goals as $g ) {
		$opts .= '<option>' . esc_html( $g ) . '</option>';
	}
	$times = '';
	foreach ( forma_rows( 'trial_times' ) as $i => $t ) {
		$times .= '<label class="tbtn"><input type="radio" name="time" value="' . esc_attr( $t['label'] ) . '" required' . ( 0 === $i ? ' checked' : '' ) . '><span>' . esc_html( $t['label'] ) . '<small>' . esc_html( $t['range'] ) . '</small></span></label>';
	}
	$as = '';
	foreach ( forma_lines( forma_f( 'trial_assurances' ) ) as $a ) {
		$as .= '<span>✓ ' . esc_html( $a ) . '</span>';
	}
	ob_start(); ?>
	<section class="trial" id="trial" aria-labelledby="trial-title">
		<div class="trial-media dk">
			<?php echo forma_img( forma_f( 'trial_image' ), 'forma-card', '', false, '(max-width:1080px) 100vw, 50vw' ); // phpcs:ignore ?>
			<div class="trial-shade" aria-hidden="true"></div>
			<div class="trial-copy"><p class="label"><?php echo esc_html( forma_f( 'trial_eyebrow' ) ); ?></p><h2 class="disp" id="trial-title"><?php echo forma_rich( forma_f( 'trial_heading' ) ); // phpcs:ignore ?></h2><p><?php echo esc_html( forma_f( 'trial_lead' ) ); ?></p></div>
		</div>
		<div class="trial-form-wrap">
			<form class="trial-form" data-forma-form novalidate action="#" method="post">
				<h3><?php echo esc_html( forma_f( 'trial_form_title' ) ); ?></h3>
				<p class="form-note"><?php echo esc_html( forma_f( 'trial_form_note' ) ); ?></p>
				<div class="form-error" role="alert" hidden></div>
				<div class="grid2">
					<div class="fld"><label for="fm-first">First name</label><input id="fm-first" name="first_name" autocomplete="given-name" required aria-describedby="fm-first-err"><span class="err" id="fm-first-err"></span></div>
					<div class="fld"><label for="fm-last">Last name</label><input id="fm-last" name="last_name" autocomplete="family-name" required aria-describedby="fm-last-err"><span class="err" id="fm-last-err"></span></div>
				</div>
				<div class="grid2">
					<div class="fld"><label for="fm-email">Email</label><input id="fm-email" name="email" type="email" autocomplete="email" required aria-describedby="fm-email-err"><span class="err" id="fm-email-err"></span></div>
					<div class="fld"><label for="fm-phone">Phone</label><input id="fm-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" required aria-describedby="fm-phone-err"><span class="err" id="fm-phone-err"></span></div>
				</div>
				<div class="fld"><label for="fm-goal">Main goal</label><select id="fm-goal" name="goal" required aria-describedby="fm-goal-err"><?php echo $opts; // phpcs:ignore ?></select><span class="err" id="fm-goal-err"></span></div>
				<fieldset class="fld"><legend>Preferred time</legend><div class="times"><?php echo $times; // phpcs:ignore ?></div></fieldset>
				<div class="hp" aria-hidden="true"><label>Leave empty <input name="website" tabindex="-1" autocomplete="off"></label></div>
				<button type="submit" class="btn btn-dark btn-xl"><?php echo esc_html( forma_f( 'trial_submit', 'Claim my free week' ) ); ?> →</button>
				<div class="assure"><?php echo $as; // phpcs:ignore ?></div>
			</form>
			<div class="trial-success" role="status" tabindex="-1" hidden>
				<h3><?php echo esc_html( forma_f( 'trial_success_title', 'You are in.' ) ); ?></h3>
				<p><?php echo esc_html( forma_f( 'trial_success_text' ) ); ?></p>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
} );
