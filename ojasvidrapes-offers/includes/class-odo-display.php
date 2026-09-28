<?php
/**
 * Telling the shopper the offer exists, and how close they are to it.
 *
 * @package OjasviDrapesOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end output.
 */
class ODO_Display {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );

		// Product page, under the price.
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'on_product' ), 11 );

		// Cart and checkout.
		add_action( 'woocommerce_before_cart', array( __CLASS__, 'on_cart' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'on_cart' ), 5 );

		// The free line itself.
		add_filter( 'woocommerce_cart_item_subtotal', array( __CLASS__, 'line_subtotal' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'line_name' ), 10, 3 );
		add_filter( 'woocommerce_cart_item_name', array( __CLASS__, 'look_name' ), 11, 3 );

		// A badge on product cards.
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'on_card' ), 15 );

		add_shortcode( 'od_offer', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Styles and the countdown script, only where an offer is running.
	 */
	public static function assets() {
		// Registered everywhere, printed only where something asks for it.
		wp_register_style( 'odo', ODO_URI . 'assets/odo.css', array(), ODO_VERSION );
		wp_register_script( 'odo', ODO_URI . 'assets/odo.js', array(), ODO_VERSION, true );

		if ( ODO_Offer::live() ) {
			wp_enqueue_style( 'odo' );
			wp_enqueue_script( 'odo' );
		}

		wp_localize_script(
			'odo',
			'odoData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'hours'   => __( 'Hours', 'ojasvidrapes-offers' ),
					'minutes' => __( 'Minutes', 'ojasvidrapes-offers' ),
					'seconds' => __( 'Seconds', 'ojasvidrapes-offers' ),
					'ended'   => __( 'This offer has ended.', 'ojasvidrapes-offers' ),
					'total'   => __( 'Total for %d pieces', 'ojasvidrapes-offers' ),
					'one'     => __( 'This piece only', 'ojasvidrapes-offers' ),
					'save'    => __( 'You save %s', 'ojasvidrapes-offers' ),
					'adding'  => __( 'Adding…', 'ojasvidrapes-offers' ),
					'added'   => __( 'Added', 'ojasvidrapes-offers' ),
					'error'   => __( 'That did not work. Please try again.', 'ojasvidrapes-offers' ),
					'viewBag' => __( 'View bag', 'ojasvidrapes-offers' ),
				),
			)
		);
	}

	/* ---------------------------------------------------------------------
	 * Product page and cards
	 * ------------------------------------------------------------------ */

	/**
	 * The offer strip under the price on a product page.
	 */
	public static function on_product() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		foreach ( ODO_Offer::for_product( $product->get_id() ) as $offer ) {
			echo self::banner( $offer, 'product' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * A small flag on the product card.
	 */
	public static function on_card() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$offers = ODO_Offer::for_product( $product->get_id() );

		if ( ! $offers ) {
			return;
		}

		printf(
			'<span class="odo-flag">%s</span>',
			esc_html( $offers[0]->headline() )
		);
	}

	/* ---------------------------------------------------------------------
	 * Cart
	 * ------------------------------------------------------------------ */

	/**
	 * Every running offer at the top of the cart, with progress.
	 */
	public static function on_cart() {
		if ( ! WC()->cart ) {
			return;
		}

		$progress = ODO_Cart::progress();

		// Only force a pass when nothing has calculated the cart yet.
		if ( ! $progress ) {
			WC()->cart->calculate_totals();
			$progress = ODO_Cart::progress();
		}

		foreach ( ODO_Offer::live() as $offer ) {
			$state = isset( $progress[ $offer->id() ] ) ? $progress[ $offer->id() ] : null;

			// Do not shout about an offer the shopper has nothing towards.
			if ( ! $state || $state['have'] < 1 ) {
				continue;
			}

			echo self::banner( $offer, 'cart', $state ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * Strike through a discounted line and say what happened to it.
	 *
	 * @param string $html  Current subtotal html.
	 * @param array  $item  Cart item.
	 * @param string $key   Cart item key.
	 * @return string
	 */
	public static function line_subtotal( $html, $item, $key ) {
		$applied = ODO_Cart::applied_to( $key );

		if ( ! $applied || $applied['saved'] <= 0 ) {
			return $html;
		}

		$qty  = (int) $item['quantity'];
		$full = wc_price( $applied['unit'] * $qty );

		return '<del aria-hidden="true">' . $full . '</del> <ins class="odo-line-now">' . $html . '</ins>';
	}

	/**
	 * A note beside the product name on a discounted line.
	 *
	 * @param string $name Current name html.
	 * @param array  $item Cart item.
	 * @param string $key  Cart item key.
	 * @return string
	 */
	public static function line_name( $name, $item, $key ) {
		$applied = ODO_Cart::applied_to( $key );

		if ( ! $applied ) {
			return $name;
		}

		$qty  = (int) $item['quantity'];
		$free = (int) $applied['free'];

		// A quantity break has no free unit — it discounts the whole line.
		if ( $free < 1 ) {
			if ( empty( $applied['look'] ) && $applied['saved'] > 0 ) {
				return $name . '<span class="odo-line-flag">'
					. esc_html__( 'Quantity discount applied', 'ojasvidrapes-offers' )
					. '</span>';
			}

			return $name;
		}

		$label = ( $free >= $qty )
			? __( 'Free with this offer', 'ojasvidrapes-offers' )
			: sprintf(
				/* translators: 1: number free, 2: quantity on the line */
				__( '%1$d of %2$d free with this offer', 'ojasvidrapes-offers' ),
				$free,
				$qty
			);

		return $name . '<span class="odo-line-flag">' . esc_html( $label ) . '</span>';
	}

	/**
	 * The matching-piece discount gets its own note.
	 *
	 * @param string $name Current name html.
	 * @param array  $item Cart item.
	 * @param string $key  Cart item key.
	 * @return string
	 */
	public static function look_name( $name, $item, $key ) {
		unset( $item );

		$applied = ODO_Cart::applied_to( $key );

		if ( ! $applied || empty( $applied['look'] ) || $applied['saved'] <= 0 ) {
			return $name;
		}

		return $name . '<span class="odo-line-flag odo-line-flag--look">'
			. esc_html__( 'Matching piece discount', 'ojasvidrapes-offers' )
			. '</span>';
	}

	/* ---------------------------------------------------------------------
	 * The banner
	 * ------------------------------------------------------------------ */

	/**
	 * [od_offer id="12"] — the banner anywhere.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'od_offer' );
		$out  = '';

		foreach ( ODO_Offer::live() as $offer ) {
			if ( $atts['id'] && (int) $atts['id'] !== $offer->id() ) {
				continue;
			}

			$out .= self::banner( $offer, 'standalone' );
		}

		return $out;
	}

	/**
	 * Draw one offer.
	 *
	 * @param ODO_Offer  $offer Offer.
	 * @param string     $where product|cart|standalone.
	 * @param array|null $state Cart progress, when there is any.
	 * @return string
	 */
	public static function banner( $offer, $where = 'product', $state = null ) {
		wp_enqueue_style( 'odo' );
		wp_enqueue_script( 'odo' );

		$ends = $offer->shows_countdown() ? $offer->ends_at() : 0;

		ob_start();
		?>
		<section class="odo-banner odo-banner--<?php echo esc_attr( $where ); ?>">
			<span class="odo-banner__tab" aria-hidden="true">
				<span class="odo-banner__pct">%</span>
			</span>

			<div class="odo-banner__body">
				<p class="odo-banner__head"><?php echo esc_html( $offer->headline() ); ?></p>

				<?php if ( $offer->subline() ) : ?>
					<p class="odo-banner__sub"><?php echo esc_html( $offer->subline() ); ?></p>
				<?php endif; ?>

				<?php if ( 'tiers' === $offer->kind() && $offer->tiers() ) : ?>
					<ul class="odo-tiers">
						<?php foreach ( array_reverse( $offer->tiers() ) as $tier ) : ?>
							<li>
								<span class="odo-tiers__qty">
									<?php
									printf(
										/* translators: %d: quantity */
										esc_html__( 'Buy %d', 'ojasvidrapes-offers' ),
										(int) $tier['qty']
									);
									?>
								</span>
								<span class="odo-tiers__off">
									<?php
									printf(
										/* translators: %d: percentage off */
										esc_html__( 'save %d%%', 'ojasvidrapes-offers' ),
										(int) $tier['percent']
									);
									?>
								</span>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $state ) : ?>
					<p class="odo-banner__progress">
						<?php
						if ( 'tiers' === $offer->kind() ) {
							if ( $state['saved'] > 0 ) {
								printf(
									/* translators: %s: the amount saved */
									esc_html__( 'You are saving %s', 'ojasvidrapes-offers' ),
									wp_kses_post( wc_price( $state['saved'] ) )
								);
							}
						} elseif ( $state['free'] > 0 && $state['saved'] > 0 ) {
							printf(
								/* translators: 1: how many are free, 2: the amount saved */
								esc_html( _n( '%1$d item free — you are saving %2$s', '%1$d items free — you are saving %2$s', (int) $state['free'], 'ojasvidrapes-offers' ) ),
								(int) $state['free'],
								wp_kses_post( wc_price( $state['saved'] ) )
							);
						} elseif ( $state['need'] > 0 ) {
							printf(
								/* translators: %d: how many more to add */
								esc_html( _n( 'Add %d more to get one free', 'Add %d more to get one free', (int) $state['need'], 'ojasvidrapes-offers' ) ),
								(int) $state['need']
							);
						}
						?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( $ends ) : ?>
				<div class="odo-countdown" data-ends="<?php echo esc_attr( $ends ); ?>">
					<p class="odo-countdown__label"><?php esc_html_e( 'Hurry, offer ends in', 'ojasvidrapes-offers' ); ?></p>
					<div class="odo-countdown__clock" aria-live="off"></div>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}
}
