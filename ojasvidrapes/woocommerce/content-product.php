<?php
/**
 * Product card in a loop.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$od_id       = $product->get_id();
$od_link     = get_permalink( $od_id );
$od_second   = od_secondary_image( $product );
// The small line above the name: the category, the pattern, the colour —
// whatever this shop browses by, and nothing at all when it browses by nothing.
$od_tax      = od_browse_taxonomy();
$od_cats     = $od_tax ? get_the_terms( $od_id, $od_tax ) : array();
$od_cat_name = ( $od_cats && ! is_wp_error( $od_cats ) ) ? $od_cats[0]->name : '';
$od_rating   = (float) $product->get_average_rating();
?>
<li <?php wc_product_class( 'od-product-card', $product ); ?>>

	<div class="od-pcard__media<?php echo $od_second ? '' : ' od-pcard__media--single'; ?>">
		<a href="<?php echo esc_url( $od_link ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			echo wp_kses_post(
				$product->get_image(
					'od-product',
					array(
						'class'   => 'od-pcard__img od-pcard__img--front',
						'loading' => 'lazy',
					)
				)
			);
			?>

			<?php if ( $od_second ) : ?>
				<img class="od-pcard__img od-pcard__img--back" src="<?php echo esc_url( $od_second ); ?>" alt="" loading="lazy" />
			<?php endif; ?>
		</a>

		<?php
		od_product_badges( $product, 'card' );
		od_card_actions( $product );
		?>

		<div class="od-pcard__buy">
			<?php if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) : ?>
				<button type="button" class="od-btn od-btn--sm od-ajax-add" data-id="<?php echo esc_attr( $od_id ); ?>">
					<?php od_the_icon( 'bag', 15 ); ?>
					<?php esc_html_e( 'Add to bag', 'ojasvidrapes' ); ?>
				</button>
			<?php elseif ( ! $product->is_in_stock() ) : ?>
				<a class="od-btn od-btn--sm od-btn--solid-dark" href="<?php echo esc_url( $od_link ); ?>">
					<?php esc_html_e( 'Notify me', 'ojasvidrapes' ); ?>
				</a>
			<?php else : ?>
				<a class="od-btn od-btn--sm" href="<?php echo esc_url( $od_link ); ?>">
					<?php esc_html_e( 'Select options', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>

	<div class="od-pcard__body">
		<?php if ( $od_cat_name ) : ?>
			<span class="od-pcard__cat"><?php echo esc_html( $od_cat_name ); ?></span>
		<?php endif; ?>

		<h3 class="od-pcard__title">
			<a href="<?php echo esc_url( $od_link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</h3>

		<?php if ( $od_rating > 0 ) : ?>
			<?php echo od_stars( $od_rating, $product->get_review_count() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endif; ?>

		<?php
		od_price_block( $product );
		od_card_swatches( $product );
		od_stock_meter( $product );

		if ( $product->get_short_description() ) {
			echo '<p class="od-pcard__desc">' . esc_html( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 22 ) ) . '</p>';
		}
		?>
	</div>
</li>
