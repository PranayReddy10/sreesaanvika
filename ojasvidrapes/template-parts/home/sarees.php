<?php
/**
 * Saree spotlight.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$od_has = od_product_loop( array_merge( od_cat_query( array( 'sarees', 'saree', 'silk-sarees' ) ), array( 'orderby' => 'popularity' ) ), 0, array( 'section' => 'sarees' ) );
$od_loop = ob_get_clean();

if ( ! $od_has ) {
	return;
}
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'Six yards of grace', 'ojasvidrapes' ),
			__( 'The <em>Saree</em> Edit', 'ojasvidrapes' ),
			__( 'Kanchipuram, Banarasi, Pochampally, Chanderi and Bhagalpuri silks — straight from the weavers.', 'ojasvidrapes' )
		);

		echo $od_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
