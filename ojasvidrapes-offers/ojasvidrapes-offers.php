<?php
/**
 * Plugin Name:       Ojasvi Drapes Offers
 * Plugin URI:        https://ojasvidrapes.in/
 * Description:       Buy 2 get 1 free, and offers like it, without a promo code. Pick the products an offer covers; when enough of them are in the cart the cheapest ones go free on their own.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Ojasvi Drapes
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ojasvidrapes-offers
 *
 * @package OjasviDrapesOffers
 */

defined( 'ABSPATH' ) || exit;

define( 'ODO_VERSION', '2.0.0' );
define( 'ODO_FILE', __FILE__ );
define( 'ODO_DIR', plugin_dir_path( __FILE__ ) );
define( 'ODO_URI', plugin_dir_url( __FILE__ ) );

/**
 * Everything here hangs off a WooCommerce cart.
 */
function odo_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action(
			'admin_notices',
			function () {
				echo '<div class="notice notice-warning"><p>'
					. esc_html__( 'Ojasvi Drapes Offers needs WooCommerce to be active.', 'ojasvidrapes-offers' )
					. '</p></div>';
			}
		);
		return;
	}

	require_once ODO_DIR . 'includes/class-odo-offer.php';
	require_once ODO_DIR . 'includes/migrate-rename.php';
	require_once ODO_DIR . 'includes/class-odo-admin.php';
	require_once ODO_DIR . 'includes/class-odo-cart.php';
	require_once ODO_DIR . 'includes/class-odo-display.php';
	require_once ODO_DIR . 'includes/class-odo-bundle.php';
	require_once ODO_DIR . 'includes/class-odo-bundle-display.php';

	ODO_Offer::init();
	ODO_Admin::init();
	ODO_Cart::init();
	ODO_Display::init();
	ODO_Bundle::init();
	ODO_Bundle_Display::init();

	load_plugin_textdomain( 'ojasvidrapes-offers', false, dirname( plugin_basename( ODO_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'odo_boot' );

/**
 * Declare compatibility with High-Performance Order Storage.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ODO_FILE, true );
		}
	}
);

/**
 * The offers list is the plugin's own screen.
 *
 * @param array $links Existing links.
 * @return array
 */
function odo_action_links( $links ) {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'edit.php?post_type=od_offer' ) ) . '">'
		. esc_html__( 'Offers', 'ojasvidrapes-offers' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'odo_action_links' );

/**
 * Make the offers screen reachable straight after activation.
 */
function odo_activate() {
	if ( ! post_type_exists( 'od_offer' ) ) {
		require_once ODO_DIR . 'includes/class-odo-offer.php';
		ODO_Offer::register_type();
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'odo_activate' );
