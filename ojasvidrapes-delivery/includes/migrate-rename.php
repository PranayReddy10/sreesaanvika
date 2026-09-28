<?php
/**
 * One-time move from the ssd_ names to odd_.
 *
 * Shipments already recorded against orders, and the settings that point at
 * Delhivery, all lived under the old prefix. Without this a rebranded shop
 * would lose every tracking number it had taken.
 *
 * @package OjasviDrapesDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Carry settings and shipments across, once.
 */
function odd_migrate_rename() {
	if ( get_option( 'odd_renamed_from_ssd' ) ) {
		return;
	}

	foreach ( array(
		'api_key',
		'courier',
		'courier_name',
		'tracking_url',
		'promise',
		'support_phone',
		'support_email',
		'complete_on_delivery',
		'email_dispatch',
		'email_delivered',
		'delhivery_token',
		'poll_minutes',
	) as $name ) {
		$value = get_option( 'ssd_' . $name, null );

		if ( null !== $value && null === get_option( 'odd_' . $name, null ) ) {
			update_option( 'odd_' . $name, $value );
		}
	}

	odd_migrate_order_meta();

	// The old schedule fires a hook nothing listens for any more.
	wp_clear_scheduled_hook( 'ssd_poll_delhivery' );

	update_option( 'odd_renamed_from_ssd', ODD_VERSION );
}
add_action( 'plugins_loaded', 'odd_migrate_rename', 20 );

/**
 * Rename the shipment meta on every order.
 *
 * Orders live in post meta on a classic store and in their own table under
 * High-Performance Order Storage, so both are covered — whichever is absent
 * simply is not touched.
 */
function odd_migrate_order_meta() {
	global $wpdb;

	$tables = array( $wpdb->postmeta => 'meta_key' );
	$hpos   = $wpdb->prefix . 'wc_orders_meta';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $hpos ) ) === $hpos ) {
		$tables[ $hpos ] = 'meta_key';
	}

	foreach ( $tables as $table => $column ) {
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->prepare(
				"UPDATE `{$table}` SET `{$column}` = REPLACE( `{$column}`, '_ssd_', '_odd_' ) WHERE `{$column}` LIKE %s",
				$wpdb->esc_like( '_ssd_' ) . '%'
			)
		);
	}

	wp_cache_flush();
}
