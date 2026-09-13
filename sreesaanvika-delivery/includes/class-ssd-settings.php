<?php
/**
 * The settings screen: which courier, and the key the delivery app pushes with.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings page under WooCommerce.
 */
class SSD_Settings {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_ssd_new_key', array( __CLASS__, 'new_key' ) );
	}

	/**
	 * Add the page.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Delivery', 'sreesaanvika-delivery' ),
			__( 'Delivery', 'sreesaanvika-delivery' ),
			'manage_woocommerce',
			'ssd-settings',
			array( __CLASS__, 'render' )
		);
	}

	/**
	 * The options this plugin owns.
	 *
	 * @return array<string,string> option => sanitize callback.
	 */
	protected static function fields() {
		return array(
			'ssd_courier'              => 'sanitize_key',
			'ssd_courier_name'         => 'sanitize_text_field',
			'ssd_tracking_url'         => 'esc_url_raw',
			'ssd_support_phone'        => 'sanitize_text_field',
			'ssd_support_email'        => 'sanitize_email',
			'ssd_complete_on_delivery' => 'sanitize_key',
			'ssd_email_dispatch'       => 'sanitize_key',
			'ssd_email_delivered'      => 'sanitize_key',
			'ssd_promise'              => 'sanitize_text_field',
			'ssd_delhivery_token'      => 'sanitize_text_field',
			'ssd_poll_minutes'         => 'sanitize_key',
		);
	}

	/**
	 * Register them.
	 */
	public static function register() {
		foreach ( self::fields() as $option => $sanitize ) {
			register_setting(
				'ssd_settings',
				$option,
				array(
					'sanitize_callback' => $sanitize,
					'type'              => 'string',
				)
			);
		}
	}

	/**
	 * Roll the push key.
	 */
	public static function new_key() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sreesaanvika-delivery' ) );
		}

		check_admin_referer( 'ssd_new_key' );

		update_option( 'ssd_api_key', wp_generate_password( 40, false, false ) );

		wp_safe_redirect( admin_url( 'admin.php?page=ssd-settings&ssd-key=1' ) );
		exit;
	}

	/**
	 * Draw the page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$couriers = ssd_couriers();
		$current  = get_option( 'ssd_courier', 'delhivery' );
		$key      = (string) get_option( 'ssd_api_key', '' );
		$endpoint = rest_url( 'sreesaanvika-delivery/v1/shipment' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Delivery', 'sreesaanvika-delivery' ); ?></h1>

			<?php if ( isset( $_GET['ssd-key'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p>
					<?php esc_html_e( 'New key generated. Paste it into your delivery app — the old one has stopped working.', 'sreesaanvika-delivery' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'ssd_settings' ); ?>

				<h2 class="title"><?php esc_html_e( 'Your courier', 'sreesaanvika-delivery' ); ?></h2>
				<p class="description" style="max-width:640px">
					<?php esc_html_e( 'The shop uses one courier, so this is set once. Everything the customer sees — the name, the tracking link — comes from here.', 'sreesaanvika-delivery' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ssd_courier"><?php esc_html_e( 'Courier', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<select name="ssd_courier" id="ssd_courier">
								<?php foreach ( $couriers as $slug => $courier ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>>
										<?php echo esc_html( $courier['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_courier_name"><?php esc_html_e( 'Name to show customers', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="ssd_courier_name" id="ssd_courier_name"
								value="<?php echo esc_attr( get_option( 'ssd_courier_name', '' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Only needed for "Other", or to use a different name from the one above.', 'sreesaanvika-delivery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_tracking_url"><?php esc_html_e( 'Tracking link', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<input type="text" class="large-text code" name="ssd_tracking_url" id="ssd_tracking_url"
								value="<?php echo esc_attr( get_option( 'ssd_tracking_url', '' ) ); ?>"
								placeholder="<?php echo esc_attr( isset( $couriers[ $current ] ) ? $couriers[ $current ]['url'] : 'https://…/{tracking}' ); ?>" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: the {tracking} placeholder */
									esc_html__( 'Leave empty to use the courier\'s usual link. %s is replaced with the consignment number.', 'sreesaanvika-delivery' ),
									'<code>{tracking}</code>'
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_promise"><?php esc_html_e( 'Delivery promise', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="ssd_promise" id="ssd_promise"
								value="<?php echo esc_attr( get_option( 'ssd_promise', '' ) ); ?>"
								placeholder="<?php esc_attr_e( 'Metro cities in 2–4 days, elsewhere in 4–7', 'sreesaanvika-delivery' ); ?>" />
							<p class="description"><?php esc_html_e( 'Shown to a customer whose parcel has not moved yet.', 'sreesaanvika-delivery' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'What happens automatically', 'sreesaanvika-delivery' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'On dispatch', 'sreesaanvika-delivery' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="ssd_email_dispatch" value="yes"
									<?php checked( get_option( 'ssd_email_dispatch', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Email the customer their tracking number', 'sreesaanvika-delivery' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'On delivery', 'sreesaanvika-delivery' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="ssd_email_delivered" value="yes"
									<?php checked( get_option( 'ssd_email_delivered', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Email the customer that it arrived', 'sreesaanvika-delivery' ); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="ssd_complete_on_delivery" value="yes"
									<?php checked( get_option( 'ssd_complete_on_delivery', 'no' ), 'yes' ); ?> />
								<?php esc_html_e( 'Also mark the WooCommerce order Completed', 'sreesaanvika-delivery' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_support_phone"><?php esc_html_e( 'Support phone', 'sreesaanvika-delivery' ); ?></label></th>
						<td><input type="text" class="regular-text" name="ssd_support_phone" id="ssd_support_phone"
							value="<?php echo esc_attr( get_option( 'ssd_support_phone', '' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_support_email"><?php esc_html_e( 'Support email', 'sreesaanvika-delivery' ); ?></label></th>
						<td><input type="email" class="regular-text" name="ssd_support_email" id="ssd_support_email"
							value="<?php echo esc_attr( get_option( 'ssd_support_email', '' ) ); ?>" /></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Delhivery — ask them automatically', 'sreesaanvika-delivery' ); ?></h2>

			<p style="max-width:720px">
				<?php esc_html_e( 'With a Delhivery API token the shop can check on every parcel still in flight by itself, and move the order along without anyone touching it. A push from your delivery app is still better where you can set one up — it arrives the moment a scan happens rather than on the next check.', 'sreesaanvika-delivery' ); ?>
			</p>

			<?php if ( isset( $_GET['ssd-polled'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<?php $ssd_polled = get_transient( 'ssd_poll_result' ); ?>
				<?php if ( $ssd_polled ) : ?>
					<?php delete_transient( 'ssd_poll_result' ); ?>
					<div class="notice notice-<?php echo empty( $ssd_polled['error'] ) ? 'success' : 'error'; ?>">
						<p>
							<?php
							printf(
								/* translators: 1: parcels checked, 2: orders updated */
								esc_html__( 'Checked %1$d parcels, updated %2$d.', 'sreesaanvika-delivery' ),
								(int) $ssd_polled['checked'],
								(int) $ssd_polled['updated']
							);

							if ( ! empty( $ssd_polled['error'] ) ) {
								echo ' ' . esc_html( $ssd_polled['error'] );
							}
							?>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'ssd_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ssd_delhivery_token"><?php esc_html_e( 'Delhivery API token', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<input type="text" class="large-text code" name="ssd_delhivery_token" id="ssd_delhivery_token"
								value="<?php echo esc_attr( get_option( 'ssd_delhivery_token', '' ) ); ?>" autocomplete="off" />
							<p class="description"><?php esc_html_e( 'From your Delhivery panel, under API setup. Leave empty to turn the checking off.', 'sreesaanvika-delivery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ssd_poll_minutes"><?php esc_html_e( 'Check how often', 'sreesaanvika-delivery' ); ?></label></th>
						<td>
							<?php $ssd_every = (string) get_option( 'ssd_poll_minutes', 'off' ); ?>
							<select name="ssd_poll_minutes" id="ssd_poll_minutes">
								<option value="off" <?php selected( $ssd_every, 'off' ); ?>><?php esc_html_e( 'Never — I will push updates instead', 'sreesaanvika-delivery' ); ?></option>
								<option value="15" <?php selected( $ssd_every, '15' ); ?>><?php esc_html_e( 'Every 15 minutes', 'sreesaanvika-delivery' ); ?></option>
								<option value="30" <?php selected( $ssd_every, '30' ); ?>><?php esc_html_e( 'Every 30 minutes', 'sreesaanvika-delivery' ); ?></option>
								<option value="hourly" <?php selected( $ssd_every, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'sreesaanvika-delivery' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Only parcels that have a tracking number and have not finished are asked about, so this costs very little.', 'sreesaanvika-delivery' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Delhivery settings', 'sreesaanvika-delivery' ) ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ssd_poll_now" />
				<?php wp_nonce_field( 'ssd_poll_now' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Check Delhivery now', 'sreesaanvika-delivery' ); ?></button>
			</form>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Connecting your delivery app', 'sreesaanvika-delivery' ); ?></h2>

			<p style="max-width:720px">
				<?php esc_html_e( 'Your delivery app can push every scan straight onto the order, so the website says "Out for delivery" at the same moment the app does. Point a webhook at this address and send the key with it. Nothing is exposed publicly: a request without the key is refused.', 'sreesaanvika-delivery' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Endpoint', 'sreesaanvika-delivery' ); ?></th>
					<td><code><?php echo esc_html( $endpoint ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Key', 'sreesaanvika-delivery' ); ?></th>
					<td>
						<code><?php echo esc_html( $key ); ?></code>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:10px">
							<input type="hidden" name="action" value="ssd_new_key" />
							<?php wp_nonce_field( 'ssd_new_key' ); ?>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Generate a new key', 'sreesaanvika-delivery' ); ?></button>
						</form>
						<p class="description"><?php esc_html_e( 'Send it as the X-SSD-Key header, or as a key field in the body.', 'sreesaanvika-delivery' ); ?></p>
					</td>
				</tr>
			</table>

			<p><strong><?php esc_html_e( 'An example push', 'sreesaanvika-delivery' ); ?></strong></p>

			<pre style="background:#fff;border:1px solid #ccd0d4;padding:14px;overflow:auto;max-width:900px">curl -X POST <?php echo esc_html( $endpoint ); ?> \
  -H "Content-Type: application/json" \
  -H "X-SSD-Key: <?php echo esc_html( $key ); ?>" \
  -d '{
    "order_number": "1234",
    "tracking": "ABC123456789",
    "status": "out",
    "location": "Falaknuma, Hyderabad",
    "note": "With the rider"
  }'</pre>

			<p style="max-width:720px">
				<?php
				printf(
					/* translators: %s: comma separated status slugs */
					esc_html__( 'status is one of: %s. Send only what changed — a push with just a status will not wipe the tracking number.', 'sreesaanvika-delivery' ),
					'<code>' . esc_html( implode( '</code>, <code>', array_keys( ssd_statuses() ) ) ) . '</code>'
				);
				?>
			</p>

			<p style="max-width:720px">
				<?php esc_html_e( 'To read one back, GET the same address with ?order_number=1234 and the key header. If your courier cannot send webhooks, use the Import tracking numbers screen instead — it takes the CSV manifest the courier gives you.', 'sreesaanvika-delivery' ); ?>
			</p>
		</div>
		<?php
	}
}
