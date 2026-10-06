<?php
/**
 * Mobile navigation drawer.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="od-drawer" id="od-drawer" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Menu', 'ojasvidrapes' ); ?>">

	<div class="od-drawer__head">
		<?php od_brand( 'sm' ); ?>
		<button type="button" class="od-icon-btn" data-close aria-label="<?php esc_attr_e( 'Close menu', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'close', 19 ); ?>
		</button>
	</div>

	<div class="od-drawer__body">
		<?php
		wp_nav_menu(
			array(
				'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
				'container'      => false,
				'menu_class'     => 'od-drawer__menu',
				'depth'          => 3,
				'walker'         => new OD_Drawer_Walker(),
				'fallback_cb'    => 'od_menu_fallback',
			)
		);
		?>
	</div>

	<div class="od-drawer__foot">
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<a class="od-btn od-btn--block" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">
				<?php od_the_icon( 'user', 17 ); ?>
				<?php echo is_user_logged_in() ? esc_html__( 'My account', 'ojasvidrapes' ) : esc_html__( 'Sign in / Sign up', 'ojasvidrapes' ); ?>
			</a>
		<?php else : ?>
			<a class="od-btn od-btn--block" href="<?php echo esc_url( od_page_url( 'auth' ) ); ?>">
				<?php esc_html_e( 'Sign in / Sign up', 'ojasvidrapes' ); ?>
			</a>
		<?php endif; ?>

		<?php
		$phone = od_option( 'footer_phone', '' );

		if ( $phone ) :
			?>
			<a class="od-btn od-btn--ghost od-btn--block" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>">
				<?php od_the_icon( 'headset', 17 ); ?>
				<?php echo esc_html( $phone ); ?>
			</a>
		<?php endif; ?>

		<div style="display:flex;gap:10px">
			<?php if ( od_option( 'wishlist_on', true ) ) : ?>
				<a class="od-btn od-btn--solid-dark od-btn--sm" style="flex:1" href="<?php echo esc_url( od_page_url( 'wishlist' ) ); ?>">
					<?php od_the_icon( 'heart', 15 ); ?>
					<?php esc_html_e( 'Wishlist', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>

			<?php if ( od_option( 'compare_on', true ) ) : ?>
				<a class="od-btn od-btn--solid-dark od-btn--sm" style="flex:1" href="<?php echo esc_url( od_page_url( 'compare' ) ); ?>">
					<?php od_the_icon( 'compare', 15 ); ?>
					<?php esc_html_e( 'Compare', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php od_socials( 'od-drawer__socials' ); ?>
	</div>
</div>
