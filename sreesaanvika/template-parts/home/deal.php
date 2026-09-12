<?php
/**
 * Deal of the day — a countdown beside a single on-sale product.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$ss_ids = wc_get_product_ids_on_sale();

if ( ! $ss_ids ) {
	return;
}

ob_start();
$ss_has = ss_product_loop(
	array(
		'post__in'       => $ss_ids,
		'orderby'        => 'rand',
		'posts_per_page' => 4,
	),
	4
);
$ss_loop = ob_get_clean();

if ( ! $ss_has ) {
	return;
}
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<div class="ss-promo" style="min-height:auto;flex-direction:column;align-items:stretch;gap:32px">
			<div class="ss-between" style="flex-wrap:wrap;gap:24px">
				<div class="ss-section-head ss-section-head--left" style="margin:0;max-width:520px">
					<span class="ss-eyebrow"><?php esc_html_e( 'Ends soon', 'sreesaanvika' ); ?></span>
					<h2><?php echo ss_kses( __( 'Deal of the <em>Day</em>', 'sreesaanvika' ) ); ?></h2>
					<p><?php esc_html_e( 'Hand-picked pieces at their lowest price of the season. When the clock runs out, the price goes back up.', 'sreesaanvika' ); ?></p>
				</div>

				<?php ss_countdown( ss_option( 'deal_end', '' ) ); ?>
			</div>

			<?php echo $ss_loop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</section>
