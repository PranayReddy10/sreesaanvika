<?php
/**
 * One-time move from the old Sree Saanvika names to Ojasvi Drapes.
 *
 * Renaming the code is free; renaming what is already in the database is not.
 * Every Customizer setting, every colour gallery and every saved option lived
 * under an ss_ name, so without this a rebranded shop would come back up with
 * its defaults and its product photos unattached.
 *
 * It runs once, leaves the old rows alone, and never overwrites a new name
 * that already holds something.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

const OD_RENAME_FLAG = 'od_renamed_from_ss';

/**
 * Carry the old settings across.
 */
function od_migrate_rename() {
	if ( get_option( OD_RENAME_FLAG ) ) {
		return;
	}

	od_migrate_theme_mods();
	od_migrate_options();
	od_migrate_post_meta();

	update_option( OD_RENAME_FLAG, OD_VERSION );
}
add_action( 'after_setup_theme', 'od_migrate_rename', 5 );

/**
 * Every ss_ Customizer setting becomes its od_ twin.
 */
function od_migrate_theme_mods() {
	$mods = get_theme_mods();

	if ( ! is_array( $mods ) ) {
		return;
	}

	foreach ( $mods as $key => $value ) {
		if ( 0 !== strpos( $key, 'ss_' ) ) {
			continue;
		}

		$new = 'od_' . substr( $key, 3 );

		// A setting already saved under the new name wins.
		if ( null === get_theme_mod( $new, null ) ) {
			set_theme_mod( $new, $value );
		}
	}
}

/**
 * The handful of options the theme owns.
 */
function od_migrate_options() {
	$pairs = array(
		'ss_setup_complete'  => 'od_setup_complete',
		'ss_newsletter_list' => 'od_newsletter_list',
	);

	foreach ( $pairs as $old => $new ) {
		$value = get_option( $old, null );

		if ( null !== $value && null === get_option( $new, null ) ) {
			update_option( $new, $value );
		}
	}
}

/**
 * Product meta: colour galleries, and the cart/checkout block backup.
 */
function od_migrate_post_meta() {
	global $wpdb;

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"UPDATE {$wpdb->postmeta} SET meta_key = REPLACE( meta_key, '_ss_', '_od_' ) WHERE meta_key LIKE %s",
			$wpdb->esc_like( '_ss_' ) . '%'
		)
	);

	wp_cache_flush();
}
