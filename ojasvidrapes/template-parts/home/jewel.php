<?php
/**
 * Jewellery spotlight.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$od_has = od_product_loop( array_merge( od_cat_query( array( 'jewellery', 'jewelry', 'temple-jewellery' ) ), array( 'orderby' => 'date' ) ), 0, array( 'section' => 'jewel' ) );
$od_loop = ob_get_clean();

if ( ! $od_has ) {
	return;
}
?>
<section class="od-section od-reveal" style="background:var(--od-bg-alt)">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'Antique finish, temple craft', 'ojasvidrapes' ),
			__( 'The <em>Jewellery</em> Vault', 'ojasvidrapes' ),
			__( 'Nakshi haarams, jhumkas, vanki and maang tikka — the finishing touch to every drape.', 'ojasvidrapes' )
		);

		echo $od_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
