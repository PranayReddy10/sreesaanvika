<?php
/**
 * Add-to-cart row shown once a variation is chosen.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;
?>
<div class="woocommerce-variation-add-to-cart variations_button">
	<?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>

	<div class="ss-buyrow">
		<?php
		do_action( 'woocommerce_before_add_to_cart_quantity' );

		woocommerce_quantity_input(
			array(
				'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
				'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
				'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wc_clean( wp_unslash( $_POST['quantity'] ) ) ) : $product->get_min_purchase_quantity(), // phpcs:ignore WordPress.Security.NonceVerification.Missing
			)
		);

		do_action( 'woocommerce_after_add_to_cart_quantity' );
		?>

		<button type="submit" class="single_add_to_cart_button ss-btn button alt">
			<?php ss_the_icon( 'bag', 16 ); ?>
			<?php echo esc_html( $product->single_add_to_cart_text() ); ?>
		</button>

		<?php if ( ss_option( 'compare_on', true ) ) : ?>
			<button type="button" class="ss-icon-btn ss-compare-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
				aria-label="<?php esc_attr_e( 'Add to compare', 'sreesaanvika' ); ?>" aria-pressed="false">
				<?php ss_the_icon( 'compare', 19 ); ?>
			</button>
		<?php endif; ?>
	</div>

	<?php ss_buy_now_button(); ?>

	<?php ss_line_total(); ?>

	<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>

	<input type="hidden" name="add-to-cart" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="product_id" value="<?php echo absint( $product->get_id() ); ?>" />
	<input type="hidden" name="variation_id" class="variation_id" value="0" />
</div>
