<?php
/**
 * The endpoint the delivery app pushes scans to.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST routes for reading and writing a shipment.
 */
class SSD_REST {

	const NS = 'sreesaanvika-delivery/v1';

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	/**
	 * Register the routes.
	 */
	public static function routes() {
		register_rest_route(
			self::NS,
			'/shipment',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'read' ),
					'permission_callback' => array( __CLASS__, 'authorised' ),
					'args'                => self::locator_args(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'write' ),
					'permission_callback' => array( __CLASS__, 'authorised' ),
					'args'                => self::write_args(),
				),
			)
		);

		// A convenience for apps that would rather push a batch.
		register_rest_route(
			self::NS,
			'/shipments',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'write_many' ),
				'permission_callback' => array( __CLASS__, 'authorised' ),
			)
		);
	}

	/**
	 * How an order is named in a request.
	 *
	 * @return array
	 */
	protected static function locator_args() {
		return array(
			'order_id'     => array(
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
			'order_number' => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Everything a push may carry.
	 *
	 * @return array
	 */
	protected static function write_args() {
		return array_merge(
			self::locator_args(),
			array(
				'status'   => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
				'tracking' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'courier'  => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_key',
				),
				'eta'      => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'location' => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'note'     => array(
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'time'     => array(
					'type' => 'string',
				),
			)
		);
	}

	/**
	 * Only a request carrying the shop's key gets in.
	 *
	 * A shop manager signed in to WordPress is let through too, so the
	 * endpoint can be tried from the browser without copying the key about.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public static function authorised( $request ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			return true;
		}

		$expected = (string) get_option( 'ssd_api_key', '' );

		if ( '' === $expected ) {
			return new WP_Error(
				'ssd_no_key',
				__( 'No delivery key is set on this shop yet.', 'sreesaanvika-delivery' ),
				array( 'status' => 503 )
			);
		}

		$given = (string) $request->get_header( 'x_ssd_key' );

		if ( '' === $given ) {
			$given = (string) $request->get_param( 'key' );
		}

		// Constant time, so the key cannot be guessed a character at a time.
		if ( '' !== $given && hash_equals( $expected, $given ) ) {
			return true;
		}

		return new WP_Error(
			'ssd_forbidden',
			__( 'That key is not right.', 'sreesaanvika-delivery' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Resolve the order a request is about.
	 *
	 * @param WP_REST_Request|array $request Request or plain array.
	 * @return SSD_Shipment|WP_Error
	 */
	protected static function locate( $request ) {
		$id     = 0;
		$number = '';

		if ( $request instanceof WP_REST_Request ) {
			$id     = absint( $request->get_param( 'order_id' ) );
			$number = (string) $request->get_param( 'order_number' );
		} else {
			$id     = isset( $request['order_id'] ) ? absint( $request['order_id'] ) : 0;
			$number = isset( $request['order_number'] ) ? (string) $request['order_number'] : '';
		}

		$shipment = $id ? SSD_Shipment::get( $id ) : SSD_Shipment::find_by_number( $number );

		if ( ! $shipment ) {
			return new WP_Error(
				'ssd_not_found',
				__( 'No order with that id or number.', 'sreesaanvika-delivery' ),
				array( 'status' => 404 )
			);
		}

		return $shipment;
	}

	/**
	 * Read one shipment.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function read( $request ) {
		$shipment = self::locate( $request );

		if ( is_wp_error( $shipment ) ) {
			return $shipment;
		}

		return rest_ensure_response( $shipment->to_array() );
	}

	/**
	 * Apply one push.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function write( $request ) {
		$shipment = self::locate( $request );

		if ( is_wp_error( $shipment ) ) {
			return $shipment;
		}

		$result = self::apply( $shipment, $request->get_params() );

		return rest_ensure_response(
			array(
				'changed'  => $result,
				'shipment' => $shipment->to_array(),
			)
		);
	}

	/**
	 * Apply a batch. Each entry is reported on separately so one bad order
	 * number does not throw away the rest of the run.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function write_many( $request ) {
		$rows = $request->get_param( 'shipments' );
		$out  = array();

		if ( ! is_array( $rows ) ) {
			return new WP_Error(
				'ssd_bad_batch',
				__( 'Send a shipments array.', 'sreesaanvika-delivery' ),
				array( 'status' => 400 )
			);
		}

		foreach ( array_slice( $rows, 0, 200 ) as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$shipment = self::locate( $row );

			if ( is_wp_error( $shipment ) ) {
				$out[] = array(
					'order_number' => isset( $row['order_number'] ) ? sanitize_text_field( $row['order_number'] ) : '',
					'error'        => $shipment->get_error_message(),
				);
				continue;
			}

			$out[] = array(
				'order_id' => $shipment->order()->get_id(),
				'changed'  => self::apply( $shipment, $row ),
				'status'   => $shipment->status(),
			);
		}

		return rest_ensure_response( array( 'results' => $out ) );
	}

	/**
	 * Hand one row of pushed data to the shipment.
	 *
	 * @param SSD_Shipment $shipment Shipment.
	 * @param array        $data     Raw request data.
	 * @return bool
	 */
	protected static function apply( $shipment, array $data ) {
		$update = array();

		foreach ( array( 'status', 'courier', 'location', 'note', 'eta' ) as $key ) {
			if ( isset( $data[ $key ] ) ) {
				$update[ $key ] = $data[ $key ];
			}
		}

		// Accept the courier's own field names as well as ours.
		foreach ( array( 'tracking', 'tracking_id', 'awb', 'waybill' ) as $key ) {
			if ( isset( $data[ $key ] ) && '' !== $data[ $key ] ) {
				$update['tracking'] = $data[ $key ];
				break;
			}
		}

		if ( ! empty( $data['time'] ) ) {
			$time = is_numeric( $data['time'] ) ? (int) $data['time'] : strtotime( (string) $data['time'] );

			if ( $time ) {
				$update['time'] = $time;
			}
		}

		return $shipment->update( $update, __( 'the delivery app', 'sreesaanvika-delivery' ) );
	}
}
