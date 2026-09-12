<?php
/**
 * Empty cart.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );

ss_empty_state(
	'bag',
	__( 'Your bag is empty', 'sreesaanvika' ),
	__( 'Nothing here yet. Browse the new arrivals — there are handloom sarees, temple jewellery and festive dresses waiting.', 'sreesaanvika' ),
	wc_get_page_permalink( 'shop' ),
	__( 'Start shopping', 'sreesaanvika' )
);

if ( ss_option( 'wishlist_on', true ) ) {
	$ss_saved = ss_get_list( 'wishlist' );

	if ( $ss_saved ) {
		echo '<div class="ss-section">';
		ss_section_head( __( 'Saved for later', 'sreesaanvika' ), __( 'From your <em>Wishlist</em>', 'sreesaanvika' ) );

		ss_product_loop(
			array(
				'post__in' => $ss_saved,
				'orderby'  => 'post__in',
			)
		);

		echo '</div>';
	}
}
