<?php
/**
 * Product summary column.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$od_cats  = get_the_terms( $product->get_id(), 'product_cat' );
$od_sku   = $product->get_sku();
$od_sold  = $product->get_total_sales();
$od_rate  = (float) $product->get_average_rating();
$od_count = $product->get_review_count();
?>

<div class="od-summary__brandline">
	<?php if ( $od_cats && ! is_wp_error( $od_cats ) ) : ?>
		<a href="<?php echo esc_url( get_term_link( $od_cats[0] ) ); ?>"><?php echo esc_html( $od_cats[0]->name ); ?></a>
	<?php endif; ?>

	<?php if ( $od_sku ) : ?>
		<span class="sep" aria-hidden="true">·</span>
		<span class="sku"><?php echo esc_html__( 'SKU', 'ojasvidrapes' ) . ' ' . esc_html( $od_sku ); ?></span>
	<?php endif; ?>
</div>

<h1 class="product_title entry-title"><?php the_title(); ?></h1>

<div class="od-summary__meta-row">
	<?php if ( $od_rate > 0 ) : ?>
		<a href="#tab-reviews" class="od-rating-link" style="display:inline-flex;align-items:center;gap:6px">
			<?php echo od_stars( $od_rate, $od_count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
	<?php endif; ?>

	<?php if ( $od_sold > 3 ) : ?>
		<span class="od-sold-note">
			<?php od_the_icon( 'flame', 15 ); ?>
			<?php
			printf(
				/* translators: %s: number of units sold */
				esc_html__( '%s sold recently', 'ojasvidrapes' ),
				esc_html( number_format_i18n( $od_sold ) )
			);
			?>
		</span>
	<?php endif; ?>
</div>

<div data-var-price>
	<?php od_price_block( $product, 'od-price-block price' ); ?>
</div>

<p class="od-tax-note"><?php esc_html_e( 'Inclusive of all taxes · Free shipping over ₹2,999', 'ojasvidrapes' ); ?></p>

<?php if ( $product->get_short_description() ) : ?>
	<div class="woocommerce-product-details__short-description">
		<?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?>
	</div>
<?php endif; ?>

<?php od_single_offers(); ?>

<?php
/*
 * The standard WooCommerce summary hook, so any plugin that expects to put
 * something in this column can. The theme lays out the title, price, excerpt
 * and add-to-cart itself, and od_unhook_woo_summary() takes WooCommerce's own
 * callbacks off this action to stop them being printed a second time — what
 * is left is other people's.
 */
do_action( 'woocommerce_single_product_summary' );
?>

<?php
/**
 * The add-to-cart form. Woo renders the variation selects; shop.js mirrors
 * each one into swatch buttons and keeps the select as the source of truth.
 */
woocommerce_template_single_add_to_cart();
?>

<?php od_single_trust(); ?>

<?php od_single_pincode(); ?>

<div class="od-metalist">
	<?php
	$od_rows = array(
		'fabric'    => __( 'Fabric', 'ojasvidrapes' ),
		'work'      => __( 'Work', 'ojasvidrapes' ),
		'occasion'  => __( 'Occasion', 'ojasvidrapes' ),
		'blouse'    => __( 'Blouse piece', 'ojasvidrapes' ),
		'length'    => __( 'Length', 'ojasvidrapes' ),
		'wash_care' => __( 'Wash care', 'ojasvidrapes' ),
	);

	foreach ( $od_rows as $od_key => $od_label ) :
		$od_value = od_product_attribute( $product, $od_key );

		if ( ! $od_value ) {
			continue;
		}
		?>
		<div>
			<span class="k"><?php echo esc_html( $od_label ); ?></span>
			<span class="v"><?php echo wp_kses_post( $od_value ); ?></span>
		</div>
	<?php endforeach; ?>

	<?php
	$od_tags = get_the_terms( $product->get_id(), 'product_tag' );

	if ( $od_tags && ! is_wp_error( $od_tags ) ) :
		?>
		<div>
			<span class="k"><?php esc_html_e( 'Tags', 'ojasvidrapes' ); ?></span>
			<span class="v">
				<?php
				$od_links = array();

				foreach ( $od_tags as $od_tag ) {
					$od_links[] = '<a href="' . esc_url( get_term_link( $od_tag ) ) . '">' . esc_html( $od_tag->name ) . '</a>';
				}

				echo wp_kses_post( implode( ', ', $od_links ) );
				?>
			</span>
		</div>
	<?php endif; ?>
</div>

<?php od_single_share(); ?>
