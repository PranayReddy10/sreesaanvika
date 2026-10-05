<?php
/**
 * Plugin Name: OJASVI — Back button cart fix
 * Description: Stops the browser's Back and Forward buttons adding a piece to the bag a second time.
 * Version:     1.0
 *
 * Why this exists
 * ---------------
 * Adding to the bag is a form post, and WooCommerce answers it by drawing the
 * page rather than redirecting. The page left in the browser's history is
 * therefore a POST, so pressing Back or Forward onto it offers to resubmit —
 * and resubmitting adds the piece again. This is WooCommerce's own behaviour,
 * not the theme's, which is why it lives here on its own and not in the theme.
 *
 * All it does is send the shopper back to the page they came from after a
 * successful add. That turns the history entry into an ordinary page view, so
 * Back and Forward show the page again and change nothing.
 *
 * To remove it, delete this file (or switch the snippet off). Nothing else
 * depends on it.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'woocommerce_add_to_cart_redirect',
	function ( $url ) {
		// Something else already chose where to go — Buy it now, for one.
		if ( $url ) {
			return $url;
		}

		// WooCommerce's own "redirect to the cart" setting still wins.
		if ( 'yes' === get_option( 'woocommerce_cart_redirect_after_add' ) ) {
			return wc_get_cart_url();
		}

		$back = wp_get_referer();
		$back = $back ? $back : home_url( add_query_arg( array() ) );

		// Never send them to a URL that would add the piece all over again.
		return remove_query_arg( array( 'add-to-cart', 'quantity', 'variation_id' ), $back );
	},
	20
);
