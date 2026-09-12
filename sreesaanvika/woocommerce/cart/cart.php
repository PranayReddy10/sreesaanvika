<?php
/**
 * Cart page.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );

$ss_saving = 0;

foreach ( WC()->cart->get_cart() as $ss_item ) {
	$ss_product = $ss_item['data'];

	if ( $ss_product && $ss_product->is_on_sale() ) {
		$ss_regular = (float) $ss_product->get_regular_price();
		$ss_now     = (float) $ss_product->get_price();

		if ( $ss_regular > $ss_now ) {
			$ss_saving += ( $ss_regular - $ss_now ) * (int) $ss_item['quantity'];
		}
	}
}
?>
<div class="ss-cart">

	<form class="woocommerce-cart-form ss-cart__main" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="ss-cart__items">
			<?php
			do_action( 'woocommerce_before_cart_contents' );

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
				$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

				if ( ! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
					continue;
				}

				$product_permalink = apply_filters( 'woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink( $cart_item ) : '', $cart_item, $cart_item_key );
				?>
				<div class="ss-cartrow woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

					<div class="ss-cartrow__thumb">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'ss-product' ), $cart_item, $cart_item_key );

						if ( $product_permalink ) {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) );
						} else {
							echo wp_kses_post( $thumbnail );
						}
						?>
					</div>

					<div class="ss-cartrow__info">
						<h3 class="ss-cartrow__name">
							<?php
							if ( $product_permalink ) {
								printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), esc_html( $_product->get_name() ) );
							} else {
								echo esc_html( $_product->get_name() );
							}
							?>
						</h3>

						<div class="ss-cartrow__vars">
							<?php
							echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							if ( $_product->is_sold_individually() ) {
								echo '<span>' . esc_html__( 'One per order', 'sreesaanvika' ) . '</span>';
							}

							if ( ! $_product->is_in_stock() ) {
								echo '<span style="color:var(--ss-error)">' . esc_html__( 'Out of stock', 'sreesaanvika' ) . '</span>';
							}
							?>
						</div>

						<div class="ss-cartrow__unit">
							<?php
							printf(
								/* translators: %s: unit price */
								esc_html__( 'Unit price: %s', 'sreesaanvika' ),
								wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ) )
							);
							?>
						</div>

						<?php if ( $_product->is_on_sale() ) : ?>
							<span class="ss-cartrow__badge">
								<?php ss_the_icon( 'tag', 14 ); ?>
								<?php esc_html_e( 'Discount applied', 'sreesaanvika' ); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="ss-cartrow__ctrl">
						<?php
						if ( $_product->is_sold_individually() ) {
							$product_quantity = sprintf( '<input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
						} else {
							$product_quantity = woocommerce_quantity_input(
								array(
									'input_name'   => "cart[{$cart_item_key}][qty]",
									'input_value'  => $cart_item['quantity'],
									'max_value'    => $_product->get_max_purchase_quantity(),
									'min_value'    => '0',
									'product_name' => $_product->get_name(),
								),
								$_product,
								false
							);
						}

						echo apply_filters( 'woocommerce_cart_item_quantity', $product_quantity, $cart_item_key, $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>

						<div class="ss-cartrow__total">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
						</div>

						<?php
						echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="ss-cartrow__remove remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								/* translators: %s: product name */
								esc_attr( sprintf( __( 'Remove %s from your bag', 'sreesaanvika' ), $_product->get_name() ) ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
						);
						?>
					</div>
				</div>
				<?php
			}

			do_action( 'woocommerce_cart_contents' );
			?>
		</div>

		<div class="ss-between" style="margin-top:20px;flex-wrap:wrap">
			<a class="ss-btn ss-btn--ghost ss-btn--sm" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php ss_the_icon( 'arrow-left', 15 ); ?>
				<?php esc_html_e( 'Continue shopping', 'sreesaanvika' ); ?>
			</a>

			<button type="submit" class="ss-btn ss-btn--solid-dark ss-btn--sm" name="update_cart" value="<?php esc_attr_e( 'Update bag', 'sreesaanvika' ); ?>">
				<?php ss_the_icon( 'refresh', 15 ); ?>
				<?php esc_html_e( 'Update bag', 'sreesaanvika' ); ?>
			</button>
		</div>

		<?php do_action( 'woocommerce_cart_actions' ); ?>
		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<aside class="ss-cart__aside">

		<?php if ( wc_coupons_enabled() ) : ?>
			<div class="ss-summary-box">
				<h3><?php ss_the_icon( 'tag', 18 ); ?> <?php esc_html_e( 'Have a coupon?', 'sreesaanvika' ); ?></h3>

				<form class="ss-coupon" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<label class="screen-reader-text" for="coupon_code"><?php esc_html_e( 'Coupon code', 'sreesaanvika' ); ?></label>
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value=""
						placeholder="<?php esc_attr_e( 'Enter code', 'sreesaanvika' ); ?>" />
					<button type="submit" class="ss-btn ss-btn--sm" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'sreesaanvika' ); ?>">
						<?php esc_html_e( 'Apply', 'sreesaanvika' ); ?>
					</button>
					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</form>
			</div>
		<?php endif; ?>

		<div class="ss-summary-box cart-collaterals">
			<h3><?php esc_html_e( 'Order summary', 'sreesaanvika' ); ?></h3>

			<div class="ss-summary-box__row">
				<span><?php esc_html_e( 'Subtotal', 'sreesaanvika' ); ?></span>
				<span><?php wc_cart_totals_subtotal_html(); ?></span>
			</div>

			<?php if ( $ss_saving > 0 ) : ?>
				<div class="ss-summary-box__row ss-summary-box__row--save">
					<span><?php esc_html_e( 'You save', 'sreesaanvika' ); ?></span>
					<span>&minus; <?php echo wp_kses_post( wc_price( $ss_saving ) ); ?></span>
				</div>
			<?php endif; ?>

			<?php foreach ( WC()->cart->get_coupons() as $ss_code => $ss_coupon ) : ?>
				<div class="ss-summary-box__row ss-summary-box__row--save">
					<span><?php wc_cart_totals_coupon_label( $ss_coupon ); ?></span>
					<span><?php wc_cart_totals_coupon_html( $ss_coupon ); ?></span>
				</div>
			<?php endforeach; ?>

			<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
				<div class="ss-summary-box__row">
					<span><?php esc_html_e( 'Shipping', 'sreesaanvika' ); ?></span>
					<span><?php esc_html_e( 'Calculated at checkout', 'sreesaanvika' ); ?></span>
				</div>
			<?php endif; ?>

			<?php foreach ( WC()->cart->get_fees() as $ss_fee ) : ?>
				<div class="ss-summary-box__row">
					<span><?php echo esc_html( $ss_fee->name ); ?></span>
					<span><?php wc_cart_totals_fee_html( $ss_fee ); ?></span>
				</div>
			<?php endforeach; ?>

			<div class="ss-summary-box__total">
				<span><?php esc_html_e( 'Total', 'sreesaanvika' ); ?></span>
				<strong><?php wc_cart_totals_order_total_html(); ?></strong>
			</div>

			<div style="margin-top:20px">
				<?php do_action( 'woocommerce_proceed_to_checkout' ); ?>
			</div>

			<div class="ss-securenote">
				<?php ss_the_icon( 'lock', 15 ); ?>
				<span><?php esc_html_e( 'Secure checkout · UPI, cards, netbanking, COD', 'sreesaanvika' ); ?></span>
			</div>
		</div>

		<div class="ss-summary-box">
			<div class="ss-trust" style="grid-template-columns:1fr;gap:10px">
				<div class="ss-trust__item" style="display:flex;align-items:center;gap:12px;text-align:left">
					<?php ss_the_icon( 'truck', 22 ); ?>
					<span><?php esc_html_e( 'Free shipping on orders above ₹2,999', 'sreesaanvika' ); ?></span>
				</div>
				<div class="ss-trust__item" style="display:flex;align-items:center;gap:12px;text-align:left">
					<?php ss_the_icon( 'refresh', 22 ); ?>
					<span><?php esc_html_e( '7-day returns with free reverse pickup', 'sreesaanvika' ); ?></span>
				</div>
				<div class="ss-trust__item" style="display:flex;align-items:center;gap:12px;text-align:left">
					<?php ss_the_icon( 'headset', 22 ); ?>
					<span><?php esc_html_e( 'Stylist support on WhatsApp, 10am – 7pm', 'sreesaanvika' ); ?></span>
				</div>
			</div>
		</div>

	</aside>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
