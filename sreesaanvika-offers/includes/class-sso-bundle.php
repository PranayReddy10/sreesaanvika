<?php
/**
 * Complete the look: the matching products picked for each product.
 *
 * A saree gets its jhumkas and its bangles attached by hand, and the shop can
 * say "and take 15% off the jewellery when you buy them together".
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * The pairing, stored on the product.
 */
class SSO_Bundle {

	/**
	 * One row per partner, so a reverse lookup is a plain meta query rather
	 * than a LIKE against a serialised array.
	 */
	const ITEM = '_sso_bundle_item';

	const TITLE     = '_sso_bundle_title';
	const PERCENT   = '_sso_bundle_percent';
	const BOTH_WAYS = '_sso_bundle_both_ways';

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
		add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'save' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * The products picked as matches for this one.
	 *
	 * @param int $product_id Product id.
	 * @return int[]
	 */
	public static function partners( $product_id ) {
		$ids = get_post_meta( absint( $product_id ), self::ITEM, false );

		return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	}

	/**
	 * Products that named this one as a match and asked for it to work both
	 * ways — so a pair of jhumkas can show the saree it was chosen for.
	 *
	 * @param int $product_id Product id.
	 * @return int[]
	 */
	public static function partners_of( $product_id ) {
		$product_id = absint( $product_id );

		if ( ! $product_id ) {
			return array();
		}

		$owners = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					'relation' => 'AND',
					array(
						'key'   => self::ITEM,
						'value' => $product_id,
					),
					array(
						'key'   => self::BOTH_WAYS,
						'value' => 'yes',
					),
				),
			)
		);

		return array_map( 'absint', $owners );
	}

	/**
	 * The full set to show beside a product: the ones it picked, plus the ones
	 * that picked it back.
	 *
	 * @param int $product_id Product id.
	 * @return int[]
	 */
	public static function matches( $product_id ) {
		$ids = array_merge( self::partners( $product_id ), self::partners_of( $product_id ) );
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

		$out = array();

		foreach ( $ids as $id ) {
			if ( $id === absint( $product_id ) ) {
				continue;
			}

			$product = wc_get_product( $id );

			// A match nobody can buy is worse than no match at all.
			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() || 'publish' !== $product->get_status() ) {
				continue;
			}

			$out[] = $id;
		}

		return array_slice( $out, 0, 8 );
	}

	/**
	 * Heading over the row.
	 *
	 * @param int $product_id Product id.
	 * @return string
	 */
	public static function title( $product_id ) {
		$title = (string) get_post_meta( absint( $product_id ), self::TITLE, true );

		return $title ? $title : __( 'Complete the look', 'sreesaanvika-offers' );
	}

	/**
	 * How much comes off the matching items when they are bought together.
	 *
	 * @param int $product_id Product id.
	 * @return float
	 */
	public static function percent( $product_id ) {
		$pct = (float) get_post_meta( absint( $product_id ), self::PERCENT, true );

		return max( 0, min( 90, $pct ) );
	}

	/* ---------------------------------------------------------------------
	 * Admin
	 * ------------------------------------------------------------------ */

	/**
	 * The panel on the product editor.
	 */
	public static function metabox() {
		add_meta_box(
			'sso-bundle',
			__( 'Complete the look', 'sreesaanvika-offers' ),
			array( __CLASS__, 'panel' ),
			'product',
			'normal',
			'default'
		);
	}

	/**
	 * Draw it.
	 *
	 * @param WP_Post $post Product.
	 */
	public static function panel( $post ) {
		$partners = self::partners( $post->ID );

		wp_nonce_field( 'sso_bundle', 'sso_bundle_nonce' );
		?>
		<p class="description" style="margin-bottom:14px">
			<?php esc_html_e( 'Pick the pieces that go with this one — the jhumkas for a saree, the bangles, a matching blouse. They appear under the product with tick boxes and a running total, so a shopper can take the whole look in one go, and again in the cart as a reminder.', 'sreesaanvika-offers' ); ?>
		</p>

		<p>
			<label for="sso_bundle"><strong><?php esc_html_e( 'Matching products', 'sreesaanvika-offers' ); ?></strong></label><br />
			<select class="wc-product-search" multiple="multiple" style="width:100%" id="sso_bundle" name="sso_bundle[]"
				data-placeholder="<?php esc_attr_e( 'Search for a product…', 'sreesaanvika-offers' ); ?>"
				data-action="woocommerce_json_search_products">
				<?php foreach ( $partners as $id ) : ?>
					<?php $partner = wc_get_product( $id ); ?>
					<?php if ( $partner ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" selected>
							<?php echo esc_html( wp_strip_all_tags( $partner->get_formatted_name() ) ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
		</p>

		<p>
			<label for="sso_bundle_title"><strong><?php esc_html_e( 'Heading', 'sreesaanvika-offers' ); ?></strong></label><br />
			<input type="text" class="large-text" id="sso_bundle_title" name="sso_bundle_title"
				value="<?php echo esc_attr( get_post_meta( $post->ID, self::TITLE, true ) ); ?>"
				placeholder="<?php esc_attr_e( 'Complete the look', 'sreesaanvika-offers' ); ?>" />
		</p>

		<p>
			<label for="sso_bundle_percent"><strong><?php esc_html_e( 'Discount on the matching items', 'sreesaanvika-offers' ); ?></strong></label><br />
			<input type="number" min="0" max="90" step="1" id="sso_bundle_percent" name="sso_bundle_percent" style="width:90px"
				value="<?php echo esc_attr( self::percent( $post->ID ) ); ?>" /> %
			<br />
			<span class="description">
				<?php esc_html_e( 'Taken off the matching items when this product is in the cart with them. Leave at 0 to show the pairing without a discount.', 'sreesaanvika-offers' ); ?>
			</span>
		</p>

		<p>
			<label>
				<input type="checkbox" name="sso_bundle_both_ways" value="yes"
					<?php checked( 'yes', get_post_meta( $post->ID, self::BOTH_WAYS, true ) ); ?> />
				<?php esc_html_e( 'Show this product on the matching products\' pages too', 'sreesaanvika-offers' ); ?>
			</label>
			<br />
			<span class="description">
				<?php esc_html_e( 'Pick the jewellery here once and the saree turns up beside the jewellery as well.', 'sreesaanvika-offers' ); ?>
			</span>
		</p>
		<?php
	}

	/**
	 * Save the panel.
	 *
	 * @param int $product_id Product id.
	 */
	public static function save( $product_id ) {
		if ( ! isset( $_POST['sso_bundle_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sso_bundle_nonce'] ) ), 'sso_bundle' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$ids = isset( $_POST['sso_bundle'] ) ? (array) wp_unslash( $_POST['sso_bundle'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
		$ids = array_diff( $ids, array( absint( $product_id ) ) );

		// One meta row per partner, rewritten from scratch each save.
		delete_post_meta( $product_id, self::ITEM );

		foreach ( $ids as $id ) {
			add_post_meta( $product_id, self::ITEM, $id );
		}

		update_post_meta(
			$product_id,
			self::TITLE,
			isset( $_POST['sso_bundle_title'] ) ? sanitize_text_field( wp_unslash( $_POST['sso_bundle_title'] ) ) : ''
		);

		update_post_meta(
			$product_id,
			self::PERCENT,
			isset( $_POST['sso_bundle_percent'] ) ? max( 0, min( 90, (float) wp_unslash( $_POST['sso_bundle_percent'] ) ) ) : 0
		);

		update_post_meta( $product_id, self::BOTH_WAYS, isset( $_POST['sso_bundle_both_ways'] ) ? 'yes' : 'no' );
	}

	/**
	 * WooCommerce's product search on the product editor.
	 *
	 * @param string $hook Screen.
	 */
	public static function assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( ! $screen || 'product' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
	}
}
