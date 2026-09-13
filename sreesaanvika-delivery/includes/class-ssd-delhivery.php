<?php
/**
 * Delhivery: ask them where the parcels are, on a schedule.
 *
 * This is for a shop whose courier cannot push to us. Where Delhivery can be
 * pointed at the REST endpoint instead, prefer that — it is immediate, and it
 * does not spend an API call on parcels that have not moved.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Polls Delhivery's tracking API for shipments still in flight.
 */
class SSD_Delhivery {

	const HOOK     = 'ssd_poll_delhivery';
	const ENDPOINT = 'https://track.delhivery.com/api/v1/packages/json/';

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( self::HOOK, array( __CLASS__, 'poll' ) );
		add_action( 'admin_post_ssd_poll_now', array( __CLASS__, 'poll_now' ) );
		add_action( 'update_option_ssd_poll_minutes', array( __CLASS__, 'reschedule' ) );
		add_action( 'update_option_ssd_delhivery_token', array( __CLASS__, 'reschedule' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'schedules' ) ); // phpcs:ignore WordPress.WP.CronInterval

		self::reschedule();
	}

	/**
	 * Add the intervals the shop can choose from.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public static function schedules( $schedules ) {
		foreach ( array( 15, 30 ) as $minutes ) {
			$schedules[ 'ssd_' . $minutes . 'min' ] = array(
				'interval' => $minutes * MINUTE_IN_SECONDS,
				/* translators: %d: number of minutes */
				'display'  => sprintf( __( 'Every %d minutes', 'sreesaanvika-delivery' ), $minutes ),
			);
		}

		return $schedules;
	}

	/**
	 * Is polling switched on and configured?
	 *
	 * @return bool
	 */
	public static function enabled() {
		return 'delhivery' === get_option( 'ssd_courier', 'delhivery' )
			&& '' !== trim( (string) get_option( 'ssd_delhivery_token', '' ) )
			&& 'off' !== get_option( 'ssd_poll_minutes', 'off' );
	}

	/**
	 * Put the schedule where the settings say it should be.
	 */
	public static function reschedule() {
		$next = wp_next_scheduled( self::HOOK );

		if ( ! self::enabled() ) {
			if ( $next ) {
				wp_unschedule_event( $next, self::HOOK );
			}

			return;
		}

		$choice = (string) get_option( 'ssd_poll_minutes', 'hourly' );
		$every  = in_array( $choice, array( '15', '30' ), true ) ? 'ssd_' . $choice . 'min' : 'hourly';

		// Already on the right schedule — leave it be.
		if ( $next && wp_get_schedule( self::HOOK ) === $every ) {
			return;
		}

		if ( $next ) {
			wp_unschedule_event( $next, self::HOOK );
		}

		wp_schedule_event( time() + MINUTE_IN_SECONDS, $every, self::HOOK );
	}

	/**
	 * Run a check from the settings page.
	 */
	public static function poll_now() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to do that.', 'sreesaanvika-delivery' ) );
		}

		check_admin_referer( 'ssd_poll_now' );

		set_transient( 'ssd_poll_result', self::poll(), 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=ssd-settings&ssd-polled=1' ) );
		exit;
	}

	/**
	 * The orders worth asking about: shipped, not finished, not ancient.
	 *
	 * @return SSD_Shipment[] keyed by waybill.
	 */
	protected static function in_flight() {
		$orders = wc_get_orders(
			array(
				'limit'        => 100,
				'orderby'      => 'date',
				'order'        => 'DESC',
				'status'       => array( 'processing', 'on-hold', 'completed' ),
				'date_created' => '>' . ( time() - 45 * DAY_IN_SECONDS ),
			)
		);

		$out = array();

		foreach ( $orders as $order ) {
			$shipment = new SSD_Shipment( $order );
			$waybill  = $shipment->tracking();

			if ( ! $waybill ) {
				continue;
			}

			// Nothing left to learn about these.
			if ( in_array( $shipment->status(), array( 'delivered', 'returned', 'cancelled' ), true ) ) {
				continue;
			}

			$out[ $waybill ] = $shipment;
		}

		return $out;
	}

	/**
	 * Ask Delhivery about every parcel still in flight.
	 *
	 * @return array{checked:int,updated:int,error:string}
	 */
	public static function poll() {
		$token = trim( (string) get_option( 'ssd_delhivery_token', '' ) );

		if ( ! $token ) {
			return array(
				'checked' => 0,
				'updated' => 0,
				'error'   => __( 'No Delhivery token saved.', 'sreesaanvika-delivery' ),
			);
		}

		$flight = self::in_flight();

		if ( ! $flight ) {
			return array(
				'checked' => 0,
				'updated' => 0,
				'error'   => '',
			);
		}

		$updated = 0;
		$error   = '';

		// Delhivery takes a comma separated list; keep the batches modest.
		foreach ( array_chunk( array_keys( $flight ), 25 ) as $batch ) {
			$response = wp_remote_get(
				add_query_arg(
					array(
						'waybill' => implode( ',', $batch ),
						'token'   => $token,
					),
					self::ENDPOINT
				),
				array(
					'timeout' => 25,
					'headers' => array( 'Accept' => 'application/json' ),
				)
			);

			if ( is_wp_error( $response ) ) {
				$error = $response->get_error_message();
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );

			if ( 200 !== $code ) {
				/* translators: %d: HTTP status code */
				$error = sprintf( __( 'Delhivery answered with %d. Check the token.', 'sreesaanvika-delivery' ), $code );
				continue;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( ! is_array( $body ) || empty( $body['ShipmentData'] ) ) {
				continue;
			}

			foreach ( $body['ShipmentData'] as $entry ) {
				if ( empty( $entry['Shipment'] ) ) {
					continue;
				}

				$parcel  = $entry['Shipment'];
				$waybill = isset( $parcel['AWB'] ) ? (string) $parcel['AWB'] : '';

				if ( ! $waybill || ! isset( $flight[ $waybill ] ) ) {
					continue;
				}

				if ( self::apply_parcel( $flight[ $waybill ], $parcel ) ) {
					$updated++;
				}
			}
		}

		return array(
			'checked' => count( $flight ),
			'updated' => $updated,
			'error'   => $error,
		);
	}

	/**
	 * Turn one Delhivery record into a shipment update.
	 *
	 * @param SSD_Shipment $shipment Shipment.
	 * @param array        $parcel   Delhivery's Shipment object.
	 * @return bool Whether anything changed.
	 */
	protected static function apply_parcel( $shipment, array $parcel ) {
		$state = isset( $parcel['Status'] ) && is_array( $parcel['Status'] ) ? $parcel['Status'] : array();

		$status = self::map_status(
			isset( $state['Status'] ) ? (string) $state['Status'] : '',
			isset( $state['StatusType'] ) ? (string) $state['StatusType'] : ''
		);

		$update = array(
			'location' => isset( $state['StatusLocation'] ) ? (string) $state['StatusLocation'] : '',
			'note'     => isset( $state['Instructions'] ) ? (string) $state['Instructions'] : '',
		);

		if ( $status ) {
			$update['status'] = $status;
		}

		if ( ! empty( $state['StatusDateTime'] ) ) {
			$when = strtotime( (string) $state['StatusDateTime'] );

			if ( $when ) {
				$update['time'] = $when;
			}
		}

		if ( ! empty( $parcel['ExpectedDeliveryDate'] ) ) {
			$eta = strtotime( (string) $parcel['ExpectedDeliveryDate'] );

			if ( $eta ) {
				$update['eta'] = gmdate( 'Y-m-d', $eta );
			}
		}

		return $shipment->update( $update, __( 'Delhivery', 'sreesaanvika-delivery' ) );
	}

	/**
	 * Delhivery's wording, in ours.
	 *
	 * Matched on the text first because it is the more specific of the two,
	 * then on the status type, which only says delivered, returned or still
	 * out. Anything unrecognised leaves the status alone rather than guessing
	 * — the scan is still recorded as a note.
	 *
	 * @param string $status Status text.
	 * @param string $type   Status type: UD, DL, RT and so on.
	 * @return string Our status slug, or an empty string.
	 */
	public static function map_status( $status, $type ) {
		$text = strtolower( trim( $status ) );

		/*
		 * Order matters: these are substring matches, so anything containing a
		 * shorter phrase has to be tested before it. "Undelivered" before
		 * "delivered", "not picked" before "picked".
		 */
		$words = array(
			'undelivered'      => 'failed',
			'not delivered'    => 'failed',
			'delivered'        => 'delivered',
			'rto'              => 'returned',
			'returned'         => 'returned',
			'canceled'         => 'cancelled',
			'cancelled'        => 'cancelled',
			'lost'             => 'failed',
			'damaged'          => 'failed',
			'out for delivery' => 'out',
			'dispatched'       => 'out',
			'in transit'       => 'transit',
			'pending'          => 'transit',
			'manifested'       => 'packed',
			'not picked'       => 'packed',
			'picked'           => 'dispatched',
		);

		foreach ( $words as $needle => $slug ) {
			if ( '' !== $text && false !== strpos( $text, $needle ) ) {
				return $slug;
			}
		}

		$types = array(
			'DL' => 'delivered',
			'RT' => 'returned',
			'UD' => 'transit',
			'PP' => 'packed',
		);

		$type = strtoupper( trim( $type ) );

		return isset( $types[ $type ] ) ? $types[ $type ] : '';
	}
}
