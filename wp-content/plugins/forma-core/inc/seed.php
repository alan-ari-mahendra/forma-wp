<?php
/**
 * WP-CLI: `wp forma seed --images=<dir>` — idempotent bootstrap.
 * Creates the Home page (Elementor layout of 13 section containers), imports the 15 images, fills SCF fields
 * with the mockup copy (editable afterwards), builds menus, options, and Elementor global colors/fonts.
 */
defined( 'ABSPATH' ) || exit;

class Forma_Seed_Command {

	private $img = [];

	public function __invoke( $args, $assoc ) {
		$dir = rtrim( $assoc['images'] ?? '', '/' );
		$this->import_images( $dir );
		$pid = $this->page();
		$this->fields( $pid );
		$this->options();
		$this->menus();
		$this->elementor_kit();
		$this->elementor_layout( $pid );
		update_option( 'blogdescription', 'Boutique training club in East Austin' );
		update_option( 'blogname', 'FORMA Athletic Club' );
		WP_CLI::success( 'FORMA seeded. Front page ID ' . $pid );
	}

	private function import_images( $dir ) {
		$alts = [
			'forma-hero-athlete-barbell-dark-gym' => 'Athlete mid-lift under a single warm light in a dark gym',
			'forma-intro-coach-squat-cueing' => 'Coach cueing a member on squat form',
			'forma-program-strength-barbell' => 'Members in a barbell strength class',
			'forma-program-conditioning-sled' => 'Athlete pushing a sled on the turf lane',
			'forma-program-boxing-heavy-bag' => 'Boxer working a heavy bag',
			'forma-program-recovery-contrast' => 'Recovery area with sauna and cold plunge',
			'forma-facility-strength-floor' => 'Main strength floor with racks and platforms',
			'forma-facility-turf-lane' => 'Turf lane with sleds',
			'forma-facility-boxing-room' => 'Boxing room with heavy bags',
			'forma-facility-recovery-suite' => 'Sauna and cold plunge recovery suite',
			'forma-coaches-hero-head-coach' => 'Head coach in a wide dramatic shot',
			'forma-coach-marcus-hale' => 'Portrait of coach Marcus Hale',
			'forma-coach-dani-reyes' => 'Portrait of coach Dani Reyes',
			'forma-coach-theo-grant' => 'Portrait of coach Theo Grant',
			'forma-coach-priya-nair' => 'Portrait of coach Priya Nair',
			'forma-trial-group-class' => 'Members finishing a group class together',
		];
		foreach ( $alts as $slug => $alt ) {
			$existing = get_posts( [ 'post_type' => 'attachment', 'name' => $slug, 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'inherit' ] );
			if ( $existing ) {
				$this->img[ $slug ] = (int) $existing[0];
				continue;
			}
			$file = "$dir/$slug.webp";
			if ( ! file_exists( $file ) ) {
				WP_CLI::warning( "Missing $file" );
				continue;
			}
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			$tmp = wp_tempnam( $file );
			copy( $file, $tmp );
			$id = media_handle_sideload( [ 'name' => "$slug.webp", 'tmp_name' => $tmp ], 0, ucwords( str_replace( '-', ' ', $slug ) ) );
			if ( is_wp_error( $id ) ) {
				WP_CLI::warning( $id->get_error_message() );
				continue;
			}
			wp_update_post( [ 'ID' => $id, 'post_name' => $slug ] );
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			$this->img[ $slug ] = $id;
		}
	}

	private function i( $slug ) {
		return $this->img[ $slug ] ?? 0;
	}

	private function page() {
		$id = forma_pid();
		if ( ! $id || 'page' !== get_post_type( $id ) ) {
			$id = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'FORMA Athletic Club', 'post_name' => 'home' ] );
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $id );
		}
		return $id;
	}

	private function fields( $pid ) {
		$u = function ( $k, $v ) use ( $pid ) {
			update_field( 'field_forma_' . $k, $v, $pid );
		};
		$u( 'hero_eyebrow', 'Boutique training club · East Austin' );
		$u( 'hero_heading', 'Train with' );
		$u( 'hero_heading_accent', 'intent.' );
		$u( 'hero_description', 'Coached strength, conditioning and boxing classes capped at 14 people. Real programming, real coaching, no crowded floor.' );
		$u( 'hero_cta1_text', 'Book Free Trial →' ); $u( 'hero_cta1_url', '#trial' );
		$u( 'hero_cta2_text', 'View Schedule' ); $u( 'hero_cta2_url', '#schedule' );
		$u( 'hero_image', $this->i( 'forma-hero-athlete-barbell-dark-gym' ) );
		$u( 'hero_stats', [
			[ 'value' => '4.9', 'label' => '380 Google reviews', 'stars' => 1 ],
			[ 'value' => '1,200+', 'label' => 'Active members' ],
			[ 'value' => '60+', 'label' => 'Coached classes per week' ],
			[ 'value' => '14', 'label' => 'Max people per class' ],
		] );
		$u( 'marquee_text', "Strength\nConditioning\nBoxing\nMobility\nRecovery\nOpen Gym" );

		$u( 'intro_eyebrow', 'The FORMA method' );
		$u( 'intro_heading', "Not a gym.\nA {mark}training club.{/mark}" );
		$u( 'intro_lead', 'Big-box gyms give you equipment and leave you alone. We give you a coach, a plan and a room full of people who show up. Every session is programmed in 6-week blocks so you always know what you are building toward.' );
		$u( 'intro_image', $this->i( 'forma-intro-coach-squat-cueing' ) );
		$u( 'principles', [
			[ 'title' => 'Capped at 14', 'text' => 'Small enough that your coach sees every rep and knows your name by week two.' ],
			[ 'title' => 'Coached, always', 'text' => 'No class runs without a certified coach on the floor. Scaling options for every movement.' ],
			[ 'title' => 'Measured progress', 'text' => 'Benchmark tests every 6 weeks, logged in the app, so progress is something you can see.' ],
		] );

		$u( 'programs_eyebrow', 'Programs' ); $u( 'programs_heading', "Four ways\nto train." );
		$u( 'programs_lead', 'Mix any class on an unlimited membership. Most members combine two strength sessions with one conditioning and one recovery each week.' );
		$u( 'programs', [
			[ 'name' => 'Strength', 'description' => 'Barbell-based progressive overload. Squat, hinge, press and pull, programmed in 6-week blocks with a test week at the end.', 'image' => $this->i( 'forma-program-strength-barbell' ), 'duration' => '60 min', 'level' => 'All', 'intensity' => 4, 'bullets' => "Coached warm-up and movement prep\nMain lift with individual load targets\nAccessory superset and core finisher", 'cta_text' => 'Try a Strength class', 'cta_url' => '#trial' ],
			[ 'name' => 'Conditioning', 'description' => 'Interval-based engine work on rowers, sleds and bikes. Scaled to your pace so everyone finishes the round.', 'image' => $this->i( 'forma-program-conditioning-sled' ), 'duration' => '45 min', 'level' => 'All', 'intensity' => 5, 'bullets' => "Dynamic warm-up\nTimed intervals with coach pacing\nCool-down and breathing reset", 'cta_text' => 'Try a Conditioning class', 'cta_url' => '#trial' ],
			[ 'name' => 'Boxing', 'description' => 'Technique first. Stance, footwork and combinations on the heavy bag, with partner drills once the basics are solid.', 'image' => $this->i( 'forma-program-boxing-heavy-bag' ), 'duration' => '50 min', 'level' => 'Beginner friendly', 'intensity' => 3, 'bullets' => "Shadow boxing and footwork\nBag rounds with coach feedback\nCore finisher", 'cta_text' => 'Try a Boxing class', 'cta_url' => '#trial' ],
			[ 'name' => 'Recovery', 'description' => 'Guided mobility, breathwork and contrast therapy to keep you training week after week.', 'image' => $this->i( 'forma-program-recovery-contrast' ), 'duration' => '40 min', 'level' => 'All', 'intensity' => 1, 'bullets' => "Guided mobility flow\nBreathwork and down-regulation\nSauna and cold plunge access", 'cta_text' => 'Try a Recovery class', 'cta_url' => '#trial' ],
		] );

		$u( 'facilities_eyebrow', 'The space' ); $u( 'facilities_heading', "10,000 sq ft\nbuilt to train." );
		$u( 'facilities_lead', 'Competition-grade equipment, a dedicated boxing room and a recovery suite. Everything you need, nothing you will never use.' );
		$u( 'facilities', [
			[ 'name' => 'Strength floor', 'description' => '12 racks, calibrated plates, platforms', 'image' => $this->i( 'forma-facility-strength-floor' ), 'category' => 'Strength' ],
			[ 'name' => 'Turf lane', 'description' => '25 m turf, sleds, prowlers', 'image' => $this->i( 'forma-facility-turf-lane' ), 'category' => 'Conditioning' ],
			[ 'name' => 'Boxing room', 'description' => '16 heavy bags', 'image' => $this->i( 'forma-facility-boxing-room' ), 'category' => 'Boxing' ],
			[ 'name' => 'Recovery', 'description' => 'Sauna & cold plunge', 'image' => $this->i( 'forma-facility-recovery-suite' ), 'category' => 'Recovery' ],
		] );
		$u( 'facilities_hours', 'Mon to Fri 5:30 AM to 10 PM, Sat to Sun 7 AM to 6 PM' );
		$u( 'facilities_included', 'Towels, lockers, showers, filtered water' );
		$u( 'facilities_parking', 'Free member lot and bike racks' );

		$u( 'schedule_eyebrow', 'Weekly schedule' ); $u( 'schedule_heading', "Find your\nclass." );
		$u( 'schedule_lead', 'Classes run from 5:30 AM. Book up to 7 days ahead in the FORMA app. Spots update in real time.' );
		$u( 'schedule_footnote', 'All classes are coached. Arrive 10 minutes early for your first visit.' );
		$u( 'schedule_download_text', 'Download the full monthly timetable' ); $u( 'schedule_download_url', '#trial' );
		$tpl = [
			[ 'Squat Block, Week 3', '05:30', '06:30', 'Strength', 'Marcus Hale', 3, 14 ],
			[ 'Engine Intervals', '06:30', '07:15', 'Conditioning', 'Dani Reyes', 0, 14, 1 ],
			[ 'Fundamentals', '07:30', '08:20', 'Boxing', 'Theo Grant', 6, 14 ],
			[ 'Press & Pull', '12:00', '13:00', 'Strength', 'Marcus Hale', 8, 14 ],
			[ 'Sled & Row', '17:30', '18:15', 'Conditioning', 'Dani Reyes', 2, 14 ],
			[ 'Combos & Bag Rounds', '18:30', '19:20', 'Boxing', 'Theo Grant', 4, 14 ],
			[ 'Mobility + Contrast', '19:30', '20:10', 'Recovery', 'Priya Nair', 9, 14 ],
		];
		$classes = [];
		foreach ( forma_days() as $di => $day ) {
			foreach ( $tpl as $ti => $t ) {
				if ( $di >= 5 && $ti % 2 ) { // lighter weekend
					continue;
				}
				// shift times slightly on weekends and vary availability by day so filters are visibly data-driven
				$weekend = $di >= 5;
				$spots   = max( 0, $t[5] + ( ( $di + $ti ) % 3 ) - 1 );
				$classes[] = [ 'class_name' => $t[0], 'day' => $day, 'start_time' => $weekend ? sprintf( '%02d:00', 8 + $ti ) : $t[1], 'end_time' => $weekend ? sprintf( '%02d:00', 9 + $ti ) : $t[2], 'program' => $t[3], 'coach' => $t[4], 'spots' => 0 === $di ? $t[5] : $spots, 'max_spots' => $t[6], 'waitlist' => ( 0 === $di && ! empty( $t[7] ) ) ? 1 : 0, 'booking_url' => '#trial' ];
			}
		}
		$u( 'classes', $classes );

		$u( 'coaches_eyebrow', 'Coaching team' ); $u( 'coaches_heading', "Coaches who\nactually {mark}coach.{/mark}" );
		$u( 'coaches_lead', 'Every FORMA coach holds a national certification, completes our in-house coaching program and keeps learning every quarter.' );
		$u( 'coaches_hero_image', $this->i( 'forma-coaches-hero-head-coach' ) );
		$u( 'coaches', [
			[ 'name' => 'Marcus Hale', 'specialty' => 'Head Coach, Strength', 'bio' => 'Builds the strength blocks and runs the coaching program.', 'image' => $this->i( 'forma-coach-marcus-hale' ), 'credentials' => 'NSCA-CSCS, 12 yrs' ],
			[ 'name' => 'Dani Reyes', 'specialty' => 'Conditioning Lead', 'bio' => 'Designs interval sessions that scale to every fitness level.', 'image' => $this->i( 'forma-coach-dani-reyes' ), 'credentials' => 'NASM-CPT, 8 yrs' ],
			[ 'name' => 'Theo Grant', 'specialty' => 'Boxing Coach', 'bio' => 'Teaches boxing fundamentals from stance to combinations.', 'image' => $this->i( 'forma-coach-theo-grant' ), 'credentials' => 'USA Boxing L2, 10 yrs' ],
			[ 'name' => 'Priya Nair', 'specialty' => 'Mobility & Recovery', 'bio' => 'Leads guided mobility and recovery sessions.', 'image' => $this->i( 'forma-coach-priya-nair' ), 'credentials' => 'DPT, FRC' ],
		] );

		$u( 'pricing_eyebrow', 'Membership' ); $u( 'pricing_heading', "Simple\npricing." );
		$u( 'pricing_lead', 'No joining fee. No long contract. Freeze up to 2 months a year, cancel with 30 days notice.' );
		$u( 'promo_title', 'New here? First week {mark}free.{/mark}' );
		$u( 'promo_text', '7 days of unlimited classes, onboarding with a coach, no card required.' );
		$u( 'promo_cta_text', 'Claim free week' ); $u( 'promo_cta_url', '#trial' );
		$u( 'plans', [
			[ 'name' => 'Drop-in', 'description' => 'For travelers and occasional training.', 'price' => '$32', 'period' => '/ class', 'benefits' => "Any single class\nTowel and locker\nValid for 30 days", 'cta_text' => 'Buy drop-in', 'cta_url' => '#trial', 'featured' => 0 ],
			[ 'name' => 'Unlimited', 'description' => 'For members training 3 or more times a week.', 'price' => '$219', 'period' => '/ month', 'benefits' => "Unlimited classes\nRecovery suite access\nBenchmark testing every 6 weeks\n2 guest passes per month", 'cta_text' => 'Start free week', 'cta_url' => '#trial', 'featured' => 1, 'badge' => 'Most popular' ],
			[ 'name' => '8 Classes', 'description' => 'For a steady twice-a-week routine.', 'price' => '$169', 'period' => '/ month', 'benefits' => "8 classes per month\nUnused classes roll over once\nBenchmark testing every 6 weeks", 'cta_text' => 'Choose 8 classes', 'cta_url' => '#trial', 'featured' => 0 ],
		] );
		$u( 'pricing_footnote', 'Student, first responder and corporate rates available. Ask at the front desk.' );

		$u( 'testimonials', [
			[ 'name' => 'Jordan M.', 'quote' => 'I tried four gyms in two years. FORMA is the first one where I never skip.', 'goal' => 'member since 2023', 'rating' => 5 ],
		] );
		$u( 'results', [
			[ 'value' => '+45 KG', 'label' => 'Deadlift in 6 months', 'quote' => 'The 6-week blocks changed everything. I finally stopped guessing what to do and started seeing numbers move.', 'name' => 'Alyssa T.', 'goal' => 'Strength, 4x a week' ],
			[ 'value' => '4x', 'label' => 'Average weekly attendance', 'quote' => 'Small classes meant the coaches noticed when I was missing. That accountability is worth the membership alone.', 'name' => 'Ryan C.', 'goal' => 'Conditioning & Boxing' ],
			[ 'value' => '12 wks', 'label' => 'Training streak', 'quote' => 'Recovery sessions with Priya are the part of my week I protect first. I train harder now and feel better.', 'name' => 'Monica L.', 'goal' => 'Strength & Recovery' ],
		] );
		$u( 'proof_stats', [ [ 'value' => '4.9', 'label' => 'Google' ], [ 'value' => '5.0', 'label' => 'ClassPass' ], [ 'value' => '96%', 'label' => 'Member retention' ] ] );

		$u( 'faq_eyebrow', 'FAQ' ); $u( 'faq_heading', "Before\nyou come in." );
		$u( 'faq_lead', 'Still unsure? Message us and a coach will reply within the hour during opening times.' );
		$u( 'faq_cta_text', 'Contact a coach' ); $u( 'faq_cta_url', '#trial' );
		$u( 'faqs', [
			[ 'question' => 'I have never trained before. Is FORMA right for me?', 'answer' => 'Yes. Every class is coached and every movement has a scaled version. Your first visit includes a 20-minute onboarding with a coach, so you walk into class knowing the basics.' ],
			[ 'question' => 'How does the free trial week work?', 'answer' => 'Book through the form below. You get seven days of unlimited classes and a coach-led onboarding. No card is required and nothing renews automatically.' ],
			[ 'question' => 'How big are the classes?', 'answer' => 'Every class is capped at 14 people so your coach can see every rep.' ],
			[ 'question' => 'Can I freeze or cancel my membership?', 'answer' => 'Yes. You can freeze for up to 2 months a year and cancel with 30 days notice. There is no long contract.' ],
			[ 'question' => 'Is there parking?', 'answer' => 'Yes. There is a free member lot and bike racks at the entrance.' ],
		] );

		$u( 'trial_image', $this->i( 'forma-trial-group-class' ) );
		$u( 'trial_eyebrow', 'Free trial week' ); $u( 'trial_heading', "Your first\nweek is\non us." );
		$u( 'trial_lead', 'Seven days, unlimited classes, a coach-led onboarding. If it is not for you, you walk away. No card, no catch.' );
		$u( 'trial_form_title', 'Book your free week' ); $u( 'trial_form_note', 'A coach will text you within 2 hours to confirm your first class.' );
		$u( 'trial_submit', 'Claim my free week' );
		$u( 'trial_goals', "Get stronger\nLose fat\nLearn boxing" );
		$u( 'trial_times', [ [ 'label' => 'Morning', 'range' => '5:30 to 9 AM' ], [ 'label' => 'Midday', 'range' => '11 AM to 2 PM' ], [ 'label' => 'Evening', 'range' => '5 to 8 PM' ] ] );
		$u( 'trial_assurances', "No card required\nNo auto-renewal\nCancel anytime" );
		$u( 'trial_success_title', 'You are in.' );
		$u( 'trial_success_text', 'Thanks. A coach will text you within 2 hours to confirm your first class.' );
	}

	private function options() {
		$o = function ( $k, $v ) {
			update_field( 'field_forma_' . $k, $v, 'option' );
		};
		$o( 'announce_on', 1 ); $o( 'announce_text', 'Your first week is free · New members only' );
		$o( 'nav_login_text', 'Member Login' ); $o( 'nav_login_url', '#' ); $o( 'nav_cta_text', 'Book Free Trial' ); $o( 'nav_cta_url', '#trial' );
		$o( 'footer_newsletter_title', 'Train smarter, weekly.' ); $o( 'footer_newsletter_text', 'One email a week: programming notes, recovery tips and class updates. No spam.' );
		$o( 'biz_street', '1180 E 6th St' ); $o( 'biz_city', 'Austin' ); $o( 'biz_region', 'TX' ); $o( 'biz_zip', '78702' ); $o( 'biz_country', 'US' );
		$o( 'biz_phone', '(512) 555 0147' ); $o( 'biz_email', 'hello@formaclub.co' ); $o( 'biz_price_range', '$$' );
		$o( 'biz_hours', [
			[ 'label' => 'Mon to Fri', 'days' => 'Monday,Tuesday,Wednesday,Thursday,Friday', 'opens' => '05:30', 'closes' => '22:00', 'display' => '5:30 AM to 10 PM' ],
			[ 'label' => 'Sat to Sun', 'days' => 'Saturday,Sunday', 'opens' => '07:00', 'closes' => '18:00', 'display' => '7 AM to 6 PM' ],
		] );
		$o( 'socials', [ [ 'label' => 'IG', 'name' => 'Instagram', 'url' => 'https://instagram.com/' ], [ 'label' => 'TT', 'name' => 'TikTok', 'url' => 'https://tiktok.com/' ], [ 'label' => 'YT', 'name' => 'YouTube', 'url' => 'https://youtube.com/' ] ] );
		$o( 'footer_copyright', '© 2026 FORMA Athletic Club. All rights reserved.' );
	}

	private function menus() {
		$make = function ( $name, $loc, $items ) {
			$m = wp_get_nav_menu_object( $name );
			if ( ! $m ) {
				$mid = wp_create_nav_menu( $name );
				foreach ( $items as $t => $u ) {
					wp_update_nav_menu_item( $mid, 0, [ 'menu-item-title' => $t, 'menu-item-url' => $u, 'menu-item-status' => 'publish', 'menu-item-type' => 'custom' ] );
				}
			} else {
				$mid = $m->term_id;
			}
			$locs         = get_theme_mod( 'nav_menu_locations', [] );
			$locs[ $loc ] = $mid;
			set_theme_mod( 'nav_menu_locations', $locs );
		};
		$anchors = [ 'Programs' => home_url( '/#programs' ), 'Schedule' => home_url( '/#schedule' ), 'Coaches' => home_url( '/#coaches' ), 'Membership' => home_url( '/#membership' ), 'FAQ' => home_url( '/#faq' ) ];
		$make( 'FORMA Primary', 'forma-primary', $anchors );
		$make( 'FORMA Footer Club', 'forma-club', $anchors );
		$make( 'FORMA Footer Members', 'forma-members', [ 'Member login' => '#', 'Download app' => '#', 'Gift cards' => '#', 'Careers' => '#' ] );
	}

	private function elementor_kit() {
		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}
		$kit = (int) get_option( 'elementor_active_kit' );
		if ( ! $kit ) {
			return;
		}
		$s = get_post_meta( $kit, '_elementor_page_settings', true );
		$s = is_array( $s ) ? $s : [];
		$c = [ 'light' => '#F1F0EC', 'dark' => '#0F0F0F', 'ink' => '#111111', 'ink-2' => '#4A4945', 'muted' => '#6B6A65', 'line' => '#DCDAD4', 'volt' => '#D4FF3F', 'volt-ink' => '#2D3311', 'alert' => '#B3261E' ];
		$colors = [];
		foreach ( $c as $k => $hex ) {
			$colors[] = [ '_id' => 'forma' . substr( md5( $k ), 0, 4 ), 'title' => 'FORMA ' . $k, 'color' => $hex ];
		}
		$s['custom_colors']      = $colors;
		$s['system_colors']      = [ [ '_id' => 'primary', 'title' => 'Primary', 'color' => '#111111' ], [ '_id' => 'secondary', 'title' => 'Secondary', 'color' => '#4A4945' ], [ '_id' => 'text', 'title' => 'Text', 'color' => '#111111' ], [ '_id' => 'accent', 'title' => 'Accent', 'color' => '#D4FF3F' ] ];
		$s['system_typography']  = [
			[ '_id' => 'primary', 'title' => 'Headings', 'typography_typography' => 'custom', 'typography_font_family' => 'Barlow Condensed', 'typography_font_weight' => '800', 'typography_text_transform' => 'uppercase' ],
			[ '_id' => 'secondary', 'title' => 'Subheads', 'typography_typography' => 'custom', 'typography_font_family' => 'Barlow Condensed', 'typography_font_weight' => '700', 'typography_text_transform' => 'uppercase' ],
			[ '_id' => 'text', 'title' => 'Body', 'typography_typography' => 'custom', 'typography_font_family' => 'Barlow', 'typography_font_weight' => '400' ],
			[ '_id' => 'accent', 'title' => 'Accent', 'typography_typography' => 'custom', 'typography_font_family' => 'Barlow', 'typography_font_weight' => '600' ],
		];
		$s['container_width']    = [ 'unit' => 'px', 'size' => 1240 ];
		update_post_meta( $kit, '_elementor_page_settings', $s );
	}

	private function elementor_layout( $pid ) {
		$secs = [
			'hero' => 'hero', 'marquee' => 'marquee', 'intro' => 'introduction', 'programs' => 'programs', 'facilities' => 'facilities',
			'schedule' => 'class-schedule', 'coaches' => 'coaches', 'pricing' => 'pricing', 'testimonials' => 'testimonials', 'faq' => 'faq', 'trial' => 'trial-form',
		];
		$els = [];
		foreach ( $secs as $sc => $css ) {
			$els[] = [
				'id' => substr( md5( $sc ), 0, 7 ), 'elType' => 'container', 'isInner' => false,
				'settings' => [ 'content_width' => 'full', 'flex_direction' => 'column', 'flex_gap' => [ 'size' => 0, 'unit' => 'px', 'column' => '0', 'row' => '0', 'isLinked' => true ], 'padding' => [ 'unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true ], '_css_classes' => 'forma-section forma-' . $css ],
				'elements' => [ [ 'id' => substr( md5( 'w' . $sc ), 0, 7 ), 'elType' => 'widget', 'widgetType' => 'shortcode', 'settings' => [ 'shortcode' => '[forma_' . $sc . ']' ], 'elements' => [] ] ],
			];
		}
		update_post_meta( $pid, '_elementor_data', wp_slash( wp_json_encode( $els ) ) );
		update_post_meta( $pid, '_elementor_edit_mode', 'builder' );
		update_post_meta( $pid, '_elementor_template_type', 'wp-page' );
		update_post_meta( $pid, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.0.0' );
		update_post_meta( $pid, '_wp_page_template', 'elementor_header_footer' );
		if ( class_exists( '\Elementor\Plugin' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}
}
WP_CLI::add_command( 'forma seed', 'Forma_Seed_Command' );
