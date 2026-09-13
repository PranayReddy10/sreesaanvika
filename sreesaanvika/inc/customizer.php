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
	 * The default is never passed in — it always comes from ss_defaults(), the
	 * same registry ss_option() reads on the front end, so the Customizer
	 * preview and the live site can never disagree.
	 *
	 * @param string $id       Setting id (without ss_).
	 * @param array  $args     Control args.
	 * @param string $sanitize Sanitize callback.
	 */
	$add = function ( $id, $args, $sanitize = 'sanitize_text_field' ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'ss_' . $id,
			array(
				'default'           => ss_default( $id ),
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
		} elseif ( 'picker' === $type && class_exists( 'SS_Customize_Picker' ) ) {
			unset( $args['type'] );

			$wp_customize->add_control(
				new SS_Customize_Picker( $wp_customize, 'ss_' . $id, $args )
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
		'color_bg'         => __( 'Page background', 'sreesaanvika' ),
		'color_surface'    => __( 'Card surface', 'sreesaanvika' ),
		'color_gold'       => __( 'Primary accent (gold)', 'sreesaanvika' ),
		'color_gold_light' => __( 'Accent highlight', 'sreesaanvika' ),
		'color_maroon'     => __( 'Secondary accent (maroon)', 'sreesaanvika' ),
		'color_marigold'   => __( 'Tertiary accent (marigold)', 'sreesaanvika' ),
		'color_text'       => __( 'Body text', 'sreesaanvika' ),
	);

	foreach ( $colors as $id => $label ) {
		$add(
			$id,
			array(
				'label'   => $label,
				'section' => 'ss_colors',
				'type'    => 'color',
			),
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

	$add( 'brand_tagline', array( 'label' => __( 'Brand tagline (under the logo)', 'sreesaanvika' ), 'section' => 'ss_header' ) );
	$add( 'topbar_on', array( 'label' => __( 'Show the announcement bar', 'sreesaanvika' ), 'section' => 'ss_header', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add(
		'topbar_items',
		array(
			'label'       => __( 'Announcement messages', 'sreesaanvika' ),
			'description' => __( 'One per line. They scroll across the top bar.', 'sreesaanvika' ),
			'section'     => 'ss_header',
			'type'        => 'textarea',
		),
		'sanitize_textarea_field'
	);
	$add( 'topbar_phone', array( 'label' => __( 'Top bar phone number', 'sreesaanvika' ), 'section' => 'ss_header' ) );
	$add( 'sticky_header', array( 'label' => __( 'Sticky header on scroll', 'sreesaanvika' ), 'section' => 'ss_header', 'type' => 'checkbox' ), 'ss_sanitize_bool' );

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

	foreach ( array( 1, 2, 3 ) as $i ) {
		$add( "hero{$i}_eyebrow", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — eyebrow', 'sreesaanvika' ), $i ), 'section' => 'ss_hero' ) );
		$add( "hero{$i}_title", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — title (use <em> for the gold words)', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'textarea' ), 'ss_sanitize_html' );
		$add( "hero{$i}_text", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — description', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'textarea' ), 'sanitize_textarea_field' );
		$add( "hero{$i}_btn", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — button label', 'sreesaanvika' ), $i ), 'section' => 'ss_hero' ) );
		$add( "hero{$i}_url", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — button link', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'url' ), 'esc_url_raw' );
		$add( "hero{$i}_img", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — background image', 'sreesaanvika' ), $i ), 'section' => 'ss_hero', 'type' => 'image' ), 'esc_url_raw' );
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
			'ss_sanitize_choice'
		);
	}

	$add( 'hero_autoplay', array( 'label' => __( 'Auto-advance slides', 'sreesaanvika' ), 'section' => 'ss_hero', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'hero_speed', array( 'label' => __( 'Seconds per slide', 'sreesaanvika' ), 'section' => 'ss_hero', 'type' => 'number', 'input_attrs' => array( 'min' => 3, 'max' => 20 ) ), 'absint' );

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
		'sec_usp'         => __( 'Trust / USP strip', 'sreesaanvika' ),
		'sec_catrail'     => __( 'Round category rail', 'sreesaanvika' ),
		'sec_cats'        => __( 'Category mosaic', 'sreesaanvika' ),
		'sec_new'         => __( 'New arrivals', 'sreesaanvika' ),
		'sec_promo'       => __( 'Offer banners', 'sreesaanvika' ),
		'sec_bestsellers' => __( 'Best sellers', 'sreesaanvika' ),
		'sec_deal'        => __( 'Deal of the day (countdown)', 'sreesaanvika' ),
		'sec_sarees'      => __( 'Saree spotlight', 'sreesaanvika' ),
		'sec_jewel'       => __( 'Jewellery spotlight', 'sreesaanvika' ),
		'sec_lookbook'    => __( 'Lookbook strip', 'sreesaanvika' ),
		'sec_band'        => __( 'Story band', 'sreesaanvika' ),
		'sec_reviews'     => __( 'Customer reviews', 'sreesaanvika' ),
		'sec_blog'        => __( 'Journal / blog posts', 'sreesaanvika' ),
		'sec_gram'        => __( 'Instagram grid', 'sreesaanvika' ),
		'sec_newsletter'  => __( 'Newsletter', 'sreesaanvika' ),
	);

	foreach ( $toggles as $id => $label ) {
		$add(
			$id,
			array(
				'label'   => $label,
				'section' => 'ss_home',
				'type'    => 'checkbox',
			),
			'ss_sanitize_bool'
		);
	}

	/* -- The collections mosaic -- */
	$add(
		'cats_source',
		array(
			'label'       => __( 'The collections — show', 'sreesaanvika' ),
			'description' => __( 'The big mosaic under the hero. Categories send a shopper browsing; products send them straight to one piece.', 'sreesaanvika' ),
			'section'     => 'ss_home',
			'type'        => 'select',
			'choices'     => array(
				'categories' => __( 'Categories', 'sreesaanvika' ),
				'products'   => __( 'Chosen products', 'sreesaanvika' ),
			),
		),
		'ss_sanitize_choice'
	);

	$add(
		'cats_slugs',
		array(
			'label'       => __( 'The collections — which categories', 'sreesaanvika' ),
			'description' => __( 'Search, tick, and drag into the order they should appear. Leave empty to use the busiest categories automatically.', 'sreesaanvika' ),
			'section'     => 'ss_home',
			'type'        => 'picker',
			'entity'      => 'product_cat',
		),
		'sanitize_textarea_field'
	);

	$add(
		'cats_products',
		array(
			'label'       => __( 'The collections — which products', 'sreesaanvika' ),
			'description' => __( 'Used when the mosaic is set to products. Search, choose, and drag into order.', 'sreesaanvika' ),
			'section'     => 'ss_home',
			'type'        => 'picker',
			'entity'      => 'product',
		),
		'sanitize_textarea_field'
	);

	$add( 'cats_count', array( 'label' => __( 'The collections — how many (automatic)', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 20 ) ), 'absint' );

	/* -- Shop by category rail -- */
	$add(
		'catrail_slugs',
		array(
			'label'       => __( 'Shop by category — which categories', 'sreesaanvika' ),
			'description' => __( 'The round rail. Search, tick, and drag into order. Leave empty for the busiest categories.', 'sreesaanvika' ),
			'section'     => 'ss_home',
			'type'        => 'picker',
			'entity'      => 'product_cat',
		),
		'sanitize_textarea_field'
	);

	$add( 'catrail_count', array( 'label' => __( 'Shop by category — how many (automatic)', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 30 ) ), 'absint' );
	$add( 'catrail_top_level', array( 'label' => __( 'Shop by category — top-level categories only', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'checkbox' ), 'ss_sanitize_bool' );

	$add( 'loadmore', array( 'label' => __( 'Show a "Load more" button under product sections', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'loadmore_step', array( 'label' => __( 'How many more each click loads', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 24 ) ), 'absint' );

	$add( 'products_per_section', array( 'label' => __( 'Products shown per section', 'sreesaanvika' ), 'section' => 'ss_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 12 ) ), 'absint' );

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

	$add( 'promo1_off', array( 'label' => __( 'Banner 1 — big text', 'sreesaanvika' ), 'section' => 'ss_promo' ) );
	$add( 'promo1_title', array( 'label' => __( 'Banner 1 — heading', 'sreesaanvika' ), 'section' => 'ss_promo' ) );
	$add( 'promo1_text', array( 'label' => __( 'Banner 1 — text', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'promo1_url', array( 'label' => __( 'Banner 1 — link', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'url' ), 'esc_url_raw' );
	$add( 'promo1_img', array( 'label' => __( 'Banner 1 — image', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'image' ), 'esc_url_raw' );

	$add( 'promo2_off', array( 'label' => __( 'Banner 2 — big text', 'sreesaanvika' ), 'section' => 'ss_promo' ) );
	$add( 'promo2_title', array( 'label' => __( 'Banner 2 — heading', 'sreesaanvika' ), 'section' => 'ss_promo' ) );
	$add( 'promo2_text', array( 'label' => __( 'Banner 2 — text', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'promo2_url', array( 'label' => __( 'Banner 2 — link', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'url' ), 'esc_url_raw' );
	$add( 'promo2_img', array( 'label' => __( 'Banner 2 — image', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'image' ), 'esc_url_raw' );

	$add( 'deal_end', array( 'label' => __( 'Deal of the day — end date/time', 'sreesaanvika' ), 'description' => __( 'Format: YYYY-MM-DD HH:MM', 'sreesaanvika' ), 'section' => 'ss_promo' ) );
	$add( 'band_img', array( 'label' => __( 'Story band — background image', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'image' ), 'esc_url_raw' );
	$add( 'band_title', array( 'label' => __( 'Story band — heading', 'sreesaanvika' ), 'section' => 'ss_promo' ) );
	$add( 'band_text', array( 'label' => __( 'Story band — text', 'sreesaanvika' ), 'section' => 'ss_promo', 'type' => 'textarea' ), 'sanitize_textarea_field' );

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

	$add( 'shop_columns', array( 'label' => __( 'Products per row', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 6 ) ), 'absint' );
	$add( 'shop_per_page', array( 'label' => __( 'Products per page', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 4, 'max' => 60 ) ), 'absint' );
	$add( 'shop_sidebar', array( 'label' => __( 'Show the filter sidebar', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'card_hover_img', array( 'label' => __( 'Swap to the second image on hover', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'card_swatches', array( 'label' => __( 'Show colour swatches on product cards', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'quickview', array( 'label' => __( 'Enable quick view', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'wishlist_on', array( 'label' => __( 'Enable wishlist', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'compare_on', array( 'label' => __( 'Enable compare', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'compare_max', array( 'label' => __( 'Maximum products to compare', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 6 ) ), 'absint' );
	$add( 'use_woo_gallery', array( 'label' => __( 'Use the default WooCommerce gallery instead of the theme gallery', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'sticky_buy', array( 'label' => __( 'Sticky add-to-cart bar on mobile', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'pincode_check', array( 'label' => __( 'Show the delivery PIN code checker', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'free_ship_threshold', array( 'label' => __( 'Free shipping threshold (₹)', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number' ), 'absint' );
	$add( 'stock_alert_qty', array( 'label' => __( 'Show "only N left" below this stock level', 'sreesaanvika' ), 'section' => 'ss_shop', 'type' => 'number' ), 'absint' );
	$add(
		'offers_text',
		array(
			'label'       => __( 'Offer lines on the product page', 'sreesaanvika' ),
			'description' => __( 'One per line. Wrap a coupon code in backticks to highlight it.', 'sreesaanvika' ),
			'section'     => 'ss_shop',
			'type'        => 'textarea',
		),
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

	$add( 'footer_about', array( 'label' => __( 'About text', 'sreesaanvika' ), 'section' => 'ss_footer', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'footer_address', array( 'label' => __( 'Address', 'sreesaanvika' ), 'section' => 'ss_footer', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'footer_phone', array( 'label' => __( 'Phone', 'sreesaanvika' ), 'section' => 'ss_footer' ) );
	$add( 'footer_email', array( 'label' => __( 'Email', 'sreesaanvika' ), 'section' => 'ss_footer' ), 'sanitize_email' );
	$add( 'footer_hours', array( 'label' => __( 'Support hours', 'sreesaanvika' ), 'section' => 'ss_footer' ) );
	$add( 'footer_copy', array( 'label' => __( 'Copyright line', 'sreesaanvika' ), 'section' => 'ss_footer' ) );

	foreach ( array( 'instagram', 'facebook', 'youtube', 'whatsapp', 'pinterest' ) as $net ) {
		$add(
			'social_' . $net,
			array(
				/* translators: %s: social network name */
				'label'   => sprintf( __( '%s URL', 'sreesaanvika' ), ucfirst( $net ) ),
				'section' => 'ss_footer',
				'type'    => 'url',
			),
			'esc_url_raw'
		);
	}

	$add( 'gram_handle', array( 'label' => __( 'Instagram handle (without @)', 'sreesaanvika' ), 'section' => 'ss_footer' ) );

	/* -----------------------------------------------------------------
	 * SEO & social
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_seo',
		array(
			'title'       => __( 'SEO & Social Sharing', 'sreesaanvika' ),
			'panel'       => 'ss_panel',
			'description' => __( 'The theme only writes these tags when no SEO plugin is active. Install Yoast or Rank Math and it steps aside automatically — except the verification codes and the noindex rules for cart, checkout and account, which stay.', 'sreesaanvika' ),
		)
	);

	$add( 'seo_enable', array( 'label' => __( 'Output meta tags and Open Graph', 'sreesaanvika' ), 'section' => 'ss_seo', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add( 'seo_schema', array( 'label' => __( 'Output structured data (schema.org)', 'sreesaanvika' ), 'section' => 'ss_seo', 'type' => 'checkbox' ), 'ss_sanitize_bool' );
	$add(
		'seo_meta_home',
		array(
			'label'       => __( 'Homepage meta description', 'sreesaanvika' ),
			'description' => __( 'Aim for 140–155 characters. Also used as the fallback anywhere else.', 'sreesaanvika' ),
			'section'     => 'ss_seo',
			'type'        => 'textarea',
		),
		'sanitize_textarea_field'
	);
	$add( 'seo_og_image', array( 'label' => __( 'Default share image', 'sreesaanvika' ), 'description' => __( '1200 × 630 works best.', 'sreesaanvika' ), 'section' => 'ss_seo', 'type' => 'image' ), 'esc_url_raw' );
	$add( 'seo_twitter', array( 'label' => __( 'X / Twitter handle', 'sreesaanvika' ), 'section' => 'ss_seo' ) );
	$add(
		'seo_org_type',
		array(
			'label'   => __( 'Business type in structured data', 'sreesaanvika' ),
			'section' => 'ss_seo',
			'type'    => 'select',
			'choices' => array(
				'OnlineStore'   => __( 'Online store', 'sreesaanvika' ),
				'Store'         => __( 'Store', 'sreesaanvika' ),
				'ClothingStore' => __( 'Clothing store', 'sreesaanvika' ),
				'LocalBusiness' => __( 'Local business', 'sreesaanvika' ),
				'Organization'  => __( 'Organisation', 'sreesaanvika' ),
			),
		),
		'ss_sanitize_choice'
	);
	$add( 'seo_verify_google', array( 'label' => __( 'Google Search Console code', 'sreesaanvika' ), 'section' => 'ss_seo' ) );
	$add( 'seo_verify_bing', array( 'label' => __( 'Bing Webmaster code', 'sreesaanvika' ), 'section' => 'ss_seo' ) );
	$add( 'seo_verify_facebook', array( 'label' => __( 'Facebook domain verification', 'sreesaanvika' ), 'section' => 'ss_seo' ) );
	$add( 'seo_verify_pinterest', array( 'label' => __( 'Pinterest domain verification', 'sreesaanvika' ), 'section' => 'ss_seo' ) );

	/* -----------------------------------------------------------------
	 * Policies
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_policy',
		array(
			'title'       => __( 'Policies & Legal', 'sreesaanvika' ),
			'panel'       => 'ss_panel',
			'description' => __( 'These figures are written into the policy pages and into the product structured data Google reads, so they only need changing in one place.', 'sreesaanvika' ),
		)
	);

	$add( 'legal_entity', array( 'label' => __( 'Registered business name', 'sreesaanvika' ), 'section' => 'ss_policy' ) );
	$add( 'legal_gstin', array( 'label' => __( 'GSTIN', 'sreesaanvika' ), 'section' => 'ss_policy' ) );
	$add( 'legal_jurisdiction', array( 'label' => __( 'Legal jurisdiction (city, state)', 'sreesaanvika' ), 'section' => 'ss_policy' ) );
	$add( 'grievance_officer', array( 'label' => __( 'Grievance officer name', 'sreesaanvika' ), 'description' => __( 'India\'s Consumer Protection (E-Commerce) Rules require one to be named.', 'sreesaanvika' ), 'section' => 'ss_policy' ) );
	$add( 'returns_window_days', array( 'label' => __( 'Return window (days)', 'sreesaanvika' ), 'section' => 'ss_policy', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 90 ) ), 'absint' );
	$add( 'flat_ship_rate', array( 'label' => __( 'Flat shipping rate below the free threshold (₹)', 'sreesaanvika' ), 'section' => 'ss_policy', 'type' => 'number' ), 'absint' );
	$add( 'cod_limit', array( 'label' => __( 'Cash-on-delivery limit (₹)', 'sreesaanvika' ), 'section' => 'ss_policy', 'type' => 'number' ), 'absint' );
	$add( 'policy_updated', array( 'label' => __( 'Policies last updated', 'sreesaanvika' ), 'description' => __( 'Shown on every policy page. Leave empty to use each page\'s modified date.', 'sreesaanvika' ), 'section' => 'ss_policy' ) );

	/* -----------------------------------------------------------------
	 * Preloader
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'ss_preloader',
		array(
			'title'       => __( 'Loading Screen', 'sreesaanvika' ),
			'panel'       => 'ss_panel',
			'description' => __( 'The gold medallion curtain shown while a page loads. Turn it off here at any time — nothing else changes.', 'sreesaanvika' ),
		)
	);

	$add(
		'preloader_on',
		array(
			'label'       => __( 'Show the loading screen', 'sreesaanvika' ),
			'section'     => 'ss_preloader',
			'type'        => 'checkbox',
			'description' => __( 'Off is off everywhere, straight away.', 'sreesaanvika' ),
		),
		'ss_sanitize_bool'
	);

	$add(
		'preloader_ms',
		array(
			'label'       => __( 'How long it stays, in milliseconds', 'sreesaanvika' ),
			'section'     => 'ss_preloader',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 300,
				'max'  => 6000,
				'step' => 100,
			),
			'description' => __( '2000 is two seconds. Anything past about 2500 starts to feel slow.', 'sreesaanvika' ),
		),
		'absint'
	);

	$add(
		'preloader_transitions',
		array(
			'label'       => __( 'Show it between pages too', 'sreesaanvika' ),
			'section'     => 'ss_preloader',
			'type'        => 'checkbox',
			'description' => __( 'The curtain comes back down when a shopper follows a link, so moving around the shop feels like one piece.', 'sreesaanvika' ),
		),
		'ss_sanitize_bool'
	);

	$add(
		'preloader_once',
		array(
			'label'       => __( 'Only on the first page of a visit', 'sreesaanvika' ),
			'section'     => 'ss_preloader',
			'type'        => 'checkbox',
			'description' => __( 'Kinder to a returning shopper: they see it once and then never again until they come back.', 'sreesaanvika' ),
		),
		'ss_sanitize_bool'
	);

	$add(
		'preloader_text',
		array(
			'label'       => __( 'Name on the loading screen', 'sreesaanvika' ),
			'section'     => 'ss_preloader',
			'description' => __( 'Leave empty to use the site title.', 'sreesaanvika' ),
		)
	);

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
		'ss_sanitize_choice_open'
	);

	$add( 'font_scale', array( 'label' => __( 'Base font size (px)', 'sreesaanvika' ), 'section' => 'ss_type', 'type' => 'number', 'input_attrs' => array( 'min' => 14, 'max' => 19 ) ), 'absint' );
	$add( 'radius', array( 'label' => __( 'Corner rounding (px)', 'sreesaanvika' ), 'section' => 'ss_type', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 24 ) ), 'absint' );
	$add( 'container', array( 'label' => __( 'Max content width (px)', 'sreesaanvika' ), 'section' => 'ss_type', 'type' => 'number', 'input_attrs' => array( 'min' => 1100, 'max' => 1700 ) ), 'absint' );
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
