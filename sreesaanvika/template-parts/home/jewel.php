<?php
/**
 * Jewellery spotlight.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$ss_has = ss_product_loop( array_merge( ss_cat_query( array( 'jewellery', 'jewelry', 'temple-jewellery' ) ), array( 'orderby' => 'date' ) ) );
$ss_loop = ob_get_clean();

if ( ! $ss_has ) {
	return;
}
?>
<section class="ss-section ss-reveal" style="background:var(--ss-bg-alt)">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Antique finish, temple craft', 'sreesaanvika' ),
			__( 'The <em>Jewellery</em> Vault', 'sreesaanvika' ),
			__( 'Nakshi haarams, jhumkas, vanki and maang tikka — the finishing touch to every drape.', 'sreesaanvika' )
		);

		echo $ss_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>
</section>
