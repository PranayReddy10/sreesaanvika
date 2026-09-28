<?php
/**
 * Trust strip.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="od-usp">
	<div class="od-container">
		<div class="od-usp__grid">
			<?php
			od_usp_item( 'truck', __( 'Free shipping in India', 'ojasvidrapes' ), __( 'On every order above ₹2,999', 'ojasvidrapes' ) );
			od_usp_item( 'shield', __( 'Certified handloom', 'ojasvidrapes' ), __( 'Silk Mark & Handloom Mark', 'ojasvidrapes' ) );
			od_usp_item( 'refresh', __( '7-day easy returns', 'ojasvidrapes' ), __( 'No questions, free pickup', 'ojasvidrapes' ) );
			od_usp_item( 'headset', __( 'Talk to a stylist', 'ojasvidrapes' ), __( 'WhatsApp us, 10am – 7pm', 'ojasvidrapes' ) );
			?>
		</div>
	</div>
</section>
