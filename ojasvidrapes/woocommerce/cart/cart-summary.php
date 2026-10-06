<?php
/**
 * Cart order summary.
 *
 * Its own file because the quantity endpoint re-renders it on every change.
 * A second, AJAX-only rendering of the same figures would be free to drift
 * away from this one; there is only ever the one.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_saving = 0;

foreach ( WC()->cart->get_cart() as $od_item ) {
	$od_product = $od_item['data'];

	if ( $od_product && $od_product->is_on_sale() ) {
$od_regular = (float) $od_product->get_regular_price();
$od_now     = (float) $od_product->get_price();

if ( $od_regular > $od_now ) {
	$od_saving += ( $od_regular - $od_now ) * (int) $od_item['quantity'];
}
	}
}
?>
<div class="od-summary-box cart-collaterals">
	<h3><?php esc_html_e( 'Order summary', 'ojasvidrapes' ); ?></h3>

	<div class="od-summary-box__row">
		<span><?php esc_html_e( 'Subtotal', 'ojasvidrapes' ); ?></span>
		<span><?php wc_cart_totals_subtotal_html(); ?></span>
	</div>

	<?php if ( $od_saving > 0 ) : ?>
		<div class="od-summary-box__row od-summary-box__row--save">
			<span><?php esc_html_e( 'You save', 'ojasvidrapes' ); ?></span>
			<span>&minus; <?php echo wp_kses_post( wc_price( $od_saving ) ); ?></span>
		</div>
	<?php endif; ?>

	<?php foreach ( WC()->cart->get_coupons() as $od_code => $od_coupon ) : ?>
		<div class="od-summary-box__row od-summary-box__row--save">
			<span><?php wc_cart_totals_coupon_label( $od_coupon ); ?></span>
			<span><?php wc_cart_totals_coupon_html( $od_coupon ); ?></span>
		</div>
	<?php endforeach; ?>

	<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
		<div class="od-summary-box__row">
			<span><?php esc_html_e( 'Shipping', 'ojasvidrapes' ); ?></span>
			<span><?php esc_html_e( 'Calculated at checkout', 'ojasvidrapes' ); ?></span>
		</div>
	<?php endif; ?>

	<?php foreach ( WC()->cart->get_fees() as $od_fee ) : ?>
		<div class="od-summary-box__row">
			<span><?php echo esc_html( $od_fee->name ); ?></span>
			<span><?php wc_cart_totals_fee_html( $od_fee ); ?></span>
		</div>
	<?php endforeach; ?>

	<div class="od-summary-box__total">
		<span><?php esc_html_e( 'Total', 'ojasvidrapes' ); ?></span>
		<strong><?php wc_cart_totals_order_total_html(); ?></strong>
	</div>

	<div style="margin-top:20px">
		<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
	</div>

	<div class="od-securenote">
		<?php od_the_icon( 'lock', 15 ); ?>
		<span><?php esc_html_e( 'Secure checkout · UPI, cards, netbanking, COD', 'ojasvidrapes' ); ?></span>
	</div>
</div>
