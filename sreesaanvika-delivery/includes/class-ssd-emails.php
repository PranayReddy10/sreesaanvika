<?php
/**
 * Telling the customer, without waiting for them to come and look.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dispatch and delivery notifications.
 */
class SSD_Emails {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'ssd_status_changed', array( __CLASS__, 'on_change' ), 10, 3 );
	}

	/**
	 * Send the right note, if one is wanted.
	 *
	 * @param SSD_Shipment $shipment Shipment.
	 * @param string       $status   New status.
	 * @param string       $before   Previous status.
	 */
	public static function on_change( $shipment, $status, $before ) {
		unset( $before );

		if ( 'dispatched' === $status && 'yes' === get_option( 'ssd_email_dispatch', 'yes' ) ) {
			self::send( $shipment, 'dispatched' );
		}

		if ( 'delivered' === $status && 'yes' === get_option( 'ssd_email_delivered', 'yes' ) ) {
			self::send( $shipment, 'delivered' );
		}
	}

	/**
	 * Compose and send.
	 *
	 * @param SSD_Shipment $shipment Shipment.
	 * @param string       $kind     dispatched|delivered.
	 */
	protected static function send( $shipment, $kind ) {
		$order = $shipment->order();
		$to    = $order->get_billing_email();

		if ( ! $to ) {
			return;
		}

		$shop   = get_bloginfo( 'name' );
		$number = $order->get_order_number();
		$name   = $order->get_billing_first_name();

		if ( 'dispatched' === $kind ) {
			/* translators: 1: order number, 2: shop name */
			$subject = sprintf( __( 'Your order #%1$s is on its way — %2$s', 'sreesaanvika-delivery' ), $number, $shop );
			/* translators: %s: customer first name */
			$opening = sprintf( __( '%s, your parcel has left us.', 'sreesaanvika-delivery' ), $name ? $name : __( 'Hello', 'sreesaanvika-delivery' ) );
		} else {
			/* translators: 1: order number, 2: shop name */
			$subject = sprintf( __( 'Your order #%1$s has been delivered — %2$s', 'sreesaanvika-delivery' ), $number, $shop );
			/* translators: %s: customer first name */
			$opening = sprintf( __( '%s, your parcel has arrived.', 'sreesaanvika-delivery' ), $name ? $name : __( 'Hello', 'sreesaanvika-delivery' ) );
		}

		$lines = array( '<p>' . esc_html( $opening ) . '</p>' );

		if ( $shipment->tracking() ) {
			$url = $shipment->tracking_url();

			$lines[] = '<p>' . sprintf(
				/* translators: 1: courier name, 2: tracking number */
				esc_html__( '%1$s is carrying it, under tracking number %2$s.', 'sreesaanvika-delivery' ),
				esc_html( $shipment->courier_name() ),
				$url
					? '<a href="' . esc_url( $url ) . '">' . esc_html( $shipment->tracking() ) . '</a>'
					: '<strong>' . esc_html( $shipment->tracking() ) . '</strong>'
			) . '</p>';
		}

		$eta_at = $shipment->eta() ? strtotime( $shipment->eta() ) : 0;

		if ( 'dispatched' === $kind && $eta_at ) {
			$lines[] = '<p>' . sprintf(
				/* translators: %s: expected date */
				esc_html__( 'It should reach you around %s.', 'sreesaanvika-delivery' ),
				esc_html( date_i18n( get_option( 'date_format' ), $eta_at ) )
			) . '</p>';
		}

		$lines[] = '<p><a href="' . esc_url( $order->get_view_order_url() ) . '">'
			. esc_html__( 'See your order', 'sreesaanvika-delivery' ) . '</a></p>';

		$phone = (string) get_option( 'ssd_support_phone', '' );
		$mail  = (string) get_option( 'ssd_support_email', '' );

		if ( $phone || $mail ) {
			$lines[] = '<p>' . esc_html__( 'Any trouble, just reply or call us:', 'sreesaanvika-delivery' ) . ' '
				. esc_html( trim( $phone . ( $phone && $mail ? ' · ' : '' ) . $mail ) ) . '</p>';
		}

		$body = implode( "\n", $lines );

		/**
		 * The message body, so a shop can restyle it.
		 *
		 * @param string       $body     HTML body.
		 * @param SSD_Shipment $shipment Shipment.
		 * @param string       $kind     dispatched|delivered.
		 */
		$body = apply_filters( 'ssd_email_body', $body, $shipment, $kind );

		$mailer = WC()->mailer();
		$html   = $mailer->wrap_message( $subject, $body );

		$mailer->send( $to, $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}
