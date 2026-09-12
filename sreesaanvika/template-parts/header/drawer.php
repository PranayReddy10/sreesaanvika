<?php
/**
 * Mobile navigation drawer.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="ss-drawer" id="ss-drawer" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Menu', 'sreesaanvika' ); ?>">

	<div class="ss-drawer__head">
		<?php ss_brand( 'sm' ); ?>
		<button type="button" class="ss-icon-btn" data-close aria-label="<?php esc_attr_e( 'Close menu', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'close', 19 ); ?>
		</button>
	</div>

	<div class="ss-drawer__body">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
				'container'      => false,
				'menu_class'     => 'ss-drawer__menu',
				'depth'          => 3,
				'walker'         => new SS_Drawer_Walker(),
				'fallback_cb'    => 'ss_menu_fallback',
			)
		);
		?>
	</div>

	<div class="ss-drawer__foot">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<a class="ss-btn ss-btn--block" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				<?php ss_the_icon( 'user', 17 ); ?>
				<?php echo is_user_logged_in() ? esc_html__( 'My account', 'sreesaanvika' ) : esc_html__( 'Sign in / Sign up', 'sreesaanvika' ); ?>
			</a>
		<?php else : ?>
			<a class="ss-btn ss-btn--block" href="<?php echo esc_url( ss_page_url( 'auth' ) ); ?>">
				<?php esc_html_e( 'Sign in / Sign up', 'sreesaanvika' ); ?>
			</a>
		<?php endif; ?>

		<?php
		$phone = ss_option( 'footer_phone', '' );

		if ( $phone ) :
			?>
			<a class="ss-btn ss-btn--ghost ss-btn--block" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
				<?php ss_the_icon( 'headset', 17 ); ?>
				<?php echo esc_html( $phone ); ?>
			</a>
		<?php endif; ?>

		<div style="display:flex;gap:10px">
			<?php if ( ss_option( 'wishlist_on', true ) ) : ?>
				<a class="ss-btn ss-btn--solid-dark ss-btn--sm" style="flex:1" href="<?php echo esc_url( ss_page_url( 'wishlist' ) ); ?>">
					<?php ss_the_icon( 'heart', 15 ); ?>
					<?php esc_html_e( 'Wishlist', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( ss_option( 'compare_on', true ) ) : ?>
				<a class="ss-btn ss-btn--solid-dark ss-btn--sm" style="flex:1" href="<?php echo esc_url( ss_page_url( 'compare' ) ); ?>">
					<?php ss_the_icon( 'compare', 15 ); ?>
					<?php esc_html_e( 'Compare', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php ss_socials( 'ss-drawer__socials' ); ?>
	</div>
</div>
