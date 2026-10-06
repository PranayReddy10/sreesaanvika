<?php
/**
 * Deal of the day — a countdown beside a single on-sale product.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$od_ids = wc_get_product_ids_on_sale();

if ( ! $od_ids ) {
	return;
}

ob_start();
$od_has = od_product_loop(
	array(
		'post__in'       => $od_ids,
		'orderby'        => 'rand',
		'posts_per_page' => 4,
	),
	4
);
$od_loop = ob_get_clean();

if ( ! $od_has ) {
	return;
}
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<div class="od-promo" style="min-height:auto;flex-direction:column;align-items:stretch;gap:32px">
			<div class="od-between" style="flex-wrap:wrap;gap:24px">
				<div class="od-section-head od-section-head--left" style="margin:0;max-width:520px">
					<span class="od-eyebrow"><?php esc_html_e( 'Ends soon', 'ojasvidrapes' ); ?></span>
					<h2><?php echo od_kses( __( 'Deal of the <em>Day</em>', 'ojasvidrapes' ) ); ?></h2>
					<p><?php esc_html_e( 'Hand-picked pieces at their lowest price of the season. When the clock runs out, the price goes back up.', 'ojasvidrapes' ); ?></p>
				</div>

				<?php od_countdown( od_option( 'deal_end', '' ) ); ?>
			</div>

			<?php echo $od_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</section>
