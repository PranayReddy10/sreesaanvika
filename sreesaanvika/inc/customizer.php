<?php
/**
 * Customizer options.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register panels, sections and settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function ss_customize_register( $wp_customize ) {

	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	$wp_customize->selective_refresh->add_partial(
		'blogname',
		array(
			'selector'        => '.ss-brand__name',
			'render_callback' => function () {
				return get_bloginfo( 'name' );
			},
		)
	);

	/* -----------------------------------------------------------------
	 * Panel
	 * -------------------------------------------------------------- */
	$wp_customize->add_panel(
		'ss_panel',
		array(
			'title'       => __( 'Sree Saanvika Options', 'sreesaanvika' ),
			'description' => __( 'Everything that makes the theme yours — colours, header, homepage sections and shop behaviour.', 'sreesaanvika' ),
			'priority'    => 10,
		)
	);

	/**
	 * Helper to add a setting + control in one call.
	 *
	 * @param string $id      Setting id (without ss_).
	 * @param array  $args    Control args.
	 * @param mixed  $default Default value.
	 * @param string $sanitize Sanitize callback.
	 */
	$add = function ( $id, $args, $default = '', $sanitize = 'sanitize_text_field' ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'ss_' . $id,
			array(
				'default'           => $default,
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);

		$type = isset( $args['type'] ) ? $args['type'] : 'text';

		if ( 'color' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Color_Control( $wp_customize, 'ss_' . $id, $args )
			);
		} elseif ( 'image' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Image_Control( $wp_customize, 'ss_' . $id, $args )
			);
		} else {
			$wp_customize->add_control( 'ss_' . $id, $args );
		}
	};

	/* -----------------------------------------------------------------
	 * Colours
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_colors',
		array(
			'title'       => __( 'Colours & Palette', 'sreesaanvika' ),
			'panel'       => 'ss_panel',
			'description' => __( 'The theme is built dark by design. These control the accent metals and the depth of the background.', 'sreesaanvika' ),
		)
	);

	$colors = array(
		'color_bg'       => array( __( 'Page background', 'sreesaanvika' ), '#140a12' ),
		'color_surface'  => array( __( 'Card surface', 'sreesaanvika' ), '#21121d' ),
		'color_gold'     => array( __( 'Primary accent (gold)', 'sreesaanvika' ), '#d9a441' ),
		'color_gold_light' => array( __( 'Accent highlight', 'sreesaanvika' ), '#f0d08a' ),
		'color_maroon'   => array( __( 'Secondary accent (maroon)', 'sreesaanvika' ), '#7b1e3b' ),
		'color_marigold' => array( __( 'Tertiary accent (marigold)', 'sreesaanvika' ), '#e8952f' ),
		'color_text'     => array( __( 'Body text', 'sreesaanvika' ), '#f4eaee' ),
	);

	foreach ( $colors as $id => $data ) {
		$add(
			$id,
			array(
				'label'   => $data[0],
				'section' => 'ss_colors',
				'type'    => 'color',
			),
			$data[1],
			'sanitize_hex_color'
		);
	}

	$add(
		'palette_preset',
		array(
			'label'       => __( 'Quick palette preset', 'sreesaanvika' ),
			'description' => __( 'Applies a curated colour set on top of the values above.', 'sreesaanvika' ),
			'section'     => 'ss_colors',
			'type'        => 'select',
			'choices'     => array(
				''         => __( 'Custom (use the colours above)', 'sreesaanvika' ),
				'aubergine' => __( 'Aubergine & Gold (default)', 'sreesaanvika' ),
				'midnight' => __( 'Midnight Peacock', 'sreesaanvika' ),
				'espresso' => __( 'Espresso & Copper', 'sreesaanvika' ),
				'ink'      => __( 'Temple Ink & Emerald', 'sreesaanvika' ),
			),
		),
		'aubergine',
		'ss_sanitize_choice'
	);

	/* -----------------------------------------------------------------
	 * Header
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_header',
		array(
			'title' => __( 'Header & Top Bar', 'sreesaanvika' ),
			'panel' => 'ss_panel',
		)
	);

	$add( 'brand_tagline', array( 'label' => __( 'Brand tagline (under the logo)', 'sreesaanvika' ), 'section' => 'ss_header' ), __( 'Heritage Weaves', 'sreesaanvika' ) );
	$add( 'topbar_on', array( 'label' => __( 'Show the announcement bar', 'sreesaanvika' ), 'section' => 'ss_header', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add(
		'topbar_items',
		array(
			'label'       => __( 'Announcement messages', 'sreesaanvika' ),
			'description' => __( 'One per line. They scroll across the top bar.', 'sreesaanvika' ),
			'section'     => 'ss_header',
			'type'        => 'textarea',
		),
		__( "Free shipping across India on orders above ₹2,999\nHandloom certified — direct from the weavers of Kanchipuram & Banaras\nEasy 7-day returns · 100% secure payments", 'sreesaanvika' ),
		'sanitize_textarea_field'
	);
	$add( 'topbar_phone', array( 'label' => __( 'Top bar phone number', 'sreesaanvika' ), 'section' => 'ss_header' ), '+91 98765 43210' );
	$add( 'sticky_header', array( 'label' => __( 'Sticky header on scroll', 'sreesaanvika' ), 'section' => 'ss_header', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );

	/* -----------------------------------------------------------------
	 * Homepage — hero
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_hero',
		array(
			'title'       => __( 'Homepage — Hero Slider', 'sreesaanvika' ),
			'panel'       => 'ss_panel',
			'description' => __( 'Up to three slides. Leave a title empty to skip that slide.', 'sreesaanvika' ),
		)
	);

	$hero_defaults = array(
		1 => array(
			__( 'The Bridal Edit', 'sreesaanvika' ),
			__( 'Kanchipuram Silk, <em>Woven in Gold</em>', 'sreesaanvika' ),
			__( 'Pure zari, temple borders and the kind of lustre that only a six-month loom can give.', 'sreesaanvika' ),
			__( 'Shop Sarees', 'sreesaanvika' ),
		),
		2 => array(
			__( 'Temple Jewellery', 'sreesaanvika' ),
			__( 'Heirloom <em>Antique Finish</em>', 'sreesaanvika' ),
			__( 'Nakshi haarams, jhumkas and vanki — crafted the way the temple artisans of Thanjavur still do.', 'sreesaanvika' ),
			__( 'Explore Jewellery', 'sreesaanvika' ),
		),
		3 => array(
			__( 'Festive 2025', 'sreesaanvika' ),
			__( 'Anarkalis & <em>Lehengas</em>', 'sreesaanvika' ),
			__( 'Chikankari, mirror work and hand-dyed bandhani, cut for movement.', 'sreesaanvika' ),
			__( 'Shop Dresses', 'sreesaanvika' ),
		),
	);

	foreach ( $hero_defaults as $i => $d ) {
		$add( "hero{$i}_eyebrow", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — eyebrow', 'sreesaanvika' ), $i ), 'section' => 'ss_hero' ), $d[0] );
		$add( "hero{$i}_title", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — title (use <em> for the gold words)', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'textarea' ), $d[1], 'ss_sanitize_html' );
		$add( "hero{$i}_text", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — description', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'textarea' ), $d[2], 'sanitize_textarea_field' );
		$add( "hero{$i}_btn", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — button label', 'sreesaanvika' ), $i ), 'section' => 'ss_hero' ), $d[3] );
		$add( "hero{$i}_url", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — button link', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'url' ), '', 'esc_url_raw' );
		$add( "hero{$i}_img", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — background image', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'image' ), '', 'esc_url_raw' );
		$add(
			"hero{$i}_align",
			array(
				'label'   => sprintf( /* translators: %d: slide number */ __( 'Slide %d — text position', 'sreesaanvika' ), $i ),
				'section' => 'ss_hero',
				'type'    => 'select',
				'choices' => array(
					'left'   => __( 'Left', 'sreesaanvika' ),
					'center' => __( 'Centre', 'sreesaanvika' ),
					'right'  => __( 'Right', 'sreesaanvika' ),
				),
			),
			'left',
			'ss_sanitize_choice'
		);
	}

	$add( 'hero_autoplay', array( 'label' => __( 'Auto-advance slides', 'sreesaanvika' ), 'section' => 'ss_hero', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'hero_speed', array( 'label' => __( 'Seconds per slide', 'sreesaanvika' ), 'section' => 'ss_hero', 'type' => 'number', 'input_attrs' => array( 'min' => 3, 'max' => 20 ) ), 6, 'absint' );

	/* -----------------------------------------------------------------
	 * Homepage — sections
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_home',
		array(
			'title' => __( 'Homepage — Sections', 'sreesaanvika' ),
			'panel' => 'ss_panel',
		)
	);

	$toggles = array(
		'sec_usp'         => array( __( 'Trust / USP strip', 'sreesaanvika' ), true ),
		'sec_catrail'     => array( __( 'Round category rail', 'sreesaanvika' ), true ),
		'sec_cats'        => array( __( 'Category mosaic', 'sreesaanvika' ), true ),
		'sec_new'         => array( __( 'New arrivals', 'sreesaanvika' ), true ),
		'sec_promo'       => array( __( 'Offer banners', 'sreesaanvika' ), true ),
		'sec_bestsellers' => array( __( 'Best sellers', 'sreesaanvika' ), true ),
		'sec_deal'        => array( __( 'Deal of the day (countdown)', 'sreesaanvika' ), true ),
		'sec_sarees'      => array( __( 'Saree spotlight', 'sreesaanvika' ), true ),
		'sec_jewel'       => array( __( 'Jewellery spotlight', 'sreesaanvika' ), true ),
		'sec_lookbook'    => array( __( 'Lookbook strip', 'sreesaanvika' ), true ),
		'sec_band'        => array( __( 'Story band', 'sreesaanvika' ), true ),
		'sec_reviews'     => array( __( 'Customer reviews', 'sreesaanvika' ), true ),
		'sec_blog'        => array( __( 'Journal / blog posts', 'sreesaanvika' ), true ),
		'sec_gram'        => array( __( 'Instagram grid', 'sreesaanvika' ), true ),
		'sec_newsletter'  => array( __( 'Newsletter', 'sreesaanvika' ), true ),
	);

	foreach ( $toggles as $id => $data ) {
		$add(
			$id,
			array(
				'label'   => $data[0],
				'section' => 'ss_home',
				'type'    => 'checkbox',
			),
			$data[1],
			'ss_sanitize_bool'
		);
	}

	$add( 'products_per_section', array( 'label' => __( 'Products shown per section', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 12 ) ), 8, 'absint' );

	/* -----------------------------------------------------------------
	 * Promo banners
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_promo',
		array(
			'title' => __( 'Homepage — Offer Banners', 'sreesaanvika' ),
			'panel' => 'ss_panel',
		)
	);

	$add( 'promo1_off', array( 'label' => __( 'Banner 1 — big text', 'sreesaanvika' ), 'section' => 'ss_promo' ), __( '40% OFF', 'sreesaanvika' ) );
	$add( 'promo1_title', array( 'label' => __( 'Banner 1 — heading', 'sreesaanvika' ), 'section' => 'ss_promo' ), __( 'Banarasi Silk Festival', 'sreesaanvika' ) );
	$add( 'promo1_text', array( 'label' => __( 'Banner 1 — text', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'textarea' ), __( 'Hand-woven katan silk with real zari butis. Limited looms, limited pieces.', 'sreesaanvika' ), 'sanitize_textarea_field' );
	$add( 'promo1_url', array( 'label' => __( 'Banner 1 — link', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'url' ), '', 'esc_url_raw' );
	$add( 'promo1_img', array( 'label' => __( 'Banner 1 — image', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'image' ), '', 'esc_url_raw' );

	$add( 'promo2_off', array( 'label' => __( 'Banner 2 — big text', 'sreesaanvika' ), 'section' => 'ss_promo' ), __( 'NEW IN', 'sreesaanvika' ) );
	$add( 'promo2_title', array( 'label' => __( 'Banner 2 — heading', 'sreesaanvika' ), 'section' => 'ss_promo' ), __( 'Temple Jewellery', 'sreesaanvika' ) );
	$add( 'promo2_text', array( 'label' => __( 'Banner 2 — text', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'textarea' ), __( 'Antique-finish haarams and jhumkas, hallmarked and nazariya-safe.', 'sreesaanvika' ), 'sanitize_textarea_field' );
	$add( 'promo2_url', array( 'label' => __( 'Banner 2 — link', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'url' ), '', 'esc_url_raw' );
	$add( 'promo2_img', array( 'label' => __( 'Banner 2 — image', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'image' ), '', 'esc_url_raw' );

	$add( 'deal_end', array( 'label' => __( 'Deal of the day — end date/time', 'sreesaanvika' ), 'description' => __( 'Format: YYYY-MM-DD HH:MM', 'sreesaanvika' ), 'section' => 'ss_promo' ), '' );
	$add( 'band_img', array( 'label' => __( 'Story band — background image', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'image' ), '', 'esc_url_raw' );
	$add( 'band_title', array( 'label' => __( 'Story band — heading', 'sreesaanvika' ), 'section' => 'ss_promo' ), __( 'Woven by hands that have known the loom for six generations', 'sreesaanvika' ) );
	$add( 'band_text', array( 'label' => __( 'Story band — text', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'textarea' ), __( 'Every Sree Saanvika saree is sourced straight from weaver families in Kanchipuram, Banaras, Pochampally and Bhagalpur — no middlemen, fair wages, and a name tag on every drape.', 'sreesaanvika' ), 'sanitize_textarea_field' );

	/* -----------------------------------------------------------------
	 * Shop
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_shop',
		array(
			'title' => __( 'Shop & Product Page', 'sreesaanvika' ),
			'panel' => 'ss_panel',
		)
	);

	$add( 'shop_columns', array( 'label' => __( 'Products per row', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 6 ) ), 4, 'absint' );
	$add( 'shop_per_page', array( 'label' => __( 'Products per page', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 4, 'max' => 60 ) ), 12, 'absint' );
	$add( 'shop_sidebar', array( 'label' => __( 'Show the filter sidebar', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'card_hover_img', array( 'label' => __( 'Swap to the second image on hover', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'card_swatches', array( 'label' => __( 'Show colour swatches on product cards', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'quickview', array( 'label' => __( 'Enable quick view', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'wishlist_on', array( 'label' => __( 'Enable wishlist', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'compare_on', array( 'label' => __( 'Enable compare', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'compare_max', array( 'label' => __( 'Maximum products to compare', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 6 ) ), 4, 'absint' );
	$add( 'use_woo_gallery', array( 'label' => __( 'Use the default WooCommerce gallery instead of the theme gallery', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), false, 'ss_sanitize_bool' );
	$add( 'sticky_buy', array( 'label' => __( 'Sticky add-to-cart bar on mobile', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'pincode_check', array( 'label' => __( 'Show the delivery PIN code checker', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), true, 'ss_sanitize_bool' );
	$add( 'free_ship_threshold', array( 'label' => __( 'Free shipping threshold (₹)', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number' ), 2999, 'absint' );
	$add( 'stock_alert_qty', array( 'label' => __( 'Show "only N left" below this stock level', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number' ), 8, 'absint' );
	$add(
		'offers_text',
		array(
			'label'       => __( 'Offer lines on the product page', 'sreesaanvika' ),
			'description' => __( 'One per line. Wrap a coupon code in backticks to highlight it.', 'sreesaanvika' ),
			'section'     => 'ss_shop',
			'type'        => 'textarea',
		),
		__( "Extra 10% off on prepaid orders — code `SAANVIKA10`\nFlat ₹500 off on your first order above ₹4,999\nFree fall & pico stitching on all silk sarees\nBank offer: 5% cashback on HDFC credit cards", 'sreesaanvika' ),
		'sanitize_textarea_field'
	);

	/* -----------------------------------------------------------------
	 * Footer
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_footer',
		array(
			'title' => __( 'Footer', 'sreesaanvika' ),
			'panel' => 'ss_panel',
		)
	);

	$add( 'footer_about', array( 'label' => __( 'About text', 'sreesaanvika' ), 'section' => 'ss_footer', 'type' => 'textarea' ), __( 'Sree Saanvika brings you handloom sarees, temple jewellery and festive dresses sourced directly from Indian weavers and artisans — honest pricing, heirloom quality.', 'sreesaanvika' ), 'sanitize_textarea_field' );
	$add( 'footer_address', array( 'label' => __( 'Address', 'sreesaanvika' ), 'section' => 'ss_footer', 'type' => 'textarea' ), __( "Plot 42, Jubilee Hills Road No. 36,\nHyderabad, Telangana 500033", 'sreesaanvika' ), 'sanitize_textarea_field' );
	$add( 'footer_phone', array( 'label' => __( 'Phone', 'sreesaanvika' ), 'section' => 'ss_footer' ), '+91 98765 43210' );
	$add( 'footer_email', array( 'label' => __( 'Email', 'sreesaanvika' ), 'section' => 'ss_footer' ), 'care@sreesaanvika.in', 'sanitize_email' );
	$add( 'footer_hours', array( 'label' => __( 'Support hours', 'sreesaanvika' ), 'section' => 'ss_footer' ), __( 'Mon–Sat, 10 am – 7 pm IST', 'sreesaanvika' ) );
	$add( 'footer_copy', array( 'label' => __( 'Copyright line', 'sreesaanvika' ), 'section' => 'ss_footer' ), '' );

	foreach ( array( 'instagram', 'facebook', 'youtube', 'whatsapp', 'pinterest' ) as $net ) {
		$add(
			'social_' . $net,
			array(
				/* translators: %s: social network name */
				'label'   => sprintf( __( '%s URL', 'sreesaanvika' ), ucfirst( $net ) ),
				'section' => 'ss_footer',
				'type'    => 'url',
			),
			'',
			'esc_url_raw'
		);
	}

	$add( 'gram_handle', array( 'label' => __( 'Instagram handle (without @)', 'sreesaanvika' ), 'section' => 'ss_footer' ), 'sreesaanvika' );

	/* -----------------------------------------------------------------
	 * Typography
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_type',
		array(
			'title' => __( 'Typography', 'sreesaanvika' ),
			'panel' => 'ss_panel',
		)
	);

	$add(
		'font_head',
		array(
			'label'   => __( 'Heading font', 'sreesaanvika' ),
			'section' => 'ss_type',
			'type'    => 'select',
			'choices' => array(
				'"Playfair Display", Georgia, serif'    => 'Playfair Display',
				'"Cormorant Garamond", Georgia, serif'  => 'Cormorant Garamond',
				'"Jost", sans-serif'                    => 'Jost',
			),
		),
		'"Playfair Display", Georgia, serif',
		'ss_sanitize_choice_open'
	);

	$add( 'font_scale', array( 'label' => __( 'Base font size (px)', 'sreesaanvika' ), 'section' => 'ss_type', 'type' => 'number', 'input_attrs' => array( 'min' => 14, 'max' => 19 ) ), 16, 'absint' );
	$add( 'radius', array( 'label' => __( 'Corner rounding (px)', 'sreesaanvika' ), 'section' => 'ss_type', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 24 ) ), 10, 'absint' );
	$add( 'container', array( 'label' => __( 'Max content width (px)', 'sreesaanvika' ), 'section' => 'ss_type', 'type' => 'number', 'input_attrs' => array( 'min' => 1100, 'max' => 1700 ) ), 1320, 'absint' );
}
add_action( 'customize_register', 'ss_customize_register' );

/**
 * Checkbox sanitiser.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function ss_sanitize_bool( $value ) {
	return (bool) $value;
}

/**
 * Select sanitiser bound to the registered choices.
 *
 * @param string               $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return string
 */
function ss_sanitize_choice( $value, $setting = null ) {
	if ( ! $setting instanceof WP_Customize_Setting ) {
		return sanitize_text_field( $value );
	}

	$control = $setting->manager->get_control( $setting->id );

	if ( $control && isset( $control->choices[ $value ] ) ) {
		return $value;
	}

	return $setting->default;
}

/**
 * Font-stack sanitiser — allows quotes and commas.
 *
 * @param string $value Raw value.
 * @return string
 */
function ss_sanitize_choice_open( $value ) {
	return preg_replace( '/[^a-zA-Z0-9 ,\'"\-]/', '', (string) $value );
}

/**
 * Allow a small set of inline tags in headline options.
 *
 * @param string $value Raw value.
 * @return string
 */
function ss_sanitize_html( $value ) {
	return wp_kses(
		$value,
		array(
			'em'     => array(),
			'strong' => array(),
			'br'     => array(),
			'span'   => array( 'class' => array() ),
		)
	);
}

/**
 * Live-preview script for the customizer.
 */
function ss_customize_preview_js() {
	wp_enqueue_script(
		'ss-customize-preview',
		SS_URI . '/assets/js/customizer.js',
		array( 'customize-preview' ),
		SS_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'ss_customize_preview_js' );
