<?php
/**
 * New arrivals.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$ss_has = ss_product_loop( array( 'orderby' => 'date', 'order' => 'DESC' ), 0, array( 'section' => 'new' ) );
$ss_loop = ob_get_clean();

if ( ! $ss_has ) {
	return;
}
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Fresh off the loom', 'sreesaanvika' ),
			__( 'New <em>Arrivals</em>', 'sreesaanvika' ),
			__( 'The newest weaves, added this week.', 'sreesaanvika' )
		);

		echo $ss_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<div class="ss-text-center" style="margin-top:36px">
			<a class="ss-btn ss-btn--ghost ss-btn--lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'View all new arrivals', 'sreesaanvika' ); ?>
				<?php ss_the_icon( 'arrow-right', 16 ); ?>
			</a>
		</div>
	</div>
</section>
