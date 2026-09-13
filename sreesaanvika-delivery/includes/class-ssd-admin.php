<?php
/**
 * The order screen: where staff read and set the delivery status.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Order metabox, orders-list column, bulk action and the CSV manifest import.
 */
class SSD_Admin {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'metabox' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( __CLASS__, 'save' ) );
		add_action( 'save_post_shop_order', array( __CLASS__, 'save' ) );

		// Orders list — the classic post table and the HPOS table.
		add_filter( 'manage_edit-shop_order_columns', array( __CLASS__, 'column' ), 20 );
		add_filter( 'woocommerce_shop_order_list_table_columns', array( __CLASS__, 'column' ), 20 );
		add_action( 'manage_shop_order_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_action( 'woocommerce_shop_order_list_table_custom_column', array( __CLASS__, 'column_content_hpos' ), 10, 2 );

		add_filter( 'bulk_actions-edit-shop_order', array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'bulk_actions' ) );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( __CLASS__, 'handle_bulk' ), 10, 3 );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( __CLASS__, 'handle_bulk' ), 10, 3 );

		add_action( 'admin_menu', array( __CLASS__, 'import_page' ) );
		add_action( 'admin_post_ssd_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * The screen ids an order is edited on, classic and HPOS.
	 *
	 * @return array
	 */
	protected static function order_screens() {
		$hpos = class_exists( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )
			&& wc_get_container()->get( \Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled();

		return $hpos ? array( wc_get_page_screen_id( 'shop-order' ) ) : array( 'shop_order' );
	}

	/**
	 * Add the panel.
	 */
	public static function metabox() {
		foreach ( self::order_screens() as $screen ) {
			add_meta_box(
				'ssd-shipment',
				__( 'Delivery', 'sreesaanvika-delivery' ),
				array( __CLASS__, 'render' ),
				$screen,
				'side',
				'high'
			);
		}
	}

	/**
	 * Draw the panel.
	 *
	 * @param WP_Post|WC_Order $post Order or post.
	 */
	public static function render( $post ) {
		$order = $post instanceof WC_Order ? $post : wc_get_order( $post->ID );

		if ( ! $order ) {
			return;
		}

		$shipment = new SSD_Shipment( $order );
		$couriers = ssd_couriers();
		$url      = $shipment->tracking_url();

		wp_nonce_field( 'ssd_shipment', 'ssd_nonce' );
		?>
		<div class="ssd-box">
			<p>
				<label for="ssd_status"><strong><?php esc_html_e( 'Where is it?', 'sreesaanvika-delivery' ); ?></strong></label>
				<select name="ssd_status" id="ssd_status" class="widefat">
					<?php foreach ( ssd_statuses() as $slug => $label ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $shipment->status(), $slug ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="ssd_tracking"><strong><?php esc_html_e( 'Consignment / AWB number', 'sreesaanvika-delivery' ); ?></strong></label>
				<input type="text" name="ssd_tracking" id="ssd_tracking" class="widefat"
					value="<?php echo esc_attr( $shipment->tracking() ); ?>" autocomplete="off" />
				<?php if ( $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer" class="ssd-track-link">
						<?php
						printf(
							/* translators: %s: courier name */
							esc_html__( 'Track on %s', 'sreesaanvika-delivery' ),
							esc_html( $shipment->courier_name() )
						);
						?>
					</a>
				<?php endif; ?>
			</p>

			<p>
				<label for="ssd_courier"><strong><?php esc_html_e( 'Courier', 'sreesaanvika-delivery' ); ?></strong></label>
				<select name="ssd_courier" id="ssd_courier" class="widefat">
					<?php foreach ( $couriers as $slug => $courier ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $shipment->courier(), $slug ); ?>>
							<?php echo esc_html( $courier['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="ssd_eta"><strong><?php esc_html_e( 'Expected delivery', 'sreesaanvika-delivery' ); ?></strong></label>
				<input type="date" name="ssd_eta" id="ssd_eta" class="widefat" value="<?php echo esc_attr( $shipment->eta() ); ?>" />
			</p>

			<p>
				<label for="ssd_note"><strong><?php esc_html_e( 'Add a note to the timeline', 'sreesaanvika-delivery' ); ?></strong></label>
				<input type="text" name="ssd_note" id="ssd_note" class="widefat"
					placeholder="<?php esc_attr_e( 'Left at reception', 'sreesaanvika-delivery' ); ?>" />
				<span class="description"><?php esc_html_e( 'The customer sees this.', 'sreesaanvika-delivery' ); ?></span>
			</p>

			<?php $timeline = array_reverse( $shipment->timeline() ); ?>

			<?php if ( $timeline ) : ?>
				<p><strong><?php esc_html_e( 'History', 'sreesaanvika-delivery' ); ?></strong></p>
				<ul class="ssd-history">
					<?php foreach ( array_slice( $timeline, 0, 12 ) as $event ) : ?>
						<li>
							<span class="ssd-history__when">
								<?php echo esc_html( wp_date( 'j M, g:ia', (int) $event['time'] ) ); ?>
							</span>
							<span class="ssd-history__what">
								<?php echo esc_html( ssd_status_label( $event['status'] ) ); ?>
								<?php if ( ! empty( $event['location'] ) ) : ?>
									· <?php echo esc_html( $event['location'] ); ?>
								<?php endif; ?>
							</span>
							<?php if ( ! empty( $event['note'] ) ) : ?>
								<span class="ssd-history__note"><?php echo esc_html( $event['note'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save the panel.
	 *
	 * @param int $order_id Order id.
	 */
	public static function save( $order_id ) {
		if ( ! isset( $_POST['ssd_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ssd_nonce'] ) ), 'ssd_shipment' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_shop_orders' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$shipment = SSD_Shipment::get( $order_id );

		if ( ! $shipment ) {
			return;
		}

		$user = wp_get_current_user();

		$shipment->update(
			array(
				'status'   => isset( $_POST['ssd_status'] ) ? sanitize_key( wp_unslash( $_POST['ssd_status'] ) ) : '',
				'tracking' => isset( $_POST['ssd_tracking'] ) ? sanitize_text_field( wp_unslash( $_POST['ssd_tracking'] ) ) : null,
				'courier'  => isset( $_POST['ssd_courier'] ) ? sanitize_key( wp_unslash( $_POST['ssd_courier'] ) ) : '',
				'eta'      => isset( $_POST['ssd_eta'] ) ? sanitize_text_field( wp_unslash( $_POST['ssd_eta'] ) ) : null,
				'note'     => isset( $_POST['ssd_note'] ) ? sanitize_text_field( wp_unslash( $_POST['ssd_note'] ) ) : '',
			),
			$user->display_name ? $user->display_name : __( 'the shop', 'sreesaanvika-delivery' )
		);
	}

	/* ---------------------------------------------------------------------
	 * Orders list
	 * ------------------------------------------------------------------ */

	/**
	 * Add a Delivery column before the order actions.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function column( $columns ) {
		$out = array();

		foreach ( $columns as $key => $label ) {
			if ( in_array( $key, array( 'order_actions', 'wc_actions' ), true ) ) {
				$out['ssd_delivery'] = __( 'Delivery', 'sreesaanvika-delivery' );
			}

			$out[ $key ] = $label;
		}

		if ( ! isset( $out['ssd_delivery'] ) ) {
			$out['ssd_delivery'] = __( 'Delivery', 'sreesaanvika-delivery' );
		}

		return $out;
	}

	/**
	 * Classic table cell.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Order id.
	 */
	public static function column_content( $column, $post_id ) {
		if ( 'ssd_delivery' === $column ) {
			self::cell( $post_id );
		}
	}

	/**
	 * HPOS table cell.
	 *
	 * @param string   $column Column key.
	 * @param WC_Order $order  Order.
	 */
	public static function column_content_hpos( $column, $order ) {
		if ( 'ssd_delivery' === $column ) {
			self::cell( $order );
		}
	}

	/**
	 * The cell itself.
	 *
	 * @param WC_Order|int $order Order.
	 */
	protected static function cell( $order ) {
		$shipment = SSD_Shipment::get( $order );

		if ( ! $shipment ) {
			return;
		}

		printf(
			'<span class="ssd-pill ssd-pill--%1$s">%2$s</span>',
			esc_attr( $shipment->status() ),
			esc_html( $shipment->status_label() )
		);

		if ( $shipment->tracking() ) {
			$url = $shipment->tracking_url();

			echo '<br />';

			if ( $url ) {
				printf(
					'<a href="%1$s" target="_blank" rel="noopener noreferrer"><small>%2$s</small></a>',
					esc_url( $url ),
					esc_html( $shipment->tracking() )
				);
			} else {
				echo '<small>' . esc_html( $shipment->tracking() ) . '</small>';
			}
		}
	}

	/**
	 * Bulk actions for the common moves.
	 *
	 * @param array $actions Actions.
	 * @return array
	 */
	public static function bulk_actions( $actions ) {
		$actions['ssd_mark_dispatched'] = __( 'Delivery: mark dispatched', 'sreesaanvika-delivery' );
		$actions['ssd_mark_delivered']  = __( 'Delivery: mark delivered', 'sreesaanvika-delivery' );

		return $actions;
	}

	/**
	 * Run a bulk action.
	 *
	 * @param string $redirect Redirect url.
	 * @param string $action   Action key.
	 * @param array  $ids      Order ids.
	 * @return string
	 */
	public static function handle_bulk( $redirect, $action, $ids ) {
		$map = array(
			'ssd_mark_dispatched' => 'dispatched',
			'ssd_mark_delivered'  => 'delivered',
		);

		if ( ! isset( $map[ $action ] ) ) {
			return $redirect;
		}

		$done = 0;

		foreach ( (array) $ids as $id ) {
			$shipment = SSD_Shipment::get( $id );

			if ( $shipment && $shipment->update( array( 'status' => $map[ $action ] ), __( 'a bulk action', 'sreesaanvika-delivery' ) ) ) {
				$done++;
			}
		}

		return add_query_arg( 'ssd_bulk', $done, $redirect );
	}

	/* ---------------------------------------------------------------------
	 * CSV manifest import
	 * ------------------------------------------------------------------ */

	/**
	 * Add the import screen.
	 */
	public static function import_page() {
		add_submenu_page(
			'woocommerce',
			__( 'Import tracking numbers', 'sreesaanvika-delivery' ),
			__( 'Import tracking', 'sreesaanvika-delivery' ),
			'manage_woocommerce',
			'ssd-import',
			array( __CLASS__, 'render_import' )
		);
	}

	/**
	 * Draw the import screen.
	 */
	public static function render_import() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$result = get_transient( 'ssd_import_result' );

		if ( $result ) {
			delete_transient( 'ssd_import_result' );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Import tracking numbers', 'sreesaanvika-delivery' ); ?></h1>

			<?php if ( $result ) : ?>
				<div class="notice notice-<?php echo empty( $result['missed'] ) ? 'success' : 'warning'; ?>">
					<p>
						<?php
						printf(
							/* translators: %d: number of orders */
							esc_html( _n( 'Updated %d order.', 'Updated %d orders.', (int) $result['done'], 'sreesaanvika-delivery' ) ),
							(int) $result['done']
						);

						if ( ! empty( $result['missed'] ) ) {
							echo ' ';
							printf(
								/* translators: %s: comma separated order numbers */
								esc_html__( 'These order numbers were not found: %s', 'sreesaanvika-delivery' ),
								esc_html( implode( ', ', array_slice( $result['missed'], 0, 25 ) ) )
							);
						}
						?>
					</p>
				</div>
			<?php endif; ?>

			<p style="max-width:700px">
				<?php esc_html_e( 'Upload the manifest your courier gives you after a pickup. Two columns are enough: the order number and the consignment number. A header row is fine — it is skipped when the first cell is not a number.', 'sreesaanvika-delivery' ); ?>
			</p>

			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ssd_import" />
				<?php wp_nonce_field( 'ssd_import' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ssd_csv"><?php esc_html_e( 'CSV file', 'sreesaanvika-delivery' ); ?></label></th>
						<td><input type="file" name="ssd_csv" id="ssd_csv" accept=".csv,text/csv" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_import_status"><?php esc_html_e( 'Mark them as', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<select name="ssd_import_status" id="ssd_import_status">
								<?php foreach ( ssd_statuses() as $slug => $label ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( 'dispatched', $slug ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Import', 'sreesaanvika-delivery' ) ); ?>
			</form>

			<p><strong><?php esc_html_e( 'What the file should look like', 'sreesaanvika-delivery' ); ?></strong></p>
			<pre style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-width:420px">order,awb
1234,ABC123456789
1235,ABC123456790</pre>
		</div>
		<?php
	}

	/**
	 * Read the uploaded manifest.
	 */
	public static function handle_import() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sreesaanvika-delivery' ) );
		}

		check_admin_referer( 'ssd_import' );

		$status = isset( $_POST['ssd_import_status'] ) ? sanitize_key( wp_unslash( $_POST['ssd_import_status'] ) ) : 'dispatched';
		$done   = 0;
		$missed = array();

		if ( empty( $_FILES['ssd_csv']['tmp_name'] ) || ! is_uploaded_file( $_FILES['ssd_csv']['tmp_name'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			set_transient( 'ssd_import_result', array( 'done' => 0, 'missed' => array( __( 'no file', 'sreesaanvika-delivery' ) ) ), 60 );
			wp_safe_redirect( admin_url( 'admin.php?page=ssd-import' ) );
			exit;
		}

		$handle = fopen( $_FILES['ssd_csv']['tmp_name'], 'r' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput, WordPress.WP.AlternativeFunctions

		if ( $handle ) {
			while ( false !== ( $row = fgetcsv( $handle, 4096 ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition
				if ( count( $row ) < 2 ) {
					continue;
				}

				$number   = trim( (string) $row[0] );
				$tracking = trim( (string) $row[1] );

				// The header row, or anything else without a usable order number.
				if ( '' === $number || '' === $tracking || ! preg_match( '/\d/', $number ) ) {
					continue;
				}

				$shipment = SSD_Shipment::find_by_number( $number );

				if ( ! $shipment ) {
					$missed[] = $number;
					continue;
				}

				$shipment->update(
					array(
						'tracking' => $tracking,
						'status'   => $status,
					),
					__( 'the courier manifest', 'sreesaanvika-delivery' )
				);

				$done++;
			}

			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		}

		set_transient( 'ssd_import_result', array( 'done' => $done, 'missed' => $missed ), 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=ssd-import' ) );
		exit;
	}

	/**
	 * Panel styling.
	 *
	 * @param string $hook Current screen.
	 */
	public static function assets( $hook ) {
		unset( $hook );

		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$wanted = array_merge( self::order_screens(), array( 'edit-shop_order', 'woocommerce_page_wc-orders' ) );

		if ( ! in_array( $screen->id, $wanted, true ) ) {
			return;
		}

		wp_enqueue_style( 'ssd-admin', SSD_URI . 'assets/ssd-admin.css', array(), SSD_VERSION );
	}
}
