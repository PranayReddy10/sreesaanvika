<?php
/**
 * Simple product add to cart.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product->is_purchasable() ) {
	return;
}

echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

if ( $product->is_in_stock() ) :
	?>
	<?php do_action( 'woocommerce_before_add_to_cart_form' ); ?>

	<form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data">
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

			<button type="submit" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>"
				class="single_add_to_cart_button ss-btn button alt">
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

		<?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
	</form>

	<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>
	<?php
endif;
