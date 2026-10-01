<?php
/**
 * Elementor compatibility.
 *
 * Two things matter for a page builder to work with a theme like this one:
 *
 * 1. `the_content()` must always run on a singular template. Elementor's
 *    editor loads the front end in an iframe and looks for the rendered
 *    content wrapper; if the theme skips `the_content()` — for example
 *    because the post content is empty, which is exactly the case for a page
 *    whose layout lives in Elementor's own `_elementor_data` meta — the
 *    preview never finishes loading and the editor falls back to the
 *    "Can't Edit? Enable Safe Mode" panel.
 *
 * 2. The theme's own layout must step aside on a page built with Elementor,
 *    otherwise the storefront sections and the narrow article card fight the
 *    builder's full-width sections.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is Elementor active?
 *
 * @return bool
 */
function od_has_elementor() {
	return did_action( 'elementor/loaded' ) > 0;
}

/**
 * Was this post laid out in Elementor?
 *
 * @param int $post_id Post id, defaults to the current post.
 * @return bool
 */
function od_built_with_elementor( $post_id = 0 ) {
	if ( ! od_has_elementor() ) {
		return false;
	}

	$post_id = $post_id ? $post_id : get_the_ID();

	if ( ! $post_id ) {
		return false;
	}

	// Elementor's own API is the reliable check; the meta is the fallback for
	// the brief window before the documents manager is ready.
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
		$document = \Elementor\Plugin::$instance->documents->get( $post_id );

		if ( $document ) {
			return (bool) $document->is_built_with_elementor();
		}
	}

	return 'builder' === get_post_meta( $post_id, '_elementor_edit_mode', true );
}

/**
 * Are we inside the Elementor editor's preview iframe?
 *
 * @return bool
 */
function od_elementor_preview() {
	return od_has_elementor()
		&& class_exists( '\Elementor\Plugin' )
		&& isset( \Elementor\Plugin::$instance->preview )
		&& \Elementor\Plugin::$instance->preview->is_preview_mode();
}

/**
 * True when the theme should hand the whole content area to Elementor.
 *
 * @return bool
 */
function od_elementor_owns_page() {
	return od_elementor_preview() || od_built_with_elementor();
}

/**
 * Is the front page itself laid out in Elementor?
 *
 * Asked from the admin, where there is no current post, so the page has to be
 * named explicitly.
 *
 * @return bool
 */
function od_front_page_is_elementor() {
	if ( 'page' !== get_option( 'show_on_front' ) ) {
		return false;
	}

	$id = (int) get_option( 'page_on_front' );

	return $id && od_built_with_elementor( $id );
}

/**
 * Who draws the front page.
 *
 * A page built in Elementor takes over by default, because otherwise the
 * builder's full-width sections and the theme's own storefront sections would
 * be stacked on the same page. That is almost always what a shop wants, but it
 * is silent: the Customizer's hero slider and section switches simply stop
 * having any effect, with nothing to say why. So it can also be set outright,
 * for a shop that would rather keep its Elementor page on file and go back to
 * the theme's sections.
 *
 * @return string "elementor" or "theme".
 */
function od_front_page_source() {
	/*
	 * The editor preview always belongs to Elementor, whatever the setting —
	 * the iframe has to find the rendered content wrapper or the editor never
	 * finishes loading.
	 */
	if ( od_elementor_preview() ) {
		return 'elementor';
	}

	if ( 'theme' === od_option( 'home_source', 'auto' ) ) {
		return 'theme';
	}

	return od_built_with_elementor() ? 'elementor' : 'theme';
}

/**
 * The link that opens the front page in the Elementor editor.
 *
 * @return string Empty when there is no Elementor front page to open.
 */
function od_front_page_elementor_link() {
	if ( ! od_front_page_is_elementor() ) {
		return '';
	}

	return admin_url( 'post.php?post=' . (int) get_option( 'page_on_front' ) . '&action=elementor' );
}

/**
 * Register the header and footer theme locations for Elementor Pro's
 * Theme Builder. Only these two — the theme keeps ownership of single,
 * archive and every WooCommerce template.
 *
 * @param object $manager Elementor locations manager.
 */
function od_elementor_locations( $manager ) {
	$manager->register_location( 'header' );
	$manager->register_location( 'footer' );
}
add_action( 'elementor/theme/register_locations', 'od_elementor_locations' );

/**
 * Render an Elementor Theme Builder template for a location.
 *
 * @param string $location "header" or "footer".
 * @return bool True when Elementor printed something and the theme should
 *              skip its own markup.
 */
function od_elementor_location( $location ) {
	if ( ! function_exists( 'elementor_theme_do_location' ) ) {
		return false;
	}

	return (bool) elementor_theme_do_location( $location );
}

/**
 * Give the editor preview a sane canvas.
 *
 * Inside the preview iframe the sticky header and the fixed panels overlay
 * the widgets the shop owner is trying to click, so they are pinned back to
 * the normal flow.
 */
function od_elementor_preview_css() {
	if ( ! od_elementor_preview() ) {
		return;
	}

	$css = '.od-header{position:static !important;}'
		. '.od-panel,.od-drawer,.od-scrim,.od-search-overlay,.od-to-top,.od-comparebar,.od-stickybuy{display:none !important;}'
		. 'body.od-no-scroll{overflow:visible !important;}';

	wp_add_inline_style( 'od-main', $css );
}
add_action( 'wp_enqueue_scripts', 'od_elementor_preview_css', 30 );

/**
 * Register the widget category.
 *
 * @param object $manager Elements manager.
 */
function od_elementor_category( $manager ) {
	$manager->add_category(
		'ojasvidrapes',
		array(
			'title' => esc_html__( 'Ojasvi Drapes', 'ojasvidrapes' ),
			'icon'  => 'eicon-woocommerce',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'od_elementor_category' );

/**
 * Every widget class the theme ships, in the order they appear on the
 * homepage. Also drives the one-click homepage converter.
 *
 * @return array
 */
function od_elementor_widget_classes() {
	return array(
		'OD_Widget_Hero',
		'OD_Widget_USP',
		'OD_Widget_Category_Rail',
		'OD_Widget_Category_Mosaic',
		'OD_Widget_Products',
		'OD_Widget_Promo',
		'OD_Widget_Lookbook',
		'OD_Widget_Band',
		'OD_Widget_Testimonials',
		'OD_Widget_Instagram',
		'OD_Widget_Newsletter',
		'OD_Widget_Heading',
	);
}

/**
 * Register the widgets with Elementor.
 *
 * @param object $widgets_manager Widgets manager.
 */
function od_elementor_register_widgets( $widgets_manager ) {
	// Elementor still fires the deprecated hook alongside the modern one on
	// some versions; registering twice throws a duplicate-widget error.
	static $done = false;

	if ( $done ) {
		return;
	}

	$done = true;

	require_once OD_DIR . '/inc/elementor/class-od-widget.php';
	require_once OD_DIR . '/inc/elementor/widgets-content.php';
	require_once OD_DIR . '/inc/elementor/widgets-catalog.php';
	require_once OD_DIR . '/inc/elementor/widgets-social.php';

	foreach ( od_elementor_widget_classes() as $class ) {
		if ( ! class_exists( $class ) ) {
			continue;
		}

		// register() is Elementor 3.5+; register_widget_type() is the old name.
		if ( method_exists( $widgets_manager, 'register' ) ) {
			$widgets_manager->register( new $class() );
		} else {
			$widgets_manager->register_widget_type( new $class() );
		}
	}
}
add_action( 'elementor/widgets/register', 'od_elementor_register_widgets' );
add_action( 'elementor/widgets/widgets_registered', 'od_elementor_register_widgets' );

/**
 * The note shown on the Customizer's homepage sections when Elementor has
 * taken the front page over.
 *
 * Without it, the hero slider controls look and behave exactly as normal —
 * they save, the preview may even seem to agree — while the live homepage goes
 * on showing whatever is in the Elementor page. The note says which of the two
 * is in charge, and gives both ways out.
 *
 * @return string Markup for a section description, or an empty string.
 */
function od_elementor_home_notice() {
	if ( ! od_front_page_is_elementor() ) {
		return '';
	}

	$style = 'display:block;margin-top:10px;padding:10px 12px;border-left:3px solid #d9a441;background:rgba(217,164,65,.09);line-height:1.6;';

	if ( 'theme' === od_option( 'home_source', 'auto' ) ) {
		return '<span style="' . esc_attr( $style ) . '">'
			. esc_html__( 'Your homepage was built in Elementor, but “Homepage layout” below is set to the theme’s sections, so these settings are the ones in use. The Elementor page is kept and can be switched back to at any time.', 'ojasvidrapes' )
			. '</span>';
	}

	return '<span style="' . esc_attr( $style ) . '"><strong>'
		. esc_html__( 'These settings are not being used right now.', 'ojasvidrapes' )
		. '</strong> '
		. esc_html__( 'Your homepage was built in Elementor, so Elementor draws it and the slider below is ignored. Either edit the slider in Elementor, or set “Homepage layout” in Homepage — Sections to the theme’s sections.', 'ojasvidrapes' )
		. ' <a href="' . esc_url( od_front_page_elementor_link() ) . '" target="_blank" rel="noopener">'
		. esc_html__( 'Edit the homepage in Elementor', 'ojasvidrapes' )
		. '</a></span>';
}

/**
 * Say on every admin screen when the homepage settings are not being used.
 *
 * The Customizer says so too, but a shop owner who has been told the homepage
 * is set up in the Customizer will change a setting, see the live page
 * unchanged, and conclude the theme is broken — which is exactly what
 * happened. This is the one notice worth interrupting them for, so it carries
 * both ways out and can be dismissed for good once they have chosen one.
 */
function od_elementor_home_admin_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ! od_front_page_is_elementor() || 'theme' === od_option( 'home_source', 'auto' ) ) {
		return;
	}

	if ( get_user_meta( get_current_user_id(), 'od_dismissed_home_notice', true ) ) {
		return;
	}

	$customize = add_query_arg(
		array( 'autofocus[control]' => 'od_home_source' ),
		admin_url( 'customize.php' )
	);

	printf(
		'<div class="notice notice-warning is-dismissible od-home-notice"><p><strong>%1$s</strong> %2$s</p><p><a class="button button-primary" href="%3$s">%4$s</a> <a class="button" href="%5$s">%6$s</a></p></div>',
		esc_html__( 'Your homepage is built in Elementor.', 'ojasvidrapes' ),
		esc_html__( 'Elementor draws the whole front page, so the hero slider and the homepage sections in the Customizer are not being used. Edit the page in Elementor, or switch the homepage over to the theme’s sections — your Elementor page is kept either way.', 'ojasvidrapes' ),
		esc_url( od_front_page_elementor_link() ),
		esc_html__( 'Edit the homepage in Elementor', 'ojasvidrapes' ),
		esc_url( $customize ),
		esc_html__( 'Use the theme’s sections instead', 'ojasvidrapes' )
	);

	// Dismissing a core notice only hides it for the page view, so the choice
	// is recorded against the user.
	?>
	<script>
	( function () {
		var n = document.querySelector( '.od-home-notice' );
		if ( ! n ) { return; }
		n.addEventListener( 'click', function ( e ) {
			if ( ! e.target.classList.contains( 'notice-dismiss' ) ) { return; }
			var body = new FormData();
			body.append( 'action', 'od_dismiss_home_notice' );
			body.append( 'nonce', '<?php echo esc_js( wp_create_nonce( 'od_dismiss_home_notice' ) ); ?>' );
			fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, { method: 'POST', body: body, credentials: 'same-origin' } );
		} );
	}() );
	</script>
	<?php
}
add_action( 'admin_notices', 'od_elementor_home_admin_notice' );

/**
 * Remember that the homepage notice was dismissed.
 */
function od_dismiss_home_notice() {
	check_ajax_referer( 'od_dismiss_home_notice', 'nonce' );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error();
	}

	update_user_meta( get_current_user_id(), 'od_dismissed_home_notice', 1 );
	wp_send_json_success();
}
add_action( 'wp_ajax_od_dismiss_home_notice', 'od_dismiss_home_notice' );
