<?php
/**
 * Building an offer: pick the products, say what they get.
 *
 * @package SreeSaanvikaOffers
 */

defined( 'ABSPATH' ) || exit;

/**
 * The offer editor.
 */
class SSO_Admin {

	/**
	 * Hook up.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'metaboxes' ) );
		add_action( 'save_post_' . SSO_Offer::TYPE, array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );

		add_filter( 'manage_' . SSO_Offer::TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . SSO_Offer::TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	/**
	 * A more useful prompt than "Add title".
	 *
	 * @param string  $text Placeholder.
	 * @param WP_Post $post Post.
	 * @return string
	 */
	public static function title_placeholder( $text, $post ) {
		return SSO_Offer::TYPE === $post->post_type
			? __( 'Name this offer — Saree Buy 2 Get 1', 'sreesaanvika-offers' )
			: $text;
	}

	/**
	 * Panels.
	 */
	public static function metaboxes() {
		add_meta_box(
			'sso-deal',
			__( 'The deal', 'sreesaanvika-offers' ),
			array( __CLASS__, 'deal_panel' ),
			SSO_Offer::TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'sso-products',
			__( 'Which products it covers', 'sreesaanvika-offers' ),
			array( __CLASS__, 'products_panel' ),
			SSO_Offer::TYPE,
			'normal',
			'high'
		);

		add_meta_box(
			'sso-copy',
			__( 'What the customer sees', 'sreesaanvika-offers' ),
			array( __CLASS__, 'copy_panel' ),
			SSO_Offer::TYPE,
			'normal',
			'default'
		);

		add_meta_box(
			'sso-when',
			__( 'When it runs', 'sreesaanvika-offers' ),
			array( __CLASS__, 'when_panel' ),
			SSO_Offer::TYPE,
			'side',
			'default'
		);
	}

	/**
	 * Buy / get / how much off.
	 *
	 * @param WP_Post $post Offer.
	 */
	public static function deal_panel( $post ) {
		$offer = new SSO_Offer( $post );

		wp_nonce_field( 'sso_offer', 'sso_nonce' );
		?>
		<p class="description" style="margin-bottom:16px">
			<?php esc_html_e( 'No promo code. When a shopper has enough of the products below in their cart, the discount comes off the total on its own.', 'sreesaanvika-offers' ); ?>
		</p>

		<p class="sso-kind">
			<label>
				<input type="radio" name="sso_kind" value="bogo" <?php checked( 'bogo', $offer->kind() ); ?> />
				<strong><?php esc_html_e( 'Buy some, get some free', 'sreesaanvika-offers' ); ?></strong>
				<span><?php esc_html_e( 'Any three sarees, the cheapest is free. Counted across everything the offer covers.', 'sreesaanvika-offers' ); ?></span>
			</label>

			<label>
				<input type="radio" name="sso_kind" value="tiers" <?php checked( 'tiers', $offer->kind() ); ?> />
				<strong><?php esc_html_e( 'The more you buy, the cheaper', 'sreesaanvika-offers' ); ?></strong>
				<span><?php esc_html_e( 'Two of the same saree, 10% off. Counted per product, so it catches the shopper buying a pair.', 'sreesaanvika-offers' ); ?></span>
			</label>
		</p>

		<div class="sso-when-tiers">
			<p><strong><?php esc_html_e( 'Quantity breaks', 'sreesaanvika-offers' ); ?></strong></p>

			<table class="widefat striped" style="max-width:420px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'From this many', 'sreesaanvika-offers' ); ?></th>
						<th><?php esc_html_e( 'Take off', 'sreesaanvika-offers' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					$rows = $offer->tiers();

					// Smallest first for editing, with spare rows to fill in.
					usort(
						$rows,
						function ( $a, $b ) {
							return $a['qty'] <=> $b['qty'];
						}
					);

					$rows = array_pad( $rows, max( 4, count( $rows ) + 2 ), array( 'qty' => '', 'percent' => '' ) );
					?>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<td>
								<input type="number" min="2" max="99" name="sso_tier_qty[]" style="width:90px"
									value="<?php echo esc_attr( $row['qty'] ); ?>" placeholder="2" />
							</td>
							<td>
								<input type="number" min="1" max="90" step="1" name="sso_tier_percent[]" style="width:90px"
									value="<?php echo esc_attr( $row['percent'] ); ?>" placeholder="10" /> %
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="description">
				<?php esc_html_e( 'Fill in as many as you need and leave the rest empty — 2 for 10%, 3 for 15%. A shopper always gets the best one their quantity earns.', 'sreesaanvika-offers' ); ?>
			</p>
		</div>

		<p class="sso-deal">
			<label>
				<?php esc_html_e( 'Buy', 'sreesaanvika-offers' ); ?>
				<input type="number" name="sso_buy" min="1" max="20" value="<?php echo esc_attr( $offer->buy() ); ?>" />
			</label>

			<label>
				<?php esc_html_e( 'get', 'sreesaanvika-offers' ); ?>
				<input type="number" name="sso_get" min="1" max="20" value="<?php echo esc_attr( $offer->get() ); ?>" />
			</label>

			<label>
				<?php esc_html_e( 'at', 'sreesaanvika-offers' ); ?>
				<input type="number" name="sso_percent" min="1" max="100" step="1" value="<?php echo esc_attr( $offer->percent() ); ?>" />
				<?php esc_html_e( '% off', 'sreesaanvika-offers' ); ?>
			</label>
		</p>

		<p class="description sso-when-bogo">
			<?php
			printf(
				/* translators: 1: buy count, 2: get count, 3: total in the cart */
				esc_html__( 'Buy %1$d, get %2$d free means the shopper needs %3$d of these products in the cart. The cheapest %2$d are the free ones. Leave the percentage at 100 for free; set it to 50 and the cheapest is half price instead.', 'sreesaanvika-offers' ),
				$offer->buy(),
				$offer->get(),
				$offer->group_size()
			);
			?>
		</p>

		<p class="sso-when-bogo">
			<label>
				<input type="checkbox" name="sso_repeat" value="yes" <?php checked( $offer->repeats() ); ?> />
				<?php esc_html_e( 'Apply it again for every further set in the same cart', 'sreesaanvika-offers' ); ?>
			</label>
			<br />
			<span class="description">
				<?php esc_html_e( 'On: six sarees get two free. Off: six sarees still get one.', 'sreesaanvika-offers' ); ?>
			</span>
		</p>

		<script>
			// Show only the half of this panel that belongs to the chosen kind.
			( function () {
				var panel = document.getElementById( 'sso-deal' );

				if ( ! panel ) { return; }

				function paint() {
					var chosen = panel.querySelector( 'input[name="sso_kind"]:checked' );
					var tiers = chosen && 'tiers' === chosen.value;

					panel.querySelectorAll( '.sso-when-tiers' ).forEach( function ( el ) { el.hidden = ! tiers; } );
					panel.querySelectorAll( '.sso-when-bogo, .sso-deal' ).forEach( function ( el ) { el.hidden = tiers; } );
				}

				panel.addEventListener( 'change', function ( e ) {
					if ( 'sso_kind' === e.target.name ) { paint(); }
				} );

				paint();
			}() );
		</script>
		<?php
	}

	/**
	 * The product picker.
	 *
	 * @param WP_Post $post Offer.
	 */
	public static function products_panel( $post ) {
		$offer = new SSO_Offer( $post );
		?>
		<p>
			<label for="sso_products"><strong><?php esc_html_e( 'Products', 'sreesaanvika-offers' ); ?></strong></label><br />
			<select class="wc-product-search" multiple="multiple" style="width:100%" id="sso_products"
				name="sso_products[]" data-placeholder="<?php esc_attr_e( 'Search for a product…', 'sreesaanvika-offers' ); ?>"
				data-action="woocommerce_json_search_products_and_variations">
				<?php foreach ( $offer->product_ids() as $id ) : ?>
					<?php $product = wc_get_product( $id ); ?>
					<?php if ( $product ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" selected>
							<?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			<span class="description">
				<?php esc_html_e( 'Pick them by hand. This is the list the offer counts against.', 'sreesaanvika-offers' ); ?>
			</span>
		</p>

		<p>
			<label for="sso_categories"><strong><?php esc_html_e( 'Or whole categories', 'sreesaanvika-offers' ); ?></strong></label><br />
			<?php
			$categories = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'number'     => 300,
				)
			);
			$chosen     = $offer->category_ids();
			?>
			<select multiple="multiple" style="width:100%;min-height:120px" id="sso_categories" name="sso_categories[]">
				<?php if ( $categories && ! is_wp_error( $categories ) ) : ?>
					<?php foreach ( $categories as $category ) : ?>
						<option value="<?php echo esc_attr( $category->term_id ); ?>"
							<?php selected( in_array( (int) $category->term_id, $chosen, true ) ); ?>>
							<?php echo esc_html( $category->name ); ?>
						</option>
					<?php endforeach; ?>
				<?php endif; ?>
			</select>
			<span class="description">
				<?php esc_html_e( 'Everything in these categories joins the offer, on top of the products above. Handy for "all sarees".', 'sreesaanvika-offers' ); ?>
			</span>
		</p>

		<p>
			<label for="sso_exclude"><strong><?php esc_html_e( 'Except these', 'sreesaanvika-offers' ); ?></strong></label><br />
			<select class="wc-product-search" multiple="multiple" style="width:100%" id="sso_exclude"
				name="sso_exclude[]" data-placeholder="<?php esc_attr_e( 'Search for a product…', 'sreesaanvika-offers' ); ?>"
				data-action="woocommerce_json_search_products_and_variations">
				<?php foreach ( $offer->excluded_ids() as $id ) : ?>
					<?php $product = wc_get_product( $id ); ?>
					<?php if ( $product ) : ?>
						<option value="<?php echo esc_attr( $id ); ?>" selected>
							<?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?>
						</option>
					<?php endif; ?>
				<?php endforeach; ?>
			</select>
			<span class="description">
				<?php esc_html_e( 'Kept out even when a category above would have let them in.', 'sreesaanvika-offers' ); ?>
			</span>
		</p>
		<?php
	}

	/**
	 * Wording.
	 *
	 * @param WP_Post $post Offer.
	 */
	public static function copy_panel( $post ) {
		$offer = new SSO_Offer( $post );
		?>
		<p>
			<label for="sso_headline"><strong><?php esc_html_e( 'Headline', 'sreesaanvika-offers' ); ?></strong></label><br />
			<input type="text" class="large-text" id="sso_headline" name="sso_headline"
				value="<?php echo esc_attr( $offer->meta( 'headline', '' ) ); ?>"
				placeholder="<?php esc_attr_e( 'BUY 2 GET 1 FREE', 'sreesaanvika-offers' ); ?>" />
		</p>

		<p>
			<label for="sso_subline"><strong><?php esc_html_e( 'Line underneath', 'sreesaanvika-offers' ); ?></strong></label><br />
			<input type="text" class="large-text" id="sso_subline" name="sso_subline"
				value="<?php echo esc_attr( $offer->meta( 'subline', '' ) ); ?>"
				placeholder="<?php esc_attr_e( 'Add any 3 sarees — get 1 absolutely free', 'sreesaanvika-offers' ); ?>" />
		</p>

		<p class="description">
			<?php esc_html_e( 'Both appear on every product the offer covers and at the top of the cart, along with a line telling the shopper how many more to add.', 'sreesaanvika-offers' ); ?>
		</p>
		<?php
	}

	/**
	 * Dates.
	 *
	 * @param WP_Post $post Offer.
	 */
	public static function when_panel( $post ) {
		$offer = new SSO_Offer( $post );
		?>
		<p>
			<label for="sso_starts"><strong><?php esc_html_e( 'Starts', 'sreesaanvika-offers' ); ?></strong></label><br />
			<input type="datetime-local" class="widefat" id="sso_starts" name="sso_starts"
				value="<?php echo esc_attr( $offer->meta( 'starts', '' ) ); ?>" />
		</p>

		<p>
			<label for="sso_ends"><strong><?php esc_html_e( 'Ends', 'sreesaanvika-offers' ); ?></strong></label><br />
			<input type="datetime-local" class="widefat" id="sso_ends" name="sso_ends"
				value="<?php echo esc_attr( $offer->meta( 'ends', '' ) ); ?>" />
			<span class="description"><?php esc_html_e( 'Leave both empty to run until you unpublish it.', 'sreesaanvika-offers' ); ?></span>
		</p>

		<p>
			<label>
				<input type="checkbox" name="sso_countdown" value="yes" <?php checked( 'yes', $offer->meta( 'countdown', 'no' ) ); ?> />
				<?php esc_html_e( 'Show a countdown to the end', 'sreesaanvika-offers' ); ?>
			</label>
		</p>
		<?php
	}

	/**
	 * Save every panel.
	 *
	 * @param int     $post_id Offer id.
	 * @param WP_Post $post    Offer.
	 */
	public static function save( $post_id, $post ) {
		unset( $post );

		if ( ! isset( $_POST['sso_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['sso_nonce'] ) ), 'sso_offer' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$numbers = array(
			'buy'     => 2,
			'get'     => 1,
			'percent' => 100,
		);

		foreach ( $numbers as $key => $fallback ) {
			$value = isset( $_POST[ 'sso_' . $key ] ) ? absint( wp_unslash( $_POST[ 'sso_' . $key ] ) ) : $fallback;
			update_post_meta( $post_id, '_sso_' . $key, max( 1, $value ) );
		}

		foreach ( array( 'headline', 'subline', 'starts', 'ends' ) as $key ) {
			$value = isset( $_POST[ 'sso_' . $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'sso_' . $key ] ) ) : '';
			update_post_meta( $post_id, '_sso_' . $key, $value );
		}

		$kind = isset( $_POST['sso_kind'] ) ? sanitize_key( wp_unslash( $_POST['sso_kind'] ) ) : 'bogo';
		update_post_meta( $post_id, '_sso_kind', in_array( $kind, array( 'bogo', 'tiers' ), true ) ? $kind : 'bogo' );

		$quantities = isset( $_POST['sso_tier_qty'] ) ? (array) wp_unslash( $_POST['sso_tier_qty'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$percents   = isset( $_POST['sso_tier_percent'] ) ? (array) wp_unslash( $_POST['sso_tier_percent'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$tiers      = array();

		foreach ( $quantities as $row => $quantity ) {
			$quantity = absint( $quantity );
			$percent  = isset( $percents[ $row ] ) ? (float) $percents[ $row ] : 0;

			// An empty row is how a break is left out.
			if ( $quantity < 2 || $percent <= 0 ) {
				continue;
			}

			$tiers[ $quantity ] = array(
				'qty'     => $quantity,
				'percent' => min( 90, $percent ),
			);
		}

		ksort( $tiers );
		update_post_meta( $post_id, '_sso_tiers', array_values( $tiers ) );

		foreach ( array( 'repeat', 'countdown' ) as $key ) {
			update_post_meta( $post_id, '_sso_' . $key, isset( $_POST[ 'sso_' . $key ] ) ? 'yes' : 'no' );
		}

		foreach ( array( 'products', 'categories', 'exclude' ) as $key ) {
			$ids = isset( $_POST[ 'sso_' . $key ] ) ? (array) wp_unslash( $_POST[ 'sso_' . $key ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

			update_post_meta( $post_id, '_sso_' . $key, $ids );
		}
	}

	/**
	 * Offers list columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public static function columns( $columns ) {
		$out = array();

		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;

			if ( 'title' === $key ) {
				$out['sso_deal']    = __( 'Deal', 'sreesaanvika-offers' );
				$out['sso_covers']  = __( 'Covers', 'sreesaanvika-offers' );
				$out['sso_running'] = __( 'Running', 'sreesaanvika-offers' );
			}
		}

		return $out;
	}

	/**
	 * Offers list cells.
	 *
	 * @param string $column  Column key.
	 * @param int    $post_id Offer id.
	 */
	public static function column( $column, $post_id ) {
		$offer = new SSO_Offer( $post_id );

		if ( 'sso_deal' === $column ) {
			if ( 'tiers' === $offer->kind() ) {
				$bits = array();

				foreach ( array_reverse( $offer->tiers() ) as $tier ) {
					/* translators: 1: quantity, 2: percentage off */
					$bits[] = sprintf( esc_html__( '%1$d+ → %2$d%% off', 'sreesaanvika-offers' ), (int) $tier['qty'], (int) $tier['percent'] );
				}

				echo $bits ? esc_html( implode( ', ', $bits ) ) : '—';
			} else {
				printf(
					/* translators: 1: buy count, 2: get count, 3: percentage */
					esc_html__( 'Buy %1$d, get %2$d at %3$d%% off', 'sreesaanvika-offers' ),
					(int) $offer->buy(),
					(int) $offer->get(),
					(int) $offer->percent()
				);
			}
		}

		if ( 'sso_covers' === $column ) {
			$bits = array();

			if ( $offer->product_ids() ) {
				$bits[] = sprintf(
					/* translators: %d: number of products */
					_n( '%d product', '%d products', count( $offer->product_ids() ), 'sreesaanvika-offers' ),
					count( $offer->product_ids() )
				);
			}

			if ( $offer->category_ids() ) {
				$bits[] = sprintf(
					/* translators: %d: number of categories */
					_n( '%d category', '%d categories', count( $offer->category_ids() ), 'sreesaanvika-offers' ),
					count( $offer->category_ids() )
				);
			}

			echo $bits ? esc_html( implode( ' + ', $bits ) ) : '<span style="color:#b32d2e">' . esc_html__( 'nothing yet', 'sreesaanvika-offers' ) . '</span>';
		}

		if ( 'sso_running' === $column ) {
			echo $offer->is_running()
				? '<span style="color:#1a7f5a">' . esc_html__( 'Yes', 'sreesaanvika-offers' ) . '</span>'
				: '<span style="color:#787c82">' . esc_html__( 'No', 'sreesaanvika-offers' ) . '</span>';
		}
	}

	/**
	 * WooCommerce's product search field and a little styling.
	 *
	 * @param string $hook Screen.
	 */
	public static function assets( $hook ) {
		unset( $hook );

		$screen = get_current_screen();

		if ( ! $screen || SSO_Offer::TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );

		wp_add_inline_style(
			'woocommerce_admin_styles',
			'.sso-deal{display:flex;gap:26px;align-items:center;font-size:15px}'
			. '.sso-deal input{width:70px}'
			. '.sso-deal label{display:flex;gap:8px;align-items:center}'
			. '.sso-kind{display:grid;gap:12px;margin-bottom:20px}'
			. '.sso-kind label{display:grid;grid-template-columns:22px 1fr;gap:2px 4px;align-items:start}'
			. '.sso-kind input{grid-row:span 2;margin-top:3px}'
			. '.sso-kind span{grid-column:2;color:#787c82;font-size:12px}'
			. '.sso-when-tiers{margin-bottom:20px}'
		);
	}
}
