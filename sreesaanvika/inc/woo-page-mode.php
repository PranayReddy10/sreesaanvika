<?php
/**
 * Switch the Cart and Checkout pages between WooCommerce's blocks and the
 * classic shortcodes.
 *
 * WooCommerce 8.3+ builds both pages from blocks. Blocks never load the
 * theme's cart.php / form-checkout.php templates, so the theme's own designs —
 * the free-shipping meter, the savings line, the three-step indicator — do not
 * appear, and the blocks bring a light palette of their own.
 *
 * The theme restyles the blocks so either choice looks right. This gives the
 * shop owner a one-click way to pick which one they want, with the previous
 * content kept so the switch is reversible.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

const SS_PAGE_BACKUP_META = '_ss_content_backup';

/**
 * The Cart and Checkout pages, keyed by the shortcode that replaces the block.
 *
 * @return array
 */
function ss_woo_pages() {
	if ( ! function_exists( 'wc_get_page_id' ) ) {
		return array();
	}

	return array(
		'cart'     => array(
			'id'        => wc_get_page_id( 'cart' ),
			'shortcode' => '[woocommerce_cart]',
			'block'     => 'woocommerce/cart',
			'label'     => __( 'Cart', 'sreesaanvika' ),
		),
		'checkout' => array(
			'id'        => wc_get_page_id( 'checkout' ),
			'shortcode' => '[woocommerce_checkout]',
			'block'     => 'woocommerce/checkout',
			'label'     => __( 'Checkout', 'sreesaanvika' ),
		),
	);
}

/**
 * Is this page currently built from a WooCommerce block?
 *
 * @param int    $page_id Page id.
 * @param string $block   Block name.
 * @return bool
 */
function ss_page_uses_block( $page_id, $block ) {
	if ( $page_id <= 0 ) {
		return false;
	}

	$post = get_post( $page_id );

	return $post && has_block( $block, $post );
}

/**
 * Which mode each Woo page is in: "block", "shortcode" or "other".
 *
 * @return array
 */
function ss_woo_page_modes() {
	$modes = array();

	foreach ( ss_woo_pages() as $key => $page ) {
		if ( $page['id'] <= 0 ) {
			$modes[ $key ] = 'missing';
			continue;
		}

		$post = get_post( $page['id'] );

		if ( ! $post ) {
			$modes[ $key ] = 'missing';
		} elseif ( has_block( $page['block'], $post ) ) {
			$modes[ $key ] = 'block';
		} elseif ( has_shortcode( $post->post_content, trim( $page['shortcode'], '[]' ) ) ) {
			$modes[ $key ] = 'shortcode';
		} else {
			$modes[ $key ] = 'other';
		}
	}

	return $modes;
}

/**
 * Swap both pages to the theme's shortcode templates, or back again.
 */
function ss_switch_woo_pages() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'sreesaanvika' ) );
	}

	check_admin_referer( 'ss_woo_page_mode' );

	$to      = isset( $_POST['ss_mode'] ) && 'block' === $_POST['ss_mode'] ? 'block' : 'shortcode';
	$changed = array();

	foreach ( ss_woo_pages() as $page ) {
		if ( $page['id'] <= 0 ) {
			continue;
		}

		$post = get_post( $page['id'] );

		if ( ! $post ) {
			continue;
		}

		if ( 'shortcode' === $to ) {
			// Nothing to do if it is already the shortcode.
			if ( has_shortcode( $post->post_content, trim( $page['shortcode'], '[]' ) ) ) {
				continue;
			}

			// Keep the block markup so the switch can be undone.
			update_post_meta( $page['id'], SS_PAGE_BACKUP_META, $post->post_content );

			wp_update_post(
				array(
					'ID'           => $page['id'],
					'post_content' => $page['shortcode'],
				)
			);

			$changed[] = $page['label'];
		} else {
			$backup = get_post_meta( $page['id'], SS_PAGE_BACKUP_META, true );

			if ( ! $backup ) {
				// No backup: rebuild a stock block.
				$backup = '<!-- wp:' . $page['block'] . ' --><div class="wp-block-' . str_replace( '/', '-', $page['block'] ) . '"></div><!-- /wp:' . $page['block'] . ' -->';
			}

			wp_update_post(
				array(
					'ID'           => $page['id'],
					'post_content' => $backup,
				)
			);

			delete_post_meta( $page['id'], SS_PAGE_BACKUP_META );
			$changed[] = $page['label'];
		}
	}

	set_transient(
		'ss_woo_mode_result',
		array(
			'mode'    => $to,
			'changed' => $changed,
		),
		60
	);

	wp_safe_redirect( admin_url( 'themes.php?page=sreesaanvika' ) );
	exit;
}
add_action( 'admin_post_ss_switch_woo_pages', 'ss_switch_woo_pages' );
