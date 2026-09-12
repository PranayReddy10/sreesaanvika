<?php
/**
 * Slide-in bag.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="ss-panel" id="ss-cart-panel" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Shopping bag', 'sreesaanvika' ); ?>">

	<div class="ss-panel__head">
		<h3><?php ss_the_icon( 'bag', 19 ); ?><?php esc_html_e( 'Your Bag', 'sreesaanvika' ); ?></h3>
		<button type="button" class="ss-icon-btn" data-close aria-label="<?php esc_attr_e( 'Close bag', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'close', 19 ); ?>
		</button>
	</div>

	<?php
	ss_minicart_body();
	ss_minicart_foot();
	?>
</aside>
