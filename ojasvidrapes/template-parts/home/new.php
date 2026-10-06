<?php
/**
 * New arrivals.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

ob_start();
$od_has = od_product_loop( array( 'orderby' => 'date', 'order' => 'DESC' ), 0, array( 'section' => 'new' ) );
$od_loop = ob_get_clean();

if ( ! $od_has ) {
	return;
}
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'Fresh off the loom', 'ojasvidrapes' ),
			__( 'New <em>Arrivals</em>', 'ojasvidrapes' ),
			__( 'The newest weaves, added this week.', 'ojasvidrapes' )
		);

		echo $od_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>

		<div class="od-text-center" style="margin-top:36px">
			<a class="od-btn od-btn--ghost od-btn--lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'View all new arrivals', 'ojasvidrapes' ); ?>
				<?php od_the_icon( 'arrow-right', 16 ); ?>
			</a>
		</div>
	</div>
</section>
