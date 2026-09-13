<?php
/**
 * Template Name: Contact
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Questions about a weave, a size or an order? We answer fast.', 'sreesaanvika' ) );

$ss_phone = ss_option( 'footer_phone', '' );
$ss_email = ss_option( 'footer_email', '' );
$ss_addr  = ss_option( 'footer_address', '' );
$ss_hours = ss_option( 'footer_hours', '' );
$ss_wa    = ss_option( 'social_whatsapp', '' );
?>

<div class="ss-container ss-section">
	<div class="ss-layout" style="--ss-aside:380px">

		<div>
			<?php
			while ( have_posts() ) :
				the_post();

				echo '<div class="ss-entry">';

				if ( trim( get_the_content() ) ) {
					the_content();
				} else {
					// A usable default form when the page has no content yet.
					?>
					<h2 style="margin-top:0"><?php esc_html_e( 'Send us a message', 'sreesaanvika' ); ?></h2>
					<p style="color:var(--ss-muted)">
						<?php esc_html_e( 'Install a form plugin such as Contact Form 7 or WPForms and drop its shortcode into this page to replace this placeholder.', 'sreesaanvika' ); ?>
					</p>

					<form method="post" action="<?php echo esc_url( $ss_email ? 'mailto:' . $ss_email : '' ); ?>">
						<div class="ss-grid ss-grid--2" style="gap:0 14px">
							<div class="ss-field">
								<label for="ss-c-name"><?php esc_html_e( 'Your name', 'sreesaanvika' ); ?></label>
								<input type="text" id="ss-c-name" name="name" required />
							</div>
							<div class="ss-field">
								<label for="ss-c-email"><?php esc_html_e( 'Email', 'sreesaanvika' ); ?></label>
								<input type="email" id="ss-c-email" name="email" required />
							</div>
						</div>

						<div class="ss-field">
							<label for="ss-c-subject"><?php esc_html_e( 'Subject', 'sreesaanvika' ); ?></label>
							<input type="text" id="ss-c-subject" name="subject" />
						</div>

						<div class="ss-field">
							<label for="ss-c-msg"><?php esc_html_e( 'Message', 'sreesaanvika' ); ?></label>
							<textarea id="ss-c-msg" name="message" required></textarea>
						</div>

						<button type="submit" class="ss-btn ss-btn--lg"><?php esc_html_e( 'Send message', 'sreesaanvika' ); ?></button>
					</form>
					<?php
				}

				echo '</div>';
			endwhile;
			?>
		</div>

		<aside class="ss-sidebar">
			<div class="ss-widget">
				<h3 class="ss-widget__title"><?php esc_html_e( 'Reach us directly', 'sreesaanvika' ); ?></h3>

				<ul class="ss-contactlist">
					<?php if ( $ss_phone ) : ?>
						<li><?php ss_the_icon( 'phone', 17 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $ss_phone ) ); ?>"><?php echo esc_html( $ss_phone ); ?></a></li>
					<?php endif; ?>

					<?php if ( $ss_email ) : ?>
						<li><?php ss_the_icon( 'mail', 17 ); ?><a href="mailto:<?php echo esc_attr( $ss_email ); ?>"><?php echo esc_html( $ss_email ); ?></a></li>
					<?php endif; ?>

					<?php if ( $ss_addr ) : ?>
						<li><?php ss_the_icon( 'pin', 17 ); ?><span><?php echo nl2br( esc_html( $ss_addr ) ); ?></span></li>
					<?php endif; ?>

					<?php if ( $ss_hours ) : ?>
						<li><?php ss_the_icon( 'clock', 17 ); ?><span><?php echo esc_html( $ss_hours ); ?></span></li>
					<?php endif; ?>
				</ul>

				<?php if ( $ss_wa ) : ?>
					<a class="ss-btn ss-btn--block" style="margin-top:20px" href="<?php echo esc_url( $ss_wa ); ?>" target="_blank" rel="noopener noreferrer">
						<?php ss_the_icon( 'whatsapp', 17 ); ?>
						<?php esc_html_e( 'Chat on WhatsApp', 'sreesaanvika' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div class="ss-widget">
				<h3 class="ss-widget__title"><?php esc_html_e( 'Common questions', 'sreesaanvika' ); ?></h3>
				<ul>
					<li><a href="<?php echo esc_url( ss_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'Where is my order?', 'sreesaanvika' ); ?></a></li>
					<li><a href="<?php echo esc_url( ss_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'How do returns work?', 'sreesaanvika' ); ?></a></li>
					<li><a href="<?php echo esc_url( ss_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'Do you ship internationally?', 'sreesaanvika' ); ?></a></li>
					<li><a href="<?php echo esc_url( ss_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'Is the zari real?', 'sreesaanvika' ); ?></a></li>
				</ul>
			</div>
		</aside>
	</div>
</div>

<?php
get_footer();
