<?php
/**
 * WooCommerce integration.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Unhook Woo defaults the theme replaces
 * ---------------------------------------------------------------------- */
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

// The loop item is rebuilt from scratch in content-product.php.
remove_action( 'woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10 );
remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
remove_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_rating', 5 );
remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );

/**
 * Wrap Woo pages in the theme layout.
 */
function ss_woo_wrapper_start() {
	if ( is_product() ) {
		echo '<div class="ss-container ss-section ss-section--tight">';
		return;
	}

	echo '<div class="ss-container ss-section ss-section--tight">';

	if ( ss_woo_has_sidebar() ) {
		echo '<div class="ss-shop-layout">';
	}
}
add_action( 'woocommerce_before_main_content', 'ss_woo_wrapper_start', 10 );

/**
 * Close the theme layout.
 */
function ss_woo_wrapper_end() {
	if ( ! is_product() && ss_woo_has_sidebar() ) {
		echo '</div>';
	}

	echo '</div>';
}
add_action( 'woocommerce_after_main_content', 'ss_woo_wrapper_end', 10 );

/**
 * Whether the filter sidebar should render.
 *
 * @return bool
 */
function ss_woo_has_sidebar() {
	if ( ! ss_option( 'shop_sidebar', true ) ) {
		return false;
	}

	return is_shop() || is_product_category() || is_product_tag();
}

/**
 * Layout CSS for the shop sidebar grid — small enough to inline.
 */
function ss_woo_layout_css() {
	if ( ! ss_is_woo() ) {
		return;
	}

	/*
	 * The sticky rule has to sit inside a min-width query. Unscoped, its
	 * `.ss-shop-layout > .ss-shop-sidebar` beat the `position: fixed` that
	 * shop.css applies below 1024px, so on a phone the filter panel stayed in
	 * the grid — translated off-screen but still occupying a full-width row,
	 * which left a blank band above the products and pushed them down.
	 */
	$css = '.ss-shop-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:clamp(20px,3vw,42px);align-items:start;}'
		. '@media(min-width:1025px){.ss-shop-layout > .ss-shop-sidebar{position:sticky;top:calc(var(--ss-header-h) + 18px);}}'
		. '@media(max-width:1024px){.ss-shop-layout{grid-template-columns:minmax(0,1fr);}}';

	wp_add_inline_style( 'ss-shop', $css );
}
add_action( 'wp_enqueue_scripts', 'ss_woo_layout_css', 20 );

/* -------------------------------------------------------------------------
 * Catalog settings
 * ---------------------------------------------------------------------- */

/**
 * Products per page.
 *
 * @return int
 */
function ss_products_per_page() {
	return absint( ss_option( 'shop_per_page', 12 ) );
}
add_filter( 'loop_shop_per_page', 'ss_products_per_page', 20 );

/**
 * Products per row.
 *
 * @return int
 */
function ss_loop_columns() {
	return absint( ss_option( 'shop_columns', 4 ) );
}
add_filter( 'loop_shop_columns', 'ss_loop_columns', 20 );

/**
 * Related products count matches the grid.
 *
 * @param array $args Args.
 * @return array
 */
function ss_related_args( $args ) {
	$args['posts_per_page'] = absint( ss_option( 'shop_columns', 4 ) );
	$args['columns']        = absint( ss_option( 'shop_columns', 4 ) );

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'ss_related_args', 20 );

/**
 * Use the portrait crop for catalog images.
 *
 * @param string $size Image size.
 * @return string
 */
function ss_thumb_size( $size ) {
	return 'ss-product';
}
add_filter( 'woocommerce_gallery_thumbnail_size', 'ss_thumb_size' );

/* -------------------------------------------------------------------------
 * Product card pieces
 * ---------------------------------------------------------------------- */

/**
 * Discount percentage for a product, or 0.
 *
 * @param WC_Product $product Product.
 * @return int
 */
function ss_discount_pct( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return 0;
	}

	$regular = (float) $product->get_regular_price();
	$sale    = (float) $product->get_price();

	if ( $product->is_type( 'variable' ) ) {
		$regular = (float) $product->get_variation_regular_price( 'min' );
		$sale    = (float) $product->get_variation_price( 'min' );
	}

	if ( $regular <= 0 || $sale <= 0 || $sale >= $regular ) {
		return 0;
	}

	return (int) round( ( ( $regular - $sale ) / $regular ) * 100 );
}

/**
 * Badges shown over a product image.
 *
 * @param WC_Product $product Product.
 * @param string     $context "card"|"single".
 */
function ss_product_badges( $product, $context = 'card' ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$class = 'card' === $context ? 'ss-badges' : 'ss-gallery__badges';

	echo '<div class="' . esc_attr( $class ) . '">';

	if ( ! $product->is_in_stock() ) {
		echo '<span class="ss-badge ss-badge--soldout">' . esc_html__( 'Sold out', 'sreesaanvika' ) . '</span>';
	} else {
		$pct = ss_discount_pct( $product );

		if ( $pct > 0 ) {
			/* translators: %d: discount percentage */
			echo '<span class="ss-badge ss-badge--sale">' . esc_html( sprintf( __( '%d%% OFF', 'sreesaanvika' ), $pct ) ) . '</span>';
		}

		// "New" for anything published in the last 30 days.
		$created = $product->get_date_created();

		if ( $created && ( time() - $created->getTimestamp() ) < 30 * DAY_IN_SECONDS ) {
			echo '<span class="ss-badge ss-badge--new">' . esc_html__( 'New', 'sreesaanvika' ) . '</span>';
		}

		if ( $product->is_featured() ) {
			echo '<span class="ss-badge ss-badge--gold">' . esc_html__( 'Bestseller', 'sreesaanvika' ) . '</span>';
		}

		$total = $product->get_total_sales();

		if ( $total > 25 ) {
			echo '<span class="ss-badge ss-badge--hot">' . esc_html__( 'Trending', 'sreesaanvika' ) . '</span>';
		}
	}

	echo '</div>';
}

/**
 * Price block with the saving amount spelled out.
 *
 * @param WC_Product $product Product.
 * @param string     $class   Extra class.
 */
function ss_price_block( $product, $class = 'ss-pcard__price' ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$pct = ss_discount_pct( $product );

	/*
	 * A variable product used to print Woo's own get_price_html() here, which
	 * carries its own <del>/<ins> and so came out looking nothing like a
	 * simple product's price. Work in numbers instead and render one shape for
	 * both, which also gives shop.js something to recalculate.
	 */
	if ( $product->is_type( 'variable' ) ) {
		$now     = (float) $product->get_variation_price( 'min', true );
		$high    = (float) $product->get_variation_price( 'max', true );
		$regular = (float) $product->get_variation_regular_price( 'min', true );
		$range   = $high > $now;
	} else {
		$now     = (float) wc_get_price_to_display( $product );
		$high    = $now;
		$regular = $product->get_regular_price()
			? (float) wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) )
			: 0.0;
		$range   = false;
	}

	printf(
		'<div class="%1$s" data-price-block data-unit-price="%2$s" data-unit-regular="%3$s"%4$s>',
		esc_attr( $class ),
		esc_attr( $now ),
		esc_attr( $regular > $now ? $regular : '' ),
		// A "from — to" range has no single figure for shop.js to multiply.
		$range ? ' data-price-range' : ''
	);

	if ( $range ) {
		echo '<span class="ss-price-now">'
			. wp_kses_post( wc_price( $now ) ) . ' &ndash; ' . wp_kses_post( wc_price( $high ) )
			. '</span>';
	} else {
		echo '<span class="ss-price-now">' . wp_kses_post( wc_price( $now ) ) . '</span>';

		if ( $regular > $now ) {
			echo '<span class="ss-price-was">' . wp_kses_post( wc_price( $regular ) ) . '</span>';
		}
	}

	if ( $pct > 0 ) {
		/* translators: %d: discount percentage */
		echo '<span class="ss-price-off">' . esc_html( sprintf( __( '%d%% off', 'sreesaanvika' ), $pct ) ) . '</span>';
	}

	echo '</div>';
}

/**
 * Colour swatches derived from a variable product's colour attribute.
 *
 * @param WC_Product $product Product.
 * @param int        $limit   Max swatches to show.
 */
function ss_card_swatches( $product, $limit = 5 ) {
	if ( ! ss_option( 'card_swatches', true ) || ! $product instanceof WC_Product ) {
		return;
	}

	$terms = ss_product_color_terms( $product );

	if ( ! $terms ) {
		return;
	}

	echo '<div class="ss-pcard__swatches">';

	$shown = array_slice( $terms, 0, $limit );
	$link  = get_permalink( $product->get_id() );

	foreach ( $shown as $term ) {
		/*
		 * A colour with its own photos swaps the card image where it stands;
		 * one without can only be chosen on the product page, so the swatch
		 * links there with the colour pre-selected. Either way it does
		 * something when clicked.
		 */
		$chip  = function_exists( 'ss_color_swatch_image' ) ? ss_color_swatch_image( $product, $term->slug ) : '';
		$front = function_exists( 'ss_color_swatch_image' ) ? ss_color_swatch_image( $product, $term->slug, 'ss-product' ) : '';

		printf(
			'<button type="button" class="ss-swatch%1$s" style="background-color:%2$s" title="%3$s"'
			. ' data-color="%4$s" data-img="%5$s" data-front="%6$s" data-href="%7$s">'
			. '%8$s<span class="screen-reader-text">%3$s</span></button>',
			$chip ? ' ss-swatch--img' : '',
			esc_attr( ss_color_hex( $term->name, $term->term_id ) ),
			esc_attr( $term->name ),
			esc_attr( $term->slug ),
			esc_url( $chip ),
			esc_url( $front ),
			esc_url( add_query_arg( 'attribute_' . sanitize_title( $term->taxonomy ), $term->slug, $link ) ),
			$chip ? '<img src="' . esc_url( $chip ) . '" alt="" loading="lazy" />' : ''
		);
	}

	$extra = count( $terms ) - count( $shown );

	if ( $extra > 0 ) {
		echo '<span class="ss-swatch ss-swatch--more">+' . esc_html( $extra ) . '</span>';
	}

	echo '</div>';
}

/*
 * WooCommerce only sends a variation's price to the browser when the
 * variations differ in price, so on a product where every colour costs the
 * same the price simply never moves when you choose one — which reads as a
 * broken page rather than as "same price". Always send it.
 */
add_filter( 'woocommerce_show_variation_price', '__return_true' );

/**
 * The colour attribute terms attached to a product.
 *
 * @param WC_Product $product Product.
 * @return array
 */
function ss_product_color_terms( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$taxonomies = array( 'pa_color', 'pa_colour', 'pa_shade' );
	$found      = array();

	foreach ( $taxonomies as $tax ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}

		$terms = wc_get_product_terms( $product->get_id(), $tax, array( 'fields' => 'all' ) );

		if ( $terms && ! is_wp_error( $terms ) ) {
			$found = array_merge( $found, $terms );
		}
	}

	return $found;
}

/**
 * Low-stock meter under the price.
 *
 * @param WC_Product $product Product.
 */
function ss_stock_meter( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->managing_stock() ) {
		return;
	}

	$left  = (int) $product->get_stock_quantity();
	$alert = absint( ss_option( 'stock_alert_qty', 8 ) );

	if ( $left <= 0 || $left > $alert || ! $alert ) {
		return;
	}

	$pct = max( 8, min( 100, ( $left / max( 1, $alert ) ) * 100 ) );

	echo '<div class="ss-stockbar">';
	echo '<div class="ss-stockbar__track"><div class="ss-stockbar__fill" style="width:' . esc_attr( $pct ) . '%"></div></div>';
	/* translators: %d: units left in stock */
	echo '<span class="ss-stockbar__label">' . esc_html( sprintf( _n( 'Only %d piece left', 'Only %d pieces left', $left, 'sreesaanvika' ), $left ) ) . '</span>';
	echo '</div>';
}

/**
 * Wishlist / compare / quick-view buttons for a card.
 *
 * @param WC_Product $product Product.
 */
function ss_card_actions( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$id = $product->get_id();

	echo '<div class="ss-pcard__actions">';

	if ( ss_option( 'wishlist_on', true ) ) {
		printf(
			'<button type="button" class="ss-icon-btn ss-wishlist-btn" data-id="%1$d" data-tip="%2$s" aria-label="%2$s" aria-pressed="false">%3$s</button>',
			absint( $id ),
			esc_attr__( 'Add to wishlist', 'sreesaanvika' ),
			ss_icon( 'heart', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	if ( ss_option( 'compare_on', true ) ) {
		printf(
			'<button type="button" class="ss-icon-btn ss-compare-btn" data-id="%1$d" data-tip="%2$s" aria-label="%2$s" aria-pressed="false">%3$s</button>',
			absint( $id ),
			esc_attr__( 'Add to compare', 'sreesaanvika' ),
			ss_icon( 'compare', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	if ( ss_option( 'quickview', true ) ) {
		printf(
			'<button type="button" class="ss-icon-btn ss-quickview-btn" data-id="%1$d" data-tip="%2$s" aria-label="%2$s">%3$s</button>',
			absint( $id ),
			esc_attr__( 'Quick view', 'sreesaanvika' ),
			ss_icon( 'eye', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	echo '</div>';
}

/**
 * Second gallery image URL for the hover swap.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function ss_secondary_image( $product ) {
	if ( ! ss_option( 'card_hover_img', true ) || ! $product instanceof WC_Product ) {
		return '';
	}

	$ids = $product->get_gallery_image_ids();

	if ( empty( $ids ) ) {
		return '';
	}

	return wp_get_attachment_image_url( $ids[0], 'ss-product' );
}

/* -------------------------------------------------------------------------
 * Cart fragments — keep the header counter and drawer live
 * ---------------------------------------------------------------------- */

/**
 * Refresh the header cart count and the mini-cart body over AJAX.
 *
 * @param array $fragments Fragments.
 * @return array
 */
function ss_cart_fragments( $fragments ) {
	ob_start();
	ss_cart_count_badge();
	$fragments['.ss-cart-count'] = ob_get_clean();

	ob_start();
	ss_minicart_body();
	$fragments['.ss-minicart-body'] = ob_get_clean();

	ob_start();
	ss_minicart_foot();
	$fragments['.ss-minicart-foot'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'ss_cart_fragments' );

/**
 * The little number on the bag icon.
 */
function ss_cart_count_badge() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

	printf(
		'<span class="ss-count-badge ss-cart-count"%s>%s</span>',
		$count ? '' : ' hidden',
		esc_html( $count )
	);
}

/**
 * Mini-cart line items.
 */
function ss_minicart_body() {
	echo '<div class="ss-minicart-body ss-panel__body">';

	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		echo '<div class="ss-panel__empty">';
		echo ss_icon( 'bag', 62 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . esc_html__( 'Your bag is waiting to be filled.', 'sreesaanvika' ) . '</p>';
		echo '<a class="ss-btn ss-btn--ghost ss-btn--sm" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Start shopping', 'sreesaanvika' ) . '</a>';
		echo '</div></div>';
		return;
	}

	// Free-shipping progress meter.
	$threshold = (float) ss_option( 'free_ship_threshold', 2999 );

	if ( $threshold > 0 ) {
		$subtotal = (float) WC()->cart->get_displayed_subtotal();
		$pct      = min( 100, ( $subtotal / $threshold ) * 100 );
		$left     = max( 0, $threshold - $subtotal );

		echo '<div class="ss-ship-meter">';

		if ( $left > 0 ) {
			printf(
				'<p>%s</p>',
				wp_kses_post(
					sprintf(
						/* translators: %s: formatted amount remaining */
						__( 'Add %s more for <strong>free shipping</strong>', 'sreesaanvika' ),
						wc_price( $left )
					)
				)
			);
		} else {
			echo '<p><strong>' . esc_html__( 'Free shipping unlocked', 'sreesaanvika' ) . '</strong></p>';
		}

		echo '<div class="ss-ship-meter__bar"><div class="ss-ship-meter__fill" style="width:' . esc_attr( $pct ) . '%"></div></div>';
		echo '</div>';
	}

	echo '<ul class="ss-minicart__list">';

	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );

		if ( ! $product || ! $product->exists() || $item['quantity'] <= 0 ) {
			continue;
		}

		$link  = $product->is_visible() ? $product->get_permalink( $item ) : '';
		$thumb = $product->get_image( 'ss-thumb' );

		echo '<li class="ss-minicart__item">';
		echo '<div>' . wp_kses_post( $thumb ) . '</div>';
		echo '<div>';
		echo '<div class="ss-minicart__name">' . ( $link ? '<a href="' . esc_url( $link ) . '">' : '' ) . esc_html( $product->get_name() ) . ( $link ? '</a>' : '' ) . '</div>';

		$meta = wc_get_formatted_cart_item_data( $item, true );

		if ( $meta ) {
			echo '<div class="ss-minicart__meta">' . wp_kses_post( $meta ) . '</div>';
		}

		echo '<div class="ss-minicart__meta">' . esc_html( $item['quantity'] ) . ' &times; ' . wp_kses_post( wc_price( $product->get_price() ) ) . '</div>';
		echo '</div>';
		echo '<div class="ss-minicart__price">' . wp_kses_post( WC()->cart->get_product_subtotal( $product, $item['quantity'] ) ) . '</div>';

		printf(
			'<a href="%s" class="ss-minicart__remove" data-cart-key="%s" aria-label="%s">&times;</a>',
			esc_url( wc_get_cart_remove_url( $key ) ),
			esc_attr( $key ),
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Remove %s', 'sreesaanvika' ), $product->get_name() ) )
		);

		echo '</li>';
	}

	echo '</ul></div>';
}

/**
 * Mini-cart footer with the subtotal and buttons.
 */
function ss_minicart_foot() {
	$empty = ! WC()->cart || WC()->cart->is_empty();

	echo '<div class="ss-minicart-foot ss-panel__foot"' . ( $empty ? ' hidden' : '' ) . '>';

	if ( ! $empty ) {
		echo '<div class="ss-minicart__subtotal"><span>' . esc_html__( 'Subtotal', 'sreesaanvika' ) . '</span><strong>' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</strong></div>';
		echo '<a class="ss-btn ss-btn--block" href="' . esc_url( wc_get_checkout_url() ) . '">' . esc_html__( 'Checkout', 'sreesaanvika' ) . '</a>';
		echo '<a class="ss-btn ss-btn--ghost ss-btn--block" style="margin-top:8px" href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'View bag', 'sreesaanvika' ) . '</a>';
		echo '<div class="ss-securenote">' . ss_icon( 'lock', 15 ) . '<span>' . esc_html__( '100% secure payments · UPI, cards, netbanking', 'sreesaanvika' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Single product extras
 * ---------------------------------------------------------------------- */

/**
 * Offers panel under the price.
 */
function ss_single_offers() {
	$lines = ss_list( ss_option( 'offers_text', '' ) );

	if ( ! $lines ) {
		return;
	}

	echo '<div class="ss-offers">';
	echo '<h4>' . esc_html__( 'Available offers', 'sreesaanvika' ) . '</h4>';
	echo '<ul>';

	foreach ( $lines as $line ) {
		// `CODE` becomes a highlighted coupon chip.
		$html = preg_replace( '/`([^`]+)`/', '<code>$1</code>', esc_html( $line ) );
		echo '<li>' . ss_icon( 'tag', 16 ) . '<span>' . wp_kses( $html, array( 'code' => array() ) ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</ul></div>';
}

/**
 * Trust badges under the buy button.
 */
function ss_single_trust() {
	$items = array(
		array( 'truck', __( 'Free shipping over ₹2,999', 'sreesaanvika' ) ),
		array( 'refresh', __( '7-day easy returns', 'sreesaanvika' ) ),
		array( 'shield', __( 'Authentic handloom', 'sreesaanvika' ) ),
	);

	echo '<div class="ss-trust">';

	foreach ( $items as $item ) {
		echo '<div class="ss-trust__item">' . ss_icon( $item[0], 22 ) . '<span>' . esc_html( $item[1] ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}

/**
 * PIN code delivery checker.
 */
function ss_single_pincode() {
	if ( ! ss_option( 'pincode_check', true ) ) {
		return;
	}
	?>
	<div class="ss-delivery">
		<label for="ss-pin"><?php esc_html_e( 'Check delivery & COD availability', 'sreesaanvika' ); ?></label>
		<div class="ss-delivery__row">
			<input type="text" id="ss-pin" inputmode="numeric" maxlength="6" placeholder="<?php esc_attr_e( 'Enter 6-digit PIN code', 'sreesaanvika' ); ?>" />
			<button type="button" class="ss-btn ss-btn--ghost ss-btn--sm ss-pin-check"><?php esc_html_e( 'Check', 'sreesaanvika' ); ?></button>
		</div>
		<p class="ss-delivery__result" role="status"></p>
	</div>
	<?php
}

/**
 * Share row.
 */
function ss_single_share() {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );

	$links = array(
		'whatsapp'  => 'https://wa.me/?text=' . $title . '%20' . $url,
		'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
		'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title,
		'twitter'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
	);

	echo '<div class="ss-share"><span>' . esc_html__( 'Share', 'sreesaanvika' ) . '</span>';

	foreach ( $links as $net => $href ) {
		printf(
			'<a class="ss-icon-btn" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
			esc_url( $href ),
			/* translators: %s: network name */
			esc_attr( sprintf( __( 'Share on %s', 'sreesaanvika' ), ucfirst( $net ) ) ),
			ss_icon( $net, 17 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	printf(
		'<button type="button" class="ss-icon-btn ss-copy-link" data-url="%s" aria-label="%s">%s</button>',
		esc_url( get_permalink() ),
		esc_attr__( 'Copy link', 'sreesaanvika' ),
		ss_icon( 'link', 17 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);

	echo '</div>';
}

/**
 * A "Buy it now" button that adds to the cart and jumps to checkout.
 */
function ss_buy_now_button() {
	global $product;

	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}

	/*
	 * The button has to carry name="add-to-cart" itself. A browser submits
	 * only the clicked submit button's name/value pair, so a button named
	 * ss_buy_now sent the flag but never told WooCommerce what to add, and
	 * the click did nothing at all on a simple product.
	 *
	 * The flag moves to a hidden field that shop.js flips on click. Without
	 * JS the button still adds the product to the bag — it just does not skip
	 * ahead to checkout.
	 */
	printf(
		'<input type="hidden" name="ss_buy_now" value="0" class="ss-buy-now-flag" />'
		. '<button type="submit" name="add-to-cart" value="%1$d" class="ss-btn ss-btn--ghost ss-buynow" data-buy-now>%2$s</button>',
		absint( $product->get_id() ),
		esc_html__( 'Buy it now', 'sreesaanvika' )
	);
}

/**
 * Redirect straight to checkout when "Buy it now" was used.
 *
 * @param string $url Redirect URL.
 * @return string
 */
function ss_buy_now_redirect( $url ) {
	// The hidden field is always submitted, so test the value, not its presence.
	$flag = isset( $_REQUEST['ss_buy_now'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ss_buy_now'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $flag && '0' !== $flag ) {
		return wc_get_checkout_url();
	}

	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'ss_buy_now_redirect' );

/**
 * Highlight the active My Account menu item for the CSS.
 *
 * @param array  $classes Classes.
 * @param string $endpoint Endpoint.
 * @return array
 */
function ss_account_menu_classes( $classes, $endpoint ) {
	if ( in_array( 'is-active', $classes, true ) ) {
		return $classes;
	}

	if ( ( '' === $endpoint || 'dashboard' === $endpoint ) && is_account_page() && ! WC()->query->get_current_endpoint() ) {
		$classes[] = 'is-active';
	} elseif ( $endpoint && WC()->query->get_current_endpoint() === $endpoint ) {
		$classes[] = 'is-active';
	}

	return $classes;
}
add_filter( 'woocommerce_account_menu_item_classes', 'ss_account_menu_classes', 10, 2 );

/**
 * Nicer placeholder image.
 *
 * @param string $src Placeholder src.
 * @return string
 */
function ss_placeholder_img( $src ) {
	return ss_placeholder( 'product' );
}
add_filter( 'woocommerce_placeholder_img_src', 'ss_placeholder_img' );

/**
 * Route customers to the theme's auth page instead of the bare Woo login.
 *
 * Only when a page using the auth template exists and the user is logged out.
 */
function ss_auth_redirect() {
	if ( ! is_account_page() || is_user_logged_in() ) {
		return;
	}

	// Endpoints like lost-password must keep working.
	if ( WC()->query && WC()->query->get_current_endpoint() ) {
		return;
	}

	if ( ! ss_option( 'auth_redirect', false ) ) {
		return;
	}

	$url = ss_page_url( 'auth' );

	if ( $url && wp_parse_url( $url, PHP_URL_PATH ) !== wp_parse_url( wc_get_page_permalink( 'myaccount' ), PHP_URL_PATH ) ) {
		wp_safe_redirect( $url );
		exit;
	}
}
add_action( 'template_redirect', 'ss_auth_redirect' );

/**
 * Show the theme's attribute swatches on variable products.
 *
 * Woo renders a <select> per attribute; the JS in shop.js mirrors each one
 * into swatch buttons, so the select stays the source of truth (and keeps
 * working without JS).
 *
 * @param array $args Dropdown args.
 * @return array
 */
function ss_variation_dropdown_args( $args ) {
	$args['class'] = trim( ( isset( $args['class'] ) ? $args['class'] : '' ) . ' ss-variation-select' );

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'ss_variation_dropdown_args' );

/**
 * Rating breakdown shown above the review list.
 *
 * @param WC_Product $product Product.
 */
function ss_reviews_summary( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->get_review_count() ) {
		return;
	}

	$total = (int) $product->get_review_count();

	// Tally each star level from the comment meta.
	$comments = get_comments(
		array(
			'post_id' => $product->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => 500,
		)
	);

	$buckets = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );

	foreach ( $comments as $comment ) {
		$rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );

		if ( isset( $buckets[ $rating ] ) ) {
			$buckets[ $rating ]++;
		}
	}

	$counted = array_sum( $buckets );
	$counted = $counted ? $counted : $total;
	?>
	<div class="ss-reviews-summary">
		<div class="ss-reviews-score">
			<strong><?php echo esc_html( number_format_i18n( (float) $product->get_average_rating(), 1 ) ); ?></strong>
			<?php echo ss_stars( (float) $product->get_average_rating() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<small>
				<?php
				printf(
					/* translators: %s: number of reviews */
					esc_html( _n( 'Based on %s review', 'Based on %s reviews', $total, 'sreesaanvika' ) ),
					esc_html( number_format_i18n( $total ) )
				);
				?>
			</small>
		</div>

		<div class="ss-reviews-bars">
			<?php foreach ( $buckets as $stars => $count ) : ?>
				<?php $pct = $counted ? round( ( $count / $counted ) * 100 ) : 0; ?>
				<div class="ss-reviews-bar">
					<span class="lbl"><?php echo esc_html( $stars ); ?> ★</span>
					<span class="track"><span class="fill" style="width:<?php echo esc_attr( $pct ); ?>%"></span></span>
					<span class="num"><?php echo esc_html( $count ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Drop WooCommerce's float-based layout stylesheets.
 *
 * `woocommerce-layout.css` positions the single-product columns, the cart
 * table, checkout and My Account with floats and percentage widths — for
 * example `div.product div.summary { float: right; width: 48% }`. This theme
 * lays all of those out with grid and flex, and the leftover 48% width is what
 * throws the product page out of alignment.
 *
 * `woocommerce-smallscreen.css` is the responsive half of the same file and is
 * replaced by the theme's own media queries.
 *
 * `woocommerce-general.css` stays: it carries the star-rating and notice icon
 * fonts the theme styles on top of.
 *
 * Sites that would rather keep Woo's layout can opt out:
 *   add_filter( 'ss_dequeue_woo_layout', '__return_false' );
 */
function ss_dequeue_woo_layout() {
	if ( ! apply_filters( 'ss_dequeue_woo_layout', true ) ) {
		return;
	}

	wp_dequeue_style( 'woocommerce-layout' );
	wp_dequeue_style( 'woocommerce-smallscreen' );
}
add_action( 'wp_enqueue_scripts', 'ss_dequeue_woo_layout', 99 );

/**
 * Force the Cart and Checkout blocks into their dark-controls mode.
 *
 * WooCommerce Blocks ships a proper dark treatment for every field, label,
 * dropdown and control, switched on by a `has-dark-controls` class on the
 * block wrapper. It is normally toggled per block in the editor ("Dark mode
 * inputs"), which means a Cart or Checkout page created before the theme was
 * installed keeps the light default and renders white boxes on the dark page.
 *
 * Adding the class at render time fixes those pages without the shop owner
 * having to open and re-save each block.
 *
 * @param string $content Rendered block HTML.
 * @param array  $block   Parsed block.
 * @return string
 */
function ss_woo_block_dark_controls( $content, $block ) {
	if ( empty( $block['blockName'] ) || ! is_string( $content ) || '' === trim( $content ) ) {
		return $content;
	}

	$targets = array( 'woocommerce/checkout', 'woocommerce/cart' );

	if ( ! in_array( $block['blockName'], $targets, true ) ) {
		return $content;
	}

	if ( false !== strpos( $content, 'has-dark-controls' ) ) {
		return $content;
	}

	if ( ! apply_filters( 'ss_woo_block_dark_controls', true, $block ) ) {
		return $content;
	}

	return preg_replace_callback(
		'/^(\s*<div\b)([^>]*)>/',
		function ( $matches ) {
			$attrs = $matches[2];

			if ( preg_match( '/\bclass\s*=\s*"/', $attrs ) ) {
				$attrs = preg_replace( '/\bclass\s*=\s*"/', 'class="has-dark-controls ', $attrs, 1 );
			} else {
				$attrs .= ' class="has-dark-controls"';
			}

			return $matches[1] . $attrs . '>';
		},
		$content,
		1
	);
}
add_filter( 'render_block', 'ss_woo_block_dark_controls', 10, 2 );
