<?php
/**
 * OJASVI theme bootstrap.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

define( 'OD_VERSION', '2.4.4' );
define( 'OD_DIR', get_template_directory() );
define( 'OD_URI', get_template_directory_uri() );

/**
 * Theme supports, menus and image sizes.
 */
function od_setup() {
	load_theme_textdomain( 'ojasvidrapes', OD_DIR . '/languages' );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'customize-selective-refresh-widgets' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'wp-block-styles' );
	add_editor_style( 'assets/css/editor.css' );

	/*
	 * Tells WooCommerce Blocks the theme is dark, so a newly inserted Cart or
	 * Checkout block defaults its "dark mode inputs" attribute on. Blocks that
	 * already exist are handled by od_woo_block_dark_controls().
	 */
	add_theme_support( 'dark-editor-style' );

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
	if ( ! od_option( 'use_woo_gallery', false ) ) {
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
			'primary' => __( 'Primary Menu', 'ojasvidrapes' ),
			'topbar'  => __( 'Top Bar Menu', 'ojasvidrapes' ),
			'footer1' => __( 'Footer — Shop', 'ojasvidrapes' ),
			'footer2' => __( 'Footer — Help', 'ojasvidrapes' ),
			'footer3' => __( 'Footer — About', 'ojasvidrapes' ),
			'mobile'  => __( 'Mobile Drawer Menu', 'ojasvidrapes' ),
		)
	);

	// Custom crops tuned for saree / dress portrait imagery.
	add_image_size( 'od-product', 700, 933, true );
	add_image_size( 'od-product-lg', 1200, 1600, true );
	add_image_size( 'od-thumb', 180, 240, true );
	add_image_size( 'od-hero', 1920, 1100, true );
	add_image_size( 'od-category', 800, 800, true );
	add_image_size( 'od-blog', 800, 500, true );

	add_theme_support( 'editor-color-palette', od_editor_palette() );

	if ( ! isset( $GLOBALS['content_width'] ) ) {
		$GLOBALS['content_width'] = 1320;
	}
}
add_action( 'after_setup_theme', 'od_setup' );

/**
 * Editor colour palette mirroring the CSS tokens.
 *
 * @return array
 */
function od_editor_palette() {
	return array(
		array(
			'name'  => __( 'Aubergine', 'ojasvidrapes' ),
			'slug'  => 'od-bg',
			'color' => '#140a12',
		),
		array(
			'name'  => __( 'Surface', 'ojasvidrapes' ),
			'slug'  => 'od-surface',
			'color' => '#21121d',
		),
		array(
			'name'  => __( 'Antique Gold', 'ojasvidrapes' ),
			'slug'  => 'od-gold',
			'color' => '#d9a441',
		),
		array(
			'name'  => __( 'Marigold', 'ojasvidrapes' ),
			'slug'  => 'od-marigold',
			'color' => '#e8952f',
		),
		array(
			'name'  => __( 'Kumkum Maroon', 'ojasvidrapes' ),
			'slug'  => 'od-maroon',
			'color' => '#7b1e3b',
		),
		array(
			'name'  => __( 'Temple Emerald', 'ojasvidrapes' ),
			'slug'  => 'od-emerald',
			'color' => '#1f7a63',
		),
		array(
			'name'  => __( 'Ivory Text', 'ojasvidrapes' ),
			'slug'  => 'od-text',
			'color' => '#f4eaee',
		),
	);
}

/**
 * Front-end assets.
 */
function od_assets() {
	$fonts = 'https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Jost:wght@300;400;500;600;700&display=swap';

	wp_enqueue_style( 'od-fonts', $fonts, array(), null );
	wp_enqueue_style( 'od-main', OD_URI . '/assets/css/main.css', array(), OD_VERSION );

	/*
	 * Shop styles load wherever WooCommerce is active, not just on shop
	 * screens. Product cards also appear on the homepage sections, in
	 * Elementor widgets, in the mini-cart and anywhere a shortcode drops a
	 * grid, and scoping this to is_woocommerce() left all of those unstyled.
	 */
	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_style( 'od-shop', OD_URI . '/assets/css/shop.css', array( 'od-main' ), OD_VERSION );
	}

	wp_enqueue_style( 'ojasvidrapes-style', get_stylesheet_uri(), array( 'od-main' ), OD_VERSION );

	// Inline the customizer-driven palette overrides.
	wp_add_inline_style( 'od-main', od_dynamic_css() );

	wp_enqueue_script( 'od-main', OD_URI . '/assets/js/theme.js', array(), OD_VERSION, true );

	if ( class_exists( 'WooCommerce' ) ) {
		wp_enqueue_script( 'od-shop', OD_URI . '/assets/js/shop.js', array( 'od-main' ), OD_VERSION, true );
	}

	wp_localize_script(
		'od-main',
		'odData',
		array(
			'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
			'nonce'       => wp_create_nonce( 'od_nonce' ),
			'homeUrl'     => home_url( '/' ),
			'isWoo'       => od_is_woo(),
			'compareUrl'  => od_page_url( 'compare' ),
			'wishlistUrl' => od_page_url( 'wishlist' ),
			'cartUrl'     => function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '',
			'maxCompare'  => (int) od_option( 'compare_max', 4 ),
			'freeShip'    => (float) od_option( 'free_ship_threshold', 2999 ),
			/*
			 * Decoded, because WooCommerce returns the symbol as an HTML
			 * entity (&#8377;) and these are written to the page as text.
			 */
			'currency'    => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '₹',
			// Enough of WooCommerce's price settings to reprice in JS.
			'price'       => function_exists( 'wc_get_price_decimals' ) ? array(
				'symbol'   => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'decimals' => wc_get_price_decimals(),
				'thousand' => wc_get_price_thousand_separator(),
				'decimal'  => wc_get_price_decimal_separator(),
				'position' => get_option( 'woocommerce_currency_pos', 'left' ),
			) : array(),
			'i18n'        => array(
				'added'          => __( 'Added to your bag', 'ojasvidrapes' ),
				'wishAdded'      => __( 'Saved to wishlist', 'ojasvidrapes' ),
				'wishRemoved'    => __( 'Removed from wishlist', 'ojasvidrapes' ),
				'compareAdded'   => __( 'Added to compare', 'ojasvidrapes' ),
				'compareRemoved' => __( 'Removed from compare', 'ojasvidrapes' ),
				'compareFull'    => __( 'You can compare up to %d products', 'ojasvidrapes' ),
				'copied'         => __( 'Link copied', 'ojasvidrapes' ),
				'error'          => __( 'Something went wrong. Please try again.', 'ojasvidrapes' ),
				'selectOptions'  => __( 'Please choose the available options first', 'ojasvidrapes' ),
				'deliverTo'      => __( 'Delivery to %s in 3–6 business days', 'ojasvidrapes' ),
				'badPin'         => __( 'Enter a valid 6-digit PIN code', 'ojasvidrapes' ),
				'viewImage'      => __( 'View image', 'ojasvidrapes' ),
				'maxQty'         => __( 'That is all we have in stock', 'ojasvidrapes' ),
				'minQty'         => __( 'Minimum quantity', 'ojasvidrapes' ),
				/* translators: %d: discount percentage */
				'percentOff'     => __( '%d%% off', 'ojasvidrapes' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'od_assets' );

/**
 * Block editor assets so the admin preview matches the front end.
 */
function od_editor_assets() {
	wp_enqueue_style( 'od-editor', OD_URI . '/assets/css/editor.css', array(), OD_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'od_editor_assets' );

/**
 * Widget areas.
 */
function od_widgets() {
	$common = array(
		'before_widget' => '<section id="%1$s" class="od-widget widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="od-widget__title">',
		'after_title'   => '</h3>',
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Blog Sidebar', 'ojasvidrapes' ),
				'id'          => 'sidebar-blog',
				'description' => __( 'Shown beside posts and the blog archive.', 'ojasvidrapes' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Shop Filters Sidebar', 'ojasvidrapes' ),
				'id'          => 'sidebar-shop',
				'description' => __( 'Product filter widgets for shop and category pages.', 'ojasvidrapes' ),
			)
		)
	);

	register_sidebar(
		array_merge(
			$common,
			array(
				'name'        => __( 'Footer Extra', 'ojasvidrapes' ),
				'id'          => 'footer-extra',
				'description' => __( 'Optional widgets above the footer bottom bar.', 'ojasvidrapes' ),
			)
		)
	);
}
add_action( 'widgets_init', 'od_widgets' );

/**
 * Body classes describing the current layout.
 *
 * @param array $classes Existing classes.
 * @return array
 */
function od_body_class( $classes ) {
	$classes[] = 'od-theme';

	// Lets the handful of rules that assume a dark page correct themselves.
	if ( function_exists( 'od_palette_is_light' ) && od_palette_is_light() ) {
		$classes[] = 'od-light';
	}

	if ( ! is_active_sidebar( 'sidebar-blog' ) ) {
		$classes[] = 'od-no-sidebar';
	}

	if ( od_is_woo() ) {
		$classes[] = 'od-woo';
	}

	if ( is_page_template( 'page-templates/template-auth.php' ) ) {
		$classes[] = 'od-auth-page';
	}

	return $classes;
}
add_filter( 'body_class', 'od_body_class' );

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
function od_menu_atts( $atts, $item ) {
	if ( ! empty( $item->classes ) && is_array( $item->classes ) ) {
		if ( in_array( 'mega', $item->classes, true ) ) {
			$atts['aria-haspopup'] = 'true';
		}
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'od_menu_atts', 10, 2 );

/**
 * Add helper classes to menu items flagged in the menu editor.
 *
 * @param array  $classes Item classes.
 * @param object $item    Menu item.
 * @return array
 */
function od_menu_classes( $classes, $item ) {
	if ( in_array( 'mega', (array) $classes, true ) ) {
		$classes[] = 'od-mega';
	}
	if ( in_array( 'hot', (array) $classes, true ) ) {
		$classes[] = 'menu-item--hot';
	}
	if ( in_array( 'new', (array) $classes, true ) ) {
		$classes[] = 'menu-item--new';
	}
	return $classes;
}
add_filter( 'nav_menu_css_class', 'od_menu_classes', 10, 2 );

/* -------------------------------------------------------------------------
 * Includes
 * ---------------------------------------------------------------------- */
require_once OD_DIR . '/inc/migrate-rename.php';
require_once OD_DIR . '/inc/defaults.php';
require_once OD_DIR . '/inc/legal-content.php';
require_once OD_DIR . '/inc/helpers.php';
require_once OD_DIR . '/inc/icons.php';
require_once OD_DIR . '/inc/branding.php';
require_once OD_DIR . '/inc/nav-walker.php';
require_once OD_DIR . '/inc/customize-picker.php';
require_once OD_DIR . '/inc/customizer.php';
require_once OD_DIR . '/inc/dynamic-css.php';
require_once OD_DIR . '/inc/template-tags.php';
require_once OD_DIR . '/inc/ajax.php';
require_once OD_DIR . '/inc/compare-wishlist.php';
require_once OD_DIR . '/inc/seo.php';
require_once OD_DIR . '/inc/demo-content.php';
require_once OD_DIR . '/inc/tgm-notice.php';
require_once OD_DIR . '/inc/elementor.php';
require_once OD_DIR . '/inc/elementor-import.php';

if ( class_exists( 'WooCommerce' ) ) {
	require_once OD_DIR . '/inc/woocommerce.php';
	require_once OD_DIR . '/inc/woo-page-mode.php';
	require_once OD_DIR . '/inc/color-gallery.php';
}
