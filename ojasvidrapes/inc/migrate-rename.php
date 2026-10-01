<?php
/**
 * One-time move from the old Sree Saanvika names to Ojasvi Drapes.
 *
 * Renaming the code is free; renaming what is already in the database is not.
 * Two separate things have to be carried across.
 *
 * The first is the theme's own settings, which all lived under an ss_ name.
 *
 * The second is easy to miss and hurts more: WordPress keys a theme's mods to
 * the theme's *folder*, in an option called theme_mods_{folder}. Renaming the
 * folder therefore does not rename those settings, it orphans them — the new
 * folder starts with an empty set, so the logo disappears, every menu comes
 * back unassigned, Additional CSS is gone and all three widget areas empty
 * themselves into Inactive Widgets. Nothing is lost from the database; the
 * site simply stops looking at the row that holds it. So this reads the old
 * theme's row directly rather than asking for "the current theme's mods",
 * which was the bug in the first release of this file.
 *
 * It leaves the old rows untouched, so the old theme still works if it is
 * reactivated, and it never overwrites a new name that already holds
 * something.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which revision of this migration has run.
 *
 * Bump OD_RENAME_REV whenever the migration is corrected, so a site that
 * already ran an earlier, broken version runs the fixed one.
 *
 *   1 — first release; read the new theme's mods, so it carried nothing.
 *   2 — reads the old theme's row, and carries the logo, menus, Additional
 *       CSS and widgets too.
 */
const OD_RENAME_FLAG = 'od_rename_rev';
const OD_RENAME_REV  = 2;

/**
 * Carry the old settings across.
 */
function od_migrate_rename() {
	if ( (int) get_option( OD_RENAME_FLAG, 0 ) >= OD_RENAME_REV ) {
		return;
	}

	$old = od_old_theme_mods();

	if ( $old ) {
		od_migrate_theme_mods( $old );
		od_migrate_core_mods( $old );
		od_migrate_widgets( $old );
	}

	od_migrate_options();
	od_migrate_post_meta();

	update_option( OD_RENAME_FLAG, OD_RENAME_REV );

	// The first release wrote its own flag; it has no meaning now.
	delete_option( 'od_renamed_from_ss' );
}
add_action( 'after_setup_theme', 'od_migrate_rename', 5 );

/**
 * The old theme's saved mods.
 *
 * The folder could have been called anything — WordPress appends "-2" to a
 * second upload, and a ZIP downloaded from GitHub unpacks as
 * "sreesaanvika-main" — so instead of guessing the name this looks at every
 * theme_mods_* row other than the one in use and takes whichever holds the
 * most ss_ settings. A row belonging to some unrelated theme holds none and
 * so can never win.
 *
 * @return array Mods from the old theme, or an empty array.
 */
function od_old_theme_mods() {
	global $wpdb;

	$current = 'theme_mods_' . get_option( 'stylesheet' );

	$names = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'theme\\_mods\\_%'"
	);

	$best  = array();
	$score = 0;

	foreach ( (array) $names as $name ) {
		if ( $name === $current ) {
			continue;
		}

		$mods = get_option( $name );

		if ( ! is_array( $mods ) ) {
			continue;
		}

		$hits = 0;

		foreach ( array_keys( $mods ) as $key ) {
			if ( is_string( $key ) && 0 === strpos( $key, 'ss_' ) ) {
				++$hits;
			}
		}

		if ( $hits > $score ) {
			$score = $hits;
			$best  = $mods;
		}
	}

	return $best;
}

/**
 * Every ss_ Customizer setting becomes its od_ twin.
 *
 * @param array $old Mods from the old theme.
 */
function od_migrate_theme_mods( array $old ) {
	foreach ( $old as $key => $value ) {
		if ( ! is_string( $key ) || 0 !== strpos( $key, 'ss_' ) ) {
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
 * The settings WordPress itself keys to the theme folder.
 *
 * These are not the theme's to name, so they keep their own keys — they just
 * have to be copied from the old folder's row into the new one.
 *
 * @param array $old Mods from the old theme.
 */
function od_migrate_core_mods( array $old ) {
	$keys = array(
		'custom_logo',
		'nav_menu_locations',
		'custom_css_post_id',
		'background_color',
		'background_image',
		'background_position_x',
		'background_position_y',
		'background_size',
		'background_repeat',
		'background_attachment',
		'header_image',
		'header_image_data',
		'header_textcolor',
		'header_video',
	);

	foreach ( $keys as $key ) {
		if ( ! array_key_exists( $key, $old ) ) {
			continue;
		}

		if ( null === get_theme_mod( $key, null ) ) {
			set_theme_mod( $key, $old[ $key ] );
		}
	}
}

/**
 * Put the widgets back in the three sidebars.
 *
 * On a theme switch WordPress parks the outgoing theme's widgets in
 * wp_inactive_widgets and stashes the layout under the old theme's
 * sidebars_widgets mod. Because the sidebars have the same ids either side of
 * the rename, that stash can be replayed as-is.
 *
 * Only empty sidebars are filled, so a shop that has already rebuilt its
 * footer by hand does not have the old layout pushed back on top of it.
 *
 * @param array $old Mods from the old theme.
 */
function od_migrate_widgets( array $old ) {
	if ( empty( $old['sidebars_widgets']['data'] ) || ! is_array( $old['sidebars_widgets']['data'] ) ) {
		return;
	}

	$stashed = $old['sidebars_widgets']['data'];
	$current = get_option( 'sidebars_widgets' );

	if ( ! is_array( $current ) ) {
		return;
	}

	$inactive = isset( $current['wp_inactive_widgets'] ) ? (array) $current['wp_inactive_widgets'] : array();
	$moved    = false;

	foreach ( $stashed as $sidebar => $widgets ) {
		if ( 'wp_inactive_widgets' === $sidebar || ! is_array( $widgets ) ) {
			continue;
		}

		// Don't disturb a sidebar the shop owner has already filled.
		if ( ! empty( $current[ $sidebar ] ) ) {
			continue;
		}

		// Only widgets actually sitting in Inactive Widgets can be restored;
		// anything else has since been deleted.
		$restore = array_values( array_intersect( $widgets, $inactive ) );

		if ( ! $restore ) {
			continue;
		}

		$current[ $sidebar ] = $restore;
		$inactive            = array_values( array_diff( $inactive, $restore ) );
		$moved               = true;
	}

	if ( ! $moved ) {
		return;
	}

	$current['wp_inactive_widgets'] = $inactive;

	update_option( 'sidebars_widgets', $current );
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
 * Product meta: the colour galleries, and the cart/checkout block backup.
 *
 * Named one key at a time rather than renaming every _ss_ key in the table,
 * which would also rename meta belonging to some unrelated plugin that had
 * picked the same prefix.
 */
function od_migrate_post_meta() {
	global $wpdb;

	$keys = array(
		'_ss_color_galleries' => '_od_color_galleries',
		'_ss_content_backup'  => '_od_content_backup',
	);

	foreach ( $keys as $old => $new ) {
		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->postmeta,
			array( 'meta_key' => $new ),
			array( 'meta_key' => $old )
		);
	}

	wp_cache_flush();
}
