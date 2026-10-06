<?php
/**
 * Best sellers.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$od_has = od_product_loop(
	array(
		'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'  => 'meta_value_num',
		'order'    => 'DESC',
	),
	0,
	array( 'section' => 'bestsellers' )
);
$od_loop = ob_get_clean();

if ( ! $od_has ) {
	return;
}
?>
<section class="od-section od-reveal" style="background:var(--od-bg-alt)">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'Loved by our customers', 'ojasvidrapes' ),
			__( 'Best <em>Sellers</em>', 'ojasvidrapes' ),
			__( 'The pieces that keep going out of stock — and keep coming back.', 'ojasvidrapes' )
		);

		echo $od_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
