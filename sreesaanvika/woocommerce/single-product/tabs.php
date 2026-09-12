<?php
/**
 * Product information tabs.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$ss_tabs = array();

if ( $product->get_description() ) {
	$ss_tabs['description'] = __( 'Description', 'sreesaanvika' );
}

$ss_attributes = array_filter( $product->get_attributes(), function ( $attribute ) {
	return $attribute->get_visible();
} );

if ( $ss_attributes || $product->has_dimensions() || $product->has_weight() ) {
	$ss_tabs['specs'] = __( 'Specifications', 'sreesaanvika' );
}

$ss_tabs['care']     = __( 'Care & Handling', 'sreesaanvika' );
$ss_tabs['shipping'] = __( 'Shipping & Returns', 'sreesaanvika' );

if ( comments_open() || $product->get_review_count() ) {
	/* translators: %d: review count */
	$ss_tabs['reviews'] = sprintf( __( 'Reviews (%d)', 'sreesaanvika' ), $product->get_review_count() );
}

if ( ! $ss_tabs ) {
	return;
}

$ss_first = array_key_first( $ss_tabs );
?>
<div class="ss-tabs">

	<div class="ss-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Product information', 'sreesaanvika' ); ?>">
		<?php foreach ( $ss_tabs as $ss_key => $ss_label ) : ?>
			<button type="button" role="tab" id="tabbtn-<?php echo esc_attr( $ss_key ); ?>"
				aria-controls="tab-<?php echo esc_attr( $ss_key ); ?>"
				aria-selected="<?php echo $ss_key === $ss_first ? 'true' : 'false'; ?>"
				class="<?php echo $ss_key === $ss_first ? 'is-active' : ''; ?>">
				<?php echo esc_html( $ss_label ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<?php if ( isset( $ss_tabs['description'] ) ) : ?>
		<div class="ss-tabs__panel<?php echo 'description' === $ss_first ? ' is-active' : ''; ?>"
			id="tab-description" role="tabpanel" aria-labelledby="tabbtn-description">
			<?php echo wp_kses_post( wpautop( do_shortcode( $product->get_description() ) ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( isset( $ss_tabs['specs'] ) ) : ?>
		<div class="ss-tabs__panel<?php echo 'specs' === $ss_first ? ' is-active' : ''; ?>"
			id="tab-specs" role="tabpanel" aria-labelledby="tabbtn-specs">
			<?php wc_display_product_attributes( $product ); ?>
		</div>
	<?php endif; ?>

	<div class="ss-tabs__panel" id="tab-care" role="tabpanel" aria-labelledby="tabbtn-care">
		<h3><?php esc_html_e( 'Keeping your weave alive', 'sreesaanvika' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Dry clean only for the first two washes on any pure silk or zari saree.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Store folded in a cotton or muslin cloth — never in plastic, which traps moisture and dulls zari.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Refold along a different line every three months so the silk does not crease permanently.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Keep perfume and deodorant away from the fabric; spray before you drape, not after.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Iron on the reverse at a low setting with a cotton cloth between the iron and the weave.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'For oxidised and antique-finish jewellery, wipe with a dry cloth and store in the pouch provided.', 'sreesaanvika' ); ?></li>
		</ul>
	</div>

	<div class="ss-tabs__panel" id="tab-shipping" role="tabpanel" aria-labelledby="tabbtn-shipping">
		<h3><?php esc_html_e( 'Shipping', 'sreesaanvika' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Free shipping across India on orders above ₹2,999; a flat ₹99 below that.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Metro cities: 2–4 business days. Rest of India: 4–7 business days.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Cash on delivery available on orders up to ₹15,000.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'International shipping is calculated at checkout and takes 7–14 business days.', 'sreesaanvika' ); ?></li>
		</ul>

		<h3><?php esc_html_e( 'Returns & exchanges', 'sreesaanvika' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Seven days from delivery, on unworn pieces with the tags intact.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Free reverse pickup in serviceable PIN codes; refunds land within 5–7 business days.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Custom-stitched blouses, altered garments and pierced jewellery are final sale.', 'sreesaanvika' ); ?></li>
			<li><?php esc_html_e( 'Slight variations in colour and weave are the mark of a handloom, not a defect.', 'sreesaanvika' ); ?></li>
		</ul>
	</div>

	<?php if ( isset( $ss_tabs['reviews'] ) ) : ?>
		<div class="ss-tabs__panel" id="tab-reviews" role="tabpanel" aria-labelledby="tabbtn-reviews">
			<?php ss_reviews_summary( $product ); ?>
			<?php comments_template(); ?>
		</div>
	<?php endif; ?>
</div>
