<?php
/**
 * Plugin Name:       OJASVI Delivery
 * Plugin URI:        https://ojasvidrapes.in/
 * Description:       One courier, tracked end to end. Records the tracking number and delivery status against each WooCommerce order, shows the shopper where their parcel is, and takes status pushes from your delivery app over a REST endpoint.
 * Version:           2.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            OJASVI
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ojasvidrapes-delivery
 * Domain Path:       /languages
 *
 * @package OjasviDrapesDelivery
 */

defined( 'ABSPATH' ) || exit;

define( 'ODD_VERSION', '2.0.0' );
define( 'ODD_FILE', __FILE__ );
define( 'ODD_DIR', plugin_dir_path( __FILE__ ) );
define( 'ODD_URI', plugin_dir_url( __FILE__ ) );

/**
 * WooCommerce has to be there — everything here hangs off an order.
 */
function odd_requirements_met() {
	return class_exists( 'WooCommerce' );
}

/**
 * Load the plugin once WooCommerce is known to be present.
 */
function odd_boot() {
	if ( ! odd_requirements_met() ) {
		add_action( 'admin_notices', 'odd_missing_woo_notice' );
		return;
	}

	require_once ODD_DIR . 'includes/migrate-rename.php';
	require_once ODD_DIR . 'includes/couriers.php';
	require_once ODD_DIR . 'includes/class-odd-shipment.php';
	require_once ODD_DIR . 'includes/class-odd-settings.php';
	require_once ODD_DIR . 'includes/class-odd-admin.php';
	require_once ODD_DIR . 'includes/class-odd-rest.php';
	require_once ODD_DIR . 'includes/class-odd-frontend.php';
	require_once ODD_DIR . 'includes/class-odd-emails.php';
	require_once ODD_DIR . 'includes/class-odd-delhivery.php';

	ODD_Settings::init();
	ODD_Admin::init();
	ODD_REST::init();
	ODD_Frontend::init();
	ODD_Emails::init();
	ODD_Delhivery::init();

	load_plugin_textdomain( 'ojasvidrapes-delivery', false, dirname( plugin_basename( ODD_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'odd_boot' );

/**
 * Say what is missing rather than failing silently.
 */
function odd_missing_woo_notice() {
	echo '<div class="notice notice-warning"><p>'
		. esc_html__( 'OJASVI Delivery needs WooCommerce to be active. Activate WooCommerce and this starts working on its own.', 'ojasvidrapes-delivery' )
		. '</p></div>';
}

/**
 * Declare compatibility with WooCommerce's High-Performance Order Storage.
 *
 * Every read and write here goes through the order object rather than post
 * meta, so HPOS is fine — but WooCommerce hides the plugin from HPOS stores
 * unless it is told so explicitly.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', ODD_FILE, true );
		}
	}
);

/**
 * Give the shop a push key on activation so the REST endpoint is never open.
 */
function odd_activate() {
	if ( ! get_option( 'odd_api_key' ) ) {
		update_option( 'odd_api_key', wp_generate_password( 40, false, false ) );
	}
}
register_activation_hook( __FILE__, 'odd_activate' );

/**
 * Settings link on the plugins screen.
 *
 * @param array $links Existing links.
 * @return array
 */
function odd_action_links( $links ) {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'admin.php?page=odd-settings' ) ) . '">'
		. esc_html__( 'Settings', 'ojasvidrapes-delivery' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'odd_action_links' );
