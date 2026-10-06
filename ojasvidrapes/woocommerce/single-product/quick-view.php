<?php
/**
 * Quick view body, loaded over AJAX.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$od_rating = (float) $product->get_average_rating();
?>
<div class="od-quickview">

	<div class="od-quickview__media">
		<?php echo wp_kses_post( $product->get_image( 'od-product-lg' ) ); ?>
	</div>

	<div class="od-quickview__body">
		<?php
		$od_cats = get_the_terms( $product->get_id(), 'product_cat' );

		if ( $od_cats && ! is_wp_error( $od_cats ) ) :
			?>
			<span class="od-pcard__cat"><?php echo esc_html( $od_cats[0]->name ); ?></span>
		<?php endif; ?>

		<h2 style="margin:0;font-size:clamp(1.4rem,2.6vw,2rem)">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="color:inherit"><?php echo esc_html( $product->get_name() ); ?></a>
		</h2>

		<?php if ( $od_rating > 0 ) : ?>
			<?php echo od_stars( $od_rating, $product->get_review_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

		<?php od_price_block( $product, 'od-price-block price' ); ?>

		<?php if ( $product->get_short_description() ) : ?>
			<div class="woocommerce-product-details__short-description">
				<?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 40 ) ) ); ?>
			</div>
		<?php endif; ?>

		<?php od_card_swatches( $product, 8 ); ?>

		<?php echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="od-buyrow" style="margin-top:6px">
			<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
				<button type="button" class="od-btn od-ajax-add od-btn--cart" data-id="<?php echo esc_attr( $product->get_id() ); ?>">
					<?php od_the_icon( 'bag', 16 ); ?>
					<?php esc_html_e( 'Add to bag', 'ojasvidrapes' ); ?>
				</button>
			<?php else : ?>
				<a class="od-btn od-btn--cart" href="<?php echo esc_url( $product->get_permalink() ); ?>">
					<?php echo esc_html( $product->is_in_stock() ? __( 'Select options', 'ojasvidrapes' ) : __( 'View product', 'ojasvidrapes' ) ); ?>
				</a>
			<?php endif; ?>

			<?php if ( od_option( 'wishlist_on', true ) ) : ?>
				<button type="button" class="od-icon-btn od-wishlist-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Add to wishlist', 'ojasvidrapes' ); ?>" aria-pressed="false">
					<?php od_the_icon( 'heart', 19 ); ?>
				</button>
			<?php endif; ?>

			<?php if ( od_option( 'compare_on', true ) ) : ?>
				<button type="button" class="od-icon-btn od-compare-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Add to compare', 'ojasvidrapes' ); ?>" aria-pressed="false">
					<?php od_the_icon( 'compare', 19 ); ?>
				</button>
			<?php endif; ?>
		</div>

		<a class="od-readmore" href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<?php esc_html_e( 'See full details', 'ojasvidrapes' ); ?>
			<?php od_the_icon( 'arrow-right', 15 ); ?>
		</a>
	</div>
</div>
