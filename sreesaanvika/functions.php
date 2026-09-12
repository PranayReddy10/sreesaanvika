<?php
/**
 * Sree Saanvika theme bootstrap.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

define( 'SS_VERSION', '1.0.1' );
define( 'SS_DIR', get_template_directory() );
define( 'SS_URI', get_template_directory_uri() );

/**
 * Theme supports, menus and image sizes.
 */
function ss_setup() {
	load_theme_textdomain( 'sreesaanvika', SS_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_editor_style( 'assets/css/editor.css' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 90,
			'width'       => 300,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support(
		'custom-background',
		array( 'default-color' => '140a12' )
	);

	// WooCommerce.
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 600,
			'single_image_width'    => 1200,
			'product_grid'          => array(
				'default_rows'    => 3,
				'min_rows'        => 1,
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 6,
			),
		)
	);

	// The theme ships its own gallery, so Woo's lightbox/zoom stay off by default.
	if ( ! ss_option( 'use_woo_gallery', false ) ) {
		remove_theme_support( 'wc-product-gallery-zoom' );
		remove_theme_support( 'wc-product-gallery-lightbox' );
		remove_theme_support( 'wc-product-gallery-slider' );
	} else {
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'sreesaanvika' ),
			'topbar'  => __( 'Top Bar Menu', 'sreesaanvika' ),
			'footer1' => __( 'Footer — Shop', 'sreesaanvika' ),
			'footer2' => __( 'Footer — Help', 'sreesaanvika' ),
			'footer3' => __( 'Footer — About', 'sreesaanvika' ),
			'mobile'  => __( 'Mobile Drawer Menu', 'sreesaanvika' ),
		)
	);

	// Custom crops tuned for saree / dress portrait imagery.
	add_image_size( 'ss-product', 700, 933, true );
	add_image_size( 'ss-product-lg', 1200, 1600, true );
	add_image_size( 'ss-thumb', 180, 240, true );
	add_image_size( 'ss-hero', 1920, 1100, true );
	add_image_size( 'ss-category', 800, 800, true );
	add_image_size( 'ss-blog', 800, 500, true );

	add_theme_support( 'editor-color-palette', ss_editor_palette() );

	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1320;
	}
}
add_action( 'after_setup_theme', 'ss_setup' );

/**
 * Editor colour palette mirroring the CSS tokens.
 *
 * @return array
 */
function ss_editor_palette() {
	return array(
		array(
			'name'  => __( 'Aubergine', 'sreesaanvika' ),
			'slug'  => 'ss-bg',
			'color' => '#140a12',
		),
		array(
			'name'  => __( 'Surface', 'sreesaanvika' ),
			'slug'  => 'ss-surface',
			'color' => '#21121d',
		),
		array(
			'name'  => __( 'Antique Gold', 'sreesaanvika' ),
			'slug'  => 'ss-gold',
			'color' => '#d9a441',
		),
		array(
			'name'  => __( 'Marigold', 'sreesaanvika' ),
			'slug'  => 'ss-marigold',
			'color' => '#e8952f',
		),
		array(
			'name'  => __( 'Kumkum Maroon', 'sreesaanvika' ),
			'slug'  => 'ss-maroon',
			'color' => '#7b1e3b',
		),
		array(
			'name'  => __( 'Temple Emerald', 'sreesaanvika' ),
			'slug'  => 'ss-emerald',
			'color' => '#1f7a63',
		),
		array(
			'name'  => __( 'Ivory Text', 'sreesaanvika' ),
			'slug'  => 'ss-text',
			'color' => '#f4eaee',
		),
	);
}

/**
 * Front-end assets.
 */
function ss_assets() {
	$fonts = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap';

	wp_enqueue_style( 'ss-fonts', $fonts, array(), null );
	wp_enqueue_style( 'ss-main', SS_URI . '/assets/css/main.css', array(), SS_VERSION );

	if ( ss_is_woo() ) {
		wp_enqueue_style( 'ss-shop', SS_URI . '/assets/css/shop.css', array( 'ss-main' ), SS_VERSION );
	}

	wp_enqueue_style( 'sreesaanvika-style', get_stylesheet_uri(), array( 'ss-main' ), SS_VERSION );

	// Inline the customizer-driven palette overrides.
	wp_add_inline_style( 'ss-main', ss_dynamic_css() );

	wp_enqueue_script( 'ss-main', SS_URI . '/assets/js/theme.js', array(), SS_VERSION, true );

	if ( ss_is_woo() ) {
		wp_enqueue_script( 'ss-shop', SS_URI . '/assets/js/shop.js', array( 'ss-main' ), SS_VERSION, true );
	}

	wp_localize_script(
		'ss-main',
		'ssData',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'ss_nonce' ),
			'homeUrl'     => home_url( '/' ),
			'isWoo'       => ss_is_woo(),
			'compareUrl'  => ss_page_url( 'compare' ),
			'wishlistUrl' => ss_page_url( 'wishlist' ),
			'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
			'maxCompare'  => (int) ss_option( 'compare_max', 4 ),
			'freeShip'    => (float) ss_option( 'free_ship_threshold', 2999 ),
			'currency'    => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '₹',
			'i18n'        => array(
				'added'          => __( 'Added to your bag', 'sreesaanvika' ),
				'wishAdded'      => __( 'Saved to wishlist', 'sreesaanvika' ),
				'wishRemoved'    => __( 'Removed from wishlist', 'sreesaanvika' ),
				'compareAdded'   => __( 'Added to compare', 'sreesaanvika' ),
				'compareRemoved' => __( 'Removed from compare', 'sreesaanvika' ),
				'compareFull'    => __( 'You can compare up to %d products', 'sreesaanvika' ),
				'copied'         => __( 'Link copied', 'sreesaanvika' ),
				'error'          => __( 'Something went wrong. Please try again.', 'sreesaanvika' ),
				'selectOptions'  => __( 'Please choose the available options first', 'sreesaanvika' ),
				'deliverTo'      => __( 'Delivery to %s in 3–6 business days', 'sreesaanvika' ),
				'badPin'         => __( 'Enter a valid 6-digit PIN code', 'sreesaanvika' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'ss_assets' );

/**
 * Block editor assets so the admin preview matches the front end.
 */
function ss_editor_assets() {
	wp_enqueue_style( 'ss-editor', SS_URI . '/assets/css/editor.css', array(), SS_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'ss_editor_assets' );

/**
 * Widget areas.
 */
function ss_widgets() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="ss-widget widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="ss-widget__title">',
		'after_title'   => '</h3>',
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Blog Sidebar', 'sreesaanvika' ),
				'id'          => 'sidebar-blog',
				'description' => __( 'Shown beside posts and the blog archive.', 'sreesaanvika' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Shop Filters Sidebar', 'sreesaanvika' ),
				'id'          => 'sidebar-shop',
				'description' => __( 'Product filter widgets for shop and category pages.', 'sreesaanvika' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Footer Extra', 'sreesaanvika' ),
				'id'          => 'footer-extra',
				'description' => __( 'Optional widgets above the footer bottom bar.', 'sreesaanvika' ),
			)
		)
	);
}
add_action( 'widgets_init', 'ss_widgets' );

/**
 * Body classes describing the current layout.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function ss_body_class( $classes ) {
	$classes[] = 'ss-theme';

	if ( ! is_active_sidebar( 'sidebar-blog' ) ) {
		$classes[] = 'ss-no-sidebar';
	}

	if ( ss_is_woo() ) {
		$classes[] = 'ss-woo';
	}

	if ( is_page_template( 'page-templates/template-auth.php' ) ) {
		$classes[] = 'ss-auth-page';
	}

	return $classes;
}
add_filter( 'body_class', 'ss_body_class' );

/**
 * Excerpt tuning.
 */
add_filter( 'excerpt_length', function () { return 24; }, 20 );
add_filter( 'excerpt_more', function () { return '&hellip;'; } );

/**
 * Menu item classes so the nav walker output stays tidy.
 *
 * @param array  $atts Anchor attributes.
 * @param object $item Menu item.
 * @return array
 */
function ss_menu_atts( $atts, $item ) {
	if ( ! empty( $item->classes ) && is_array( $item->classes ) ) {
		if ( in_array( 'mega', $item->classes, true ) ) {
			$atts['aria-haspopup'] = 'true';
		}
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'ss_menu_atts', 10, 2 );

/**
 * Add helper classes to menu items flagged in the menu editor.
 *
 * @param array  $classes Item classes.
 * @param object $item    Menu item.
 * @return array
 */
function ss_menu_classes( $classes, $item ) {
	if ( in_array( 'mega', (array) $classes, true ) ) {
		$classes[] = 'ss-mega';
	}
	if ( in_array( 'hot', (array) $classes, true ) ) {
		$classes[] = 'menu-item--hot';
	}
	if ( in_array( 'new', (array) $classes, true ) ) {
		$classes[] = 'menu-item--new';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'ss_menu_classes', 10, 2 );

/* -------------------------------------------------------------------------
 * Includes
 * ---------------------------------------------------------------------- */
require_once SS_DIR . '/inc/defaults.php';
require_once SS_DIR . '/inc/helpers.php';
require_once SS_DIR . '/inc/icons.php';
require_once SS_DIR . '/inc/nav-walker.php';
require_once SS_DIR . '/inc/customizer.php';
require_once SS_DIR . '/inc/dynamic-css.php';
require_once SS_DIR . '/inc/template-tags.php';
require_once SS_DIR . '/inc/ajax.php';
require_once SS_DIR . '/inc/compare-wishlist.php';
require_once SS_DIR . '/inc/demo-content.php';
require_once SS_DIR . '/inc/tgm-notice.php';
require_once SS_DIR . '/inc/elementor.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once SS_DIR . '/inc/woocommerce.php';
}
