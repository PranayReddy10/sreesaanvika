<?php
/**
 * Template Name: Contact
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Questions about a weave, a size or an order? We answer fast.', 'ojasvidrapes' ) );

$od_phone = od_option( 'footer_phone', '' );
$od_email = od_option( 'footer_email', '' );
$od_addr  = od_option( 'footer_address', '' );
$od_hours = od_option( 'footer_hours', '' );
$od_wa    = od_option( 'social_whatsapp', '' );
?>

<div class="od-container od-section">
	<div class="od-layout" style="--od-aside:380px">

		<div>
			<?php
			while ( have_posts() ) :
				the_post();

				echo '<div class="od-entry">';

				if ( trim( get_the_content() ) ) {
					the_content();
				} else {
					// A usable default form when the page has no content yet.
					?>
					<h2 style="margin-top:0"><?php esc_html_e( 'Send us a message', 'ojasvidrapes' ); ?></h2>
					<p style="color:var(--od-muted)">
						<?php esc_html_e( 'Install a form plugin such as Contact Form 7 or WPForms and drop its shortcode into this page to replace this placeholder.', 'ojasvidrapes' ); ?>
					</p>

					<form method="post" action="<?php echo esc_url( $od_email ? 'mailto:' . $od_email : '' ); ?>">
						<div class="od-grid od-grid--2" style="gap:0 14px">
							<div class="od-field">
								<label for="od-c-name"><?php esc_html_e( 'Your name', 'ojasvidrapes' ); ?></label>
								<input type="text" id="od-c-name" name="name" required />
							</div>
							<div class="od-field">
								<label for="od-c-email"><?php esc_html_e( 'Email', 'ojasvidrapes' ); ?></label>
								<input type="email" id="od-c-email" name="email" required />
							</div>
						</div>

						<div class="od-field">
							<label for="od-c-subject"><?php esc_html_e( 'Subject', 'ojasvidrapes' ); ?></label>
							<input type="text" id="od-c-subject" name="subject" />
						</div>

						<div class="od-field">
							<label for="od-c-msg"><?php esc_html_e( 'Message', 'ojasvidrapes' ); ?></label>
							<textarea id="od-c-msg" name="message" required></textarea>
						</div>

						<button type="submit" class="od-btn od-btn--lg"><?php esc_html_e( 'Send message', 'ojasvidrapes' ); ?></button>
					</form>
					<?php
				}

				echo '</div>';
			endwhile;
			?>
		</div>

		<aside class="od-sidebar">
			<div class="od-widget">
				<h3 class="od-widget__title"><?php esc_html_e( 'Reach us directly', 'ojasvidrapes' ); ?></h3>

				<ul class="od-contactlist">
					<?php if ( $od_phone ) : ?>
						<li><?php od_the_icon( 'phone', 17 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $od_phone ) ); ?>"><?php echo esc_html( $od_phone ); ?></a></li>
					<?php endif; ?>

					<?php if ( $od_email ) : ?>
						<li><?php od_the_icon( 'mail', 17 ); ?><a href="mailto:<?php echo esc_attr( $od_email ); ?>"><?php echo esc_html( $od_email ); ?></a></li>
					<?php endif; ?>

					<?php if ( $od_addr ) : ?>
						<li><?php od_the_icon( 'pin', 17 ); ?><span><?php echo nl2br( esc_html( $od_addr ) ); ?></span></li>
					<?php endif; ?>

					<?php if ( $od_hours ) : ?>
						<li><?php od_the_icon( 'clock', 17 ); ?><span><?php echo esc_html( $od_hours ); ?></span></li>
					<?php endif; ?>
				</ul>

				<?php if ( $od_wa ) : ?>
					<a class="od-btn od-btn--block" style="margin-top:20px" href="<?php echo esc_url( $od_wa ); ?>" target="_blank" rel="noopener noreferrer">
						<?php od_the_icon( 'whatsapp', 17 ); ?>
						<?php esc_html_e( 'Chat on WhatsApp', 'ojasvidrapes' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="od-widget">
				<h3 class="od-widget__title"><?php esc_html_e( 'Common questions', 'ojasvidrapes' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( od_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'Where is my order?', 'ojasvidrapes' ); ?></a></li>
					<li><a href="<?php echo esc_url( od_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'How do returns work?', 'ojasvidrapes' ); ?></a></li>
					<li><a href="<?php echo esc_url( od_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'Do you ship internationally?', 'ojasvidrapes' ); ?></a></li>
					<li><a href="<?php echo esc_url( od_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'Is the zari real?', 'ojasvidrapes' ); ?></a></li>
				</ul>
			</div>
		</aside>
	</div>
</div>

<?php
get_footer();
