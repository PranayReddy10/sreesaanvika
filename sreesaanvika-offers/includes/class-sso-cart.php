<?php
/**
 * The engine: works out what is free, and makes it free.
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Applies offers to the cart.
 */
class SSO_Cart {

	/**
	 * What the last run worked out, keyed by cart item key.
	 *
	 * @var array<string,array{offer:int,free:int,saved:float,unit:float}>
	 */
	protected static $applied = array();

	/**
	 * Per-offer progress, keyed by offer id.
	 *
	 * @var array<int,array{have:int,need:int,free:int,saved:float}>
	 */
	protected static $progress = array();

	/**
	 * Hook up.
	 */
	public static function init() {
		// Late, so anything that sets a price of its own has already run.
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'apply' ), 100 );

		// Keep a record on the order of what the customer was given.
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'stamp_order_item' ), 10, 3 );
	}

	/**
	 * Note the offer on the order line, so the shop can see later why a saree
	 * went out at nothing.
	 *
	 * @param WC_Order_Item_Product $line Order line.
	 * @param string                $key  Cart item key.
	 * @param array                 $item Cart item.
	 */
	public static function stamp_order_item( $line, $key, $item ) {
		unset( $item );

		$applied = self::applied_to( $key );

		if ( ! $applied || $applied['saved'] <= 0 ) {
			return;
		}

		$offer = new SSO_Offer( $applied['offer'] );

		$line->add_meta_data( __( 'Offer', 'sreesaanvika-offers' ), $offer->name() ? $offer->name() : $offer->headline(), true );
		$line->add_meta_data( '_sso_free_units', (int) $applied['free'], true );
		$line->add_meta_data( '_sso_saved', wc_format_decimal( $applied['saved'] ), true );
	}

	/**
	 * What the last calculation decided for one cart line.
	 *
	 * @param string $key Cart item key.
	 * @return array|null
	 */
	public static function applied_to( $key ) {
		return isset( self::$applied[ $key ] ) ? self::$applied[ $key ] : null;
	}

	/**
	 * How each offer is doing in this cart.
	 *
	 * @return array
	 */
	public static function progress() {
		return self::$progress;
	}

	/**
	 * The price one unit of a cart line really costs, before we touch it.
	 *
	 * Read from a freshly loaded product rather than the object in the cart,
	 * because WooCommerce can run the totals more than once per request and
	 * the cart's object still carries the price we set last time round.
	 *
	 * @param array $item Cart item.
	 * @return float
	 */
	protected static function base_price( $item ) {
		$id      = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];
		$product = wc_get_product( $id );

		if ( ! $product ) {
			return 0.0;
		}

		/**
		 * The undiscounted unit price an offer works from.
		 *
		 * @param float $price   Unit price.
		 * @param array $item    Cart item.
		 */
		return (float) apply_filters( 'sso_base_price', (float) $product->get_price(), $item );
	}

	/**
	 * Work out and apply every live offer.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public static function apply( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		self::$applied  = array();
		self::$progress = array();

		$contents = $cart->get_cart();

		if ( ! $contents ) {
			return;
		}

		// Discounts to grant, gathered across offers, keyed by cart item.
		$free = array();

		/*
		 * Offers first, then the Complete the look pairings — which apply on
		 * their own even when no offer is running, so this must not bail out
		 * early on an empty offer list.
		 */
		foreach ( SSO_Offer::live() as $offer ) {
			$units = array();

			foreach ( $contents as $key => $item ) {
				$id = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];

				if ( ! $offer->covers( $id ) ) {
					continue;
				}

				$price = self::base_price( $item );
				$qty   = (int) $item['quantity'];

				// One entry per unit, because the offer counts items and not lines.
				for ( $i = 0; $i < $qty; $i++ ) {
					$units[] = array(
						'key'   => $key,
						'price' => $price,
					);
				}
			}

			$have  = count( $units );
			$group = $offer->group_size();
			$rounds = $group > 0 ? (int) floor( $have / $group ) : 0;

			if ( ! $offer->repeats() ) {
				$rounds = min( 1, $rounds );
			}

			$give = $rounds * $offer->get();

			self::$progress[ $offer->id() ] = array(
				'offer' => $offer,
				'have'  => $have,
				'need'  => $have > 0 && 0 === $rounds ? $group - $have : ( $group - ( $have % $group ) ) % $group,
				'free'  => $give,
				'saved' => 0.0,
			);

			if ( $give < 1 ) {
				continue;
			}

			// The cheapest go free — that is the promise on the banner.
			usort(
				$units,
				function ( $a, $b ) {
					return $a['price'] <=> $b['price'];
				}
			);

			$percent = $offer->percent() / 100;

			foreach ( array_slice( $units, 0, $give ) as $unit ) {
				$key = $unit['key'];

				if ( ! isset( $free[ $key ] ) ) {
					$free[ $key ] = array(
						'units'   => 0,
						'off'     => 0.0,
						'offer'   => $offer->id(),
						'percent' => $percent,
					);
				}

				$free[ $key ]['units']++;
				$free[ $key ]['off'] += $unit['price'] * $percent;

				self::$progress[ $offer->id() ]['saved'] += $unit['price'] * $percent;
			}
		}

		self::add_bundle_discounts( $contents, $free );

		if ( ! $free ) {
			return;
		}

		foreach ( $free as $key => $grant ) {
			if ( ! isset( $contents[ $key ] ) ) {
				continue;
			}

			$item = $contents[ $key ];
			$qty  = (int) $item['quantity'];

			if ( $qty < 1 ) {
				continue;
			}

			$base = self::base_price( $item );
			$line = ( $base * $qty ) - $grant['off'];
			$line = max( 0, $line );

			/*
			 * WooCommerce carries one price per line, so a line with two of
			 * something and one of them free is priced at the average. The
			 * line total is exactly right, and the cart says "1 of 2 free" so
			 * the unit price is never a surprise.
			 */
			$item['data']->set_price( $line / $qty );

			self::$applied[ $key ] = array(
				'offer' => $grant['offer'],
				'free'  => $grant['units'],
				'saved' => ( $base * $qty ) - $line,
				'unit'  => $base,
				'look'  => ! empty( $grant['look'] ),
			);
		}
	}

	/**
	 * "Complete the look": take the pairing discount off the matching pieces
	 * when the product they were chosen for is in the same cart.
	 *
	 * Anything an offer has already made free is left alone, so the two never
	 * stack into a negative line.
	 *
	 * @param array $contents Cart contents.
	 * @param array $free     Discounts gathered so far, by cart item key.
	 */
	protected static function add_bundle_discounts( array $contents, array &$free ) {
		if ( ! class_exists( 'SSO_Bundle' ) ) {
			return;
		}

		// Which product is on which cart line.
		$lines = array();

		foreach ( $contents as $key => $item ) {
			$lines[ (int) $item['product_id'] ][] = $key;
		}

		foreach ( $contents as $item ) {
			$lead    = (int) $item['product_id'];
			$percent = SSO_Bundle::percent( $lead );

			if ( $percent <= 0 ) {
				continue;
			}

			foreach ( SSO_Bundle::partners( $lead ) as $partner ) {
				if ( empty( $lines[ $partner ] ) ) {
					continue;
				}

				foreach ( $lines[ $partner ] as $key ) {
					// An offer already handled this line; do not discount twice.
					if ( isset( $free[ $key ] ) ) {
						continue;
					}

					$base = self::base_price( $contents[ $key ] );
					$qty  = (int) $contents[ $key ]['quantity'];

					$free[ $key ] = array(
						'units'   => 0,
						'off'     => $base * $qty * ( $percent / 100 ),
						'offer'   => 0,
						'percent' => $percent / 100,
						'look'    => true,
					);
				}
			}
		}
	}
}
