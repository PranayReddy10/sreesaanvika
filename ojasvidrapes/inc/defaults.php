<?php
/**
 * Default values for every theme option.
 *
 * This is the single source of truth. Both `od_option()` on the front end and
 * the Customizer controls read from here, so the two can never drift.
 *
 * Why it matters: `get_theme_mod()` does NOT know about the default registered
 * on a Customizer setting. It only returns the default passed to it. Inside the
 * Customizer preview WordPress filters `theme_mod_*` and hands back the
 * setting's registered default, so a section looks fine there and then renders
 * empty on the live site. Keeping the defaults here and feeding them to
 * `get_theme_mod()` is what keeps preview and production in step.
 *
 * Defaults are deliberately plain strings rather than __() calls: this array is
 * read during `after_setup_theme`, before the text domain is loaded, and
 * translating that early triggers a doing_it_wrong notice in WordPress 6.7+.
 * These strings are placeholder content the shop owner replaces anyway.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every option key mapped to its default value.
 *
 * @return array
 */
function od_defaults() {
	static $defaults = null;

	if ( null !== $defaults ) {
		return $defaults;
	}

	$defaults = array(

		/* Colours ---------------------------------------------------- */
		'color_bg'             => '#140a12',
		'color_surface'        => '#21121d',
		'color_gold'           => '#d9a441',
		'color_gold_light'     => '#f0d08a',
		'color_maroon'         => '#7b1e3b',
		'color_marigold'       => '#e8952f',
		'color_text'           => '#f4eaee',
		'palette_preset'       => 'aubergine',

		/* Header ----------------------------------------------------- */
		'brand_tagline'        => 'Heritage Weaves',
		'topbar_on'            => true,
		'topbar_items'         => "Free shipping across India on orders above ₹2,999\nHandloom certified — direct from the weavers of Kanchipuram & Banaras\nEasy 7-day returns · 100% secure payments",
		'topbar_phone'         => '+91 73869 12300',
		'sticky_header'        => true,

		/* Hero slide 1 ----------------------------------------------- */
		'hero1_eyebrow'        => 'The Bridal Edit',
		'hero1_title'          => 'Kanchipuram Silk, <em>Woven in Gold</em>',
		'hero1_text'           => 'Pure zari, temple borders and the kind of lustre that only a six-month loom can give.',
		'hero1_btn'            => 'Shop Sarees',
		'hero1_url'            => '',
		'hero1_img'            => '',
		'hero1_align'          => 'left',

		/* Hero slide 2 ----------------------------------------------- */
		'hero2_eyebrow'        => 'The Festive Edit',
		'hero2_title'          => 'Banarasi, <em>Woven in Zari</em>',
		'hero2_text'           => 'Katan silk with real zari butis, on looms that have not changed in two hundred years.',
		'hero2_btn'            => 'See the Drapes',
		'hero2_url'            => '',
		'hero2_img'            => '',
		'hero2_align'          => 'right',

		/* Hero slide 3 ----------------------------------------------- */
		'hero3_eyebrow'        => 'Everyday Silks',
		'hero3_title'          => 'Soft Silks & <em>Handloom Cottons</em>',
		'hero3_text'           => 'Light enough for a working day, handsome enough for an evening out.',
		'hero3_btn'            => 'Shop the Collection',
		'hero3_url'            => '',
		'hero3_img'            => '',
		'hero3_align'          => 'center',

		'hero_autoplay'        => true,
		'hero_speed'           => 6,

		/* Homepage sections ------------------------------------------ */
		'home_source'          => 'auto',
		'browse_by'            => 'none',
		'filter_attrs'         => 'none',
		'card_sku'             => true,

		/* Section wording -------------------------------------------- */
		'cats_eyebrow'         => 'The collection',
		'cats_title'           => 'Every <em>Drape</em> We Have',
		'cats_text'            => '',
		'sarees_eyebrow'       => 'Six yards of grace',
		'sarees_title'         => 'The <em>Saree</em> Edit',
		'sarees_text'          => 'Kanchipuram, Banarasi, Pochampally, Chanderi and Bhagalpuri silks — straight from the weavers.',

		/* Search ------------------------------------------------------ */
		'search_placeholder'   => 'Search sarees…',
		'search_terms'         => "Kanchipuram silk\nBanarasi saree\nSoft silk\nHandloom cotton\nBridal saree\nPochampally ikat",
		'catrail_text'         => '',
		'sec_usp'              => true,
		'sec_catrail'          => true,
		'sec_cats'             => false,
		'sec_new'              => false,
		'sec_promo'            => true,
		'sec_bestsellers'      => false,
		'sec_deal'             => false,
		'sec_sarees'           => true,
		'sec_jewel'            => false,
		'sec_lookbook'         => true,
		'sec_band'             => true,
		'sec_reviews'          => true,
		'sec_blog'             => false,
		'sec_gram'             => true,
		'sec_newsletter'       => true,
		'products_per_section' => 12,
		'cats_count'           => 5,
		'cats_slugs'           => '',
		'cats_source'          => 'categories',
		'cats_products'        => '',
		'catrail_count'        => 10,
		'catrail_slugs'        => '',
		'catrail_top_level'    => false,
		'loadmore'             => true,
		'loadmore_step'        => 4,

		/* Offer banners ---------------------------------------------- */
		'promo1_off'           => '40% OFF',
		'promo1_title'         => 'Banarasi Silk Festival',
		'promo1_text'          => 'Hand-woven katan silk with real zari butis. Limited looms, limited pieces.',
		'promo1_url'           => '',
		'promo1_img'           => '',
		'promo2_off'           => 'NEW IN',
		'promo2_title'         => 'Soft Silk Arrivals',
		'promo2_text'          => 'Featherweight drape, a quiet sheen, and a border worth a second look.',
		'promo2_url'           => '',
		'promo2_img'           => '',
		'deal_end'             => '',
		'band_img'             => '',
		'band_title'           => 'Woven by hands that have known the loom for six generations',
		'band_text'            => 'Every Ojasvi Drapes saree is sourced straight from weaver families in Kanchipuram, Banaras, Pochampally and Bhagalpur — no middlemen, fair wages, and a name tag on every drape.',

		/* Shop ------------------------------------------------------- */
		'shop_columns'         => 4,
		'shop_per_page'        => 12,
		'shop_sidebar'         => true,
		'card_hover_img'       => true,
		'card_swatches'        => true,
		'quickview'            => true,
		'wishlist_on'          => true,
		'compare_on'           => true,
		'compare_max'          => 4,
		'use_woo_gallery'      => false,
		'sticky_buy'           => true,
		'pincode_check'        => true,
		'free_ship_threshold'  => 2999,
		'stock_alert_qty'      => 8,
		'offers_text'          => "Extra 10% off on prepaid orders — code `OJASVI10`\nFlat ₹500 off on your first order above ₹4,999\nFree fall & pico stitching on every silk saree\nBank offer: 5% cashback on HDFC credit cards",

		/* Footer ----------------------------------------------------- */
		'footer_about'         => 'Ojasvi Drapes brings you handloom sarees sourced directly from Indian weaver families — honest pricing, heirloom quality, and a name tag on every drape.',
		'footer_address'       => "18-3-490/1, Aliyabad, Near Phool Bagh,\nChaman, Charminar, Falaknuma,\nHyderabad, Telangana 500053",
		'footer_phone'         => '+91 73869 12300',
		'footer_email'         => 'support@ojasvidrapes.in',
		'footer_hours'         => 'Mon–Sat, 10 am – 7 pm IST',
		'footer_copy'          => '',
		'social_instagram'     => '',
		'social_facebook'      => '',
		'social_youtube'       => '',
		'social_whatsapp'      => 'https://wa.me/917386912300',
		'social_pinterest'     => '',
		'gram_handle'          => 'ojasvidrapes',

		/* Typography ------------------------------------------------- */
		'font_head'            => '"Playfair Display", Georgia, serif',
		'font_scale'           => 16,
		'radius'               => 10,
		'container'            => 1320,

		/* SEO & social ----------------------------------------------- */
		'seo_enable'           => true,
		'seo_schema'           => true,
		'seo_meta_home'        => 'Shop handloom sarees at Ojasvi Drapes — Kanchipuram, Banarasi, Pochampally and soft silks sourced direct from Indian weavers, with free shipping over ₹2,999 and 7-day returns.',
		'seo_og_image'         => '',
		'seo_twitter'          => '',
		'seo_org_type'         => 'OnlineStore',
		'seo_verify_google'    => '',
		'seo_verify_bing'      => '',
		'seo_verify_facebook'  => '',
		'seo_verify_pinterest' => '',

		/* Policy figures, shared by the policy pages and product schema -- */
		'returns_window_days'  => 7,
		'flat_ship_rate'       => 99,
		'cod_limit'            => 15000,
		'policy_updated'       => '',
		// Preloader.
		'preloader_on'         => true,
		'preloader_ms'         => 2000,
		'preloader_transitions' => true,
		'preloader_once'       => false,
		'preloader_text'       => '',

		'legal_entity'         => 'Ojasvi Drapes',
		'legal_jurisdiction'   => 'Hyderabad, Telangana',
		'legal_gstin'          => '',
		'grievance_officer'    => 'Customer Care Team',

		/* Behaviour not exposed as a control ------------------------- */
		'auth_redirect'        => false,
	);

	/**
	 * Filter the theme's option defaults.
	 *
	 * A child theme can rebrand every placeholder here in one place.
	 *
	 * @param array $defaults Key => default value.
	 */
	$defaults = apply_filters( 'od_defaults', $defaults );

	return $defaults;
}

/**
 * The default for a single option key.
 *
 * @param string $key      Key without the od_ prefix.
 * @param mixed  $fallback Used when the key is not in the registry.
 * @return mixed
 */
function od_default( $key, $fallback = '' ) {
	$defaults = od_defaults();

	return array_key_exists( $key, $defaults ) ? $defaults[ $key ] : $fallback;
}
