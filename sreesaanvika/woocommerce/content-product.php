<?php
/**
 * Product card in a loop.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$ss_id       = $product->get_id();
$ss_link     = get_permalink( $ss_id );
$ss_second   = ss_secondary_image( $product );
$ss_cats     = get_the_terms( $ss_id, 'product_cat' );
$ss_cat_name = ( $ss_cats && ! is_wp_error( $ss_cats ) ) ? $ss_cats[0]->name : '';
$ss_rating   = (float) $product->get_average_rating();
?>
<li <?php wc_product_class( 'ss-product-card', $product ); ?>>

	<div class="ss-pcard__media<?php echo $ss_second ? '' : ' ss-pcard__media--single'; ?>">
		<a href="<?php echo esc_url( $ss_link ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			echo wp_kses_post(
				$product->get_image(
					'ss-product',
					array(
						'class'   => 'ss-pcard__img ss-pcard__img--front',
						'loading' => 'lazy',
					)
				)
			);
			?>

			<?php if ( $ss_second ) : ?>
				<img class="ss-pcard__img ss-pcard__img--back" src="<?php echo esc_url( $ss_second ); ?>" alt="" loading="lazy" />
			<?php endif; ?>
		</a>

		<?php
		ss_product_badges( $product, 'card' );
		ss_card_actions( $product );
		?>

		<div class="ss-pcard__buy">
			<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
				<button type="button" class="ss-btn ss-btn--sm ss-ajax-add" data-id="<?php echo esc_attr( $ss_id ); ?>">
					<?php ss_the_icon( 'bag', 15 ); ?>
					<?php esc_html_e( 'Add to bag', 'sreesaanvika' ); ?>
				</button>
			<?php elseif ( ! $product->is_in_stock() ) : ?>
				<a class="ss-btn ss-btn--sm ss-btn--solid-dark" href="<?php echo esc_url( $ss_link ); ?>">
					<?php esc_html_e( 'Notify me', 'sreesaanvika' ); ?>
				</a>
			<?php else : ?>
				<a class="ss-btn ss-btn--sm" href="<?php echo esc_url( $ss_link ); ?>">
					<?php esc_html_e( 'Select options', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="ss-pcard__body">
		<?php if ( $ss_cat_name ) : ?>
			<span class="ss-pcard__cat"><?php echo esc_html( $ss_cat_name ); ?></span>
		<?php endif; ?>

		<h3 class="ss-pcard__title">
			<a href="<?php echo esc_url( $ss_link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</h3>

		<?php if ( $ss_rating > 0 ) : ?>
			<?php echo ss_stars( $ss_rating, $product->get_review_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

		<?php
		ss_price_block( $product );
		ss_card_swatches( $product );
		ss_stock_meter( $product );

		if ( $product->get_short_description() ) {
			echo '<p class="ss-pcard__desc">' . esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 22 ) ) . '</p>';
		}
		?>
	</div>
</li>
