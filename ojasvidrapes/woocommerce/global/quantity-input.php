<?php
/**
 * Quantity stepper.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( $max_value && $min_value === $max_value ) {
	?>
	<div class="quantity hidden">
		<input type="hidden" id="<?php echo esc_attr( $input_id ); ?>" class="qty" name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $min_value ); ?>" />
	</div>
	<?php
} else {
	/*
	 * WooCommerce builds this label inside its own copy of the template, so an
	 * override has to build it too — there is no $label in the arguments. It
	 * was being read without ever being set, which is a PHP warning on every
	 * quantity box on the site: every product page, every cart line, the
	 * mini-bag. On a host that shows warnings they print straight into the
	 * page, and into anything else being sent at the time.
	 */
	/* translators: %s: Product name. */
	$label = ! empty( $args['product_name'] )
		? sprintf( esc_html__( '%s quantity', 'woocommerce' ), wp_strip_all_tags( $args['product_name'] ) )
		: esc_html__( 'Quantity', 'woocommerce' );

	$labelledby = ! empty( $args['product_name'] ) ? $label : '';
	?>
	<div class="quantity od-qty">
		<button type="button" class="od-qty-btn od-qty-minus" aria-label="<?php esc_attr_e( 'Decrease quantity', 'ojasvidrapes' ); ?>">&minus;</button>

		<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_attr( $label ); ?></label>

		<input
			type="<?php echo esc_attr( $type ); ?>"
			<?php echo ! empty( $readonly ) ? 'readonly="readonly"' : ''; ?>
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
			<?php /* Never "on": a restored value fights what the cart actually holds. */ ?>
			autocomplete="<?php echo esc_attr( isset( $autocomplete ) ? $autocomplete : 'off' ); ?>"
		/>

		<button type="button" class="od-qty-btn od-qty-plus" aria-label="<?php esc_attr_e( 'Increase quantity', 'ojasvidrapes' ); ?>">+</button>
	</div>
	<?php
}
