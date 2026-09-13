<?php
/**
 * Telling the shopper the offer exists, and how close they are to it.
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front-end output.
 */
class SSO_Display {

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

		add_shortcode( 'ss_offer', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Styles and the countdown script, only where an offer is running.
	 */
	public static function assets() {
		// Registered everywhere, printed only where something asks for it.
		wp_register_style( 'sso', SSO_URI . 'assets/sso.css', array(), SSO_VERSION );
		wp_register_script( 'sso', SSO_URI . 'assets/sso.js', array(), SSO_VERSION, true );

		if ( SSO_Offer::live() ) {
			wp_enqueue_style( 'sso' );
			wp_enqueue_script( 'sso' );
		}

		wp_localize_script(
			'sso',
			'ssoData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n'    => array(
					'hours'   => __( 'Hours', 'sreesaanvika-offers' ),
					'minutes' => __( 'Minutes', 'sreesaanvika-offers' ),
					'seconds' => __( 'Seconds', 'sreesaanvika-offers' ),
					'ended'   => __( 'This offer has ended.', 'sreesaanvika-offers' ),
					'total'   => __( 'Total for %d pieces', 'sreesaanvika-offers' ),
					'one'     => __( 'This piece only', 'sreesaanvika-offers' ),
					'save'    => __( 'You save %s', 'sreesaanvika-offers' ),
					'adding'  => __( 'Adding…', 'sreesaanvika-offers' ),
					'added'   => __( 'Added', 'sreesaanvika-offers' ),
					'error'   => __( 'That did not work. Please try again.', 'sreesaanvika-offers' ),
					'viewBag' => __( 'View bag', 'sreesaanvika-offers' ),
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

		foreach ( SSO_Offer::for_product( $product->get_id() ) as $offer ) {
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

		$offers = SSO_Offer::for_product( $product->get_id() );

		if ( ! $offers ) {
			return;
		}

		printf(
			'<span class="sso-flag">%s</span>',
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

		$progress = SSO_Cart::progress();

		// Only force a pass when nothing has calculated the cart yet.
		if ( ! $progress ) {
			WC()->cart->calculate_totals();
			$progress = SSO_Cart::progress();
		}

		foreach ( SSO_Offer::live() as $offer ) {
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
		$applied = SSO_Cart::applied_to( $key );

		if ( ! $applied || $applied['saved'] <= 0 ) {
			return $html;
		}

		$qty  = (int) $item['quantity'];
		$full = wc_price( $applied['unit'] * $qty );

		return '<del aria-hidden="true">' . $full . '</del> <ins class="sso-line-now">' . $html . '</ins>';
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
		$applied = SSO_Cart::applied_to( $key );

		if ( ! $applied ) {
			return $name;
		}

		$qty  = (int) $item['quantity'];
		$free = (int) $applied['free'];

		if ( $free < 1 ) {
			return $name;
		}

		$label = ( $free >= $qty )
			? __( 'Free with this offer', 'sreesaanvika-offers' )
			: sprintf(
				/* translators: 1: number free, 2: quantity on the line */
				__( '%1$d of %2$d free with this offer', 'sreesaanvika-offers' ),
				$free,
				$qty
			);

		return $name . '<span class="sso-line-flag">' . esc_html( $label ) . '</span>';
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

		$applied = SSO_Cart::applied_to( $key );

		if ( ! $applied || empty( $applied['look'] ) || $applied['saved'] <= 0 ) {
			return $name;
		}

		return $name . '<span class="sso-line-flag sso-line-flag--look">'
			. esc_html__( 'Matching piece discount', 'sreesaanvika-offers' )
			. '</span>';
	}

	/* ---------------------------------------------------------------------
	 * The banner
	 * ------------------------------------------------------------------ */

	/**
	 * [ss_offer id="12"] — the banner anywhere.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => 0 ), $atts, 'ss_offer' );
		$out  = '';

		foreach ( SSO_Offer::live() as $offer ) {
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
	 * @param SSO_Offer  $offer Offer.
	 * @param string     $where product|cart|standalone.
	 * @param array|null $state Cart progress, when there is any.
	 * @return string
	 */
	public static function banner( $offer, $where = 'product', $state = null ) {
		wp_enqueue_style( 'sso' );
		wp_enqueue_script( 'sso' );

		$ends = $offer->shows_countdown() ? $offer->ends_at() : 0;

		ob_start();
		?>
		<section class="sso-banner sso-banner--<?php echo esc_attr( $where ); ?>">
			<span class="sso-banner__tab" aria-hidden="true">
				<span class="sso-banner__pct">%</span>
			</span>

			<div class="sso-banner__body">
				<p class="sso-banner__head"><?php echo esc_html( $offer->headline() ); ?></p>

				<?php if ( $offer->subline() ) : ?>
					<p class="sso-banner__sub"><?php echo esc_html( $offer->subline() ); ?></p>
				<?php endif; ?>

				<?php if ( $state ) : ?>
					<p class="sso-banner__progress">
						<?php
						if ( $state['free'] > 0 && $state['saved'] > 0 ) {
							printf(
								/* translators: 1: how many are free, 2: the amount saved */
								esc_html( _n( '%1$d item free — you are saving %2$s', '%1$d items free — you are saving %2$s', (int) $state['free'], 'sreesaanvika-offers' ) ),
								(int) $state['free'],
								wp_kses_post( wc_price( $state['saved'] ) )
							);
						} elseif ( $state['need'] > 0 ) {
							printf(
								/* translators: %d: how many more to add */
								esc_html( _n( 'Add %d more to get one free', 'Add %d more to get one free', (int) $state['need'], 'sreesaanvika-offers' ) ),
								(int) $state['need']
							);
						}
						?>
					</p>
				<?php endif; ?>
			</div>

			<?php if ( $ends ) : ?>
				<div class="sso-countdown" data-ends="<?php echo esc_attr( $ends ); ?>">
					<p class="sso-countdown__label"><?php esc_html_e( 'Hurry, offer ends in', 'sreesaanvika-offers' ); ?></p>
					<div class="sso-countdown__clock" aria-live="off"></div>
				</div>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}
}
