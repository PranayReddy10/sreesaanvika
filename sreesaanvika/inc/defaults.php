<?php
/**
 * Default values for every theme option.
 *
 * This is the single source of truth. Both `ss_option()` on the front end and
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
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every option key mapped to its default value.
 *
 * @return array
 */
function ss_defaults() {
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
		'hero2_eyebrow'        => 'Temple Jewellery',
		'hero2_title'          => 'Heirloom <em>Antique Finish</em>',
		'hero2_text'           => 'Nakshi haarams, jhumkas and vanki — crafted the way the temple artisans of Thanjavur still do.',
		'hero2_btn'            => 'Explore Jewellery',
		'hero2_url'            => '',
		'hero2_img'            => '',
		'hero2_align'          => 'right',

		/* Hero slide 3 ----------------------------------------------- */
		'hero3_eyebrow'        => 'The Festive Edit',
		'hero3_title'          => 'Anarkalis & <em>Lehengas</em>',
		'hero3_text'           => 'Chikankari, mirror work and hand-dyed bandhani, cut for movement.',
		'hero3_btn'            => 'Shop Dresses',
		'hero3_url'            => '',
		'hero3_img'            => '',
		'hero3_align'          => 'center',

		'hero_autoplay'        => true,
		'hero_speed'           => 6,

		/* Homepage sections ------------------------------------------ */
		'sec_usp'              => true,
		'sec_catrail'          => true,
		'sec_cats'             => true,
		'sec_new'              => true,
		'sec_promo'            => true,
		'sec_bestsellers'      => true,
		'sec_deal'             => true,
		'sec_sarees'           => true,
		'sec_jewel'            => true,
		'sec_lookbook'         => true,
		'sec_band'             => true,
		'sec_reviews'          => true,
		'sec_blog'             => true,
		'sec_gram'             => true,
		'sec_newsletter'       => true,
		'products_per_section' => 8,

		/* Offer banners ---------------------------------------------- */
		'promo1_off'           => '40% OFF',
		'promo1_title'         => 'Banarasi Silk Festival',
		'promo1_text'          => 'Hand-woven katan silk with real zari butis. Limited looms, limited pieces.',
		'promo1_url'           => '',
		'promo1_img'           => '',
		'promo2_off'           => 'NEW IN',
		'promo2_title'         => 'Temple Jewellery',
		'promo2_text'          => 'Antique-finish haarams and jhumkas, hallmarked and nazariya-safe.',
		'promo2_url'           => '',
		'promo2_img'           => '',
		'deal_end'             => '',
		'band_img'             => '',
		'band_title'           => 'Woven by hands that have known the loom for six generations',
		'band_text'            => 'Every Sree Saanvika saree is sourced straight from weaver families in Kanchipuram, Banaras, Pochampally and Bhagalpur — no middlemen, fair wages, and a name tag on every drape.',

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
		'offers_text'          => "Extra 10% off on prepaid orders — code `SAANVIKA10`\nFlat ₹500 off on your first order above ₹4,999\nFree fall & pico stitching on all silk sarees\nBank offer: 5% cashback on HDFC credit cards",

		/* Footer ----------------------------------------------------- */
		'footer_about'         => 'Sree Saanvika brings you handloom sarees, temple jewellery and festive dresses sourced directly from Indian weavers and artisans — honest pricing, heirloom quality.',
		'footer_address'       => "Plot 42, Jubilee Hills Road No. 36,\nHyderabad, Telangana 500033",
		'footer_phone'         => '+91 73869 12300',
		'footer_email'         => 'care@sreesaanvika.in',
		'footer_hours'         => 'Mon–Sat, 10 am – 7 pm IST',
		'footer_copy'          => '',
		'social_instagram'     => '',
		'social_facebook'      => '',
		'social_youtube'       => '',
		'social_whatsapp'      => 'https://wa.me/917386912300',
		'social_pinterest'     => '',
		'gram_handle'          => 'sreesaanvika',

		/* Typography ------------------------------------------------- */
		'font_head'            => '"Playfair Display", Georgia, serif',
		'font_scale'           => 16,
		'radius'               => 10,
		'container'            => 1320,

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
	$defaults = apply_filters( 'ss_defaults', $defaults );

	return $defaults;
}

/**
 * The default for a single option key.
 *
 * @param string $key      Key without the ss_ prefix.
 * @param mixed  $fallback Used when the key is not in the registry.
 * @return mixed
 */
function ss_default( $key, $fallback = '' ) {
	$defaults = ss_defaults();

	return array_key_exists( $key, $defaults ) ? $defaults[ $key ] : $fallback;
}
