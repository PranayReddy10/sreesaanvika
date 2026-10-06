<?php
/**
 * Newsletter sign-up.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="od-section od-newsletter od-reveal">
	<div class="od-container od-container--narrow">
		<div class="od-ornament" aria-hidden="true" style="margin-bottom:16px"><?php od_the_icon( 'lotus', 22 ); ?></div>

		<h2><?php echo od_kses( __( 'Be first to the <em>New Drop</em>', 'ojasvidrapes' ) ); ?></h2>
		<p style="color:var(--od-muted);max-width:520px;margin:0 auto">
			<?php esc_html_e( 'Weave stories, early access to festive collections and a ₹500 voucher on your first order.', 'ojasvidrapes' ); ?>
		</p>

		<form class="od-newsletter__form" data-newsletter>
			<label class="screen-reader-text" for="od-news-email"><?php esc_html_e( 'Email address', 'ojasvidrapes' ); ?></label>
			<input type="email" id="od-news-email" name="email" required
				placeholder="<?php esc_attr_e( 'you@example.com', 'ojasvidrapes' ); ?>" autocomplete="email" />
			<button type="submit" class="od-btn"><?php esc_html_e( 'Subscribe', 'ojasvidrapes' ); ?></button>
		</form>

		<p class="od-newsletter__note"><?php esc_html_e( 'No spam. Unsubscribe any time.', 'ojasvidrapes' ); ?></p>
	</div>
</section>
