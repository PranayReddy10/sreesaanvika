<?php
/**
 * Trust strip.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="ss-usp">
	<div class="ss-container">
		<div class="ss-usp__grid">
			<?php
			ss_usp_item( 'truck', __( 'Free shipping in India', 'sreesaanvika' ), __( 'On every order above ₹2,999', 'sreesaanvika' ) );
			ss_usp_item( 'shield', __( 'Certified handloom', 'sreesaanvika' ), __( 'Silk Mark & Handloom Mark', 'sreesaanvika' ) );
			ss_usp_item( 'refresh', __( '7-day easy returns', 'sreesaanvika' ), __( 'No questions, free pickup', 'sreesaanvika' ) );
			ss_usp_item( 'headset', __( 'Talk to a stylist', 'sreesaanvika' ), __( 'WhatsApp us, 10am – 7pm', 'sreesaanvika' ) );
			?>
		</div>
	</div>
</section>
