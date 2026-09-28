<?php
/**
 * Site footer.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_address = od_option( 'footer_address', '' );
$od_phone   = od_option( 'footer_phone', '' );
$od_email   = od_option( 'footer_email', '' );
$od_hours   = od_option( 'footer_hours', '' );
$od_copy    = od_option( 'footer_copy', '' );
?>
	</main><!-- .od-main -->

	<?php if ( is_active_sidebar( 'footer-extra' ) ) : ?>
		<div class="od-section od-section--tight">
			<div class="od-container od-grid od-grid--3">
				<?php dynamic_sidebar( 'footer-extra' ); ?>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( ! od_elementor_location( 'footer' ) ) : ?>

	<footer class="od-footer">
		<div class="od-container">

			<div class="od-footer__top">

				<div class="od-footer__col od-footer__col--about od-footer__about">
					<?php od_brand(); ?>
					<p><?php echo esc_html( od_option( 'footer_about', '' ) ); ?></p>
					<?php od_socials(); ?>
				</div>

				<div class="od-footer__col">
					<h4><?php esc_html_e( 'Shop', 'ojasvidrapes' ); ?></h4>
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
							$od_terms = get_terms(
								array(
									'taxonomy'   => 'product_cat',
									'hide_empty' => false,
									'number'     => 6,
									'parent'     => 0,
								)
							);

							if ( $od_terms && ! is_wp_error( $od_terms ) ) {
								foreach ( $od_terms as $od_term ) {
									echo '<li><a href="' . esc_url( get_term_link( $od_term ) ) . '">' . esc_html( $od_term->name ) . '</a></li>';
								}
							}
						}

						echo '</ul>';
					}
					?>
				</div>

				<div class="od-footer__col">
					<h4><?php esc_html_e( 'Help', 'ojasvidrapes' ); ?></h4>
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
							<li><a href="<?php echo esc_url( od_page_url( 'faq' ) ); ?>"><?php esc_html_e( 'FAQs', 'ojasvidrapes' ); ?></a></li>
							<li><a href="<?php echo esc_url( od_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact us', 'ojasvidrapes' ); ?></a></li>
							<li><a href="<?php echo esc_url( od_page_url( 'track' ) ); ?>"><?php esc_html_e( 'Track my order', 'ojasvidrapes' ); ?></a></li>
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
								<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'My bag', 'ojasvidrapes' ); ?></a></li>
							<?php endif; ?>
							<li><a href="<?php echo esc_url( od_page_url( 'wishlist' ) ); ?>"><?php esc_html_e( 'Wishlist', 'ojasvidrapes' ); ?></a></li>
						</ul>
						<?php
					}
					?>
				</div>

				<div class="od-footer__col">
					<h4><?php esc_html_e( 'The House', 'ojasvidrapes' ); ?></h4>
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
							<li><a href="<?php echo esc_url( od_page_url( 'about' ) ); ?>"><?php esc_html_e( 'Our story', 'ojasvidrapes' ); ?></a></li>
							<li><a href="<?php echo esc_url( od_page_url( 'lookbook' ) ); ?>"><?php esc_html_e( 'Lookbook', 'ojasvidrapes' ); ?></a></li>
							<li><a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>"><?php esc_html_e( 'Journal', 'ojasvidrapes' ); ?></a></li>
							<li><a href="<?php echo esc_url( od_page_url( 'privacy-policy' ) ); ?>"><?php esc_html_e( 'Privacy policy', 'ojasvidrapes' ); ?></a></li>
						</ul>
						<?php
					}
					?>
				</div>

				<div class="od-footer__col od-footer__col--contact">
					<h4><?php esc_html_e( 'Reach us', 'ojasvidrapes' ); ?></h4>
					<ul class="od-contactlist">
						<?php if ( $od_address ) : ?>
							<li><?php od_the_icon( 'pin', 17 ); ?><span><?php echo nl2br( esc_html( $od_address ) ); ?></span></li>
						<?php endif; ?>

						<?php if ( $od_phone ) : ?>
							<li><?php od_the_icon( 'phone', 17 ); ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $od_phone ) ); ?>"><?php echo esc_html( $od_phone ); ?></a></li>
						<?php endif; ?>

						<?php if ( $od_email ) : ?>
							<li><?php od_the_icon( 'mail', 17 ); ?><a href="mailto:<?php echo esc_attr( $od_email ); ?>"><?php echo esc_html( $od_email ); ?></a></li>
						<?php endif; ?>

						<?php if ( $od_hours ) : ?>
							<li><?php od_the_icon( 'clock', 17 ); ?><span><?php echo esc_html( $od_hours ); ?></span></li>
						<?php endif; ?>
					</ul>
				</div>

			</div>

			<nav class="od-footer__legal" aria-label="<?php esc_attr_e( 'Policies', 'ojasvidrapes' ); ?>">
				<?php foreach ( od_legal_pages() as $od_slug => $od_legal ) : ?>
					<a href="<?php echo esc_url( od_page_url( $od_slug ) ); ?>"><?php echo esc_html( $od_legal['title'] ); ?></a>
				<?php endforeach; ?>
				<a href="<?php echo esc_url( od_page_url( 'track' ) ); ?>"><?php esc_html_e( 'Track Order', 'ojasvidrapes' ); ?></a>
				<a href="<?php echo esc_url( od_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'ojasvidrapes' ); ?></a>
			</nav>

			<div class="od-footer__bottom">
				<p style="margin:0">
					<?php
					if ( $od_copy ) {
						echo esc_html( $od_copy );
					} else {
						printf(
							/* translators: 1: year, 2: site name */
							esc_html__( '© %1$s %2$s. Handloom, handpicked, honestly priced.', 'ojasvidrapes' ),
							esc_html( gmdate( 'Y' ) ),
							esc_html( get_bloginfo( 'name' ) )
						);
					}
					?>
				</p>

				<div class="od-payments" aria-label="<?php esc_attr_e( 'Payment methods we accept', 'ojasvidrapes' ); ?>">
					<?php foreach ( array( 'UPI', 'Visa', 'Mastercard', 'RuPay', 'Net Banking', 'COD', 'EMI' ) as $od_pay ) : ?>
						<span><?php echo esc_html( $od_pay ); ?></span>
					<?php endforeach; ?>
				</div>
			</div>

		</div>
	</footer>

	<?php endif; // Elementor footer. ?>

	<button type="button" class="od-icon-btn od-to-top" aria-label="<?php esc_attr_e( 'Back to top', 'ojasvidrapes' ); ?>">
		<?php od_the_icon( 'arrow-up', 19 ); ?>
	</button>

	<?php if ( od_option( 'compare_on', true ) && class_exists( 'WooCommerce' ) ) : ?>
		<div class="od-comparebar" aria-live="polite">
			<div class="od-comparebar__thumbs"></div>
			<span class="od-comparebar__label">
				<strong>0</strong> <?php esc_html_e( 'selected to compare', 'ojasvidrapes' ); ?>
			</span>
			<a class="od-btn od-btn--sm" href="<?php echo esc_url( od_page_url( 'compare' ) ); ?>">
				<?php esc_html_e( 'Compare', 'ojasvidrapes' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php if ( od_option( 'quickview', true ) && class_exists( 'WooCommerce' ) ) : ?>
		<div class="od-modal od-modal--quickview" aria-hidden="true" role="dialog" aria-modal="true"
			aria-label="<?php esc_attr_e( 'Quick view', 'ojasvidrapes' ); ?>">
			<div class="od-modal__box">
				<button type="button" class="od-icon-btn od-modal__close" aria-label="<?php esc_attr_e( 'Close', 'ojasvidrapes' ); ?>">
					<?php od_the_icon( 'close', 19 ); ?>
				</button>
				<div class="od-modal__content"></div>
			</div>
		</div>
	<?php endif; ?>

</div><!-- .od-site -->

<?php wp_footer(); ?>
</body>
</html>
