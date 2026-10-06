<?php
/**
 * Site header.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<meta name="theme-color" content="<?php echo esc_attr( od_option( 'color_bg', '#140a12' ) ); ?>" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="od-site" id="page">

	<a class="skip-link screen-reader-text od-skip-link" href="#od-content"><?php esc_html_e( 'Skip to content', 'ojasvidrapes' ); ?></a>

	<?php
	/*
	 * Elementor Pro's Theme Builder can supply its own header. When it does,
	 * elementor_theme_do_location() prints it and the theme's header — along
	 * with the drawer, search overlay and cart panel it drives — is skipped.
	 */
	if ( ! od_elementor_location( 'header' ) ) :
		?>

	<?php if ( od_option( 'topbar_on' ) ) : ?>
		<?php
		$messages = od_list( od_option( 'topbar_items', '' ) );
		$phone    = od_option( 'topbar_phone', '' );
		?>
		<div class="od-topbar">
			<div class="od-container">
				<?php if ( $messages ) : ?>
					<div class="od-ticker" aria-hidden="true">
						<div class="od-ticker__track">
							<?php foreach ( $messages as $message ) : ?>
								<span class="od-ticker__item"><?php od_the_icon( 'sparkle', 14 ); ?><?php echo esc_html( $message ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="od-topbar__links">
					<?php if ( $phone ) : ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
						<span aria-hidden="true">|</span>
					<?php endif; ?>

					<?php
					if ( has_nav_menu( 'topbar' ) ) {
						wp_nav_menu(
							array(
								'theme_location' => 'topbar',
								'container'      => false,
								'depth'          => 1,
								'items_wrap'     => '%3$s',
								'link_before'    => '',
								'walker'         => new OD_Nav_Walker(),
								'fallback_cb'    => false,
							)
						);
					} else {
						echo '<a href="' . esc_url( od_page_url( 'track' ) ) . '">' . esc_html__( 'Track order', 'ojasvidrapes' ) . '</a>';
						echo '<span aria-hidden="true">|</span>';
						echo '<a href="' . esc_url( od_page_url( 'contact' ) ) . '">' . esc_html__( 'Help', 'ojasvidrapes' ) . '</a>';
					}
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<header class="od-header" id="od-header">
		<div class="od-container">
			<div class="od-header__inner">

				<div class="od-header__left" style="display:flex;align-items:center;gap:10px">
					<button type="button" class="od-icon-btn od-burger" data-panel="#od-drawer"
						aria-label="<?php esc_attr_e( 'Open menu', 'ojasvidrapes' ); ?>" aria-controls="od-drawer" aria-expanded="false">
						<?php od_the_icon( 'menu', 20 ); ?>
					</button>
					<?php od_brand(); ?>
				</div>

				<nav class="od-nav" aria-label="<?php esc_attr_e( 'Primary', 'ojasvidrapes' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'od-nav__list',
							'depth'          => 3,
							'walker'         => new OD_Nav_Walker(),
							'fallback_cb'    => 'od_menu_fallback',
						)
					);
					?>
				</nav>

				<div class="od-header__actions">
					<button type="button" class="od-icon-btn" data-search-open
						aria-label="<?php esc_attr_e( 'Search', 'ojasvidrapes' ); ?>">
						<?php od_the_icon( 'search', 19 ); ?>
					</button>

					<?php if ( od_option( 'compare_on', true ) ) : ?>
						<a class="od-icon-btn od-header-action--secondary" href="<?php echo esc_url( od_page_url( 'compare' ) ); ?>"
							aria-label="<?php esc_attr_e( 'Compare products', 'ojasvidrapes' ); ?>">
							<?php od_the_icon( 'compare', 19 ); ?>
							<?php od_list_count_badge( 'compare' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( od_option( 'wishlist_on', true ) ) : ?>
						<a class="od-icon-btn od-header-action--secondary" href="<?php echo esc_url( od_page_url( 'wishlist' ) ); ?>"
							aria-label="<?php esc_attr_e( 'Wishlist', 'ojasvidrapes' ); ?>">
							<?php od_the_icon( 'heart', 19 ); ?>
							<?php od_list_count_badge( 'wishlist' ); ?>
						</a>
					<?php endif; ?>

					<a class="od-icon-btn" href="<?php echo esc_url( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : od_page_url( 'auth' ) ); ?>"
						aria-label="<?php esc_attr_e( 'My account', 'ojasvidrapes' ); ?>">
						<?php od_the_icon( 'user', 19 ); ?>
					</a>

					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<button type="button" class="od-icon-btn" data-panel="#od-cart-panel"
							aria-label="<?php esc_attr_e( 'Open your bag', 'ojasvidrapes' ); ?>" aria-controls="od-cart-panel">
							<?php od_the_icon( 'bag', 19 ); ?>
							<?php od_cart_count_badge(); ?>
						</button>
					<?php endif; ?>
				</div>

			</div>
		</div>
	</header>

	<?php get_template_part( 'template-parts/header/drawer' ); ?>
	<?php get_template_part( 'template-parts/header/search' ); ?>

	<?php if ( class_exists( 'WooCommerce' ) ) : ?>
		<?php get_template_part( 'template-parts/header/cart-panel' ); ?>
	<?php endif; ?>

	<div class="od-scrim" aria-hidden="true"></div>

	<?php endif; // Elementor header. ?>

	<main class="od-main" id="od-content">
