<?php
/**
 * Showing the matching products, and letting the whole look go in at once.
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Front end for Complete the look.
 */
class SSO_Bundle_Display {

	/**
	 * Hook up.
	 */
	public static function init() {
		// Under the product, ahead of the tabs.
		add_action( 'woocommerce_after_single_product_summary', array( __CLASS__, 'on_product' ), 9 );

		// Under the cart table.
		add_action( 'woocommerce_after_cart_table', array( __CLASS__, 'on_cart' ), 10 );

		add_action( 'wc_ajax_sso_add_bundle', array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_sso_add_bundle', array( __CLASS__, 'ajax_add' ) );
		add_action( 'wp_ajax_nopriv_sso_add_bundle', array( __CLASS__, 'ajax_add' ) );
	}

	/* ---------------------------------------------------------------------
	 * Product page
	 * ------------------------------------------------------------------ */

	/**
	 * The row under the product.
	 */
	public static function on_product() {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$matches = SSO_Bundle::matches( $product->get_id() );

		if ( ! $matches ) {
			return;
		}

		echo self::row( $product->get_id(), $matches ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * The picker: this product, then each match, with a running total.
	 *
	 * @param int   $lead_id Main product id.
	 * @param int[] $matches Matching product ids.
	 * @return string
	 */
	public static function row( $lead_id, array $matches ) {
		$lead = wc_get_product( $lead_id );

		if ( ! $lead ) {
			return '';
		}

		wp_enqueue_style( 'sso' );
		wp_enqueue_script( 'sso' );

		$percent = SSO_Bundle::percent( $lead_id );
		$items   = array();

		// The main product is always in, and cannot be unticked.
		$items[] = array(
			'product' => $lead,
			'fixed'   => true,
			'price'   => (float) wc_get_price_to_display( $lead ),
			'off'     => 0.0,
		);

		foreach ( $matches as $id ) {
			$match = wc_get_product( $id );

			if ( ! $match ) {
				continue;
			}

			$price = (float) wc_get_price_to_display( $match );

			$items[] = array(
				'product' => $match,
				'fixed'   => false,
				'price'   => $price,
				'off'     => $price * ( $percent / 100 ),
			);
		}

		if ( count( $items ) < 2 ) {
			return '';
		}

		ob_start();
		?>
		<section class="sso-look" data-look data-percent="<?php echo esc_attr( $percent ); ?>">
			<header class="sso-look__head">
				<h2><?php echo esc_html( SSO_Bundle::title( $lead_id ) ); ?></h2>

				<?php if ( $percent > 0 ) : ?>
					<span class="sso-look__deal">
						<?php
						printf(
							/* translators: %d: percentage off */
							esc_html__( '%d%% off the matching pieces when you take them together', 'sreesaanvika-offers' ),
							(int) $percent
						);
						?>
					</span>
				<?php endif; ?>
			</header>

			<div class="sso-look__body">
				<ul class="sso-look__items">
					<?php foreach ( $items as $n => $item ) : ?>
						<?php
						$item_product = $item['product'];
						$in_cart      = self::in_cart( $item_product->get_id() );
						?>
						<?php if ( $n > 0 ) : ?>
							<li class="sso-look__plus" aria-hidden="true">+</li>
						<?php endif; ?>

						<li class="sso-look__item<?php echo $item['fixed'] ? ' is-lead' : ''; ?>">
							<label>
								<input type="checkbox" class="sso-look__tick" value="<?php echo esc_attr( $item_product->get_id() ); ?>"
									data-price="<?php echo esc_attr( $item['price'] - $item['off'] ); ?>"
									data-full="<?php echo esc_attr( $item['price'] ); ?>"
									<?php checked( true ); ?>
									<?php disabled( $item['fixed'] || $in_cart ); ?> />

								<span class="sso-look__card">
									<span class="sso-look__thumb">
										<?php echo wp_kses_post( $item_product->get_image( 'woocommerce_thumbnail' ) ); ?>
										<?php if ( $item['fixed'] ) : ?>
											<span class="sso-look__badge"><?php esc_html_e( 'This piece', 'sreesaanvika-offers' ); ?></span>
										<?php elseif ( $in_cart ) : ?>
											<span class="sso-look__badge"><?php esc_html_e( 'In your bag', 'sreesaanvika-offers' ); ?></span>
										<?php endif; ?>
									</span>

									<span class="sso-look__name"><?php echo esc_html( $item_product->get_name() ); ?></span>

									<span class="sso-look__price">
										<?php if ( $item['off'] > 0 ) : ?>
											<del><?php echo wp_kses_post( wc_price( $item['price'] ) ); ?></del>
											<ins><?php echo wp_kses_post( wc_price( $item['price'] - $item['off'] ) ); ?></ins>
										<?php else : ?>
											<?php echo wp_kses_post( wc_price( $item['price'] ) ); ?>
										<?php endif; ?>
									</span>
								</span>
							</label>
						</li>
					<?php endforeach; ?>
				</ul>

				<aside class="sso-look__total">
					<p class="sso-look__count" data-look-count></p>
					<p class="sso-look__sum" data-look-sum></p>
					<p class="sso-look__save" data-look-save hidden></p>

					<button type="button" class="button alt ss-btn sso-look__add" data-look-add
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'sso_add_bundle' ) ); ?>">
						<?php esc_html_e( 'Add these to my bag', 'sreesaanvika-offers' ); ?>
					</button>

					<p class="sso-look__note" data-look-note hidden></p>
				</aside>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	/**
	 * Is this product already in the cart?
	 *
	 * @param int $product_id Product id.
	 * @return bool
	 */
	protected static function in_cart( $product_id ) {
		if ( ! WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $item ) {
			if ( (int) $item['product_id'] === (int) $product_id ) {
				return true;
			}
		}

		return false;
	}

	/* ---------------------------------------------------------------------
	 * Cart
	 * ------------------------------------------------------------------ */

	/**
	 * A rail of matches for whatever is in the bag.
	 */
	public static function on_cart() {
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$in_cart = array();
		$suggest = array();

		foreach ( WC()->cart->get_cart() as $item ) {
			$in_cart[] = (int) $item['product_id'];
		}

		foreach ( $in_cart as $id ) {
			foreach ( SSO_Bundle::matches( $id ) as $match ) {
				if ( in_array( $match, $in_cart, true ) || isset( $suggest[ $match ] ) ) {
					continue;
				}

				$suggest[ $match ] = $id;
			}
		}

		if ( ! $suggest ) {
			return;
		}

		wp_enqueue_style( 'sso' );
		wp_enqueue_script( 'sso' );

		$suggest = array_slice( $suggest, 0, 4, true );
		?>
		<section class="sso-goeswith">
			<h2>
				<?php
				echo esc_html(
					/**
					 * The heading over the cart's matching pieces.
					 *
					 * @param string $heading Heading text.
					 */
					apply_filters( 'sso_cart_matches_heading', __( 'Complete your look', 'sreesaanvika-offers' ) )
				);
				?>
			</h2>

			<p class="sso-goeswith__sub"><?php esc_html_e( 'Chosen to go with the pieces in your bag', 'sreesaanvika-offers' ); ?></p>

			<ul class="sso-goeswith__items">
				<?php foreach ( $suggest as $match_id => $lead_id ) : ?>
					<?php
					$match = wc_get_product( $match_id );

					if ( ! $match ) {
						continue;
					}

					$percent = SSO_Bundle::percent( $lead_id );
					$price   = (float) wc_get_price_to_display( $match );
					?>
					<li class="sso-goeswith__item">
						<a href="<?php echo esc_url( $match->get_permalink() ); ?>" class="sso-goeswith__thumb">
							<?php echo wp_kses_post( $match->get_image( 'woocommerce_thumbnail' ) ); ?>
						</a>

						<a href="<?php echo esc_url( $match->get_permalink() ); ?>" class="sso-goeswith__name">
							<?php echo esc_html( $match->get_name() ); ?>
						</a>

						<span class="sso-goeswith__price">
							<?php if ( $percent > 0 ) : ?>
								<del><?php echo wp_kses_post( wc_price( $price ) ); ?></del>
								<ins><?php echo wp_kses_post( wc_price( $price * ( 1 - $percent / 100 ) ) ); ?></ins>
							<?php else : ?>
								<?php echo wp_kses_post( wc_price( $price ) ); ?>
							<?php endif; ?>
						</span>

						<button type="button" class="button ss-btn ss-btn--sm sso-goeswith__add" data-look-add
							data-ids="<?php echo esc_attr( $match_id ); ?>"
							data-nonce="<?php echo esc_attr( wp_create_nonce( 'sso_add_bundle' ) ); ?>">
							<?php esc_html_e( 'Add', 'sreesaanvika-offers' ); ?>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Adding the set
	 * ------------------------------------------------------------------ */

	/**
	 * Put every ticked product in the bag in one go.
	 */
	public static function ajax_add() {
		check_ajax_referer( 'sso_add_bundle', 'nonce' );

		$raw = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
		$ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', $raw ) ) ) ) );

		if ( ! $ids ) {
			wp_send_json_error( array( 'message' => __( 'Nothing was ticked.', 'sreesaanvika-offers' ) ) );
		}

		$added   = 0;
		$skipped = array();

		foreach ( array_slice( $ids, 0, 12 ) as $id ) {
			$product = wc_get_product( $id );

			if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
				$skipped[] = $product ? $product->get_name() : (string) $id;
				continue;
			}

			/*
			 * A variable product cannot be added blind — the shopper has to
			 * choose. Say so rather than adding the wrong thing.
			 */
			if ( $product->is_type( 'variable' ) ) {
				$skipped[] = $product->get_name();
				continue;
			}

			if ( WC()->cart->add_to_cart( $id, 1 ) ) {
				$added++;
			} else {
				$skipped[] = $product->get_name();
			}
		}

		if ( ! $added ) {
			wp_send_json_error(
				array(
					'message' => __( 'Those need choosing on their own product page.', 'sreesaanvika-offers' ),
					'skipped' => $skipped,
				)
			);
		}

		$message = sprintf(
			/* translators: %d: number of products added */
			_n( '%d piece added to your bag', '%d pieces added to your bag', $added, 'sreesaanvika-offers' ),
			$added
		);

		if ( $skipped ) {
			$message .= ' · ' . sprintf(
				/* translators: %s: comma separated product names */
				__( 'Choose the options for %s first', 'sreesaanvika-offers' ),
				implode( ', ', array_slice( $skipped, 0, 3 ) )
			);
		}

		wp_send_json_success(
			array(
				'added'    => $added,
				'message'  => $message,
				'cart_url' => wc_get_cart_url(),
				'count'    => WC()->cart->get_cart_contents_count(),
			)
		);
	}
}
