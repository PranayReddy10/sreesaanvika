<?php
/**
 * Compare and wishlist storage.
 *
 * Guests are stored in a cookie; logged-in customers get user meta so the
 * list follows them between devices. Both lists are plain arrays of IDs.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

const SS_WISHLIST_KEY = 'ss_wishlist';
const SS_COMPARE_KEY  = 'ss_compare';

/**
 * Read a stored list.
 *
 * @param string $type "wishlist"|"compare".
 * @return int[]
 */
function ss_get_list( $type = 'wishlist' ) {
	$key = ( 'compare' === $type ) ? SS_COMPARE_KEY : SS_WISHLIST_KEY;

	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), $key, true );
		$ids = is_array( $ids ) ? $ids : array();
	} else {
		$raw = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';
		$ids = $raw ? array_map( 'absint', explode( ',', $raw ) ) : array();
	}

	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

	if ( 'compare' === $type ) {
		$ids = array_slice( $ids, 0, absint( ss_option( 'compare_max', 4 ) ) );
	}

	return $ids;
}

/**
 * Persist a list.
 *
 * @param int[]  $ids  Product ids.
 * @param string $type "wishlist"|"compare".
 */
function ss_save_list( $ids, $type = 'wishlist' ) {
	$key = ( 'compare' === $type ) ? SS_COMPARE_KEY : SS_WISHLIST_KEY;
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );

	if ( 'compare' === $type ) {
		$ids = array_slice( $ids, 0, absint( ss_option( 'compare_max', 4 ) ) );
	}

	if ( is_user_logged_in() ) {
		update_user_meta( get_current_user_id(), $key, $ids );
	}

	// The cookie is always written so the header counters stay right for
	// cached pages and for a user who later logs out.
	if ( ! headers_sent() ) {
		setcookie(
			$key,
			implode( ',', $ids ),
			array(
				'expires'  => time() + 30 * DAY_IN_SECONDS,
				'path'     => COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => false,
				'samesite' => 'Lax',
			)
		);
	}

	$_COOKIE[ $key ] = implode( ',', $ids );
}

/**
 * Toggle a product in a list.
 *
 * @param int    $product_id Product id.
 * @param string $type       "wishlist"|"compare".
 * @return array{action:string,ids:int[],full:bool}
 */
function ss_toggle_list( $product_id, $type = 'wishlist' ) {
	$product_id = absint( $product_id );
	$ids        = ss_get_list( $type );
	$max        = absint( ss_option( 'compare_max', 4 ) );

	if ( in_array( $product_id, $ids, true ) ) {
		$ids    = array_values( array_diff( $ids, array( $product_id ) ) );
		$action = 'removed';
	} else {
		if ( 'compare' === $type && count( $ids ) >= $max ) {
			return array(
				'action' => 'full',
				'ids'    => $ids,
				'full'   => true,
			);
		}

		$ids[]  = $product_id;
		$action = 'added';
	}

	ss_save_list( $ids, $type );

	return array(
		'action' => $action,
		'ids'    => $ids,
		'full'   => false,
	);
}

/**
 * Merge the guest cookie list into user meta right after login.
 *
 * @param string  $user_login Username.
 * @param WP_User $user       User.
 */
function ss_merge_lists_on_login( $user_login, $user ) {
	foreach ( array( 'wishlist' => SS_WISHLIST_KEY, 'compare' => SS_COMPARE_KEY ) as $type => $key ) {
		$raw = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';

		if ( ! $raw ) {
			continue;
		}

		$cookie_ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
		$saved      = get_user_meta( $user->ID, $key, true );
		$saved      = is_array( $saved ) ? $saved : array();
		$merged     = array_values( array_unique( array_merge( $saved, $cookie_ids ) ) );

		if ( 'compare' === $type ) {
			$merged = array_slice( $merged, 0, absint( ss_option( 'compare_max', 4 ) ) );
		}

		update_user_meta( $user->ID, $key, $merged );
	}
}
add_action( 'wp_login', 'ss_merge_lists_on_login', 10, 2 );

/**
 * Products in a list, skipping anything unpublished or deleted.
 *
 * @param string $type "wishlist"|"compare".
 * @return WC_Product[]
 */
function ss_get_list_products( $type = 'wishlist' ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$out = array();

	foreach ( ss_get_list( $type ) as $id ) {
		$product = wc_get_product( $id );

		if ( $product && 'publish' === get_post_status( $id ) ) {
			$out[] = $product;
		}
	}

	return $out;
}

/**
 * Count badge markup for the header icons.
 *
 * @param string $type "wishlist"|"compare".
 */
function ss_list_count_badge( $type ) {
	$count = count( ss_get_list( $type ) );

	printf(
		'<span class="ss-count-badge ss-%s-count"%s>%s</span>',
		esc_attr( $type ),
		$count ? '' : ' hidden',
		esc_html( $count )
	);
}

/**
 * The attribute rows shown on the compare table.
 *
 * @return array
 */
function ss_compare_rows() {
	return apply_filters(
		'ss_compare_rows',
		array(
			'price'       => __( 'Price', 'sreesaanvika' ),
			'rating'      => __( 'Rating', 'sreesaanvika' ),
			'availability' => __( 'Availability', 'sreesaanvika' ),
			'sku'         => __( 'SKU', 'sreesaanvika' ),
			'categories'  => __( 'Category', 'sreesaanvika' ),
			'fabric'      => __( 'Fabric', 'sreesaanvika' ),
			'color'       => __( 'Colours', 'sreesaanvika' ),
			'occasion'    => __( 'Occasion', 'sreesaanvika' ),
			'work'        => __( 'Work / Craft', 'sreesaanvika' ),
			'blouse'      => __( 'Blouse piece', 'sreesaanvika' ),
			'length'      => __( 'Length', 'sreesaanvika' ),
			'weight'      => __( 'Weight', 'sreesaanvika' ),
			'wash_care'   => __( 'Wash care', 'sreesaanvika' ),
			'description' => __( 'Description', 'sreesaanvika' ),
		)
	);
}

/**
 * Value for one compare row.
 *
 * @param WC_Product $product Product.
 * @param string     $row     Row key.
 * @return string HTML.
 */
function ss_compare_value( $product, $row ) {
	switch ( $row ) {
		case 'price':
			return wp_kses_post( $product->get_price_html() );

		case 'rating':
			$rating = (float) $product->get_average_rating();
			return $rating ? ss_stars( $rating, $product->get_review_count() ) : '<span class="ss-no">' . esc_html__( 'No reviews yet', 'sreesaanvika' ) . '</span>';

		case 'availability':
			return $product->is_in_stock()
				? '<span class="ss-yes">' . esc_html__( 'In stock', 'sreesaanvika' ) . '</span>'
				: '<span class="ss-no">' . esc_html__( 'Sold out', 'sreesaanvika' ) . '</span>';

		case 'sku':
			$sku = $product->get_sku();
			return $sku ? esc_html( $sku ) : '<span class="ss-no">&mdash;</span>';

		case 'categories':
			$terms = wc_get_product_category_list( $product->get_id(), ', ' );
			return $terms ? wp_kses_post( $terms ) : '<span class="ss-no">&mdash;</span>';

		case 'description':
			$text = $product->get_short_description();
			$text = $text ? $text : $product->get_description();
			return $text ? esc_html( wp_trim_words( wp_strip_all_tags( $text ), 26 ) ) : '<span class="ss-no">&mdash;</span>';

		case 'color':
			$terms = ss_product_color_terms( $product );

			if ( ! $terms ) {
				return '<span class="ss-no">&mdash;</span>';
			}

			$out = '<div class="ss-pcard__swatches">';

			foreach ( $terms as $term ) {
				$out .= '<span class="ss-swatch" style="background-color:' . esc_attr( ss_color_hex( $term->name, $term->term_id ) ) . '" title="' . esc_attr( $term->name ) . '"></span>';
			}

			return $out . '</div>';

		default:
			// Everything else maps to a product attribute of the same name.
			$value = ss_product_attribute( $product, $row );
			return $value ? wp_kses_post( $value ) : '<span class="ss-no">&mdash;</span>';
	}
}

/**
 * Read an attribute by a loose name, trying pa_ taxonomies then custom ones.
 *
 * @param WC_Product $product Product.
 * @param string     $name    Attribute name, e.g. "fabric".
 * @return string
 */
function ss_product_attribute( $product, $name ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$candidates = array( 'pa_' . $name, $name, str_replace( '_', '-', $name ), 'pa_' . str_replace( '_', '-', $name ) );

	foreach ( $candidates as $key ) {
		$value = $product->get_attribute( $key );

		if ( $value ) {
			return $value;
		}
	}

	return '';
}
