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
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is Elementor active?
 *
 * @return bool
 */
function ss_has_elementor() {
	return did_action( 'elementor/loaded' ) > 0;
}

/**
 * Was this post laid out in Elementor?
 *
 * @param int $post_id Post id, defaults to the current post.
 * @return bool
 */
function ss_built_with_elementor( $post_id = 0 ) {
	if ( ! ss_has_elementor() ) {
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
function ss_elementor_preview() {
	return ss_has_elementor()
		&& class_exists( '\Elementor\Plugin' )
		&& isset( \Elementor\Plugin::$instance->preview )
		&& \Elementor\Plugin::$instance->preview->is_preview_mode();
}

/**
 * True when the theme should hand the whole content area to Elementor.
 *
 * @return bool
 */
function ss_elementor_owns_page() {
	return ss_elementor_preview() || ss_built_with_elementor();
}

/**
 * Register the header and footer theme locations for Elementor Pro's
 * Theme Builder. Only these two — the theme keeps ownership of single,
 * archive and every WooCommerce template.
 *
 * @param object $manager Elementor locations manager.
 */
function ss_elementor_locations( $manager ) {
	$manager->register_location( 'header' );
	$manager->register_location( 'footer' );
}
add_action( 'elementor/theme/register_locations', 'ss_elementor_locations' );

/**
 * Render an Elementor Theme Builder template for a location.
 *
 * @param string $location "header" or "footer".
 * @return bool True when Elementor printed something and the theme should
 *              skip its own markup.
 */
function ss_elementor_location( $location ) {
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
function ss_elementor_preview_css() {
	if ( ! ss_elementor_preview() ) {
		return;
	}

	$css = '.ss-header{position:static !important;}'
		. '.ss-panel,.ss-drawer,.ss-scrim,.ss-search-overlay,.ss-to-top,.ss-comparebar,.ss-stickybuy{display:none !important;}'
		. 'body.ss-no-scroll{overflow:visible !important;}';

	wp_add_inline_style( 'ss-main', $css );
}
add_action( 'wp_enqueue_scripts', 'ss_elementor_preview_css', 30 );

/**
 * Register the widget category.
 *
 * @param object $manager Elements manager.
 */
function ss_elementor_category( $manager ) {
	$manager->add_category(
		'sreesaanvika',
		array(
			'title' => esc_html__( 'Sree Saanvika', 'sreesaanvika' ),
			'icon'  => 'eicon-woocommerce',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'ss_elementor_category' );

/**
 * Every widget class the theme ships, in the order they appear on the
 * homepage. Also drives the one-click homepage converter.
 *
 * @return array
 */
function ss_elementor_widget_classes() {
	return array(
		'SS_Widget_Hero',
		'SS_Widget_USP',
		'SS_Widget_Category_Rail',
		'SS_Widget_Category_Mosaic',
		'SS_Widget_Products',
		'SS_Widget_Promo',
		'SS_Widget_Lookbook',
		'SS_Widget_Band',
		'SS_Widget_Testimonials',
		'SS_Widget_Instagram',
		'SS_Widget_Newsletter',
		'SS_Widget_Heading',
	);
}

/**
 * Register the widgets with Elementor.
 *
 * @param object $widgets_manager Widgets manager.
 */
function ss_elementor_register_widgets( $widgets_manager ) {
	// Elementor still fires the deprecated hook alongside the modern one on
	// some versions; registering twice throws a duplicate-widget error.
	static $done = false;

	if ( $done ) {
		return;
	}

	$done = true;

	require_once SS_DIR . '/inc/elementor/class-ss-widget.php';
	require_once SS_DIR . '/inc/elementor/widgets-content.php';
	require_once SS_DIR . '/inc/elementor/widgets-catalog.php';
	require_once SS_DIR . '/inc/elementor/widgets-social.php';

	foreach ( ss_elementor_widget_classes() as $class ) {
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
add_action( 'elementor/widgets/register', 'ss_elementor_register_widgets' );
add_action( 'elementor/widgets/widgets_registered', 'ss_elementor_register_widgets' );
