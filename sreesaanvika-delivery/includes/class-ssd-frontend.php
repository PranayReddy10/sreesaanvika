<?php
/**
 * What the customer sees: a progress line wherever they look for their order.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end output.
 */
class SSD_Frontend {

	/**
	 * Hook up.
	 */
	public static function init() {
		/*
		 * One hook covers all three places a customer looks: the thank-you
		 * page, My Account → order, and the result of the order tracking form
		 * on the theme's Track Your Order page.
		 */
		add_action( 'woocommerce_order_details_after_order_table', array( __CLASS__, 'render_for_order' ), 5 );

		add_shortcode( 'sreesaanvika_tracking', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	/**
	 * Stylesheet, only where it is used.
	 */
	public static function assets() {
		if ( ! function_exists( 'is_wc_endpoint_url' ) ) {
			return;
		}

		wp_register_style( 'ssd-front', SSD_URI . 'assets/ssd-front.css', array(), SSD_VERSION );
	}

	/**
	 * Render for an order object, from the WooCommerce hook.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function render_for_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$shipment = new SSD_Shipment( $order );

		// Nothing to say yet on an order that has not been picked or paid for.
		if ( 'pending' === $shipment->status() && ! $shipment->tracking() && $order->has_status( array( 'cancelled', 'failed', 'refunded' ) ) ) {
			return;
		}

		echo self::panel( $shipment ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * [sreesaanvika_tracking] — a standalone tracker for any page.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		unset( $atts );

		wp_enqueue_style( 'ssd-front' );

		$number = isset( $_GET['ssd_order'] ) ? sanitize_text_field( wp_unslash( $_GET['ssd_order'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$email  = isset( $_GET['ssd_email'] ) ? sanitize_text_field( wp_unslash( $_GET['ssd_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		ob_start();
		?>
		<div class="ssd-tracker">
			<form method="get" class="ssd-tracker__form">
				<p>
					<label for="ssd_order"><?php esc_html_e( 'Order number', 'sreesaanvika-delivery' ); ?></label>
					<input type="text" name="ssd_order" id="ssd_order" value="<?php echo esc_attr( $number ); ?>"
						placeholder="<?php esc_attr_e( '1234', 'sreesaanvika-delivery' ); ?>" required />
				</p>
				<p>
					<label for="ssd_email"><?php esc_html_e( 'Email or phone on the order', 'sreesaanvika-delivery' ); ?></label>
					<input type="text" name="ssd_email" id="ssd_email" value="<?php echo esc_attr( $email ); ?>" required />
				</p>
				<p>
					<button type="submit" class="ss-btn button"><?php esc_html_e( 'Track', 'sreesaanvika-delivery' ); ?></button>
				</p>
			</form>

			<?php
			if ( $number && $email ) {
				echo self::lookup( $number, $email ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Find an order for the public tracker.
	 *
	 * The email or phone has to match, so an order cannot be read by guessing
	 * numbers, and a wrong match says the same thing as a missing one.
	 *
	 * @param string $number Order number.
	 * @param string $who    Email or phone as given at checkout.
	 * @return string
	 */
	protected static function lookup( $number, $who ) {
		$shipment = SSD_Shipment::find_by_number( $number );
		$fail     = '<p class="ssd-tracker__miss">'
			. esc_html__( 'We could not find that order. Check the number and the email or phone you used at checkout.', 'sreesaanvika-delivery' )
			. '</p>';

		if ( ! $shipment ) {
			return $fail;
		}

		$order = $shipment->order();
		$given = strtolower( trim( $who ) );

		$email = strtolower( (string) $order->get_billing_email() );
		$phone = preg_replace( '/\D+/', '', (string) $order->get_billing_phone() );
		$digits = preg_replace( '/\D+/', '', $given );

		$match = ( $email && $email === $given )
			|| ( $phone && $digits && strlen( $digits ) >= 10 && substr( $phone, -10 ) === substr( $digits, -10 ) );

		if ( ! $match ) {
			return $fail;
		}

		return self::panel( $shipment, true );
	}

	/**
	 * The progress panel.
	 *
	 * @param SSD_Shipment $shipment Shipment.
	 * @param bool         $summary  Include the order summary line.
	 * @return string
	 */
	public static function panel( $shipment, $summary = false ) {
		wp_enqueue_style( 'ssd-front' );

		$status   = $shipment->status();
		$stages   = ssd_progress_stages();
		$at       = array_search( $status, $stages, true );
		$at       = false === $at ? 0 : $at;
		$unhappy  = ssd_is_unhappy( $status );
		$url      = $shipment->tracking_url();
		$tracking = $shipment->tracking();
		$eta      = $shipment->eta();
		$eta_at   = $eta ? strtotime( $eta ) : 0;
		$promise  = (string) get_option( 'ssd_promise', '' );
		$phone    = (string) get_option( 'ssd_support_phone', '' );
		$mail     = (string) get_option( 'ssd_support_email', '' );

		ob_start();
		?>
		<section class="ssd-panel<?php echo $unhappy ? ' ssd-panel--stopped' : ''; ?>">
			<header class="ssd-panel__head">
				<h2><?php esc_html_e( 'Delivery', 'sreesaanvika-delivery' ); ?></h2>
				<span class="ssd-panel__state"><?php echo esc_html( $shipment->status_label() ); ?></span>
			</header>

			<?php if ( $summary ) : ?>
				<p class="ssd-panel__order">
					<?php
					printf(
						/* translators: 1: order number, 2: order date */
						esc_html__( 'Order %1$s, placed %2$s', 'sreesaanvika-delivery' ),
						esc_html( '#' . $shipment->order()->get_order_number() ),
						esc_html( wc_format_datetime( $shipment->order()->get_date_created() ) )
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! $unhappy ) : ?>
				<ol class="ssd-steps" style="--ssd-at:<?php echo esc_attr( $at ); ?>;--ssd-of:<?php echo esc_attr( count( $stages ) - 1 ); ?>">
					<?php foreach ( $stages as $i => $stage ) : ?>
						<li class="ssd-step<?php echo $i <= $at ? ' is-done' : ''; ?><?php echo $i === $at ? ' is-now' : ''; ?>">
							<span class="ssd-step__dot" aria-hidden="true"></span>
							<span class="ssd-step__label"><?php echo esc_html( ssd_status_label( $stage ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>

			<dl class="ssd-facts">
				<?php if ( $tracking ) : ?>
					<div>
						<dt><?php esc_html_e( 'Tracking number', 'sreesaanvika-delivery' ); ?></dt>
						<dd>
							<?php if ( $url ) : ?>
								<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
									<?php echo esc_html( $tracking ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $tracking ); ?>
							<?php endif; ?>
						</dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Courier', 'sreesaanvika-delivery' ); ?></dt>
						<dd><?php echo esc_html( $shipment->courier_name() ); ?></dd>
					</div>
				<?php endif; ?>

				<?php if ( $eta_at ) : ?>
					<div>
						<dt><?php esc_html_e( 'Expected', 'sreesaanvika-delivery' ); ?></dt>
						<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), $eta_at ) ); ?></dd>
					</div>
				<?php endif; ?>
			</dl>

			<?php if ( ! $tracking && $promise && ! $unhappy ) : ?>
				<p class="ssd-panel__promise"><?php echo esc_html( $promise ); ?></p>
			<?php endif; ?>

			<?php $timeline = array_reverse( $shipment->timeline() ); ?>

			<?php if ( $timeline ) : ?>
				<ul class="ssd-timeline">
					<?php foreach ( $timeline as $event ) : ?>
						<li>
							<time datetime="<?php echo esc_attr( gmdate( 'c', (int) $event['time'] ) ); ?>">
								<?php echo esc_html( wp_date( 'j M, g:ia', (int) $event['time'] ) ); ?>
							</time>
							<strong><?php echo esc_html( ssd_status_label( $event['status'] ) ); ?></strong>
							<?php if ( ! empty( $event['location'] ) ) : ?>
								<span class="ssd-timeline__where"><?php echo esc_html( $event['location'] ); ?></span>
							<?php endif; ?>
							<?php if ( ! empty( $event['note'] ) ) : ?>
								<span class="ssd-timeline__note"><?php echo esc_html( $event['note'] ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $phone || $mail ) : ?>
				<p class="ssd-panel__help">
					<?php esc_html_e( 'Something not right?', 'sreesaanvika-delivery' ); ?>
					<?php if ( $phone ) : ?>
						<a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php endif; ?>
					<?php if ( $phone && $mail ) : ?> · <?php endif; ?>
					<?php if ( $mail ) : ?>
						<a href="mailto:<?php echo esc_attr( $mail ); ?>"><?php echo esc_html( $mail ); ?></a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}
}
