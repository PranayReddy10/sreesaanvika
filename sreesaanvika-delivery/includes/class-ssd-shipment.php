<?php
/**
 * One order's shipment: where it is, and how it got there.
 *
 * @package SreeSaanvikaDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the shipment stored against a WooCommerce order.
 *
 * Everything goes through the order object rather than post meta directly, so
 * it works the same on a High-Performance Order Storage shop.
 */
class SSD_Shipment {

	const TRACKING = '_ssd_tracking';
	const COURIER  = '_ssd_courier';
	const STATUS   = '_ssd_status';
	const TIMELINE = '_ssd_timeline';
	const ETA      = '_ssd_eta';
	const UPDATED  = '_ssd_updated';

	/**
	 * The order.
	 *
	 * @var WC_Order
	 */
	protected $order;

	/**
	 * Constructor.
	 *
	 * @param WC_Order|int $order Order or id.
	 */
	public function __construct( $order ) {
		$this->order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	}

	/**
	 * Load a shipment, or null when the order does not exist.
	 *
	 * @param WC_Order|int $order Order or id.
	 * @return SSD_Shipment|null
	 */
	public static function get( $order ) {
		$shipment = new self( $order );

		return $shipment->exists() ? $shipment : null;
	}

	/**
	 * Find an order by the number the customer sees.
	 *
	 * On most shops that is the order id. A shop running a sequential-order-
	 * number plugin renumbers them, and both of the popular ones keep the
	 * customer-facing number in _order_number — so that is looked up before
	 * falling back to treating the number as an id.
	 *
	 * @param string $number Order number as the customer sees it.
	 * @return SSD_Shipment|null
	 */
	public static function find_by_number( $number ) {
		$number = trim( ltrim( (string) $number, '#' ) );

		if ( '' === $number ) {
			return null;
		}

		/**
		 * Resolve an order number to an id yourself.
		 *
		 * @param int    $id     Zero until something resolves it.
		 * @param string $number The number as typed.
		 */
		$id = (int) apply_filters( 'ssd_order_id_from_number', 0, $number );

		if ( ! $id ) {
			$found = wc_get_orders(
				array(
					'limit'      => 1,
					'return'     => 'ids',
					'status'     => 'any',
					'meta_key'   => '_order_number', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value' => $number, // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);

			if ( $found ) {
				$id = (int) $found[0];
			}
		}

		if ( ! $id && is_numeric( $number ) ) {
			$id = absint( $number );
		}

		if ( ! $id ) {
			return null;
		}

		return self::get( $id );
	}

	/**
	 * Does the order exist?
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->order instanceof WC_Order;
	}

	/**
	 * The order.
	 *
	 * @return WC_Order
	 */
	public function order() {
		return $this->order;
	}

	/**
	 * Consignment number.
	 *
	 * @return string
	 */
	public function tracking() {
		return (string) $this->order->get_meta( self::TRACKING );
	}

	/**
	 * Courier slug, falling back to the shop's usual one.
	 *
	 * @return string
	 */
	public function courier() {
		$slug = (string) $this->order->get_meta( self::COURIER );

		return $slug ? $slug : (string) get_option( 'ssd_courier', 'delhivery' );
	}

	/**
	 * Courier name for display.
	 *
	 * @return string
	 */
	public function courier_name() {
		$couriers = ssd_couriers();
		$slug     = $this->courier();

		if ( 'other' === $slug || ! isset( $couriers[ $slug ] ) ) {
			$custom = (string) get_option( 'ssd_courier_name', '' );

			return $custom ? $custom : __( 'Our courier', 'sreesaanvika-delivery' );
		}

		return $couriers[ $slug ]['name'];
	}

	/**
	 * Where the customer can follow the parcel on the courier's own site.
	 *
	 * @return string
	 */
	public function tracking_url() {
		$tracking = $this->tracking();

		if ( ! $tracking ) {
			return '';
		}

		$couriers = ssd_couriers();
		$slug     = $this->courier();
		$pattern  = (string) get_option( 'ssd_tracking_url', '' );

		if ( ! $pattern && isset( $couriers[ $slug ] ) ) {
			$pattern = $couriers[ $slug ]['url'];
		}

		if ( ! $pattern ) {
			return '';
		}

		return esc_url_raw( str_replace( '{tracking}', rawurlencode( $tracking ), $pattern ) );
	}

	/**
	 * Current stage.
	 *
	 * @return string
	 */
	public function status() {
		$status = (string) $this->order->get_meta( self::STATUS );

		return $status ? $status : 'pending';
	}

	/**
	 * Current stage, in words.
	 *
	 * @return string
	 */
	public function status_label() {
		return ssd_status_label( $this->status() );
	}

	/**
	 * Expected delivery date, as stored (Y-m-d) or empty.
	 *
	 * @return string
	 */
	public function eta() {
		return (string) $this->order->get_meta( self::ETA );
	}

	/**
	 * When the shipment last changed, as a timestamp.
	 *
	 * @return int
	 */
	public function updated() {
		return (int) $this->order->get_meta( self::UPDATED );
	}

	/**
	 * Every recorded event, oldest first.
	 *
	 * @return array<int,array{status:string,note:string,location:string,time:int}>
	 */
	public function timeline() {
		$events = $this->order->get_meta( self::TIMELINE );

		return is_array( $events ) ? $events : array();
	}

	/**
	 * Has anything been shipped yet?
	 *
	 * @return bool
	 */
	public function is_shipped() {
		return in_array( $this->status(), array( 'dispatched', 'transit', 'out', 'delivered' ), true );
	}

	/**
	 * Record a change.
	 *
	 * Only what is passed is touched, so a status push that carries no tracking
	 * number will not wipe the one already there.
	 *
	 * @param array $data   status, tracking, courier, eta, note, location, time.
	 * @param string $source Who made the change, for the order note.
	 * @return bool Whether anything actually changed.
	 */
	public function update( array $data, $source = '' ) {
		if ( ! $this->exists() ) {
			return false;
		}

		$before  = $this->status();
		$changed = false;

		if ( isset( $data['tracking'] ) ) {
			$tracking = sanitize_text_field( $data['tracking'] );

			if ( $tracking !== $this->tracking() ) {
				$this->order->update_meta_data( self::TRACKING, $tracking );
				$changed = true;
			}
		}

		if ( ! empty( $data['courier'] ) ) {
			$courier  = sanitize_key( $data['courier'] );
			$couriers = ssd_couriers();

			if ( isset( $couriers[ $courier ] ) && $courier !== (string) $this->order->get_meta( self::COURIER ) ) {
				$this->order->update_meta_data( self::COURIER, $courier );
				$changed = true;
			}
		}

		if ( isset( $data['eta'] ) ) {
			$eta = sanitize_text_field( $data['eta'] );

			if ( $eta !== $this->eta() ) {
				$this->order->update_meta_data( self::ETA, $eta );
				$changed = true;
			}
		}

		$status = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : '';
		$known  = ssd_statuses();

		if ( $status && isset( $known[ $status ] ) && $status !== $before ) {
			$this->order->update_meta_data( self::STATUS, $status );
			$changed = true;
		} elseif ( $status && ! isset( $known[ $status ] ) ) {
			$status = '';
		}

		$note     = isset( $data['note'] ) ? sanitize_text_field( $data['note'] ) : '';
		$location = isset( $data['location'] ) ? sanitize_text_field( $data['location'] ) : '';

		if ( $changed || $note || $location ) {
			$time = isset( $data['time'] ) ? absint( $data['time'] ) : 0;

			$timeline   = $this->timeline();
			$timeline[] = array(
				'status'   => $status ? $status : $this->status(),
				'note'     => $note,
				'location' => $location,
				'time'     => $time ? $time : time(),
				'source'   => sanitize_text_field( $source ),
			);

			// Keep it readable; a parcel does not need two hundred scans on file.
			if ( count( $timeline ) > 60 ) {
				$timeline = array_slice( $timeline, -60 );
			}

			$this->order->update_meta_data( self::TIMELINE, $timeline );
			$this->order->update_meta_data( self::UPDATED, time() );
			$this->order->save();

			$this->add_order_note( $status, $note, $location, $source );

			if ( $status && $status !== $before ) {
				/**
				 * A shipment moved on.
				 *
				 * @param SSD_Shipment $shipment The shipment.
				 * @param string       $status   The new status.
				 * @param string       $before   The previous status.
				 */
				do_action( 'ssd_status_changed', $this, $status, $before );

				$this->maybe_complete_order( $status );
			}

			return true;
		}

		return false;
	}

	/**
	 * Leave a trail on the order itself, where shop staff already look.
	 *
	 * @param string $status   New status.
	 * @param string $note     Free text.
	 * @param string $location Where the scan happened.
	 * @param string $source   Who reported it.
	 */
	protected function add_order_note( $status, $note, $location, $source ) {
		$parts = array();

		if ( $status ) {
			$parts[] = sprintf(
				/* translators: 1: courier name, 2: status */
				__( '%1$s: %2$s', 'sreesaanvika-delivery' ),
				$this->courier_name(),
				ssd_status_label( $status )
			);
		}

		if ( $location ) {
			$parts[] = $location;
		}

		if ( $note ) {
			$parts[] = $note;
		}

		if ( $this->tracking() ) {
			/* translators: %s: consignment number */
			$parts[] = sprintf( __( 'AWB %s', 'sreesaanvika-delivery' ), $this->tracking() );
		}

		if ( $source ) {
			/* translators: %s: where the update came from */
			$parts[] = sprintf( __( 'via %s', 'sreesaanvika-delivery' ), $source );
		}

		if ( $parts ) {
			$this->order->add_order_note( implode( ' · ', $parts ) );
		}
	}

	/**
	 * Optionally mark the WooCommerce order complete once it is delivered.
	 *
	 * @param string $status New status.
	 */
	protected function maybe_complete_order( $status ) {
		if ( 'delivered' !== $status || 'yes' !== get_option( 'ssd_complete_on_delivery', 'no' ) ) {
			return;
		}

		if ( ! $this->order->has_status( array( 'completed', 'refunded', 'cancelled' ) ) ) {
			$this->order->update_status( 'completed', __( 'Marked delivered by the courier.', 'sreesaanvika-delivery' ) );
		}
	}

	/**
	 * The shipment as plain data, for the REST response.
	 *
	 * @return array
	 */
	public function to_array() {
		return array(
			'order_id'     => $this->order->get_id(),
			'order_number' => $this->order->get_order_number(),
			'status'       => $this->status(),
			'status_label' => $this->status_label(),
			'tracking'     => $this->tracking(),
			'courier'      => $this->courier(),
			'courier_name' => $this->courier_name(),
			'tracking_url' => $this->tracking_url(),
			'eta'          => $this->eta(),
			'updated'      => $this->updated(),
			'timeline'     => $this->timeline(),
		);
	}
}
