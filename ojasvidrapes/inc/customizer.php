<?php
/**
 * Customizer options.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register panels, sections and settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer.
 */
function od_customize_register( $wp_customize ) {

	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	$wp_customize->selective_refresh->add_partial(
		'blogname',
		array(
			'selector'        => '.od-brand__name',
			'render_callback' => function () {
				return get_bloginfo( 'name' );
			},
		)
	);

	/* -----------------------------------------------------------------
	 * Panel
	 * -------------------------------------------------------------- */
	$wp_customize->add_panel(
		'od_panel',
		array(
			'title'       => __( 'Ojasvi Drapes Options', 'ojasvidrapes' ),
			'description' => __( 'Everything that makes the theme yours — colours, header, homepage sections and shop behaviour.', 'ojasvidrapes' ),
			'priority'    => 10,
		)
	);

	/**
	 * Helper to add a setting + control in one call.
	 *
	 * The default is never passed in — it always comes from od_defaults(), the
	 * same registry od_option() reads on the front end, so the Customizer
	 * preview and the live site can never disagree.
	 *
	 * @param string $id       Setting id (without od_).
	 * @param array  $args     Control args.
	 * @param string $sanitize Sanitize callback.
	 */
	$add = function ( $id, $args, $sanitize = 'sanitize_text_field' ) use ( $wp_customize ) {
		$wp_customize->add_setting(
			'od_' . $id,
			array(
				'default'           => od_default( $id ),
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			)
		);

		$type = isset( $args['type'] ) ? $args['type'] : 'text';

		if ( 'color' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Color_Control( $wp_customize, 'od_' . $id, $args )
			);
		} elseif ( 'image' === $type ) {
			$wp_customize->add_control(
				new WP_Customize_Image_Control( $wp_customize, 'od_' . $id, $args )
			);
		} elseif ( 'picker' === $type && class_exists( 'OD_Customize_Picker' ) ) {
			unset( $args['type'] );

			$wp_customize->add_control(
				new OD_Customize_Picker( $wp_customize, 'od_' . $id, $args )
			);
		} else {
			$wp_customize->add_control( 'od_' . $id, $args );
		}
	};

	/* -----------------------------------------------------------------
	 * Colours
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_colors',
		array(
			'title'       => __( 'Colours & Palette', 'ojasvidrapes' ),
			'panel'       => 'od_panel',
			'description' => __( 'The theme is built dark by design. These control the accent metals and the depth of the background.', 'ojasvidrapes' ),
		)
	);

	$colors = array(
		'color_bg'         => __( 'Page background', 'ojasvidrapes' ),
		'color_surface'    => __( 'Card surface', 'ojasvidrapes' ),
		'color_gold'       => __( 'Primary accent (gold)', 'ojasvidrapes' ),
		'color_gold_light' => __( 'Accent highlight', 'ojasvidrapes' ),
		'color_maroon'     => __( 'Secondary accent (maroon)', 'ojasvidrapes' ),
		'color_marigold'   => __( 'Tertiary accent (marigold)', 'ojasvidrapes' ),
		'color_text'       => __( 'Body text', 'ojasvidrapes' ),
	);

	foreach ( $colors as $id => $label ) {
		$add(
			$id,
			array(
				'label'   => $label,
				'section' => 'od_colors',
				'type'    => 'color',
			),
			'sanitize_hex_color'
		);
	}

	$add(
		'palette_preset',
		array(
			'label'       => __( 'Quick palette preset', 'ojasvidrapes' ),
			'description' => __( 'Applies a curated colour set on top of the values above.', 'ojasvidrapes' ),
			'section'     => 'od_colors',
			'type'        => 'select',
			'choices'     => array(
				''         => __( 'Custom (use the colours above)', 'ojasvidrapes' ),
				'aubergine' => __( 'Aubergine & Gold (default)', 'ojasvidrapes' ),
				'midnight' => __( 'Midnight Peacock', 'ojasvidrapes' ),
				'espresso' => __( 'Espresso & Copper', 'ojasvidrapes' ),
				'ink'      => __( 'Temple Ink & Emerald', 'ojasvidrapes' ),
			),
		),
		'od_sanitize_choice'
	);

	/* -----------------------------------------------------------------
	 * Header
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_header',
		array(
			'title' => __( 'Header & Top Bar', 'ojasvidrapes' ),
			'panel' => 'od_panel',
		)
	);

	$add( 'brand_tagline', array( 'label' => __( 'Brand tagline (under the logo)', 'ojasvidrapes' ), 'section' => 'od_header' ) );
	$add( 'topbar_on', array( 'label' => __( 'Show the announcement bar', 'ojasvidrapes' ), 'section' => 'od_header', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add(
		'topbar_items',
		array(
			'label'       => __( 'Announcement messages', 'ojasvidrapes' ),
			'description' => __( 'One per line. They scroll across the top bar.', 'ojasvidrapes' ),
			'section'     => 'od_header',
			'type'        => 'textarea',
		),
		'sanitize_textarea_field'
	);
	$add( 'topbar_phone', array( 'label' => __( 'Top bar phone number', 'ojasvidrapes' ), 'section' => 'od_header' ) );
	$add( 'sticky_header', array( 'label' => __( 'Sticky header on scroll', 'ojasvidrapes' ), 'section' => 'od_header', 'type' => 'checkbox' ), 'od_sanitize_bool' );

	/* -----------------------------------------------------------------
	 * Homepage — hero
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_hero',
		array(
			'title'       => __( 'Homepage — Hero Slider', 'ojasvidrapes' ),
			'panel'       => 'od_panel',
			'description' => __( 'Up to three slides. Leave a title empty to skip that slide.', 'ojasvidrapes' ),
		)
	);

	foreach ( array( 1, 2, 3 ) as $i ) {
		$add( "hero{$i}_eyebrow", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — eyebrow', 'ojasvidrapes' ), $i ), 'section' => 'od_hero' ) );
		$add( "hero{$i}_title", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — title (use <em> for the gold words)', 'ojasvidrapes' ), $i ), 'section' => 'od_hero', 'type' => 'textarea' ), 'od_sanitize_html' );
		$add( "hero{$i}_text", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — description', 'ojasvidrapes' ), $i ), 'section' => 'od_hero', 'type' => 'textarea' ), 'sanitize_textarea_field' );
		$add( "hero{$i}_btn", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — button label', 'ojasvidrapes' ), $i ), 'section' => 'od_hero' ) );
		$add( "hero{$i}_url", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — button link', 'ojasvidrapes' ), $i ), 'section' => 'od_hero', 'type' => 'url' ), 'esc_url_raw' );
		$add( "hero{$i}_img", array( 'label' => sprintf( /* translators: %d: slide number */ __( 'Slide %d — background image', 'ojasvidrapes' ), $i ), 'section' => 'od_hero', 'type' => 'image' ), 'esc_url_raw' );
		$add(
			"hero{$i}_align",
			array(
				'label'   => sprintf( /* translators: %d: slide number */ __( 'Slide %d — text position', 'ojasvidrapes' ), $i ),
				'section' => 'od_hero',
				'type'    => 'select',
				'choices' => array(
					'left'   => __( 'Left', 'ojasvidrapes' ),
					'center' => __( 'Centre', 'ojasvidrapes' ),
					'right'  => __( 'Right', 'ojasvidrapes' ),
				),
			),
			'od_sanitize_choice'
		);
	}

	$add( 'hero_autoplay', array( 'label' => __( 'Auto-advance slides', 'ojasvidrapes' ), 'section' => 'od_hero', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'hero_speed', array( 'label' => __( 'Seconds per slide', 'ojasvidrapes' ), 'section' => 'od_hero', 'type' => 'number', 'input_attrs' => array( 'min' => 3, 'max' => 20 ) ), 'absint' );

	/* -----------------------------------------------------------------
	 * Homepage — sections
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_home',
		array(
			'title' => __( 'Homepage — Sections', 'ojasvidrapes' ),
			'panel' => 'od_panel',
		)
	);

	$toggles = array(
		'sec_usp'         => __( 'Trust / USP strip', 'ojasvidrapes' ),
		'sec_catrail'     => __( 'Round category rail', 'ojasvidrapes' ),
		'sec_cats'        => __( 'Category mosaic', 'ojasvidrapes' ),
		'sec_new'         => __( 'New arrivals', 'ojasvidrapes' ),
		'sec_promo'       => __( 'Offer banners', 'ojasvidrapes' ),
		'sec_bestsellers' => __( 'Best sellers', 'ojasvidrapes' ),
		'sec_deal'        => __( 'Deal of the day (countdown)', 'ojasvidrapes' ),
		'sec_sarees'      => __( 'Saree spotlight', 'ojasvidrapes' ),
		'sec_jewel'       => __( 'Jewellery spotlight', 'ojasvidrapes' ),
		'sec_lookbook'    => __( 'Lookbook strip', 'ojasvidrapes' ),
		'sec_band'        => __( 'Story band', 'ojasvidrapes' ),
		'sec_reviews'     => __( 'Customer reviews', 'ojasvidrapes' ),
		'sec_blog'        => __( 'Journal / blog posts', 'ojasvidrapes' ),
		'sec_gram'        => __( 'Instagram grid', 'ojasvidrapes' ),
		'sec_newsletter'  => __( 'Newsletter', 'ojasvidrapes' ),
	);

	foreach ( $toggles as $id => $label ) {
		$add(
			$id,
			array(
				'label'   => $label,
				'section' => 'od_home',
				'type'    => 'checkbox',
			),
			'od_sanitize_bool'
		);
	}

	/* -- The collections mosaic -- */
	$add(
		'cats_source',
		array(
			'label'       => __( 'The collections — show', 'ojasvidrapes' ),
			'description' => __( 'The big mosaic under the hero. Categories send a shopper browsing; products send them straight to one piece.', 'ojasvidrapes' ),
			'section'     => 'od_home',
			'type'        => 'select',
			'choices'     => array(
				'categories' => __( 'Categories', 'ojasvidrapes' ),
				'products'   => __( 'Chosen products', 'ojasvidrapes' ),
			),
		),
		'od_sanitize_choice'
	);

	$add(
		'cats_slugs',
		array(
			'label'       => __( 'The collections — which categories', 'ojasvidrapes' ),
			'description' => __( 'Search, tick, and drag into the order they should appear. Leave empty to use the busiest categories automatically.', 'ojasvidrapes' ),
			'section'     => 'od_home',
			'type'        => 'picker',
			'entity'      => 'product_cat',
		),
		'sanitize_textarea_field'
	);

	$add(
		'cats_products',
		array(
			'label'       => __( 'The collections — which products', 'ojasvidrapes' ),
			'description' => __( 'Used when the mosaic is set to products. Search, choose, and drag into order.', 'ojasvidrapes' ),
			'section'     => 'od_home',
			'type'        => 'picker',
			'entity'      => 'product',
		),
		'sanitize_textarea_field'
	);

	$add( 'cats_count', array( 'label' => __( 'The collections — how many (automatic)', 'ojasvidrapes' ), 'section' => 'od_home', 'type' => 'number', 'input_attrs' => array( 'min' => 1, 'max' => 20 ) ), 'absint' );

	/* -- Shop by category rail -- */
	$add(
		'catrail_slugs',
		array(
			'label'       => __( 'Shop by category — which categories', 'ojasvidrapes' ),
			'description' => __( 'The round rail. Search, tick, and drag into order. Leave empty for the busiest categories.', 'ojasvidrapes' ),
			'section'     => 'od_home',
			'type'        => 'picker',
			'entity'      => 'product_cat',
		),
		'sanitize_textarea_field'
	);

	$add( 'catrail_count', array( 'label' => __( 'Shop by category — how many (automatic)', 'ojasvidrapes' ), 'section' => 'od_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 30 ) ), 'absint' );
	$add( 'catrail_top_level', array( 'label' => __( 'Shop by category — top-level categories only', 'ojasvidrapes' ), 'section' => 'od_home', 'type' => 'checkbox' ), 'od_sanitize_bool' );

	$add( 'loadmore', array( 'label' => __( 'Show a "Load more" button under product sections', 'ojasvidrapes' ), 'section' => 'od_home', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'loadmore_step', array( 'label' => __( 'How many more each click loads', 'ojasvidrapes' ), 'section' => 'od_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 24 ) ), 'absint' );

	$add( 'products_per_section', array( 'label' => __( 'Products shown per section', 'ojasvidrapes' ), 'section' => 'od_home', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 12 ) ), 'absint' );

	/* -----------------------------------------------------------------
	 * Promo banners
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_promo',
		array(
			'title' => __( 'Homepage — Offer Banners', 'ojasvidrapes' ),
			'panel' => 'od_panel',
		)
	);

	$add( 'promo1_off', array( 'label' => __( 'Banner 1 — big text', 'ojasvidrapes' ), 'section' => 'od_promo' ) );
	$add( 'promo1_title', array( 'label' => __( 'Banner 1 — heading', 'ojasvidrapes' ), 'section' => 'od_promo' ) );
	$add( 'promo1_text', array( 'label' => __( 'Banner 1 — text', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'promo1_url', array( 'label' => __( 'Banner 1 — link', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'url' ), 'esc_url_raw' );
	$add( 'promo1_img', array( 'label' => __( 'Banner 1 — image', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'image' ), 'esc_url_raw' );

	$add( 'promo2_off', array( 'label' => __( 'Banner 2 — big text', 'ojasvidrapes' ), 'section' => 'od_promo' ) );
	$add( 'promo2_title', array( 'label' => __( 'Banner 2 — heading', 'ojasvidrapes' ), 'section' => 'od_promo' ) );
	$add( 'promo2_text', array( 'label' => __( 'Banner 2 — text', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'promo2_url', array( 'label' => __( 'Banner 2 — link', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'url' ), 'esc_url_raw' );
	$add( 'promo2_img', array( 'label' => __( 'Banner 2 — image', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'image' ), 'esc_url_raw' );

	$add( 'deal_end', array( 'label' => __( 'Deal of the day — end date/time', 'ojasvidrapes' ), 'description' => __( 'Format: YYYY-MM-DD HH:MM', 'ojasvidrapes' ), 'section' => 'od_promo' ) );
	$add( 'band_img', array( 'label' => __( 'Story band — background image', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'image' ), 'esc_url_raw' );
	$add( 'band_title', array( 'label' => __( 'Story band — heading', 'ojasvidrapes' ), 'section' => 'od_promo' ) );
	$add( 'band_text', array( 'label' => __( 'Story band — text', 'ojasvidrapes' ), 'section' => 'od_promo', 'type' => 'textarea' ), 'sanitize_textarea_field' );

	/* -----------------------------------------------------------------
	 * Shop
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_shop',
		array(
			'title' => __( 'Shop & Product Page', 'ojasvidrapes' ),
			'panel' => 'od_panel',
		)
	);

	$add( 'shop_columns', array( 'label' => __( 'Products per row', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 6 ) ), 'absint' );
	$add( 'shop_per_page', array( 'label' => __( 'Products per page', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 4, 'max' => 60 ) ), 'absint' );
	$add( 'shop_sidebar', array( 'label' => __( 'Show the filter sidebar', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'card_hover_img', array( 'label' => __( 'Swap to the second image on hover', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'card_swatches', array( 'label' => __( 'Show colour swatches on product cards', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'quickview', array( 'label' => __( 'Enable quick view', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'wishlist_on', array( 'label' => __( 'Enable wishlist', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'compare_on', array( 'label' => __( 'Enable compare', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'compare_max', array( 'label' => __( 'Maximum products to compare', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'number', 'input_attrs' => array( 'min' => 2, 'max' => 6 ) ), 'absint' );
	$add( 'use_woo_gallery', array( 'label' => __( 'Use the default WooCommerce gallery instead of the theme gallery', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'sticky_buy', array( 'label' => __( 'Sticky add-to-cart bar on mobile', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'pincode_check', array( 'label' => __( 'Show the delivery PIN code checker', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'free_ship_threshold', array( 'label' => __( 'Free shipping threshold (₹)', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'number' ), 'absint' );
	$add( 'stock_alert_qty', array( 'label' => __( 'Show "only N left" below this stock level', 'ojasvidrapes' ), 'section' => 'od_shop', 'type' => 'number' ), 'absint' );
	$add(
		'offers_text',
		array(
			'label'       => __( 'Offer lines on the product page', 'ojasvidrapes' ),
			'description' => __( 'One per line. Wrap a coupon code in backticks to highlight it.', 'ojasvidrapes' ),
			'section'     => 'od_shop',
			'type'        => 'textarea',
		),
		'sanitize_textarea_field'
	);

	/* -----------------------------------------------------------------
	 * Footer
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_footer',
		array(
			'title' => __( 'Footer', 'ojasvidrapes' ),
			'panel' => 'od_panel',
		)
	);

	$add( 'footer_about', array( 'label' => __( 'About text', 'ojasvidrapes' ), 'section' => 'od_footer', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'footer_address', array( 'label' => __( 'Address', 'ojasvidrapes' ), 'section' => 'od_footer', 'type' => 'textarea' ), 'sanitize_textarea_field' );
	$add( 'footer_phone', array( 'label' => __( 'Phone', 'ojasvidrapes' ), 'section' => 'od_footer' ) );
	$add( 'footer_email', array( 'label' => __( 'Email', 'ojasvidrapes' ), 'section' => 'od_footer' ), 'sanitize_email' );
	$add( 'footer_hours', array( 'label' => __( 'Support hours', 'ojasvidrapes' ), 'section' => 'od_footer' ) );
	$add( 'footer_copy', array( 'label' => __( 'Copyright line', 'ojasvidrapes' ), 'section' => 'od_footer' ) );

	foreach ( array( 'instagram', 'facebook', 'youtube', 'whatsapp', 'pinterest' ) as $net ) {
		$add(
			'social_' . $net,
			array(
				/* translators: %s: social network name */
				'label'   => sprintf( __( '%s URL', 'ojasvidrapes' ), ucfirst( $net ) ),
				'section' => 'od_footer',
				'type'    => 'url',
			),
			'esc_url_raw'
		);
	}

	$add( 'gram_handle', array( 'label' => __( 'Instagram handle (without @)', 'ojasvidrapes' ), 'section' => 'od_footer' ) );

	/* -----------------------------------------------------------------
	 * SEO & social
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_seo',
		array(
			'title'       => __( 'SEO & Social Sharing', 'ojasvidrapes' ),
			'panel'       => 'od_panel',
			'description' => __( 'The theme only writes these tags when no SEO plugin is active. Install Yoast or Rank Math and it steps aside automatically — except the verification codes and the noindex rules for cart, checkout and account, which stay.', 'ojasvidrapes' ),
		)
	);

	$add( 'seo_enable', array( 'label' => __( 'Output meta tags and Open Graph', 'ojasvidrapes' ), 'section' => 'od_seo', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add( 'seo_schema', array( 'label' => __( 'Output structured data (schema.org)', 'ojasvidrapes' ), 'section' => 'od_seo', 'type' => 'checkbox' ), 'od_sanitize_bool' );
	$add(
		'seo_meta_home',
		array(
			'label'       => __( 'Homepage meta description', 'ojasvidrapes' ),
			'description' => __( 'Aim for 140–155 characters. Also used as the fallback anywhere else.', 'ojasvidrapes' ),
			'section'     => 'od_seo',
			'type'        => 'textarea',
		),
		'sanitize_textarea_field'
	);
	$add( 'seo_og_image', array( 'label' => __( 'Default share image', 'ojasvidrapes' ), 'description' => __( '1200 × 630 works best.', 'ojasvidrapes' ), 'section' => 'od_seo', 'type' => 'image' ), 'esc_url_raw' );
	$add( 'seo_twitter', array( 'label' => __( 'X / Twitter handle', 'ojasvidrapes' ), 'section' => 'od_seo' ) );
	$add(
		'seo_org_type',
		array(
			'label'   => __( 'Business type in structured data', 'ojasvidrapes' ),
			'section' => 'od_seo',
			'type'    => 'select',
			'choices' => array(
				'OnlineStore'   => __( 'Online store', 'ojasvidrapes' ),
				'Store'         => __( 'Store', 'ojasvidrapes' ),
				'ClothingStore' => __( 'Clothing store', 'ojasvidrapes' ),
				'LocalBusiness' => __( 'Local business', 'ojasvidrapes' ),
				'Organization'  => __( 'Organisation', 'ojasvidrapes' ),
			),
		),
		'od_sanitize_choice'
	);
	$add( 'seo_verify_google', array( 'label' => __( 'Google Search Console code', 'ojasvidrapes' ), 'section' => 'od_seo' ) );
	$add( 'seo_verify_bing', array( 'label' => __( 'Bing Webmaster code', 'ojasvidrapes' ), 'section' => 'od_seo' ) );
	$add( 'seo_verify_facebook', array( 'label' => __( 'Facebook domain verification', 'ojasvidrapes' ), 'section' => 'od_seo' ) );
	$add( 'seo_verify_pinterest', array( 'label' => __( 'Pinterest domain verification', 'ojasvidrapes' ), 'section' => 'od_seo' ) );

	/* -----------------------------------------------------------------
	 * Policies
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_policy',
		array(
			'title'       => __( 'Policies & Legal', 'ojasvidrapes' ),
			'panel'       => 'od_panel',
			'description' => __( 'These figures are written into the policy pages and into the product structured data Google reads, so they only need changing in one place.', 'ojasvidrapes' ),
		)
	);

	$add( 'legal_entity', array( 'label' => __( 'Registered business name', 'ojasvidrapes' ), 'section' => 'od_policy' ) );
	$add( 'legal_gstin', array( 'label' => __( 'GSTIN', 'ojasvidrapes' ), 'section' => 'od_policy' ) );
	$add( 'legal_jurisdiction', array( 'label' => __( 'Legal jurisdiction (city, state)', 'ojasvidrapes' ), 'section' => 'od_policy' ) );
	$add( 'grievance_officer', array( 'label' => __( 'Grievance officer name', 'ojasvidrapes' ), 'description' => __( 'India\'s Consumer Protection (E-Commerce) Rules require one to be named.', 'ojasvidrapes' ), 'section' => 'od_policy' ) );
	$add( 'returns_window_days', array( 'label' => __( 'Return window (days)', 'ojasvidrapes' ), 'section' => 'od_policy', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 90 ) ), 'absint' );
	$add( 'flat_ship_rate', array( 'label' => __( 'Flat shipping rate below the free threshold (₹)', 'ojasvidrapes' ), 'section' => 'od_policy', 'type' => 'number' ), 'absint' );
	$add( 'cod_limit', array( 'label' => __( 'Cash-on-delivery limit (₹)', 'ojasvidrapes' ), 'section' => 'od_policy', 'type' => 'number' ), 'absint' );
	$add( 'policy_updated', array( 'label' => __( 'Policies last updated', 'ojasvidrapes' ), 'description' => __( 'Shown on every policy page. Leave empty to use each page\'s modified date.', 'ojasvidrapes' ), 'section' => 'od_policy' ) );

	/* -----------------------------------------------------------------
	 * Preloader
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_preloader',
		array(
			'title'       => __( 'Loading Screen', 'ojasvidrapes' ),
			'panel'       => 'od_panel',
			'description' => __( 'The gold medallion curtain shown while a page loads. Turn it off here at any time — nothing else changes.', 'ojasvidrapes' ),
		)
	);

	$add(
		'preloader_on',
		array(
			'label'       => __( 'Show the loading screen', 'ojasvidrapes' ),
			'section'     => 'od_preloader',
			'type'        => 'checkbox',
			'description' => __( 'Off is off everywhere, straight away.', 'ojasvidrapes' ),
		),
		'od_sanitize_bool'
	);

	$add(
		'preloader_ms',
		array(
			'label'       => __( 'How long it stays, in milliseconds', 'ojasvidrapes' ),
			'section'     => 'od_preloader',
			'type'        => 'number',
			'input_attrs' => array(
				'min'  => 300,
				'max'  => 6000,
				'step' => 100,
			),
			'description' => __( '2000 is two seconds. Anything past about 2500 starts to feel slow.', 'ojasvidrapes' ),
		),
		'absint'
	);

	$add(
		'preloader_transitions',
		array(
			'label'       => __( 'Show it between pages too', 'ojasvidrapes' ),
			'section'     => 'od_preloader',
			'type'        => 'checkbox',
			'description' => __( 'The curtain comes back down when a shopper follows a link, so moving around the shop feels like one piece.', 'ojasvidrapes' ),
		),
		'od_sanitize_bool'
	);

	$add(
		'preloader_once',
		array(
			'label'       => __( 'Only on the first page of a visit', 'ojasvidrapes' ),
			'section'     => 'od_preloader',
			'type'        => 'checkbox',
			'description' => __( 'Kinder to a returning shopper: they see it once and then never again until they come back.', 'ojasvidrapes' ),
		),
		'od_sanitize_bool'
	);

	$add(
		'preloader_text',
		array(
			'label'       => __( 'Name on the loading screen', 'ojasvidrapes' ),
			'section'     => 'od_preloader',
			'description' => __( 'Leave empty to use the site title.', 'ojasvidrapes' ),
		)
	);

	/* -----------------------------------------------------------------
	 * Typography
	 * -------------------------------------------------------------- */
	$wp_customize->add_section(
		'od_type',
		array(
			'title' => __( 'Typography', 'ojasvidrapes' ),
			'panel' => 'od_panel',
		)
	);

	$add(
		'font_head',
		array(
			'label'   => __( 'Heading font', 'ojasvidrapes' ),
			'section' => 'od_type',
			'type'    => 'select',
			'choices' => array(
				'"Playfair Display", Georgia, serif'    => 'Playfair Display',
				'"Cormorant Garamond", Georgia, serif'  => 'Cormorant Garamond',
				'"Jost", sans-serif'                    => 'Jost',
			),
		),
		'od_sanitize_choice_open'
	);

	$add( 'font_scale', array( 'label' => __( 'Base font size (px)', 'ojasvidrapes' ), 'section' => 'od_type', 'type' => 'number', 'input_attrs' => array( 'min' => 14, 'max' => 19 ) ), 'absint' );
	$add( 'radius', array( 'label' => __( 'Corner rounding (px)', 'ojasvidrapes' ), 'section' => 'od_type', 'type' => 'number', 'input_attrs' => array( 'min' => 0, 'max' => 24 ) ), 'absint' );
	$add( 'container', array( 'label' => __( 'Max content width (px)', 'ojasvidrapes' ), 'section' => 'od_type', 'type' => 'number', 'input_attrs' => array( 'min' => 1100, 'max' => 1700 ) ), 'absint' );
}
add_action( 'customize_register', 'od_customize_register' );

/**
 * Checkbox sanitiser.
 *
 * @param mixed $value Raw value.
 * @return bool
 */
function od_sanitize_bool( $value ) {
	return (bool) $value;
}

/**
 * Select sanitiser bound to the registered choices.
 *
 * @param string               $value   Raw value.
 * @param WP_Customize_Setting $setting Setting.
 * @return string
 */
function od_sanitize_choice( $value, $setting = null ) {
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
function od_sanitize_choice_open( $value ) {
	return preg_replace( '/[^a-zA-Z0-9 ,\'"\-]/', '', (string) $value );
}

/**
 * Allow a small set of inline tags in headline options.
 *
 * @param string $value Raw value.
 * @return string
 */
function od_sanitize_html( $value ) {
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
function od_customize_preview_js() {
	wp_enqueue_script(
		'od-customize-preview',
		OD_URI . '/assets/js/customizer.js',
		array( 'customize-preview' ),
		OD_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'od_customize_preview_js' );
