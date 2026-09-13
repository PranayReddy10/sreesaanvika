<?php
/**
 * Small helpers used across the theme.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read a theme option, falling back to the registered default.
 *
 * The default always comes from ss_defaults() when the key is known there.
 * `get_theme_mod()` has no idea what default a Customizer setting was
 * registered with, so passing anything else here is how a section ends up
 * visible in the Customizer preview and blank on the live site.
 *
 * A value the shop owner has actually saved always wins — including an empty
 * string, which is how you clear a hero slide or hide a banner.
 *
 * @param string $key      Setting key (without the ss_ prefix).
 * @param mixed  $fallback Used only for keys with no registered default.
 * @return mixed
 */
function ss_option( $key, $fallback = '' ) {
	return get_theme_mod( 'ss_' . $key, ss_default( $key, $fallback ) );
}

/**
 * True on any WooCommerce screen.
 *
 * @return bool
 */
function ss_is_woo() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	return is_woocommerce() || is_cart() || is_checkout() || is_account_page()
		|| ss_is_theme_page( 'compare' ) || ss_is_theme_page( 'wishlist' ) || ss_is_theme_page( 'auth' );
}

/**
 * True when the current page uses one of the theme's page templates.
 *
 * @param string $slug Template slug, e.g. "compare".
 * @return bool
 */
function ss_is_theme_page( $slug ) {
	return is_page_template( 'page-templates/template-' . $slug . '.php' );
}

/**
 * Find the URL of a page using one of the theme templates.
 *
 * Cached in a transient so the meta query does not run on every request.
 *
 * @param string $slug Template slug.
 * @return string
 */
function ss_page_url( $slug ) {
	$cache_key = 'ss_page_url_' . $slug;
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	// Slug first. The four policy pages all share one template, so a lookup by
	// template alone would return whichever of them the query happened to hit.
	$page = get_page_by_path( $slug );
	$url  = $page ? get_permalink( $page ) : '';

	if ( ! $url && ! array_key_exists( $slug, ss_legal_pages() ) ) {
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'page-templates/template-' . $slug . '.php', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);

		$url = $pages ? get_permalink( $pages[0] ) : '';
	}

	if ( ! $url ) {
		$url = home_url( '/' . $slug . '/' );
	}

	set_transient( $cache_key, $url, DAY_IN_SECONDS );

	return $url;
}

/**
 * Clear the cached template page URLs when pages change.
 */
function ss_flush_page_urls() {
	$slugs = array( 'compare', 'wishlist', 'auth', 'lookbook', 'faq', 'contact', 'about', 'track' );

	foreach ( array_merge( $slugs, array_keys( ss_legal_pages() ) ) as $slug ) {
		delete_transient( 'ss_page_url_' . $slug );
	}
}
add_action( 'save_post_page', 'ss_flush_page_urls' );
add_action( 'deleted_post', 'ss_flush_page_urls' );

/**
 * Escaped inline background-image style, or an empty string.
 *
 * @param string $url Image URL.
 * @return string
 */
function ss_bg_style( $url ) {
	if ( ! $url ) {
		return '';
	}

	return ' style="background-image:url(' . esc_url( $url ) . ')"';
}

/**
 * A placeholder image URL used when a product or banner has no picture yet.
 *
 * @param string $type One of product|banner|avatar.
 * @return string
 */
function ss_placeholder( $type = 'product' ) {
	$file = 'placeholder-product.svg';

	if ( 'banner' === $type ) {
		$file = 'placeholder-banner.svg';
	}

	return SS_URI . '/assets/images/' . $file;
}

/**
 * Return the first two initials of a name, for avatar chips.
 *
 * @param string $name Full name.
 * @return string
 */
function ss_initials( $name ) {
	$parts = preg_split( '/\s+/', trim( wp_strip_all_tags( $name ) ) );
	$out   = '';

	foreach ( array_slice( (array) $parts, 0, 2 ) as $part ) {
		if ( '' !== $part ) {
			$out .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
		}
	}

	return $out ? $out : 'S';
}

/**
 * Map a colour attribute term to a hex value.
 *
 * Looks for a `ss_color` term meta first, then falls back to a built-in
 * lookup of common Indian textile colour names, then to a generated tint.
 *
 * @param string $name Colour name or slug.
 * @param int    $term_id Optional term id.
 * @return string Hex colour.
 */
function ss_color_hex( $name, $term_id = 0 ) {
	if ( $term_id ) {
		$meta = get_term_meta( $term_id, 'ss_color', true );
		if ( $meta ) {
			return $meta;
		}
	}

	$key = sanitize_title( $name );

	$map = array(
		'red'          => '#c0392b',
		'maroon'       => '#7b1e3b',
		'rani-pink'    => '#c2185b',
		'pink'         => '#e6799f',
		'rose'         => '#c25d7e',
		'magenta'      => '#a4245e',
		'purple'       => '#6b3fa0',
		'violet'       => '#7d5ba6',
		'wine'         => '#5c1a33',
		'orange'       => '#e8952f',
		'marigold'     => '#f0a02a',
		'mustard'      => '#d9a441',
		'yellow'       => '#e9c547',
		'gold'         => '#d4af37',
		'antique-gold' => '#b08d3f',
		'beige'        => '#c9b79c',
		'cream'        => '#e8dcc6',
		'ivory'        => '#efe6d6',
		'off-white'    => '#e6ded2',
		'peach'        => '#f0a58a',
		'green'        => '#2e7d52',
		'bottle-green' => '#14532d',
		'emerald'      => '#1f7a63',
		'mehendi'      => '#7a8c3f',
		'olive'        => '#6b6b3a',
		'teal'         => '#17565f',
		'peacock-blue' => '#0f5a75',
		'blue'         => '#2b5ea8',
		'navy'         => '#1b2a55',
		'sky-blue'     => '#6aa9d8',
		'turquoise'    => '#2fa8a0',
		'grey'         => '#7b7480',
		'silver'       => '#c0c3c8',
		'black'        => '#1b1418',
		'white'        => '#f2eeea',
		'brown'        => '#6b4429',
		'copper'       => '#a45a2a',
		'rust'         => '#9c4322',
		'lavender'     => '#b9a5d6',
		'mint'         => '#9fd6bd',
		'multicolour'  => '#c2185b',
		'multicolor'   => '#c2185b',
	);

	if ( isset( $map[ $key ] ) ) {
		return $map[ $key ];
	}

	// Deterministic fallback tint derived from the name.
	$hash = substr( md5( $key ), 0, 6 );

	return '#' . $hash;
}

/**
 * Format a price in the shop currency, falling back to a plain rupee string.
 *
 * @param float $amount Amount.
 * @return string
 */
function ss_price( $amount ) {
	if ( function_exists( 'wc_price' ) ) {
		return wc_price( $amount );
	}

	return '&#8377;' . number_format_i18n( (float) $amount );
}

/**
 * Split a comma or newline separated option into a clean array.
 *
 * @param string $raw Raw option value.
 * @param string $sep Separator.
 * @return array
 */
function ss_list( $raw, $sep = "\n" ) {
	$parts = array_map( 'trim', explode( $sep, (string) $raw ) );

	return array_values( array_filter( $parts, 'strlen' ) );
}

/**
 * Safe wrapper for kses on small marketing snippets.
 *
 * @param string $html HTML.
 * @return string
 */
function ss_kses( $html ) {
	return wp_kses(
		$html,
		array(
			'em'     => array(),
			'strong' => array(),
			'span'   => array( 'class' => array() ),
			'br'     => array(),
			'a'      => array(
				'href'   => array(),
				'title'  => array(),
				'target' => array(),
				'rel'    => array(),
			),
		)
	);
}
