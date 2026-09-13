<?php
/**
 * Saree spotlight.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$ss_has = ss_product_loop( array_merge( ss_cat_query( array( 'sarees', 'saree', 'silk-sarees' ) ), array( 'orderby' => 'popularity' ) ), 0, array( 'section' => 'sarees' ) );
$ss_loop = ob_get_clean();

if ( ! $ss_has ) {
	return;
}
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Six yards of grace', 'sreesaanvika' ),
			__( 'The <em>Saree</em> Edit', 'sreesaanvika' ),
			__( 'Kanchipuram, Banarasi, Pochampally, Chanderi and Bhagalpuri silks — straight from the weavers.', 'sreesaanvika' )
		);

		echo $ss_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
