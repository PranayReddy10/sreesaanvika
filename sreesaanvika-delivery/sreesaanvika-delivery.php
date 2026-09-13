<?php
/**
 * Plugin Name:       Sree Saanvika Delivery
 * Plugin URI:        https://sreesaanvika.in/
 * Description:       One courier, tracked end to end. Records the tracking number and delivery status against each WooCommerce order, shows the shopper where their parcel is, and takes status pushes from your delivery app over a REST endpoint.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Sree Saanvika
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sreesaanvika-delivery
 * Domain Path:       /languages
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

define( 'SSD_VERSION', '1.0.0' );
define( 'SSD_FILE', __FILE__ );
define( 'SSD_DIR', plugin_dir_path( __FILE__ ) );
define( 'SSD_URI', plugin_dir_url( __FILE__ ) );

/**
 * WooCommerce has to be there — everything here hangs off an order.
 */
function ssd_requirements_met() {
	return class_exists( 'WooCommerce' );
}

/**
 * Load the plugin once WooCommerce is known to be present.
 */
function ssd_boot() {
	if ( ! ssd_requirements_met() ) {
		add_action( 'admin_notices', 'ssd_missing_woo_notice' );
		return;
	}

	require_once SSD_DIR . 'includes/couriers.php';
	require_once SSD_DIR . 'includes/class-ssd-shipment.php';
	require_once SSD_DIR . 'includes/class-ssd-settings.php';
	require_once SSD_DIR . 'includes/class-ssd-admin.php';
	require_once SSD_DIR . 'includes/class-ssd-rest.php';
	require_once SSD_DIR . 'includes/class-ssd-frontend.php';
	require_once SSD_DIR . 'includes/class-ssd-emails.php';

	SSD_Settings::init();
	SSD_Admin::init();
	SSD_REST::init();
	SSD_Frontend::init();
	SSD_Emails::init();

	load_plugin_textdomain( 'sreesaanvika-delivery', false, dirname( plugin_basename( SSD_FILE ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'ssd_boot' );

/**
 * Say what is missing rather than failing silently.
 */
function ssd_missing_woo_notice() {
	echo '<div class="notice notice-warning"><p>'
		. esc_html__( 'Sree Saanvika Delivery needs WooCommerce to be active. Activate WooCommerce and this starts working on its own.', 'sreesaanvika-delivery' )
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
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', SSD_FILE, true );
		}
	}
);

/**
 * Give the shop a push key on activation so the REST endpoint is never open.
 */
function ssd_activate() {
	if ( ! get_option( 'ssd_api_key' ) ) {
		update_option( 'ssd_api_key', wp_generate_password( 40, false, false ) );
	}
}
register_activation_hook( __FILE__, 'ssd_activate' );

/**
 * Settings link on the plugins screen.
 *
 * @param array $links Existing links.
 * @return array
 */
function ssd_action_links( $links ) {
	array_unshift(
		$links,
		'<a href="' . esc_url( admin_url( 'admin.php?page=ssd-settings' ) ) . '">'
		. esc_html__( 'Settings', 'sreesaanvika-delivery' ) . '</a>'
	);

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ssd_action_links' );
