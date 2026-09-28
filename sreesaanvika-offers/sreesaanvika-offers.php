<?php
/**
 * Plugin Name:       Sree Saanvika Offers
 * Plugin URI:        https://sreesaanvika.in/
 * Description:       Buy 2 get 1 free, and offers like it, without a promo code. Pick the products an offer covers; when enough of them are in the cart the cheapest ones go free on their own.
 * Version:           1.2.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Sree Saanvika
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sreesaanvika-offers
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

define( 'SSO_VERSION', '1.2.1' );
define( 'SSO_FILE', __FILE__ );
define( 'SSO_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSO_URI', plugin_dir_url( __FILE__ ) );

/**
 * Everything here hangs off a WooCommerce cart.
 */
function sso_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-warning"><p>'
					. esc_html__( 'Sree Saanvika Offers needs WooCommerce to be active.', 'sreesaanvika-offers' )
					. '</p></div>';
			}
		);
		return;
	}

	require_once SSO_DIR . 'includes/class-sso-offer.php';
	require_once SSO_DIR . 'includes/class-sso-admin.php';
	require_once SSO_DIR . 'includes/class-sso-cart.php';
	require_once SSO_DIR . 'includes/class-sso-display.php';
	require_once SSO_DIR . 'includes/class-sso-bundle.php';
	require_once SSO_DIR . 'includes/class-sso-bundle-display.php';

	SSO_Offer::init();
	SSO_Admin::init();
	SSO_Cart::init();
	SSO_Display::init();
	SSO_Bundle::init();
	SSO_Bundle_Display::init();

	load_plugin_textdomain( 'sreesaanvika-offers', false, dirname( plugin_basename( SSO_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'sso_boot' );

/**
 * Declare compatibility with High-Performance Order Storage.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SSO_FILE, true );
		}
	}
);

/**
 * The offers list is the plugin's own screen.
 *
 * @param array $links Existing links.
 * @return array
 */
function sso_action_links( $links ) {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'edit.php?post_type=ss_offer' ) ) . '">'
		. esc_html__( 'Offers', 'sreesaanvika-offers' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sso_action_links' );

/**
 * Make the offers screen reachable straight after activation.
 */
function sso_activate() {
	if ( ! post_type_exists( 'ss_offer' ) ) {
		require_once SSO_DIR . 'includes/class-sso-offer.php';
		SSO_Offer::register_type();
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sso_activate' );
