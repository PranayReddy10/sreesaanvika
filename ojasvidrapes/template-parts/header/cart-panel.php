<?php
/**
 * Slide-in bag.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;
?>
<aside class="od-panel" id="od-cart-panel" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Shopping bag', 'ojasvidrapes' ); ?>">

	<div class="od-panel__head">
		<h3><?php od_the_icon( 'bag', 19 ); ?><?php esc_html_e( 'Your Bag', 'ojasvidrapes' ); ?></h3>
		<button type="button" class="od-icon-btn" data-close aria-label="<?php esc_attr_e( 'Close bag', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'close', 19 ); ?>
		</button>
	</div>

	<?php
	od_minicart_body();
	od_minicart_foot();
	?>
</aside>
