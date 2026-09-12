<?php
/**
 * Newsletter sign-up.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="ss-section ss-newsletter ss-reveal">
	<div class="ss-container ss-container--narrow">
		<div class="ss-ornament" aria-hidden="true" style="margin-bottom:16px"><?php ss_the_icon( 'lotus', 22 ); ?></div>

		<h2><?php echo ss_kses( __( 'Be first to the <em>New Drop</em>', 'sreesaanvika' ) ); ?></h2>
		<p style="color:var(--ss-muted);max-width:520px;margin:0 auto">
			<?php esc_html_e( 'Weave stories, early access to festive collections and a ₹500 voucher on your first order.', 'sreesaanvika' ); ?>
		</p>

		<form class="ss-newsletter__form" data-newsletter>
			<label class="screen-reader-text" for="ss-news-email"><?php esc_html_e( 'Email address', 'sreesaanvika' ); ?></label>
			<input type="email" id="ss-news-email" name="email" required
				placeholder="<?php esc_attr_e( 'you@example.com', 'sreesaanvika' ); ?>" autocomplete="email" />
			<button type="submit" class="ss-btn"><?php esc_html_e( 'Subscribe', 'sreesaanvika' ); ?></button>
		</form>

		<p class="ss-newsletter__note"><?php esc_html_e( 'No spam. Unsubscribe any time.', 'sreesaanvika' ); ?></p>
	</div>
</section>
