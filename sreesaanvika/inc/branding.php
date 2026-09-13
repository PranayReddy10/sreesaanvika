<?php
/**
 * Site icon, logo assets and the page preloader.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * The theme's own logo file.
 *
 * @param string $which mark|favicon|logo.
 * @return string
 */
function ss_logo_asset( $which = 'logo' ) {
	$files = array(
		'mark'    => '/assets/images/mark.svg',
		'favicon' => '/assets/images/favicon.svg',
		'logo'    => '/assets/images/logo.svg',
		'png'     => '/assets/images/icon-512.png',
	);

	return SS_URI . ( isset( $files[ $which ] ) ? $files[ $which ] : $files['logo'] );
}

/**
 * Fall back to the bundled icon when no Site Icon has been uploaded.
 *
 * WordPress prints its own tags whenever Settings → General has a Site Icon,
 * so this only fills the gap on a fresh install — a shop should never show the
 * browser's blank page icon.
 */
function ss_favicon_links() {
	if ( has_site_icon() ) {
		return;
	}

	printf(
		'<link rel="icon" href="%1$s" type="image/svg+xml" />' . "\n"
		. '<link rel="icon" href="%2$s" sizes="32x32" type="image/png" />' . "\n"
		. '<link rel="apple-touch-icon" href="%3$s" />' . "\n",
		esc_url( ss_logo_asset( 'favicon' ) ),
		esc_url( SS_URI . '/assets/images/favicon-32.png' ),
		esc_url( SS_URI . '/assets/images/apple-touch-icon.png' )
	);
}
add_action( 'wp_head', 'ss_favicon_links', 2 );
add_action( 'admin_head', 'ss_favicon_links', 2 );
add_action( 'login_head', 'ss_favicon_links', 2 );

/**
 * Tell the browser chrome what colour the site is.
 */
function ss_theme_color_meta() {
	printf(
		'<meta name="theme-color" content="%s" />' . "\n",
		esc_attr( ss_option( 'color_bg', '#140a12' ) )
	);
}
add_action( 'wp_head', 'ss_theme_color_meta', 2 );

/* -------------------------------------------------------------------------
 * Preloader
 * ---------------------------------------------------------------------- */

/**
 * Is the preloader wanted on this request?
 *
 * @return bool
 */
function ss_preloader_on() {
	if ( ! ss_option( 'preloader_on', true ) ) {
		return false;
	}

	// Never in the Customizer preview or a page builder — it just gets in the way.
	if ( is_customize_preview() || is_admin() ) {
		return false;
	}

	if ( function_exists( 'ss_has_elementor' ) && ss_has_elementor()
		&& \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
		return false;
	}

	return (bool) apply_filters( 'ss_preloader_on', true );
}

/**
 * How long the curtain stays up, in milliseconds.
 *
 * @return int
 */
function ss_preloader_ms() {
	$ms = (int) ss_option( 'preloader_ms', 2000 );

	return max( 300, min( 6000, $ms ) );
}

/**
 * The curtain itself.
 *
 * It is drawn with its own inline styles rather than waiting for main.css, so
 * it covers the page from the first paint instead of flashing the content it
 * is meant to hide. The fade-out is a CSS animation with the duration baked
 * in, which means a visitor with JavaScript off still sees it leave.
 */
function ss_preloader() {
	if ( ! ss_preloader_on() ) {
		return;
	}

	$ms   = ss_preloader_ms();
	$out  = 520;
	$name = ss_option( 'preloader_text' );
	$name = $name ? $name : get_bloginfo( 'name' );
	$tag  = ss_option( 'brand_tagline', __( 'Heritage Weaves', 'sreesaanvika' ) );
	?>
	<div id="ss-preloader" class="ss-preloader" role="status" aria-live="polite"
		data-ms="<?php echo esc_attr( $ms ); ?>"
		data-transitions="<?php echo ss_option( 'preloader_transitions', true ) ? '1' : '0'; ?>"
		data-once="<?php echo ss_option( 'preloader_once', false ) ? '1' : '0'; ?>">

		<div class="ss-preloader__inner">
			<span class="ss-preloader__medallion">
				<svg class="ss-preloader__ring" viewBox="0 0 120 120" aria-hidden="true">
					<circle class="ss-preloader__track" cx="60" cy="60" r="55" />
					<circle class="ss-preloader__sweep" cx="60" cy="60" r="55" />
				</svg>
				<img src="<?php echo esc_url( ss_logo_asset( 'mark' ) ); ?>" alt="" width="86" height="86" />
			</span>

			<span class="ss-preloader__name"><?php echo esc_html( $name ); ?></span>
			<?php if ( $tag ) : ?>
				<span class="ss-preloader__tag"><?php echo esc_html( $tag ); ?></span>
			<?php endif; ?>

			<span class="ss-preloader__bar"><i></i></span>
		</div>

		<span class="screen-reader-text"><?php esc_html_e( 'Loading', 'sreesaanvika' ); ?></span>
	</div>

	<style id="ss-preloader-css">
		.ss-preloader {
			position: fixed;
			inset: 0;
			z-index: 9999;
			display: flex;
			align-items: center;
			justify-content: center;
			background:
				radial-gradient(60% 55% at 50% 42%, #2a1524 0%, #140a12 62%, #0d060b 100%);
			animation: ss-preloader-out <?php echo absint( $out ); ?>ms ease <?php echo absint( $ms ); ?>ms forwards;
		}

		/*
		 * A separate animation name, not the same one with the delay removed:
		 * changing only the delay retimes the animation that is already
		 * running, which makes it snap rather than fade.
		 */
		.ss-preloader.is-done { animation: ss-preloader-lift <?php echo absint( $out ); ?>ms ease forwards; }
		.ss-preloader.is-back { animation: ss-preloader-in 240ms ease forwards; }

		@keyframes ss-preloader-out {
			to { opacity: 0; visibility: hidden; }
		}

		@keyframes ss-preloader-lift {
			to { opacity: 0; visibility: hidden; }
		}

		@keyframes ss-preloader-in {
			from { opacity: 0; }
			to { opacity: 1; visibility: visible; }
		}

		/* Nothing scrolls behind the curtain while it is down. */
		html.ss-loading,
		html.ss-loading body { overflow: hidden; }

		.ss-preloader__inner { text-align: center; padding: 20px; }

		.ss-preloader__medallion {
			position: relative;
			display: grid;
			place-items: center;
			width: 120px;
			height: 120px;
			margin: 0 auto 26px;
		}

		.ss-preloader__medallion img {
			width: 86px;
			height: 86px;
			display: block;
			animation: ss-preloader-breathe 2.6s ease-in-out infinite;
		}

		@keyframes ss-preloader-breathe {
			0%, 100% { transform: scale(1); }
			50% { transform: scale(1.045); }
		}

		.ss-preloader__ring {
			position: absolute;
			inset: 0;
			width: 120px;
			height: 120px;
			transform: rotate(-90deg);
		}

		.ss-preloader__track {
			fill: none;
			stroke: rgba(217, 164, 65, 0.16);
			stroke-width: 1.5;
		}

		.ss-preloader__sweep {
			fill: none;
			stroke: #d9a441;
			stroke-width: 1.5;
			stroke-linecap: round;
			stroke-dasharray: 60 286;
			animation: ss-preloader-spin 1.4s linear infinite;
			transform-origin: 60px 60px;
		}

		@keyframes ss-preloader-spin { to { transform: rotate(360deg); } }

		.ss-preloader__name {
			display: block;
			font-family: "Playfair Display", Georgia, serif;
			font-size: clamp(1.4rem, 4vw, 1.9rem);
			letter-spacing: 0.02em;
			color: #f4eaee;
		}

		.ss-preloader__tag {
			display: block;
			margin-top: 8px;
			font-family: Jost, "Helvetica Neue", Arial, sans-serif;
			font-size: 0.68rem;
			letter-spacing: 0.34em;
			text-transform: uppercase;
			color: #a8909c;
		}

		.ss-preloader__bar {
			display: block;
			width: 132px;
			height: 2px;
			margin: 24px auto 0;
			border-radius: 2px;
			background: rgba(217, 164, 65, 0.16);
			overflow: hidden;
		}

		.ss-preloader__bar i {
			display: block;
			height: 100%;
			width: 40%;
			border-radius: 2px;
			background: linear-gradient(90deg, transparent, #d9a441, #f2d79a, transparent);
			animation: ss-preloader-slide 1.25s ease-in-out infinite;
		}

		@keyframes ss-preloader-slide {
			0% { transform: translateX(-110%); }
			100% { transform: translateX(360%); }
		}

		/* Someone who has asked for less motion gets the curtain without the
		   moving parts, and it leaves sooner. */
		@media (prefers-reduced-motion: reduce) {
			.ss-preloader { animation-delay: 400ms; }
			.ss-preloader__medallion img,
			.ss-preloader__sweep,
			.ss-preloader__bar i { animation: none; }
			.ss-preloader__bar i { width: 100%; }
		}
	</style>
	<?php
}
add_action( 'wp_body_open', 'ss_preloader', 1 );
