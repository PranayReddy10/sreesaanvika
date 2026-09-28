<?php
/**
 * Variable product add to cart, rendered as swatch rows.
 *
 * Each attribute keeps Woo's own <select> (visually hidden) so validation,
 * price updates and no-JS fallback all keep working; shop.js mirrors the
 * options into colour circles or size chips.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );

do_action( 'woocommerce_before_add_to_cart_form' );
?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>"
	method="post" enctype="multipart/form-data"
	data-product_id="<?php echo absint( $product->get_id() ); ?>"
	data-product_variations="<?php echo $variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>">

	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock">
			<?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?>
		</p>
	<?php else : ?>

		<div class="variations">
			<?php foreach ( $attributes as $attribute_name => $options ) : ?>
				<?php
				$od_label    = wc_attribute_label( $attribute_name );
				$od_is_color = (bool) preg_match( '/color|colour|shade/i', $attribute_name );
				$od_is_size  = (bool) preg_match( '/size|length/i', $attribute_name );

				// Prebuild a colour lookup so the swatches match the term meta.
				$od_color_attrs = '';

				if ( $od_is_color && taxonomy_exists( $attribute_name ) ) {
					foreach ( $options as $od_option ) {
						$od_term = get_term_by( 'slug', $od_option, $attribute_name );

						if ( $od_term && ! is_wp_error( $od_term ) ) {
							$od_color_attrs .= ' data-color-' . esc_attr( $od_option ) . '="' . esc_attr( od_color_hex( $od_term->name, $od_term->term_id ) ) . '"';
						}

						// A colour with its own photos gets an image swatch rather than a circle.
						$od_swatch_img = function_exists( 'od_color_swatch_image' ) ? od_color_swatch_image( $product, $od_option ) : '';

						if ( $od_swatch_img ) {
							$od_color_attrs .= ' data-img-' . esc_attr( $od_option ) . '="' . esc_url( $od_swatch_img ) . '"';
						}
					}
				}
				?>
				<div class="od-varblock" data-attribute-row>
					<div class="od-varblock__head">
						<p class="od-varblock__label">
							<?php echo esc_html( $od_label ); ?>
							<b data-selected-label></b>
						</p>

						<?php if ( $od_is_size ) : ?>
							<button type="button" class="od-varblock__link" data-panel="#od-sizeguide">
								<?php esc_html_e( 'Size guide', 'ojasvidrapes' ); ?>
							</button>
						<?php endif; ?>
					</div>

					<div<?php echo $od_color_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						data-swatches class="<?php echo $od_is_color ? 'od-colorswatches' : 'od-fabricchips'; ?>"></div>

					<div class="od-varblock__select">
						<?php
						wc_dropdown_variation_attribute_options(
							array(
								'options'   => $options,
								'attribute' => $attribute_name,
								'product'   => $product,
							)
						);
						?>
					</div>
				</div>
			<?php endforeach; ?>

			<a class="reset_variations" href="#" style="font-size:.78rem;letter-spacing:.1em;text-transform:uppercase">
				<?php esc_html_e( 'Clear selection', 'woocommerce' ); ?>
			</a>
		</div>

		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<div class="single_variation_wrap">
			<?php
			do_action( 'woocommerce_before_single_variation' );
			do_action( 'woocommerce_single_variation' );
			do_action( 'woocommerce_after_single_variation' );
			?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php do_action( 'woocommerce_after_add_to_cart_form' ); ?>

<aside class="od-panel" id="od-sizeguide" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Size guide', 'ojasvidrapes' ); ?>">
	<div class="od-panel__head">
		<h3><?php od_the_icon( 'ruler', 19 ); ?><?php esc_html_e( 'Size guide', 'ojasvidrapes' ); ?></h3>
		<button type="button" class="od-icon-btn" data-close aria-label="<?php esc_attr_e( 'Close', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'close', 19 ); ?>
		</button>
	</div>

	<div class="od-panel__body">
		<?php wc_get_template( 'single-product/size-guide.php' ); ?>
	</div>
</aside>
