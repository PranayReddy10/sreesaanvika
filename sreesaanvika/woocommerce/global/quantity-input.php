<?php
/**
 * Quantity stepper.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( $max_value && $min_value === $max_value ) {
	?>
	<div class="quantity hidden">
		<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" class="qty" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $min_value ); ?>" />
	</div>
	<?php
} else {
	/* translators: %s: Quantity. */
	$labelledby = ! empty( $args['product_name'] ) ? sprintf( __( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) ) : '';
	?>
	<div class="quantity ss-qty">
		<button type="button" class="ss-qty-btn ss-qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'sreesaanvika' ); ?>">&minus;</button>

		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $label ); ?></label>

		<input
			type="<?php echo esc_attr( $type ); ?>"
			id="<?php echo esc_attr( $input_id ); ?>"
			class="<?php echo esc_attr( join( ' ', (array) $classes ) ); ?>"
			name="<?php echo esc_attr( $input_name ); ?>"
			value="<?php echo esc_attr( $input_value ); ?>"
			aria-label="<?php esc_attr_e( 'Product quantity', 'woocommerce' ); ?>"
			<?php if ( $min_value ) : ?>min="<?php echo esc_attr( $min_value ); ?>"<?php endif; ?>
			<?php if ( $max_value ) : ?>max="<?php echo esc_attr( 0 < $max_value ? $max_value : '' ); ?>"<?php endif; ?>
			<?php if ( ! empty( $labelledby ) ) : ?>aria-labelledby="<?php echo esc_attr( $labelledby ); ?>"<?php endif; ?>
			step="<?php echo esc_attr( $step ); ?>"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			inputmode="<?php echo esc_attr( $inputmode ); ?>"
			autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'on' ); ?>"
		/>

		<button type="button" class="ss-qty-btn ss-qty-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'sreesaanvika' ); ?>">+</button>
	</div>
	<?php
}
