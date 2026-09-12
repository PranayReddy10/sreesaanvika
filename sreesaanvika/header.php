<?php
/**
 * Site header.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<meta name="theme-color" content="<?php echo esc_attr( ss_option( 'color_bg', '#140a12' ) ); ?>" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<link rel="preconnect" href="https://fonts.googleapis.com" />
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="ss-site" id="page">

	<a class="skip-link screen-reader-text ss-skip-link" href="#ss-content"><?php esc_html_e( 'Skip to content', 'sreesaanvika' ); ?></a>

	<?php
	/*
	 * Elementor Pro's Theme Builder can supply its own header. When it does,
	 * elementor_theme_do_location() prints it and the theme's header — along
	 * with the drawer, search overlay and cart panel it drives — is skipped.
	 */
	if ( ! ss_elementor_location( 'header' ) ) :
		?>

	<?php if ( ss_option( 'topbar_on' ) ) : ?>
		<?php
		$messages = ss_list( ss_option( 'topbar_items', '' ) );
		$phone    = ss_option( 'topbar_phone', '' );
		?>
		<div class="ss-topbar">
			<div class="ss-container">
				<?php if ( $messages ) : ?>
					<div class="ss-ticker" aria-hidden="true">
						<div class="ss-ticker__track">
							<?php foreach ( $messages as $message ) : ?>
								<span class="ss-ticker__item"><?php ss_the_icon( 'sparkle', 14 ); ?><?php echo esc_html( $message ); ?></span>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>

				<div class="ss-topbar__links">
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
								'walker'         => new SS_Nav_Walker(),
								'fallback_cb'    => false,
							)
						);
					} else {
						echo '<a href="' . esc_url( ss_page_url( 'track' ) ) . '">' . esc_html__( 'Track order', 'sreesaanvika' ) . '</a>';
						echo '<span aria-hidden="true">|</span>';
						echo '<a href="' . esc_url( ss_page_url( 'contact' ) ) . '">' . esc_html__( 'Help', 'sreesaanvika' ) . '</a>';
					}
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<header class="ss-header" id="ss-header">
		<div class="ss-container">
			<div class="ss-header__inner">

				<div class="ss-header__left" style="display:flex;align-items:center;gap:10px">
					<button type="button" class="ss-icon-btn ss-burger" data-panel="#ss-drawer"
						aria-label="<?php esc_attr_e( 'Open menu', 'sreesaanvika' ); ?>" aria-controls="ss-drawer" aria-expanded="false">
						<?php ss_the_icon( 'menu', 20 ); ?>
					</button>
					<?php ss_brand(); ?>
				</div>

				<nav class="ss-nav" aria-label="<?php esc_attr_e( 'Primary', 'sreesaanvika' ); ?>">
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'ss-nav__list',
							'depth'          => 3,
							'walker'         => new SS_Nav_Walker(),
							'fallback_cb'    => 'ss_menu_fallback',
						)
					);
					?>
				</nav>

				<div class="ss-header__actions">
					<button type="button" class="ss-icon-btn" data-search-open
						aria-label="<?php esc_attr_e( 'Search', 'sreesaanvika' ); ?>">
						<?php ss_the_icon( 'search', 19 ); ?>
					</button>

					<?php if ( ss_option( 'compare_on', true ) ) : ?>
						<a class="ss-icon-btn ss-header-action--secondary" href="<?php echo esc_url( ss_page_url( 'compare' ) ); ?>"
							aria-label="<?php esc_attr_e( 'Compare products', 'sreesaanvika' ); ?>">
							<?php ss_the_icon( 'compare', 19 ); ?>
							<?php ss_list_count_badge( 'compare' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( ss_option( 'wishlist_on', true ) ) : ?>
						<a class="ss-icon-btn ss-header-action--secondary" href="<?php echo esc_url( ss_page_url( 'wishlist' ) ); ?>"
							aria-label="<?php esc_attr_e( 'Wishlist', 'sreesaanvika' ); ?>">
							<?php ss_the_icon( 'heart', 19 ); ?>
							<?php ss_list_count_badge( 'wishlist' ); ?>
						</a>
					<?php endif; ?>

					<a class="ss-icon-btn" href="<?php echo esc_url( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : ss_page_url( 'auth' ) ); ?>"
						aria-label="<?php esc_attr_e( 'My account', 'sreesaanvika' ); ?>">
						<?php ss_the_icon( 'user', 19 ); ?>
					</a>

					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<button type="button" class="ss-icon-btn" data-panel="#ss-cart-panel"
							aria-label="<?php esc_attr_e( 'Open your bag', 'sreesaanvika' ); ?>" aria-controls="ss-cart-panel">
							<?php ss_the_icon( 'bag', 19 ); ?>
							<?php ss_cart_count_badge(); ?>
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

	<div class="ss-scrim" aria-hidden="true"></div>

	<?php endif; // Elementor header. ?>

	<main class="ss-main" id="ss-content">
