<?php
/**
 * Compare and wishlist storage.
 *
 * Guests are stored in a cookie; logged-in customers get user meta so the
 * list follows them between devices. Both lists are plain arrays of IDs.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

const OD_WISHLIST_KEY = 'od_wishlist';
const OD_COMPARE_KEY  = 'od_compare';

/**
 * Read a stored list.
 *
 * @param string $type "wishlist"|"compare".
 * @return int[]
 */
function od_get_list( $type = 'wishlist' ) {
	$key = ( 'compare' === $type ) ? OD_COMPARE_KEY : OD_WISHLIST_KEY;

	if ( is_user_logged_in() ) {
		$ids = get_user_meta( get_current_user_id(), $key, true );
		$ids = is_array( $ids ) ? $ids : array();
	} else {
		$raw = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';
		$ids = $raw ? array_map( 'absint', explode( ',', $raw ) ) : array();
	}

	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

	if ( 'compare' === $type ) {
		$ids = array_slice( $ids, 0, absint( od_option( 'compare_max', 4 ) ) );
	}

	return $ids;
}

/**
 * Persist a list.
 *
 * @param int[]  $ids  Product ids.
 * @param string $type "wishlist"|"compare".
 */
function od_save_list( $ids, $type = 'wishlist' ) {
	$key = ( 'compare' === $type ) ? OD_COMPARE_KEY : OD_WISHLIST_KEY;
	$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );

	if ( 'compare' === $type ) {
		$ids = array_slice( $ids, 0, absint( od_option( 'compare_max', 4 ) ) );
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
function od_toggle_list( $product_id, $type = 'wishlist' ) {
	$product_id = absint( $product_id );
	$ids        = od_get_list( $type );
	$max        = absint( od_option( 'compare_max', 4 ) );

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

	od_save_list( $ids, $type );

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
function od_merge_lists_on_login( $user_login, $user ) {
	foreach ( array( 'wishlist' => OD_WISHLIST_KEY, 'compare' => OD_COMPARE_KEY ) as $type => $key ) {
		$raw = isset( $_COOKIE[ $key ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) ) : '';

		if ( ! $raw ) {
			continue;
		}

		$cookie_ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
		$saved      = get_user_meta( $user->ID, $key, true );
		$saved      = is_array( $saved ) ? $saved : array();
		$merged     = array_values( array_unique( array_merge( $saved, $cookie_ids ) ) );

		if ( 'compare' === $type ) {
			$merged = array_slice( $merged, 0, absint( od_option( 'compare_max', 4 ) ) );
		}

		update_user_meta( $user->ID, $key, $merged );
	}
}
add_action( 'wp_login', 'od_merge_lists_on_login', 10, 2 );

/**
 * Products in a list, skipping anything unpublished or deleted.
 *
 * @param string $type "wishlist"|"compare".
 * @return WC_Product[]
 */
function od_get_list_products( $type = 'wishlist' ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$out = array();

	foreach ( od_get_list( $type ) as $id ) {
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
function od_list_count_badge( $type ) {
	$count = count( od_get_list( $type ) );

	printf(
		'<span class="od-count-badge od-%s-count"%s>%s</span>',
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
function od_compare_rows() {
	return apply_filters(
		'od_compare_rows',
		array(
			'price'       => __( 'Price', 'ojasvidrapes' ),
			'rating'      => __( 'Rating', 'ojasvidrapes' ),
			'availability' => __( 'Availability', 'ojasvidrapes' ),
			'sku'         => __( 'SKU', 'ojasvidrapes' ),
			'categories'  => __( 'Category', 'ojasvidrapes' ),
			'fabric'      => __( 'Fabric', 'ojasvidrapes' ),
			'color'       => __( 'Colours', 'ojasvidrapes' ),
			'occasion'    => __( 'Occasion', 'ojasvidrapes' ),
			'work'        => __( 'Work / Craft', 'ojasvidrapes' ),
			'blouse'      => __( 'Blouse piece', 'ojasvidrapes' ),
			'length'      => __( 'Length', 'ojasvidrapes' ),
			'weight'      => __( 'Weight', 'ojasvidrapes' ),
			'wash_care'   => __( 'Wash care', 'ojasvidrapes' ),
			'description' => __( 'Description', 'ojasvidrapes' ),
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
function od_compare_value( $product, $row ) {
	switch ( $row ) {
		case 'price':
			return wp_kses_post( $product->get_price_html() );

		case 'rating':
			$rating = (float) $product->get_average_rating();
			return $rating ? od_stars( $rating, $product->get_review_count() ) : '<span class="od-no">' . esc_html__( 'No reviews yet', 'ojasvidrapes' ) . '</span>';

		case 'availability':
			return $product->is_in_stock()
				? '<span class="od-yes">' . esc_html__( 'In stock', 'ojasvidrapes' ) . '</span>'
				: '<span class="od-no">' . esc_html__( 'Sold out', 'ojasvidrapes' ) . '</span>';

		case 'sku':
			$sku = $product->get_sku();
			return $sku ? esc_html( $sku ) : '<span class="od-no">&mdash;</span>';

		case 'categories':
			$terms = wc_get_product_category_list( $product->get_id(), ', ' );
			return $terms ? wp_kses_post( $terms ) : '<span class="od-no">&mdash;</span>';

		case 'description':
			$text = $product->get_short_description();
			$text = $text ? $text : $product->get_description();
			return $text ? esc_html( wp_trim_words( wp_strip_all_tags( $text ), 26 ) ) : '<span class="od-no">&mdash;</span>';

		case 'color':
			$terms = od_product_color_terms( $product );

			if ( ! $terms ) {
				return '<span class="od-no">&mdash;</span>';
			}

			$out = '<div class="od-pcard__swatches">';

			foreach ( $terms as $term ) {
				$out .= '<span class="od-swatch" style="background-color:' . esc_attr( od_color_hex( $term->name, $term->term_id ) ) . '" title="' . esc_attr( $term->name ) . '"></span>';
			}

			return $out . '</div>';

		default:
			// Everything else maps to a product attribute of the same name.
			$value = od_product_attribute( $product, $row );
			return $value ? wp_kses_post( $value ) : '<span class="od-no">&mdash;</span>';
	}
}

/**
 * Read an attribute by a loose name, trying pa_ taxonomies then custom ones.
 *
 * @param WC_Product $product Product.
 * @param string     $name    Attribute name, e.g. "fabric".
 * @return string
 */
function od_product_attribute( $product, $name ) {
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
