<?php
/**
 * Product summary column.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$ss_cats  = get_the_terms( $product->get_id(), 'product_cat' );
$ss_sku   = $product->get_sku();
$ss_sold  = $product->get_total_sales();
$ss_rate  = (float) $product->get_average_rating();
$ss_count = $product->get_review_count();
?>

<div class="ss-summary__brandline">
	<?php if ( $ss_cats && ! is_wp_error( $ss_cats ) ) : ?>
		<a href="<?php echo esc_url( get_term_link( $ss_cats[0] ) ); ?>"><?php echo esc_html( $ss_cats[0]->name ); ?></a>
	<?php endif; ?>

	<?php if ( $ss_sku ) : ?>
		<span class="sep" aria-hidden="true">·</span>
		<span class="sku"><?php echo esc_html__( 'SKU', 'sreesaanvika' ) . ' ' . esc_html( $ss_sku ); ?></span>
	<?php endif; ?>
</div>

<h1 class="product_title entry-title"><?php the_title(); ?></h1>

<div class="ss-summary__meta-row">
	<?php if ( $ss_rate > 0 ) : ?>
		<a href="#tab-reviews" class="ss-rating-link" style="display:inline-flex;align-items:center;gap:6px">
			<?php echo ss_stars( $ss_rate, $ss_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	<?php endif; ?>

	<?php if ( $ss_sold > 3 ) : ?>
		<span class="ss-sold-note">
			<?php ss_the_icon( 'flame', 15 ); ?>
			<?php
			printf(
				/* translators: %s: number of units sold */
				esc_html__( '%s sold recently', 'sreesaanvika' ),
				esc_html( number_format_i18n( $ss_sold ) )
			);
			?>
		</span>
	<?php endif; ?>
</div>

<div data-var-price>
	<?php ss_price_block( $product, 'ss-price-block price' ); ?>
</div>

<p class="ss-tax-note"><?php esc_html_e( 'Inclusive of all taxes · Free shipping over ₹2,999', 'sreesaanvika' ); ?></p>

<?php if ( $product->get_short_description() ) : ?>
	<div class="woocommerce-product-details__short-description">
		<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
	</div>
<?php endif; ?>

<?php ss_single_offers(); ?>

<?php
/**
 * The add-to-cart form. Woo renders the variation selects; shop.js mirrors
 * each one into swatch buttons and keeps the select as the source of truth.
 */
woocommerce_template_single_add_to_cart();
?>

<?php ss_single_trust(); ?>

<?php ss_single_pincode(); ?>

<div class="ss-metalist">
	<?php
	$ss_rows = array(
		'fabric'    => __( 'Fabric', 'sreesaanvika' ),
		'work'      => __( 'Work', 'sreesaanvika' ),
		'occasion'  => __( 'Occasion', 'sreesaanvika' ),
		'blouse'    => __( 'Blouse piece', 'sreesaanvika' ),
		'length'    => __( 'Length', 'sreesaanvika' ),
		'wash_care' => __( 'Wash care', 'sreesaanvika' ),
	);

	foreach ( $ss_rows as $ss_key => $ss_label ) :
		$ss_value = ss_product_attribute( $product, $ss_key );

		if ( ! $ss_value ) {
			continue;
		}
		?>
		<div>
			<span class="k"><?php echo esc_html( $ss_label ); ?></span>
			<span class="v"><?php echo wp_kses_post( $ss_value ); ?></span>
		</div>
	<?php endforeach; ?>

	<?php
	$ss_tags = get_the_terms( $product->get_id(), 'product_tag' );

	if ( $ss_tags && ! is_wp_error( $ss_tags ) ) :
		?>
		<div>
			<span class="k"><?php esc_html_e( 'Tags', 'sreesaanvika' ); ?></span>
			<span class="v">
				<?php
				$ss_links = array();

				foreach ( $ss_tags as $ss_tag ) {
					$ss_links[] = '<a href="' . esc_url( get_term_link( $ss_tag ) ) . '">' . esc_html( $ss_tag->name ) . '</a>';
				}

				echo wp_kses_post( implode( ', ', $ss_links ) );
				?>
			</span>
		</div>
	<?php endif; ?>
</div>

<?php ss_single_share(); ?>
