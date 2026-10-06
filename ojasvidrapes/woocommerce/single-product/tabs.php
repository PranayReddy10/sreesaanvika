<?php
/**
 * Product information tabs.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$od_tabs = array();

if ( $product->get_description() ) {
	$od_tabs['description'] = __( 'Description', 'ojasvidrapes' );
}

$od_attributes = array_filter( $product->get_attributes(), function ( $attribute ) {
	return $attribute->get_visible();
} );

if ( $od_attributes || $product->has_dimensions() || $product->has_weight() ) {
	$od_tabs['specs'] = __( 'Specifications', 'ojasvidrapes' );
}

$od_tabs['care']     = __( 'Care & Handling', 'ojasvidrapes' );
$od_tabs['shipping'] = __( 'Shipping & Returns', 'ojasvidrapes' );

if ( comments_open() || $product->get_review_count() ) {
	/* translators: %d: review count */
	$od_tabs['reviews'] = sprintf( __( 'Reviews (%d)', 'ojasvidrapes' ), $product->get_review_count() );
}

if ( ! $od_tabs ) {
	return;
}

$od_first = array_key_first( $od_tabs );
?>
<div class="od-tabs">

	<div class="od-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'ojasvidrapes' ); ?>">
		<?php foreach ( $od_tabs as $od_key => $od_label ) : ?>
			<button type="button" role="tab" id="tabbtn-<?php echo esc_attr( $od_key ); ?>"
				aria-controls="tab-<?php echo esc_attr( $od_key ); ?>"
				aria-selected="<?php echo $od_key === $od_first ? 'true' : 'false'; ?>"
				class="<?php echo $od_key === $od_first ? 'is-active' : ''; ?>">
				<?php echo esc_html( $od_label ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php if ( isset( $od_tabs['description'] ) ) : ?>
		<div class="od-tabs__panel<?php echo 'description' === $od_first ? ' is-active' : ''; ?>"
			id="tab-description" role="tabpanel" aria-labelledby="tabbtn-description">
			<?php echo wp_kses_post( wpautop( do_shortcode( $product->get_description() ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( isset( $od_tabs['specs'] ) ) : ?>
		<div class="od-tabs__panel<?php echo 'specs' === $od_first ? ' is-active' : ''; ?>"
			id="tab-specs" role="tabpanel" aria-labelledby="tabbtn-specs">
			<?php wc_display_product_attributes( $product ); ?>
		</div>
	<?php endif; ?>

	<div class="od-tabs__panel" id="tab-care" role="tabpanel" aria-labelledby="tabbtn-care">
		<h3><?php esc_html_e( 'Keeping your weave alive', 'ojasvidrapes' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Dry clean only for the first two washes on any pure silk or zari saree.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Store folded in a cotton or muslin cloth — never in plastic, which traps moisture and dulls zari.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Refold along a different line every three months so the silk does not crease permanently.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Keep perfume and deodorant away from the fabric; spray before you drape, not after.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Iron on the reverse at a low setting with a cotton cloth between the iron and the weave.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Fold along a different line every few months so the zari never creases in the same place twice.', 'ojasvidrapes' ); ?></li>
		</ul>
	</div>

	<div class="od-tabs__panel" id="tab-shipping" role="tabpanel" aria-labelledby="tabbtn-shipping">
		<h3><?php esc_html_e( 'Shipping', 'ojasvidrapes' ); ?></h3>
		<ul>
			<li>
				<?php
				/* translators: %s: the spend that earns free shipping */
				printf( esc_html__( 'Free shipping across India on orders above %s; a flat charge below that.', 'ojasvidrapes' ), esc_html( od_free_ship_price() ) );
				?>
			</li>
			<li><?php esc_html_e( 'Metro cities: 2–4 business days. Rest of India: 4–7 business days.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Cash on delivery available on orders up to ₹15,000.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'International shipping is calculated at checkout and takes 7–14 business days.', 'ojasvidrapes' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Returns & exchanges', 'ojasvidrapes' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Seven days from delivery, on unworn pieces with the tags intact.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Free reverse pickup in serviceable PIN codes; refunds land within 5–7 business days.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Sarees that have been cut, stitched with a fall and pico, or had the blouse piece separated are final sale.', 'ojasvidrapes' ); ?></li>
			<li><?php esc_html_e( 'Slight variations in colour and weave are the mark of a handloom, not a defect.', 'ojasvidrapes' ); ?></li>
		</ul>
	</div>

	<?php if ( isset( $od_tabs['reviews'] ) ) : ?>
		<div class="od-tabs__panel" id="tab-reviews" role="tabpanel" aria-labelledby="tabbtn-reviews">
			<?php od_reviews_summary( $product ); ?>
			<?php comments_template(); ?>
		</div>
	<?php endif; ?>
</div>
