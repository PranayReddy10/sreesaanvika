<?php
/**
 * Empty cart.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_cart_is_empty' );

od_empty_state(
	'bag',
	__( 'Your bag is empty', 'ojasvidrapes' ),
	__( 'Nothing here yet. Browse the new arrivals — there are handloom sarees, temple jewellery and festive dresses waiting.', 'ojasvidrapes' ),
	wc_get_page_permalink( 'shop' ),
	__( 'Start shopping', 'ojasvidrapes' )
);

if ( od_option( 'wishlist_on', true ) ) {
	$od_saved = od_get_list( 'wishlist' );

	if ( $od_saved ) {
		echo '<div class="od-section">';
		od_section_head( __( 'Saved for later', 'ojasvidrapes' ), __( 'From your <em>Wishlist</em>', 'ojasvidrapes' ) );

		od_product_loop(
			array(
				'post__in' => $od_saved,
				'orderby'  => 'post__in',
			)
		);

		echo '</div>';
	}
}
