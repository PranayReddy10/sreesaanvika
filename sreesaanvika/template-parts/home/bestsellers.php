<?php
/**
 * Best sellers.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$ss_has = ss_product_loop(
	array(
		'meta_key' => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery
		'orderby'  => 'meta_value_num',
		'order'    => 'DESC',
	)
);
$ss_loop = ob_get_clean();

if ( ! $ss_has ) {
	return;
}
?>
<section class="ss-section ss-reveal" style="background:var(--ss-bg-alt)">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Loved by our customers', 'sreesaanvika' ),
			__( 'Best <em>Sellers</em>', 'sreesaanvika' ),
			__( 'The pieces that keep going out of stock — and keep coming back.', 'sreesaanvika' )
		);

		echo $ss_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
