<?php
/**
 * Template Name: Track Order
 *
 * Wraps WooCommerce's order-tracking form in the theme's styling, with the
 * support details beside it.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Enter your order number and the email you ordered with to see where your parcel is.', 'sreesaanvika' ) );

$ss_phone = ss_option( 'footer_phone' );
$ss_email = ss_option( 'footer_email' );
$ss_wa    = ss_option( 'social_whatsapp' );
$ss_hours = ss_option( 'footer_hours' );
?>

<div class="ss-container ss-section">
	<div class="ss-layout" style="--ss-aside:340px">

		<div class="ss-track">
			<?php
			while ( have_posts() ) :
				the_post();

				// Anything typed on the page shows above the form.
				if ( trim( get_the_content() ) ) {
					echo '<div class="ss-entry" style="margin-bottom:20px">';
					the_content();
					echo '</div>';
				}
			endwhile;
			?>

			<div class="ss-track__card">
				<div class="ss-track__head">
					<?php ss_the_icon( 'truck', 26 ); ?>
					<h2><?php esc_html_e( 'Where is my order?', 'sreesaanvika' ); ?></h2>
				</div>

				<?php
				if ( class_exists( 'WooCommerce' ) ) {
					echo do_shortcode( '[woocommerce_order_tracking]' );
				} else {
					echo '<p style="color:var(--ss-muted)">' . esc_html__( 'Order tracking needs WooCommerce to be active.', 'sreesaanvika' ) . '</p>';
				}
				?>
			</div>

			<div class="ss-grid ss-grid--2" style="margin-top:16px">
				<div class="ss-card" style="padding:22px 24px">
					<h3 style="font-size:1.05rem;margin-bottom:8px">
						<?php esc_html_e( 'Where is my order number?', 'sreesaanvika' ); ?>
					</h3>
					<p style="color:var(--ss-muted);margin:0;font-size:.9rem">
						<?php esc_html_e( 'At the top of your confirmation email, and on the order page in your account. It looks like #1234.', 'sreesaanvika' ); ?>
					</p>
				</div>

				<div class="ss-card" style="padding:22px 24px">
					<h3 style="font-size:1.05rem;margin-bottom:8px">
						<?php esc_html_e( 'How long does delivery take?', 'sreesaanvika' ); ?>
					</h3>
					<p style="color:var(--ss-muted);margin:0;font-size:.9rem">
						<?php esc_html_e( 'Metro cities in 2–4 business days, the rest of India in 4–7. You get a tracking link by SMS and email.', 'sreesaanvika' ); ?>
					</p>
				</div>
			</div>
		</div>

		<aside class="ss-sidebar">
			<div class="ss-widget">
				<h3 class="ss-widget__title"><?php esc_html_e( 'Need a hand?', 'sreesaanvika' ); ?></h3>

				<ul class="ss-contactlist">
					<?php if ( $ss_phone ) : ?>
						<li>
							<?php ss_the_icon( 'phone', 17 ); ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $ss_phone ) ); ?>"><?php echo esc_html( $ss_phone ); ?></a>
						</li>
					<?php endif; ?>

					<?php if ( $ss_email ) : ?>
						<li>
							<?php ss_the_icon( 'mail', 17 ); ?>
							<a href="mailto:<?php echo esc_attr( $ss_email ); ?>"><?php echo esc_html( $ss_email ); ?></a>
						</li>
					<?php endif; ?>

					<?php if ( $ss_hours ) : ?>
						<li><?php ss_the_icon( 'clock', 17 ); ?><span><?php echo esc_html( $ss_hours ); ?></span></li>
					<?php endif; ?>
				</ul>

				<?php if ( $ss_wa ) : ?>
					<a class="ss-btn ss-btn--block" style="margin-top:18px" href="<?php echo esc_url( $ss_wa ); ?>" target="_blank" rel="noopener noreferrer">
						<?php ss_the_icon( 'whatsapp', 17 ); ?>
						<?php esc_html_e( 'Chat on WhatsApp', 'sreesaanvika' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<div class="ss-widget">
					<h3 class="ss-widget__title"><?php esc_html_e( 'All your orders', 'sreesaanvika' ); ?></h3>
					<p style="color:var(--ss-muted);font-size:.92rem">
						<?php esc_html_e( 'Sign in to see every order, download invoices and reorder in one tap.', 'sreesaanvika' ); ?>
					</p>
					<a class="ss-btn ss-btn--ghost ss-btn--block" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
						<?php esc_html_e( 'Go to my account', 'sreesaanvika' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</div>

<?php
get_footer();
