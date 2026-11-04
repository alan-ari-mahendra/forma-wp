<?php
/**
 * Announcement bar, header and footer.
 * Preferred: Elementor Theme Builder templates (Pro Elements). If no header/footer template targets the page,
 * these PHP fallbacks render so the site is complete either way. Content comes from WP menus + FORMA Settings.
 */
defined( 'ABSPATH' ) || exit;

function forma_has_tb( $location ) {
	if ( ! class_exists( '\ElementorPro\Plugin' ) ) {
		return false;
	}
	try {
		$m = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' );
		$d = $m ? $m->get_conditions_manager()->get_documents_for_location( $location ) : [];
		return ! empty( $d );
	} catch ( \Throwable $e ) {
		return false;
	}
}

function forma_use_shell( $location ) {
	return ! is_admin() && ! forma_has_tb( $location );
}

add_filter( 'hello_elementor_header_footer', function ( $show ) {
	return ( forma_use_shell( 'header' ) || forma_use_shell( 'footer' ) ) ? false : $show;
} );

function forma_wordmark( $class = '' ) {
	return '<a class="wordmark ' . esc_attr( $class ) . '" href="' . esc_url( home_url( '/' ) ) . '" aria-label="FORMA Athletic Club, home"><span class="wm-mark" aria-hidden="true"></span>FORMA</a>';
}

add_action( 'wp_body_open', function () {
	if ( forma_opt( 'announce_on', 1 ) && forma_opt( 'announce_text' ) && ! forma_has_tb( 'header' ) ) {
		$l = forma_opt( 'announce_link_text' );
		echo '<div class="announce">' . esc_html( forma_opt( 'announce_text' ) );
		if ( $l ) {
			echo ' · <a href="' . esc_url( forma_opt( 'announce_link_url', '#trial' ) ) . '">' . esc_html( $l ) . '</a>';
		}
		echo '</div>';
	}
	if ( ! forma_use_shell( 'header' ) ) {
		return;
	}
	$menu = wp_nav_menu( [ 'theme_location' => 'forma-primary', 'container' => false, 'menu_class' => 'nav-list', 'fallback_cb' => false, 'echo' => false ] );
	if ( ! $menu ) {
		$menu = '<ul class="nav-list">';
		foreach ( [ 'Programs' => '#programs', 'Schedule' => '#schedule', 'Coaches' => '#coaches', 'Membership' => '#membership', 'FAQ' => '#faq' ] as $t => $h ) {
			$menu .= '<li><a href="' . esc_url( is_front_page() ? $h : home_url( '/' . $h ) ) . '">' . esc_html( $t ) . '</a></li>';
		}
		$menu .= '</ul>';
	}
	?>
	<header class="site-nav" id="site-nav">
		<div class="wrap nav-inner">
			<?php echo forma_wordmark(); // phpcs:ignore ?>
			<button type="button" class="nav-toggle" aria-expanded="false" aria-controls="site-nav-panel"><span class="screen-reader-text">Menu</span><i aria-hidden="true"></i></button>
			<div class="nav-panel" id="site-nav-panel">
				<nav aria-label="Primary"><?php echo $menu; // phpcs:ignore ?></nav>
				<div class="nav-actions">
					<?php if ( forma_opt( 'nav_login_text' ) ) : ?><a class="nav-login" href="<?php echo esc_url( forma_opt( 'nav_login_url', '#' ) ); ?>"><?php echo esc_html( forma_opt( 'nav_login_text' ) ); ?></a><?php endif; ?>
					<?php echo forma_btn( forma_opt( 'nav_cta_text', 'Book Free Trial' ), forma_opt( 'nav_cta_url', '#trial' ), 'btn-volt btn-sm' ); // phpcs:ignore ?>
				</div>
			</div>
		</div>
	</header>
	<?php
}, 5 );

add_action( 'wp_footer', function () {
	if ( ! forma_use_shell( 'footer' ) ) {
		return;
	}
	$menu = function ( $loc ) {
		return wp_nav_menu( [ 'theme_location' => $loc, 'container' => false, 'menu_class' => 'flist', 'fallback_cb' => false, 'echo' => false ] );
	};
	$street = forma_opt( 'biz_street' );
	$addr   = trim( $street . ( forma_opt( 'biz_city' ) ? ', ' . forma_opt( 'biz_city' ) : '' ) . ( forma_opt( 'biz_region' ) ? ', ' . forma_opt( 'biz_region' ) : '' ) . ' ' . forma_opt( 'biz_zip' ) );
	$social = '';
	foreach ( (array) forma_opt( 'socials', [] ) as $s ) {
		$social .= '<a href="' . esc_url( $s['url'] ) . '" aria-label="' . esc_attr( $s['name'] ) . '" rel="noopener" target="_blank">' . esc_html( $s['label'] ) . '</a>';
	}
	$hours = '';
	foreach ( (array) forma_opt( 'biz_hours', [] ) as $h ) {
		$hours .= '<li>' . esc_html( $h['label'] . ' ' . $h['display'] ) . '</li>';
	}
	?>
	<footer class="site-footer dk" id="site-footer">
		<div class="foot-inner">
			<div class="foot-grid">
				<div>
					<h2 class="foot-news"><?php echo esc_html( forma_opt( 'footer_newsletter_title' ) ); ?></h2>
					<p><?php echo esc_html( forma_opt( 'footer_newsletter_text' ) ); ?></p>
					<form class="news" data-forma-news novalidate>
						<label class="screen-reader-text" for="news-email">Email address</label>
						<input id="news-email" name="email" type="email" placeholder="Your email" autocomplete="email" required>
						<button type="submit" class="btn btn-volt">Join</button>
						<span class="news-msg" role="status"></span>
					</form>
				</div>
				<nav aria-label="Footer club"><p class="fh5">Club</p><?php echo $menu( 'forma-club' ); // phpcs:ignore ?></nav>
				<nav aria-label="Footer members"><p class="fh5">Members</p><?php echo $menu( 'forma-members' ); // phpcs:ignore ?></nav>
				<div><p class="fh5">Visit</p>
					<ul class="flist">
						<?php if ( $addr ) : ?><li><address><?php echo esc_html( $addr ); ?></address></li><?php endif; ?>
						<?php if ( forma_opt( 'biz_phone' ) ) : ?><li><a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', forma_opt( 'biz_phone' ) ) ); ?>"><?php echo esc_html( forma_opt( 'biz_phone' ) ); ?></a></li><?php endif; ?>
						<?php if ( forma_opt( 'biz_email' ) ) : ?><li><a href="mailto:<?php echo esc_attr( forma_opt( 'biz_email' ) ); ?>"><?php echo esc_html( forma_opt( 'biz_email' ) ); ?></a></li><?php endif; ?>
						<?php echo $hours; // phpcs:ignore ?>
					</ul></div>
			</div>
			<div class="foot-bar"><span><?php echo esc_html( forma_opt( 'footer_copyright', '© ' . gmdate( 'Y' ) . ' FORMA Athletic Club. All rights reserved.' ) ); ?></span><div class="social"><?php echo $social; // phpcs:ignore ?></div>
				<span><?php echo get_privacy_policy_url() ? '<a href="' . esc_url( get_privacy_policy_url() ) . '">Privacy</a>' : ''; // phpcs:ignore ?></span></div>
		</div>
		<div class="foot-word" aria-hidden="true">FORMA</div>
	</footer>
	<?php
}, 1 );
