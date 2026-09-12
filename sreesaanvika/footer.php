<?php
/**
 * Site footer.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_address = ss_option( 'footer_address', '' );
$ss_phone   = ss_option( 'footer_phone', '' );
$ss_email   = ss_option( 'footer_email', '' );
$ss_hours   = ss_option( 'footer_hours', '' );
$ss_copy    = ss_option( 'footer_copy', '' );
?>
	</main><!-- .ss-main -->

	<?php if ( is_active_sidebar( 'footer-extra' ) ) : ?>
		<div class="ss-section ss-section--tight">
			<div class="ss-container ss-grid ss-grid--3">
				<?php dynamic_sidebar( 'footer-extra' ); ?>
			</div>
		</div>
	<?php endif; ?>

	<footer class="ss-footer">
		<div class="ss-container">

			<div class="ss-footer__top">

				<div class="ss-footer__col ss-footer__col--about ss-footer__about">
					<?php ss_brand(); ?>
					<p><?php echo esc_html( ss_option( 'footer_about', '' ) ); ?></p>
					<?php ss_socials(); ?>
				</div>

				<div class="ss-footer__col">
					<h4><?php esc_html_e( 'Shop', 'sreesaanvika' ); ?></h4>
					<?php
					if ( has_nav_menu( 'footer1' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer1',
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					} else {
						echo '<ul>';

						if ( class_exists( 'WooCommerce' ) ) {
							$ss_terms = get_terms(
								array(
									'taxonomy'   => 'product_cat',
									'hide_empty' => false,
									'number'     => 6,
									'parent'     => 0,
								)
							);

							if ( $ss_terms && ! is_wp_error( $ss_terms ) ) {
								foreach ( $ss_terms as $ss_term ) {
									echo '<li><a href="' . esc_url( get_term_link( $ss_term ) ) . '">' . esc_html( $ss_term->name ) . '</a></li>';
								}
							}
						}

						echo '</ul>';
					}
					?>
				</div>

				<div class="ss-footer__col">
					<h4><?php esc_html_e( 'Help', 'sreesaanvika' ); ?></h4>
					<?php
					if ( has_nav_menu( 'footer2' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer2',
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					} else {
						?>
						<ul>
							<li><a href="<?php echo esc_url( ss_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'FAQs', 'sreesaanvika' ); ?></a></li>
							<li><a href="<?php echo esc_url( ss_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact us', 'sreesaanvika' ); ?></a></li>
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
								<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Track my order', 'sreesaanvika' ); ?></a></li>
								<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'My bag', 'sreesaanvika' ); ?></a></li>
							<?php endif; ?>
							<li><a href="<?php echo esc_url( ss_page_url( 'wishlist' ) ); ?>"><?php esc_html_e( 'Wishlist', 'sreesaanvika' ); ?></a></li>
						</ul>
						<?php
					}
					?>
				</div>

				<div class="ss-footer__col">
					<h4><?php esc_html_e( 'The House', 'sreesaanvika' ); ?></h4>
					<?php
					if ( has_nav_menu( 'footer3' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'footer3',
								'container'      => false,
								'depth'          => 1,
								'fallback_cb'    => false,
							)
						);
					} else {
						?>
						<ul>
							<li><a href="<?php echo esc_url( ss_page_url( 'about' ) ); ?>"><?php esc_html_e( 'Our story', 'sreesaanvika' ); ?></a></li>
							<li><a href="<?php echo esc_url( ss_page_url( 'lookbook' ) ); ?>"><?php esc_html_e( 'Lookbook', 'sreesaanvika' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Journal', 'sreesaanvika' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'sreesaanvika' ); ?></a></li>
						</ul>
						<?php
					}
					?>
				</div>

				<div class="ss-footer__col ss-footer__col--contact">
					<h4><?php esc_html_e( 'Reach us', 'sreesaanvika' ); ?></h4>
					<ul class="ss-contactlist">
						<?php if ( $ss_address ) : ?>
							<li><?php ss_the_icon( 'pin', 17 ); ?><span><?php echo nl2br( esc_html( $ss_address ) ); ?></span></li>
						<?php endif; ?>

						<?php if ( $ss_phone ) : ?>
							<li><?php ss_the_icon( 'phone', 17 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $ss_phone ) ); ?>"><?php echo esc_html( $ss_phone ); ?></a></li>
						<?php endif; ?>

						<?php if ( $ss_email ) : ?>
							<li><?php ss_the_icon( 'mail', 17 ); ?><a href="mailto:<?php echo esc_attr( $ss_email ); ?>"><?php echo esc_html( $ss_email ); ?></a></li>
						<?php endif; ?>

						<?php if ( $ss_hours ) : ?>
							<li><?php ss_the_icon( 'clock', 17 ); ?><span><?php echo esc_html( $ss_hours ); ?></span></li>
						<?php endif; ?>
					</ul>
				</div>

			</div>

			<div class="ss-footer__bottom">
				<p style="margin:0">
					<?php
					if ( $ss_copy ) {
						echo esc_html( $ss_copy );
					} else {
						printf(
							/* translators: 1: year, 2: site name */
							esc_html__( '© %1$s %2$s. Handloom, handpicked, honestly priced.', 'sreesaanvika' ),
							esc_html( gmdate( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
					?>
				</p>

				<div class="ss-payments" aria-label="<?php esc_attr_e( 'Payment methods we accept', 'sreesaanvika' ); ?>">
					<?php foreach ( array( 'UPI', 'Visa', 'Mastercard', 'RuPay', 'Net Banking', 'COD', 'EMI' ) as $ss_pay ) : ?>
						<span><?php echo esc_html( $ss_pay ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
	</footer>

	<button type="button" class="ss-icon-btn ss-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'sreesaanvika' ); ?>">
		<?php ss_the_icon( 'arrow-up', 19 ); ?>
	</button>

	<?php if ( ss_option( 'compare_on', true ) && class_exists( 'WooCommerce' ) ) : ?>
		<div class="ss-comparebar" aria-live="polite">
			<div class="ss-comparebar__thumbs"></div>
			<span class="ss-comparebar__label">
				<strong>0</strong> <?php esc_html_e( 'selected to compare', 'sreesaanvika' ); ?>
			</span>
			<a class="ss-btn ss-btn--sm" href="<?php echo esc_url( ss_page_url( 'compare' ) ); ?>">
				<?php esc_html_e( 'Compare', 'sreesaanvika' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( ss_option( 'quickview', true ) && class_exists( 'WooCommerce' ) ) : ?>
		<div class="ss-modal ss-modal--quickview" aria-hidden="true" role="dialog" aria-modal="true"
			aria-label="<?php esc_attr_e( 'Quick view', 'sreesaanvika' ); ?>">
			<div class="ss-modal__box">
				<button type="button" class="ss-icon-btn ss-modal__close" aria-label="<?php esc_attr_e( 'Close', 'sreesaanvika' ); ?>">
					<?php ss_the_icon( 'close', 19 ); ?>
				</button>
				<div class="ss-modal__content"></div>
			</div>
		</div>
	<?php endif; ?>

</div><!-- .ss-site -->

<?php wp_footer(); ?>
</body>
</html>
