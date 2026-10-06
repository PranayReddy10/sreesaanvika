<?php
/**
 * Checkout form.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>
<div class="od-steps" aria-label="<?php esc_attr_e( 'Checkout progress', 'ojasvidrapes' ); ?>">
	<span class="od-step is-done"><span class="od-step__num"><?php od_the_icon( 'check', 14 ); ?></span><?php esc_html_e( 'Bag', 'ojasvidrapes' ); ?></span>
	<span class="od-steps__line" aria-hidden="true"></span>
	<span class="od-step is-active"><span class="od-step__num">2</span><?php esc_html_e( 'Details & payment', 'ojasvidrapes' ); ?></span>
	<span class="od-steps__line" aria-hidden="true"></span>
	<span class="od-step"><span class="od-step__num">3</span><?php esc_html_e( 'Done', 'ojasvidrapes' ); ?></span>
</div>

<form name="checkout" method="post" class="checkout woocommerce-checkout"
	action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data">

	<div class="od-checkout">

		<div class="od-checkout__left">
			<?php if ( $checkout->get_checkout_fields() ) : ?>
				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

				<div class="od-checkout__panel" id="customer_details">
					<h3><?php od_the_icon( 'user', 18 ); ?> <?php esc_html_e( 'Delivery details', 'ojasvidrapes' ); ?></h3>
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			<?php endif; ?>
		</div>

		<aside class="od-checkout__right">
			<div class="od-summary-box">
				<h3 id="order_review_heading"><?php esc_html_e( 'Your order', 'ojasvidrapes' ); ?></h3>

				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>

				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>

				<div class="od-securenote">
					<?php od_the_icon( 'lock', 15 ); ?>
					<span><?php esc_html_e( 'Your payment details are encrypted end to end', 'ojasvidrapes' ); ?></span>
				</div>
			</div>
		</aside>

	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
