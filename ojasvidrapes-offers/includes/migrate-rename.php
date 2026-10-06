<?php
/**
 * One-time move from the sso_ names to odo_.
 *
 * Offers are posts of their own type, and Complete the look is a pile of
 * product meta. Both were named after the old brand, so both need carrying
 * over or every offer disappears and every pairing comes undone.
 *
 * @package OjasviDrapesOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Carry offers and pairings across, once.
 */
function odo_migrate_rename() {
	if ( get_option( 'odo_renamed_from_sso' ) ) {
		return;
	}

	global $wpdb;

	// The offers themselves: a post type rename, nothing more.
	$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->posts,
		array( 'post_type' => ODO_Offer::TYPE ),
		array( 'post_type' => 'ss_offer' )
	);

	// Their settings, and Complete the look on every product.
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_key = REPLACE( meta_key, '_sso_', '_odo_' ) WHERE meta_key LIKE %s",
			$wpdb->esc_like( '_sso_' ) . '%'
		)
	);

	wp_cache_flush();

	update_option( 'odo_renamed_from_sso', ODO_VERSION );
}
add_action( 'plugins_loaded', 'odo_migrate_rename', 20 );
