<?php
/**
 * Single product page.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

while ( have_posts() ) :
	the_post();
	global $product;

	if ( ! $product instanceof WC_Product ) {
		$product = wc_get_product( get_the_ID() );
	}
	?>

	<?php od_page_header( get_the_title() ); ?>

	<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'od-container od-section od-section--tight', $product ); ?>>

		<?php
		/**
		 * Woo notices (stock warnings, coupon messages) belong above the fold.
		 */
		do_action( 'woocommerce_before_single_product' );
		?>

		<div class="od-single">

			<div class="od-single__gallery">
				<?php
				if ( od_option( 'use_woo_gallery', false ) ) {
					woocommerce_show_product_images();
				} else {
					wc_get_template( 'single-product/product-gallery.php' );
				}
				?>
			</div>

			<div class="od-summary summary entry-summary">
				<?php wc_get_template( 'single-product/summary.php', array( 'product' => $product ) ); ?>
			</div>

		</div>

		<?php wc_get_template( 'single-product/tabs.php', array( 'product' => $product ) ); ?>

		<?php
		// Upsells and related products use the standard Woo hooks.
		woocommerce_upsell_display();
		woocommerce_output_related_products();
		?>
	</div>

	<?php if ( od_option( 'sticky_buy', true ) ) : ?>
		<div class="od-stickybuy">
			<?php echo wp_kses_post( $product->get_image( 'od-thumb' ) ); ?>

			<div class="od-stickybuy__info">
				<div class="od-stickybuy__name"><?php echo esc_html( $product->get_name() ); ?></div>
				<div class="od-stickybuy__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			</div>

			<button type="button" class="od-btn od-btn--sm">
				<?php od_the_icon( 'bag', 15 ); ?>
				<?php esc_html_e( 'Add', 'ojasvidrapes' ); ?>
			</button>
		</div>
	<?php endif; ?>

	<?php
	do_action( 'woocommerce_after_single_product' );

endwhile;

get_footer( 'shop' );
