<?php
/**
 * Template Name: Track Order
 *
 * Wraps WooCommerce's order-tracking form in the theme's styling, with the
 * support details beside it.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Enter your order number and the email you ordered with to see where your parcel is.', 'ojasvidrapes' ) );

$od_phone = od_option( 'footer_phone' );
$od_email = od_option( 'footer_email' );
$od_wa    = od_option( 'social_whatsapp' );
$od_hours = od_option( 'footer_hours' );
?>

<div class="od-container od-section">
	<div class="od-layout" style="--od-aside:340px">

		<div class="od-track">
			<?php
			while ( have_posts() ) :
				the_post();

				// Anything typed on the page shows above the form.
				if ( trim( get_the_content() ) ) {
					echo '<div class="od-entry" style="margin-bottom:20px">';
					the_content();
					echo '</div>';
				}
			endwhile;
			?>

			<div class="od-track__card">
				<div class="od-track__head">
					<?php od_the_icon( 'truck', 26 ); ?>
					<h2><?php esc_html_e( 'Where is my order?', 'ojasvidrapes' ); ?></h2>
				</div>

				<?php
				if ( class_exists( 'WooCommerce' ) ) {
					echo do_shortcode( '[woocommerce_order_tracking]' );
				} else {
					echo '<p style="color:var(--od-muted)">' . esc_html__( 'Order tracking needs WooCommerce to be active.', 'ojasvidrapes' ) . '</p>';
				}
				?>
			</div>

			<div class="od-grid od-grid--2" style="margin-top:16px">
				<div class="od-card" style="padding:22px 24px">
					<h3 style="font-size:1.05rem;margin-bottom:8px">
						<?php esc_html_e( 'Where is my order number?', 'ojasvidrapes' ); ?>
					</h3>
					<p style="color:var(--od-muted);margin:0;font-size:.9rem">
						<?php esc_html_e( 'At the top of your confirmation email, and on the order page in your account. It looks like #1234.', 'ojasvidrapes' ); ?>
					</p>
				</div>

				<div class="od-card" style="padding:22px 24px">
					<h3 style="font-size:1.05rem;margin-bottom:8px">
						<?php esc_html_e( 'How long does delivery take?', 'ojasvidrapes' ); ?>
					</h3>
					<p style="color:var(--od-muted);margin:0;font-size:.9rem">
						<?php esc_html_e( 'Metro cities in 2–4 business days, the rest of India in 4–7. You get a tracking link by SMS and email.', 'ojasvidrapes' ); ?>
					</p>
				</div>
			</div>
		</div>

		<aside class="od-sidebar">
			<div class="od-widget">
				<h3 class="od-widget__title"><?php esc_html_e( 'Need a hand?', 'ojasvidrapes' ); ?></h3>

				<ul class="od-contactlist">
					<?php if ( $od_phone ) : ?>
						<li>
							<?php od_the_icon( 'phone', 17 ); ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $od_phone ) ); ?>"><?php echo esc_html( $od_phone ); ?></a>
						</li>
					<?php endif; ?>

					<?php if ( $od_email ) : ?>
						<li>
							<?php od_the_icon( 'mail', 17 ); ?>
							<a href="mailto:<?php echo esc_attr( $od_email ); ?>"><?php echo esc_html( $od_email ); ?></a>
						</li>
					<?php endif; ?>

					<?php if ( $od_hours ) : ?>
						<li><?php od_the_icon( 'clock', 17 ); ?><span><?php echo esc_html( $od_hours ); ?></span></li>
					<?php endif; ?>
				</ul>

				<?php if ( $od_wa ) : ?>
					<a class="od-btn od-btn--block" style="margin-top:18px" href="<?php echo esc_url( $od_wa ); ?>" target="_blank" rel="noopener noreferrer">
						<?php od_the_icon( 'whatsapp', 17 ); ?>
						<?php esc_html_e( 'Chat on WhatsApp', 'ojasvidrapes' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<div class="od-widget">
					<h3 class="od-widget__title"><?php esc_html_e( 'All your orders', 'ojasvidrapes' ); ?></h3>
					<p style="color:var(--od-muted);font-size:.92rem">
						<?php esc_html_e( 'Sign in to see every order, download invoices and reorder in one tap.', 'ojasvidrapes' ); ?>
					</p>
					<a class="od-btn od-btn--ghost od-btn--block" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
						<?php esc_html_e( 'Go to my account', 'ojasvidrapes' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</aside>
	</div>
</div>

<?php
get_footer();
