<?php
/**
 * Quick view body, loaded over AJAX.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$ss_rating = (float) $product->get_average_rating();
?>
<div class="ss-quickview">

	<div class="ss-quickview__media">
		<?php echo wp_kses_post( $product->get_image( 'ss-product-lg' ) ); ?>
	</div>

	<div class="ss-quickview__body">
		<?php
		$ss_cats = get_the_terms( $product->get_id(), 'product_cat' );

		if ( $ss_cats && ! is_wp_error( $ss_cats ) ) :
			?>
			<span class="ss-pcard__cat"><?php echo esc_html( $ss_cats[0]->name ); ?></span>
		<?php endif; ?>

		<h2 style="margin:0;font-size:clamp(1.4rem,2.6vw,2rem)">
			<a href="<?php echo esc_url( $product->get_permalink() ); ?>" style="color:inherit"><?php echo esc_html( $product->get_name() ); ?></a>
		</h2>

		<?php if ( $ss_rating > 0 ) : ?>
			<?php echo ss_stars( $ss_rating, $product->get_review_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

		<?php ss_price_block( $product, 'ss-price-block price' ); ?>

		<?php if ( $product->get_short_description() ) : ?>
			<div class="woocommerce-product-details__short-description">
				<?php echo wp_kses_post( wpautop( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 40 ) ) ); ?>
			</div>
		<?php endif; ?>

		<?php ss_card_swatches( $product, 8 ); ?>

		<?php echo wc_get_stock_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="ss-buyrow" style="margin-top:6px">
			<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
				<button type="button" class="ss-btn ss-ajax-add ss-btn--cart" data-id="<?php echo esc_attr( $product->get_id() ); ?>">
					<?php ss_the_icon( 'bag', 16 ); ?>
					<?php esc_html_e( 'Add to bag', 'sreesaanvika' ); ?>
				</button>
			<?php else : ?>
				<a class="ss-btn ss-btn--cart" href="<?php echo esc_url( $product->get_permalink() ); ?>">
					<?php echo esc_html( $product->is_in_stock() ? __( 'Select options', 'sreesaanvika' ) : __( 'View product', 'sreesaanvika' ) ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ss_option( 'wishlist_on', true ) ) : ?>
				<button type="button" class="ss-icon-btn ss-wishlist-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Add to wishlist', 'sreesaanvika' ); ?>" aria-pressed="false">
					<?php ss_the_icon( 'heart', 19 ); ?>
				</button>
			<?php endif; ?>

			<?php if ( ss_option( 'compare_on', true ) ) : ?>
				<button type="button" class="ss-icon-btn ss-compare-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Add to compare', 'sreesaanvika' ); ?>" aria-pressed="false">
					<?php ss_the_icon( 'compare', 19 ); ?>
				</button>
			<?php endif; ?>
		</div>

		<a class="ss-readmore" href="<?php echo esc_url( $product->get_permalink() ); ?>">
			<?php esc_html_e( 'See full details', 'sreesaanvika' ); ?>
			<?php ss_the_icon( 'arrow-right', 15 ); ?>
		</a>
	</div>
</div>
