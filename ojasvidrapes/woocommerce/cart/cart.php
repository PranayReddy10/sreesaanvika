<?php
/**
 * Cart page.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );

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
<div class="od-cart">

	<form class="woocommerce-cart-form od-cart__main" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
		<?php do_action( 'woocommerce_before_cart_table' ); ?>

		<div class="od-cart__items">
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
				<div class="od-cartrow woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">

					<div class="od-cartrow__thumb">
						<?php
						$thumbnail = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image( 'od-product' ), $cart_item, $cart_item_key );

						if ( $product_permalink ) {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $thumbnail ) );
						} else {
							echo wp_kses_post( $thumbnail );
						}
						?>
					</div>

					<div class="od-cartrow__info">
						<h3 class="od-cartrow__name">
							<?php
							if ( $product_permalink ) {
								printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), esc_html( $_product->get_name() ) );
							} else {
								echo esc_html( $_product->get_name() );
							}
							?>
						</h3>

						<div class="od-cartrow__vars">
							<?php
							echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

							if ( $_product->is_sold_individually() ) {
								echo '<span>' . esc_html__( 'One per order', 'ojasvidrapes' ) . '</span>';
							}

							if ( ! $_product->is_in_stock() ) {
								echo '<span style="color:var(--od-error)">' . esc_html__( 'Out of stock', 'ojasvidrapes' ) . '</span>';
							}
							?>
						</div>

						<div class="od-cartrow__unit">
							<?php
							printf(
								/* translators: %s: unit price */
								esc_html__( 'Unit price: %s', 'ojasvidrapes' ),
								wp_kses_post( apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key ) )
							);
							?>
						</div>

						<?php if ( $_product->is_on_sale() ) : ?>
							<span class="od-cartrow__badge">
								<?php od_the_icon( 'tag', 14 ); ?>
								<?php esc_html_e( 'Discount applied', 'ojasvidrapes' ); ?>
							</span>
						<?php endif; ?>
					</div>

					<div class="od-cartrow__ctrl">
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

						<div class="od-cartrow__total">
							<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ) ); ?>
						</div>

						<?php
						echo apply_filters( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="od-cartrow__remove remove" aria-label="%s" data-product_id="%s" data-product_sku="%s">&times;</a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								/* translators: %s: product name */
								esc_attr( sprintf( __( 'Remove %s from your bag', 'ojasvidrapes' ), $_product->get_name() ) ),
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

		<div class="od-between" style="margin-top:20px;flex-wrap:wrap">
			<a class="od-btn od-btn--ghost od-btn--sm" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php od_the_icon( 'arrow-left', 15 ); ?>
				<?php esc_html_e( 'Continue shopping', 'ojasvidrapes' ); ?>
			</a>

			<button type="submit" class="od-btn od-btn--solid-dark od-btn--sm" name="update_cart" value="<?php esc_attr_e( 'Update bag', 'ojasvidrapes' ); ?>">
				<?php od_the_icon( 'refresh', 15 ); ?>
				<?php esc_html_e( 'Update bag', 'ojasvidrapes' ); ?>
			</button>
		</div>

		<?php do_action( 'woocommerce_cart_actions' ); ?>
		<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
		<?php do_action( 'woocommerce_after_cart_contents' ); ?>
		<?php do_action( 'woocommerce_after_cart_table' ); ?>
	</form>

	<aside class="od-cart__aside">

		<?php if ( wc_coupons_enabled() ) : ?>
			<div class="od-summary-box">
				<h3><?php od_the_icon( 'tag', 18 ); ?> <?php esc_html_e( 'Have a coupon?', 'ojasvidrapes' ); ?></h3>

				<form class="od-coupon" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>">
					<label class="screen-reader-text" for="coupon_code"><?php esc_html_e( 'Coupon code', 'ojasvidrapes' ); ?></label>
					<input type="text" name="coupon_code" class="input-text" id="coupon_code" value=""
						placeholder="<?php esc_attr_e( 'Enter code', 'ojasvidrapes' ); ?>" />
					<button type="submit" class="od-btn od-btn--sm" name="apply_coupon" value="<?php esc_attr_e( 'Apply', 'ojasvidrapes' ); ?>">
						<?php esc_html_e( 'Apply', 'ojasvidrapes' ); ?>
					</button>
					<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
				</form>
			</div>
		<?php endif; ?>

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

		<div class="od-summary-box">
			<div class="od-trust" style="grid-template-columns:1fr;gap:10px">
				<div class="od-trust__item" style="display:flex;align-items:center;gap:12px;text-align:left">
					<?php od_the_icon( 'truck', 22 ); ?>
					<span><?php esc_html_e( 'Free shipping on orders above ₹2,999', 'ojasvidrapes' ); ?></span>
				</div>
				<div class="od-trust__item" style="display:flex;align-items:center;gap:12px;text-align:left">
					<?php od_the_icon( 'refresh', 22 ); ?>
					<span><?php esc_html_e( '7-day returns with free reverse pickup', 'ojasvidrapes' ); ?></span>
				</div>
				<div class="od-trust__item" style="display:flex;align-items:center;gap:12px;text-align:left">
					<?php od_the_icon( 'headset', 22 ); ?>
					<span><?php esc_html_e( 'Stylist support on WhatsApp, 10am – 7pm', 'ojasvidrapes' ); ?></span>
				</div>
			</div>
		</div>

	</aside>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
