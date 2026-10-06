<?php
/**
 * The settings screen: which courier, and the key the delivery app pushes with.
 *
 * @package OjasviDrapesDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Settings page under WooCommerce.
 */
class ODD_Settings {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_odd_new_key', array( __CLASS__, 'new_key' ) );
	}

	/**
	 * Add the page.
	 */
	public static function menu() {
		add_submenu_page(
			'woocommerce',
			__( 'Delivery', 'ojasvidrapes-delivery' ),
			__( 'Delivery', 'ojasvidrapes-delivery' ),
			'manage_woocommerce',
			'odd-settings',
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
			'odd_courier'              => 'sanitize_key',
			'odd_courier_name'         => 'sanitize_text_field',
			'odd_tracking_url'         => 'esc_url_raw',
			'odd_support_phone'        => 'sanitize_text_field',
			'odd_support_email'        => 'sanitize_email',
			'odd_complete_on_delivery' => 'sanitize_key',
			'odd_email_dispatch'       => 'sanitize_key',
			'odd_email_delivered'      => 'sanitize_key',
			'odd_promise'              => 'sanitize_text_field',
			'odd_delhivery_token'      => 'sanitize_text_field',
			'odd_poll_minutes'         => 'sanitize_key',
		);
	}

	/**
	 * Register them.
	 */
	public static function register() {
		foreach ( self::fields() as $option => $sanitize ) {
			register_setting(
				'odd_settings',
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
			wp_die( esc_html__( 'You do not have permission to do that.', 'ojasvidrapes-delivery' ) );
		}

		check_admin_referer( 'odd_new_key' );

		update_option( 'odd_api_key', wp_generate_password( 40, false, false ) );

		wp_safe_redirect( admin_url( 'admin.php?page=odd-settings&odd-key=1' ) );
		exit;
	}

	/**
	 * Draw the page.
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$couriers = odd_couriers();
		$current  = get_option( 'odd_courier', 'delhivery' );
		$key      = (string) get_option( 'odd_api_key', '' );
		$endpoint = rest_url( 'ojasvidrapes-delivery/v1/shipment' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Delivery', 'ojasvidrapes-delivery' ); ?></h1>

			<?php if ( isset( $_GET['odd-key'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success"><p>
					<?php esc_html_e( 'New key generated. Paste it into your delivery app — the old one has stopped working.', 'ojasvidrapes-delivery' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'odd_settings' ); ?>

				<h2 class="title"><?php esc_html_e( 'Your courier', 'ojasvidrapes-delivery' ); ?></h2>
				<p class="description" style="max-width:640px">
					<?php esc_html_e( 'The shop uses one courier, so this is set once. Everything the customer sees — the name, the tracking link — comes from here.', 'ojasvidrapes-delivery' ); ?>
				</p>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="odd_courier"><?php esc_html_e( 'Courier', 'ojasvidrapes-delivery' ); ?></label></th>
						<td>
							<select name="odd_courier" id="odd_courier">
								<?php foreach ( $couriers as $slug => $courier ) : ?>
									<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $current, $slug ); ?>>
										<?php echo esc_html( $courier['name'] ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="odd_courier_name"><?php esc_html_e( 'Name to show customers', 'ojasvidrapes-delivery' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="odd_courier_name" id="odd_courier_name"
								value="<?php echo esc_attr( get_option( 'odd_courier_name', '' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'Only needed for "Other", or to use a different name from the one above.', 'ojasvidrapes-delivery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="odd_tracking_url"><?php esc_html_e( 'Tracking link', 'ojasvidrapes-delivery' ); ?></label></th>
						<td>
							<input type="text" class="large-text code" name="odd_tracking_url" id="odd_tracking_url"
								value="<?php echo esc_attr( get_option( 'odd_tracking_url', '' ) ); ?>"
								placeholder="<?php echo esc_attr( isset( $couriers[ $current ] ) ? $couriers[ $current ]['url'] : 'https://…/{tracking}' ); ?>" />
							<p class="description">
								<?php
								printf(
									/* translators: %s: the {tracking} placeholder */
									esc_html__( 'Leave empty to use the courier\'s usual link. %s is replaced with the consignment number.', 'ojasvidrapes-delivery' ),
									'<code>{tracking}</code>'
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="odd_promise"><?php esc_html_e( 'Delivery promise', 'ojasvidrapes-delivery' ); ?></label></th>
						<td>
							<input type="text" class="regular-text" name="odd_promise" id="odd_promise"
								value="<?php echo esc_attr( get_option( 'odd_promise', '' ) ); ?>"
								placeholder="<?php esc_attr_e( 'Metro cities in 2–4 days, elsewhere in 4–7', 'ojasvidrapes-delivery' ); ?>" />
							<p class="description"><?php esc_html_e( 'Shown to a customer whose parcel has not moved yet.', 'ojasvidrapes-delivery' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php esc_html_e( 'What happens automatically', 'ojasvidrapes-delivery' ); ?></h2>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'On dispatch', 'ojasvidrapes-delivery' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="odd_email_dispatch" value="yes"
									<?php checked( get_option( 'odd_email_dispatch', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Email the customer their tracking number', 'ojasvidrapes-delivery' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'On delivery', 'ojasvidrapes-delivery' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="odd_email_delivered" value="yes"
									<?php checked( get_option( 'odd_email_delivered', 'yes' ), 'yes' ); ?> />
								<?php esc_html_e( 'Email the customer that it arrived', 'ojasvidrapes-delivery' ); ?>
							</label>
							<br />
							<label>
								<input type="checkbox" name="odd_complete_on_delivery" value="yes"
									<?php checked( get_option( 'odd_complete_on_delivery', 'no' ), 'yes' ); ?> />
								<?php esc_html_e( 'Also mark the WooCommerce order Completed', 'ojasvidrapes-delivery' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="odd_support_phone"><?php esc_html_e( 'Support phone', 'ojasvidrapes-delivery' ); ?></label></th>
						<td><input type="text" class="regular-text" name="odd_support_phone" id="odd_support_phone"
							value="<?php echo esc_attr( get_option( 'odd_support_phone', '' ) ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="odd_support_email"><?php esc_html_e( 'Support email', 'ojasvidrapes-delivery' ); ?></label></th>
						<td><input type="email" class="regular-text" name="odd_support_email" id="odd_support_email"
							value="<?php echo esc_attr( get_option( 'odd_support_email', '' ) ); ?>" /></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Delhivery — ask them automatically', 'ojasvidrapes-delivery' ); ?></h2>

			<p style="max-width:720px">
				<?php esc_html_e( 'With a Delhivery API token the shop can check on every parcel still in flight by itself, and move the order along without anyone touching it. A push from your delivery app is still better where you can set one up — it arrives the moment a scan happens rather than on the next check.', 'ojasvidrapes-delivery' ); ?>
			</p>

			<?php if ( isset( $_GET['odd-polled'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<?php $odd_polled = get_transient( 'odd_poll_result' ); ?>
				<?php if ( $odd_polled ) : ?>
					<?php delete_transient( 'odd_poll_result' ); ?>
					<div class="notice notice-<?php echo empty( $odd_polled['error'] ) ? 'success' : 'error'; ?>">
						<p>
							<?php
							printf(
								/* translators: 1: parcels checked, 2: orders updated */
								esc_html__( 'Checked %1$d parcels, updated %2$d.', 'ojasvidrapes-delivery' ),
								(int) $odd_polled['checked'],
								(int) $odd_polled['updated']
							);

							if ( ! empty( $odd_polled['error'] ) ) {
								echo ' ' . esc_html( $odd_polled['error'] );
							}
							?>
						</p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<form method="post" action="options.php">
				<?php settings_fields( 'odd_settings' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="odd_delhivery_token"><?php esc_html_e( 'Delhivery API token', 'ojasvidrapes-delivery' ); ?></label></th>
						<td>
							<input type="text" class="large-text code" name="odd_delhivery_token" id="odd_delhivery_token"
								value="<?php echo esc_attr( get_option( 'odd_delhivery_token', '' ) ); ?>" autocomplete="off" />
							<p class="description"><?php esc_html_e( 'From your Delhivery panel, under API setup. Leave empty to turn the checking off.', 'ojasvidrapes-delivery' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="odd_poll_minutes"><?php esc_html_e( 'Check how often', 'ojasvidrapes-delivery' ); ?></label></th>
						<td>
							<?php $odd_every = (string) get_option( 'odd_poll_minutes', 'off' ); ?>
							<select name="odd_poll_minutes" id="odd_poll_minutes">
								<option value="off" <?php selected( $odd_every, 'off' ); ?>><?php esc_html_e( 'Never — I will push updates instead', 'ojasvidrapes-delivery' ); ?></option>
								<option value="15" <?php selected( $odd_every, '15' ); ?>><?php esc_html_e( 'Every 15 minutes', 'ojasvidrapes-delivery' ); ?></option>
								<option value="30" <?php selected( $odd_every, '30' ); ?>><?php esc_html_e( 'Every 30 minutes', 'ojasvidrapes-delivery' ); ?></option>
								<option value="hourly" <?php selected( $odd_every, 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'ojasvidrapes-delivery' ); ?></option>
							</select>
							<p class="description"><?php esc_html_e( 'Only parcels that have a tracking number and have not finished are asked about, so this costs very little.', 'ojasvidrapes-delivery' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( __( 'Save Delhivery settings', 'ojasvidrapes-delivery' ) ); ?>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="odd_poll_now" />
				<?php wp_nonce_field( 'odd_poll_now' ); ?>
				<button type="submit" class="button"><?php esc_html_e( 'Check Delhivery now', 'ojasvidrapes-delivery' ); ?></button>
			</form>

			<hr />

			<h2 class="title"><?php esc_html_e( 'Connecting your delivery app', 'ojasvidrapes-delivery' ); ?></h2>

			<p style="max-width:720px">
				<?php esc_html_e( 'Your delivery app can push every scan straight onto the order, so the website says "Out for delivery" at the same moment the app does. Point a webhook at this address and send the key with it. Nothing is exposed publicly: a request without the key is refused.', 'ojasvidrapes-delivery' ); ?>
			</p>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Endpoint', 'ojasvidrapes-delivery' ); ?></th>
					<td><code><?php echo esc_html( $endpoint ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Key', 'ojasvidrapes-delivery' ); ?></th>
					<td>
						<code><?php echo esc_html( $key ); ?></code>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:10px">
							<input type="hidden" name="action" value="odd_new_key" />
							<?php wp_nonce_field( 'odd_new_key' ); ?>
							<button type="submit" class="button button-small"><?php esc_html_e( 'Generate a new key', 'ojasvidrapes-delivery' ); ?></button>
						</form>
						<p class="description"><?php esc_html_e( 'Send it as the X-ODD-Key header, or as a key field in the body.', 'ojasvidrapes-delivery' ); ?></p>
					</td>
				</tr>
			</table>

			<p><strong><?php esc_html_e( 'An example push', 'ojasvidrapes-delivery' ); ?></strong></p>

			<pre style="background:#fff;border:1px solid #ccd0d4;padding:14px;overflow:auto;max-width:900px">curl -X POST <?php echo esc_html( $endpoint ); ?> \
  -H "Content-Type: application/json" \
  -H "X-ODD-Key: <?php echo esc_html( $key ); ?>" \
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
					esc_html__( 'status is one of: %s. Send only what changed — a push with just a status will not wipe the tracking number.', 'ojasvidrapes-delivery' ),
					'<code>' . esc_html( implode( '</code>, <code>', array_keys( odd_statuses() ) ) ) . '</code>'
				);
				?>
			</p>

			<p style="max-width:720px">
				<?php esc_html_e( 'To read one back, GET the same address with ?order_number=1234 and the key header. If your courier cannot send webhooks, use the Import tracking numbers screen instead — it takes the CSV manifest the courier gives you.', 'ojasvidrapes-delivery' ); ?>
			</p>
		</div>
		<?php
	}
}
