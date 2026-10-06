<?php
/**
 * WooCommerce integration.
 *
 * @package OjasviDrapes
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
function od_woo_wrapper_start() {
	if ( is_product() ) {
		echo '<div class="od-container od-section od-section--tight">';
		return;
	}

	echo '<div class="od-container od-section od-section--tight">';

	if ( od_woo_has_sidebar() ) {
		echo '<div class="od-shop-layout">';
	}
}
add_action( 'woocommerce_before_main_content', 'od_woo_wrapper_start', 10 );

/**
 * Close the theme layout.
 */
function od_woo_wrapper_end() {
	if ( ! is_product() && od_woo_has_sidebar() ) {
		echo '</div>';
	}

	echo '</div>';
}
add_action( 'woocommerce_after_main_content', 'od_woo_wrapper_end', 10 );

/**
 * Whether the filter sidebar should render.
 *
 * @return bool
 */
function od_woo_has_sidebar() {
	if ( ! od_option( 'shop_sidebar', true ) ) {
		return false;
	}

	return is_shop() || is_product_category() || is_product_tag();
}

/**
 * Layout CSS for the shop sidebar grid — small enough to inline.
 */
function od_woo_layout_css() {
	if ( ! od_is_woo() ) {
		return;
	}

	/*
	 * The sticky rule has to sit inside a min-width query. Unscoped, its
	 * `.od-shop-layout > .od-shop-sidebar` beat the `position: fixed` that
	 * shop.css applies below 1024px, so on a phone the filter panel stayed in
	 * the grid — translated off-screen but still occupying a full-width row,
	 * which left a blank band above the products and pushed them down.
	 */
	$css = '.od-shop-layout{display:grid;grid-template-columns:280px minmax(0,1fr);gap:clamp(20px,3vw,42px);align-items:start;}'
		. '@media(min-width:1025px){.od-shop-layout > .od-shop-sidebar{position:sticky;top:calc(var(--od-header-h) + 18px);}}'
		. '@media(max-width:1024px){.od-shop-layout{grid-template-columns:minmax(0,1fr);}}';

	wp_add_inline_style( 'od-shop', $css );
}
add_action( 'wp_enqueue_scripts', 'od_woo_layout_css', 20 );

/* -------------------------------------------------------------------------
 * Catalog settings
 * ---------------------------------------------------------------------- */

/**
 * Products per page.
 *
 * @return int
 */
function od_products_per_page() {
	return absint( od_option( 'shop_per_page', 12 ) );
}
add_filter( 'loop_shop_per_page', 'od_products_per_page', 20 );

/**
 * Products per row.
 *
 * @return int
 */
function od_loop_columns() {
	return absint( od_option( 'shop_columns', 4 ) );
}
add_filter( 'loop_shop_columns', 'od_loop_columns', 20 );

/**
 * Related products count matches the grid.
 *
 * @param array $args Args.
 * @return array
 */
function od_related_args( $args ) {
	$args['posts_per_page'] = absint( od_option( 'shop_columns', 4 ) );
	$args['columns']        = absint( od_option( 'shop_columns', 4 ) );

	return $args;
}
add_filter( 'woocommerce_output_related_products_args', 'od_related_args', 20 );

/**
 * Use the portrait crop for catalog images.
 *
 * @param string $size Image size.
 * @return string
 */
function od_thumb_size( $size ) {
	return 'od-product';
}
add_filter( 'woocommerce_gallery_thumbnail_size', 'od_thumb_size' );

/* -------------------------------------------------------------------------
 * Product card pieces
 * ---------------------------------------------------------------------- */

/**
 * Discount percentage for a product, or 0.
 *
 * @param WC_Product $product Product.
 * @return int
 */
function od_discount_pct( $product ) {
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
function od_product_badges( $product, $context = 'card' ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$class = 'card' === $context ? 'od-badges' : 'od-gallery__badges';

	echo '<div class="' . esc_attr( $class ) . '">';

	if ( ! $product->is_in_stock() ) {
		echo '<span class="od-badge od-badge--soldout">' . esc_html__( 'Sold out', 'ojasvidrapes' ) . '</span>';
	} else {
		$pct = od_discount_pct( $product );

		if ( $pct > 0 ) {
			/* translators: %d: discount percentage */
			echo '<span class="od-badge od-badge--sale">' . esc_html( sprintf( __( '%d%% OFF', 'ojasvidrapes' ), $pct ) ) . '</span>';
		}

		// "New" for anything published in the last 30 days.
		$created = $product->get_date_created();

		if ( $created && ( time() - $created->getTimestamp() ) < 30 * DAY_IN_SECONDS ) {
			echo '<span class="od-badge od-badge--new">' . esc_html__( 'New', 'ojasvidrapes' ) . '</span>';
		}

		if ( $product->is_featured() ) {
			echo '<span class="od-badge od-badge--gold">' . esc_html__( 'Bestseller', 'ojasvidrapes' ) . '</span>';
		}

		$total = $product->get_total_sales();

		if ( $total > 25 ) {
			echo '<span class="od-badge od-badge--hot">' . esc_html__( 'Trending', 'ojasvidrapes' ) . '</span>';
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
function od_price_block( $product, $class = 'od-pcard__price' ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$pct = od_discount_pct( $product );

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
		echo '<span class="od-price-now">'
			. wp_kses_post( wc_price( $now ) ) . ' &ndash; ' . wp_kses_post( wc_price( $high ) )
			. '</span>';
	} else {
		echo '<span class="od-price-now">' . wp_kses_post( wc_price( $now ) ) . '</span>';

		if ( $regular > $now ) {
			echo '<span class="od-price-was">' . wp_kses_post( wc_price( $regular ) ) . '</span>';
		}
	}

	if ( $pct > 0 ) {
		/* translators: %d: discount percentage */
		echo '<span class="od-price-off">' . esc_html( sprintf( __( '%d%% off', 'ojasvidrapes' ), $pct ) ) . '</span>';
	}

	echo '</div>';
}

/**
 * Colour swatches derived from a variable product's colour attribute.
 *
 * @param WC_Product $product Product.
 * @param int        $limit   Max swatches to show.
 */
function od_card_swatches( $product, $limit = 5 ) {
	if ( ! od_option( 'card_swatches', true ) || ! $product instanceof WC_Product ) {
		return;
	}

	$terms = od_product_color_terms( $product );

	if ( ! $terms ) {
		return;
	}

	echo '<div class="od-pcard__swatches">';

	$shown = array_slice( $terms, 0, $limit );
	$link  = get_permalink( $product->get_id() );

	foreach ( $shown as $term ) {
		/*
		 * A colour with its own photos swaps the card image where it stands;
		 * one without can only be chosen on the product page, so the swatch
		 * links there with the colour pre-selected. Either way it does
		 * something when clicked.
		 */
		$chip  = function_exists( 'od_color_swatch_image' ) ? od_color_swatch_image( $product, $term->slug ) : '';
		$front = function_exists( 'od_color_swatch_image' ) ? od_color_swatch_image( $product, $term->slug, 'od-product' ) : '';

		printf(
			'<button type="button" class="od-swatch%1$s" style="background-color:%2$s" title="%3$s"'
			. ' data-color="%4$s" data-img="%5$s" data-front="%6$s" data-href="%7$s">'
			. '%8$s<span class="screen-reader-text">%3$s</span></button>',
			$chip ? ' od-swatch--img' : '',
			esc_attr( od_color_hex( $term->name, $term->term_id ) ),
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
		echo '<span class="od-swatch od-swatch--more">+' . esc_html( $extra ) . '</span>';
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
function od_product_color_terms( $product ) {
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
function od_stock_meter( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->managing_stock() ) {
		return;
	}

	$left  = (int) $product->get_stock_quantity();
	$alert = absint( od_option( 'stock_alert_qty', 8 ) );

	if ( $left <= 0 || $left > $alert || ! $alert ) {
		return;
	}

	$pct = max( 8, min( 100, ( $left / max( 1, $alert ) ) * 100 ) );

	echo '<div class="od-stockbar">';
	echo '<div class="od-stockbar__track"><div class="od-stockbar__fill" style="width:' . esc_attr( $pct ) . '%"></div></div>';
	/* translators: %d: units left in stock */
	echo '<span class="od-stockbar__label">' . esc_html( sprintf( _n( 'Only %d piece left', 'Only %d pieces left', $left, 'ojasvidrapes' ), $left ) ) . '</span>';
	echo '</div>';
}

/**
 * Wishlist / compare / quick-view buttons for a card.
 *
 * @param WC_Product $product Product.
 */
function od_card_actions( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return;
	}

	$id = $product->get_id();

	echo '<div class="od-pcard__actions">';

	if ( od_option( 'wishlist_on', true ) ) {
		printf(
			'<button type="button" class="od-icon-btn od-wishlist-btn" data-id="%1$d" data-tip="%2$s" aria-label="%2$s" aria-pressed="false">%3$s</button>',
			absint( $id ),
			esc_attr__( 'Add to wishlist', 'ojasvidrapes' ),
			od_icon( 'heart', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	if ( od_option( 'compare_on', true ) ) {
		printf(
			'<button type="button" class="od-icon-btn od-compare-btn" data-id="%1$d" data-tip="%2$s" aria-label="%2$s" aria-pressed="false">%3$s</button>',
			absint( $id ),
			esc_attr__( 'Add to compare', 'ojasvidrapes' ),
			od_icon( 'compare', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	if ( od_option( 'quickview', true ) ) {
		printf(
			'<button type="button" class="od-icon-btn od-quickview-btn" data-id="%1$d" data-tip="%2$s" aria-label="%2$s">%3$s</button>',
			absint( $id ),
			esc_attr__( 'Quick view', 'ojasvidrapes' ),
			od_icon( 'eye', 18 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
function od_secondary_image( $product ) {
	if ( ! od_option( 'card_hover_img', true ) || ! $product instanceof WC_Product ) {
		return '';
	}

	$ids = $product->get_gallery_image_ids();

	if ( empty( $ids ) ) {
		return '';
	}

	return wp_get_attachment_image_url( $ids[0], 'od-product' );
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
function od_cart_fragments( $fragments ) {
	ob_start();
	od_cart_count_badge();
	$fragments['.od-cart-count'] = ob_get_clean();

	ob_start();
	od_minicart_body();
	$fragments['.od-minicart-body'] = ob_get_clean();

	ob_start();
	od_minicart_foot();
	$fragments['.od-minicart-foot'] = ob_get_clean();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'od_cart_fragments' );

/**
 * The little number on the bag icon.
 */
function od_cart_count_badge() {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

	printf(
		'<span class="od-count-badge od-cart-count"%s>%s</span>',
		$count ? '' : ' hidden',
		esc_html( $count )
	);
}

/**
 * What a shopper has to spend to stop paying for delivery.
 *
 * WooCommerce's own Free shipping method carries the real number, so that is
 * read first — one figure to keep up to date rather than two that can drift
 * apart. The Customizer setting is the fallback, and what a shop that has not
 * configured a shipping zone yet still shows on the product page.
 *
 * @return float Zero when nothing qualifies for free shipping.
 */
function od_free_ship_threshold() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$fallback = (float) od_option( 'free_ship_threshold', 2999 );

	/*
	 * Not cached on the way out of these branches: they are the ones taken
	 * before WooCommerce has a cart, and remembering the fallback then would
	 * freeze it for the rest of the request — which is how the page can end up
	 * naming one figure while the progress meter counts towards another.
	 */
	if ( ! function_exists( 'WC' ) || ! WC()->cart || ! WC()->shipping() ) {
		return $fallback;
	}

	$packages = WC()->cart->get_shipping_packages();

	if ( ! $packages ) {
		return $fallback;
	}

	$zone = function_exists( 'wc_get_shipping_zone' ) ? wc_get_shipping_zone( reset( $packages ) ) : null;

	if ( ! $zone ) {
		return $fallback;
	}

	$cache = $fallback;

	foreach ( $zone->get_shipping_methods( true ) as $method ) {
		if ( 'free_shipping' !== $method->id ) {
			continue;
		}

		// "A minimum order amount" and "…or a coupon" both carry a figure.
		if ( in_array( $method->get_option( 'requires' ), array( 'min_amount', 'either', 'both' ), true ) ) {
			$minimum = (float) $method->get_option( 'min_amount' );

			if ( $minimum > 0 ) {
				$cache = $minimum;
				break;
			}
		}
	}

	/**
	 * The spend that earns free shipping.
	 *
	 * @param float $threshold Amount, or zero for none.
	 */
	$cache = (float) apply_filters( 'od_free_ship_threshold', $cache );

	return $cache;
}

/**
 * The free-shipping threshold as a price, for use in a sentence.
 *
 * Every "free shipping over ..." line on the site reads this rather than
 * naming a figure of its own. The threshold is whatever WooCommerce is
 * actually configured to give free shipping at, so hard-coded copy drifts
 * away from the meter the moment that setting is changed — which is exactly
 * what happened: the page promised free shipping over one amount while the
 * bag counted towards another.
 *
 * @return string Formatted price, or "" when there is no threshold.
 */
function od_free_ship_price() {
	$threshold = od_free_ship_threshold();

	return $threshold > 0 ? wp_strip_all_tags( wc_price( $threshold, array( 'decimals' => 0 ) ) ) : '';
}

/**
 * The free-shipping progress meter.
 *
 * Shown in the bag panel, at the top of the cart and above checkout — the
 * three places a shopper is deciding whether to add one more thing.
 *
 * @param string $where minicart|cart|checkout.
 */
function od_ship_meter( $where = 'minicart' ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	$threshold = od_free_ship_threshold();

	if ( $threshold <= 0 ) {
		return;
	}

	// The figure the shopper recognises: what the goods cost, before delivery.
	$subtotal = (float) WC()->cart->get_displayed_subtotal();
	$left     = max( 0, $threshold - $subtotal );
	$pct      = $threshold > 0 ? min( 100, ( $subtotal / $threshold ) * 100 ) : 100;
	$done     = $left <= 0;

	printf(
		'<div class="od-ship-meter od-ship-meter--%1$s%2$s" aria-live="polite">',
		esc_attr( $where ),
		$done ? ' is-done' : ''
	);

	if ( $done ) {
		echo '<p>' . od_icon( 'truck', 16 ) . '<strong>' . esc_html__( 'Free shipping unlocked', 'ojasvidrapes' ) . '</strong></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		echo '<p>' . od_icon( 'truck', 16 ) . wp_kses_post( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			sprintf(
				/* translators: %s: formatted amount remaining */
				__( 'Add %s more for <strong>free shipping</strong>', 'ojasvidrapes' ),
				wc_price( $left )
			)
		) . '</p>';
	}

	printf(
		'<div class="od-ship-meter__bar"><div class="od-ship-meter__fill" style="width:%s%%"></div></div>',
		esc_attr( round( $pct, 2 ) )
	);

	echo '</div>';
}

/**
 * Put the meter where the decision is being made.
 */
function od_ship_meter_cart() {
	od_ship_meter( 'cart' );
}
add_action( 'woocommerce_before_cart_table', 'od_ship_meter_cart', 5 );

/**
 * And once more above checkout.
 */
function od_ship_meter_checkout() {
	od_ship_meter( 'checkout' );
}
add_action( 'woocommerce_before_checkout_form', 'od_ship_meter_checkout', 8 );

/**
 * Mini-cart line items.
 */
function od_minicart_body() {
	echo '<div class="od-minicart-body od-panel__body">';

	if ( ! WC()->cart || WC()->cart->is_empty() ) {
		echo '<div class="od-panel__empty">';
		echo od_icon( 'bag', 62 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . esc_html__( 'Your bag is waiting to be filled.', 'ojasvidrapes' ) . '</p>';
		echo '<a class="od-btn od-btn--ghost od-btn--sm" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Start shopping', 'ojasvidrapes' ) . '</a>';
		echo '</div></div>';
		return;
	}

	od_ship_meter();

	echo '<ul class="od-minicart__list">';

	foreach ( WC()->cart->get_cart() as $key => $item ) {
		$product = apply_filters( 'woocommerce_cart_item_product', $item['data'], $item, $key );

		if ( ! $product || ! $product->exists() || $item['quantity'] <= 0 ) {
			continue;
		}

		$link  = $product->is_visible() ? $product->get_permalink( $item ) : '';
		$thumb = $product->get_image( 'od-thumb' );

		echo '<li class="od-minicart__item">';
		echo '<div>' . wp_kses_post( $thumb ) . '</div>';
		echo '<div>';
		echo '<div class="od-minicart__name">' . ( $link ? '<a href="' . esc_url( $link ) . '">' : '' ) . esc_html( $product->get_name() ) . ( $link ? '</a>' : '' ) . '</div>';

		$meta = wc_get_formatted_cart_item_data( $item, true );

		if ( $meta ) {
			echo '<div class="od-minicart__meta">' . wp_kses_post( $meta ) . '</div>';
		}

		echo '<div class="od-minicart__meta">' . esc_html( $item['quantity'] ) . ' &times; ' . wp_kses_post( wc_price( $product->get_price() ) ) . '</div>';
		echo '</div>';
		echo '<div class="od-minicart__price">' . wp_kses_post( WC()->cart->get_product_subtotal( $product, $item['quantity'] ) ) . '</div>';

		printf(
			'<a href="%s" class="od-minicart__remove" data-cart-key="%s" aria-label="%s">&times;</a>',
			esc_url( wc_get_cart_remove_url( $key ) ),
			esc_attr( $key ),
			/* translators: %s: product name */
			esc_attr( sprintf( __( 'Remove %s', 'ojasvidrapes' ), $product->get_name() ) )
		);

		echo '</li>';
	}

	echo '</ul></div>';
}

/**
 * Mini-cart footer with the subtotal and buttons.
 */
function od_minicart_foot() {
	$empty = ! WC()->cart || WC()->cart->is_empty();

	echo '<div class="od-minicart-foot od-panel__foot"' . ( $empty ? ' hidden' : '' ) . '>';

	if ( ! $empty ) {
		echo '<div class="od-minicart__subtotal"><span>' . esc_html__( 'Subtotal', 'ojasvidrapes' ) . '</span><strong>' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</strong></div>';
		echo '<a class="od-btn od-btn--block" href="' . esc_url( wc_get_checkout_url() ) . '">' . esc_html__( 'Checkout', 'ojasvidrapes' ) . '</a>';
		echo '<a class="od-btn od-btn--ghost od-btn--block" style="margin-top:8px" href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'View bag', 'ojasvidrapes' ) . '</a>';
		echo '<div class="od-securenote">' . od_icon( 'lock', 15 ) . '<span>' . esc_html__( '100% secure payments · UPI, cards, netbanking', 'ojasvidrapes' ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}

/* -------------------------------------------------------------------------
 * Single product extras
 * ---------------------------------------------------------------------- */

/**
 * Offers panel under the price.
 */
function od_single_offers() {
	$lines = od_list( od_option( 'offers_text', '' ) );

	if ( ! $lines ) {
		return;
	}

	echo '<div class="od-offers">';
	echo '<h4>' . esc_html__( 'Available offers', 'ojasvidrapes' ) . '</h4>';
	echo '<ul>';

	foreach ( $lines as $line ) {
		// `CODE` becomes a highlighted coupon chip.
		$html = preg_replace( '/`([^`]+)`/', '<code>$1</code>', esc_html( $line ) );
		echo '<li>' . od_icon( 'tag', 16 ) . '<span>' . wp_kses( $html, array( 'code' => array() ) ) . '</span></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</ul></div>';
}

/**
 * Trust badges under the buy button.
 */
function od_single_trust() {
	$items = array(
		/* translators: %s: the spend that earns free shipping, e.g. "₹2,999" */
		array( 'truck', sprintf( __( 'Free shipping over %s', 'ojasvidrapes' ), od_free_ship_price() ) ),
		array( 'refresh', __( '7-day easy returns', 'ojasvidrapes' ) ),
		array( 'shield', __( 'Authentic handloom', 'ojasvidrapes' ) ),
	);

	echo '<div class="od-trust">';

	foreach ( $items as $item ) {
		echo '<div class="od-trust__item">' . od_icon( $item[0], 22 ) . '<span>' . esc_html( $item[1] ) . '</span></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	echo '</div>';
}

/**
 * PIN code delivery checker.
 */
function od_single_pincode() {
	if ( ! od_option( 'pincode_check', true ) ) {
		return;
	}
	?>
	<div class="od-delivery">
		<label for="od-pin"><?php esc_html_e( 'Check delivery & COD availability', 'ojasvidrapes' ); ?></label>
		<div class="od-delivery__row">
			<input type="text" id="od-pin" inputmode="numeric" maxlength="6" placeholder="<?php esc_attr_e( 'Enter 6-digit PIN code', 'ojasvidrapes' ); ?>" />
			<button type="button" class="od-btn od-btn--ghost od-btn--sm od-pin-check"><?php esc_html_e( 'Check', 'ojasvidrapes' ); ?></button>
		</div>
		<p class="od-delivery__result" role="status"></p>
	</div>
	<?php
}

/**
 * Share row.
 */
function od_single_share() {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( get_the_title() );

	$links = array(
		'whatsapp'  => 'https://wa.me/?text=' . $title . '%20' . $url,
		'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
		'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title,
		'twitter'   => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
	);

	echo '<div class="od-share"><span>' . esc_html__( 'Share', 'ojasvidrapes' ) . '</span>';

	foreach ( $links as $net => $href ) {
		printf(
			'<a class="od-icon-btn" href="%s" target="_blank" rel="noopener noreferrer" aria-label="%s">%s</a>',
			esc_url( $href ),
			/* translators: %s: network name */
			esc_attr( sprintf( __( 'Share on %s', 'ojasvidrapes' ), ucfirst( $net ) ) ),
			od_icon( $net, 17 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
	}

	printf(
		'<button type="button" class="od-icon-btn od-copy-link" data-url="%s" aria-label="%s">%s</button>',
		esc_url( get_permalink() ),
		esc_attr__( 'Copy link', 'ojasvidrapes' ),
		od_icon( 'link', 17 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);

	echo '</div>';
}

/**
 * Take WooCommerce's own callbacks off the single-product summary hook.
 *
 * The theme's summary template lays out the title, rating, price, excerpt,
 * add-to-cart and meta itself, but it still fires
 * woocommerce_single_product_summary so that plugins hooking there — offer
 * banners, size charts, trust badges — actually appear. Without this the
 * default callbacks would print all of it twice.
 */
function od_unhook_woo_summary() {
	$defaults = array(
		5  => 'woocommerce_template_single_title',
		10 => 'woocommerce_template_single_price',
		20 => 'woocommerce_template_single_excerpt',
		30 => 'woocommerce_template_single_add_to_cart',
		40 => 'woocommerce_template_single_meta',
		50 => 'woocommerce_template_single_sharing',
	);

	foreach ( $defaults as $priority => $callback ) {
		remove_action( 'woocommerce_single_product_summary', $callback, $priority );
	}

	// The rating shares priority 10 with the price.
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
}
add_action( 'wp', 'od_unhook_woo_summary' );

/**
 * A "Buy it now" button that adds to the cart and jumps to checkout.
 */
function od_buy_now_button() {
	global $product;

	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
		return;
	}

	/*
	 * The button has to carry name="add-to-cart" itself. A browser submits
	 * only the clicked submit button's name/value pair, so a button named
	 * od_buy_now sent the flag but never told WooCommerce what to add, and
	 * the click did nothing at all on a simple product.
	 *
	 * The flag moves to a hidden field that shop.js flips on click. Without
	 * JS the button still adds the product to the bag — it just does not skip
	 * ahead to checkout.
	 */
	printf(
		'<input type="hidden" name="od_buy_now" value="0" class="od-buy-now-flag" />'
		. '<button type="submit" name="add-to-cart" value="%1$d" class="od-btn od-btn--ghost od-buynow" data-buy-now>%2$s</button>',
		absint( $product->get_id() ),
		esc_html__( 'Buy it now', 'ojasvidrapes' )
	);
}

/**
 * Redirect straight to checkout when "Buy it now" was used.
 *
 * @param string $url Redirect URL.
 * @return string
 */
function od_buy_now_redirect( $url ) {
	// The hidden field is always submitted, so test the value, not its presence.
	$flag = isset( $_REQUEST['od_buy_now'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['od_buy_now'] ) ) : '0'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $flag && '0' !== $flag ) {
		return wc_get_checkout_url();
	}

	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'od_buy_now_redirect' );

/**
 * Highlight the active My Account menu item for the CSS.
 *
 * @param array  $classes Classes.
 * @param string $endpoint Endpoint.
 * @return array
 */
function od_account_menu_classes( $classes, $endpoint ) {
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
add_filter( 'woocommerce_account_menu_item_classes', 'od_account_menu_classes', 10, 2 );

/**
 * Nicer placeholder image.
 *
 * @param string $src Placeholder src.
 * @return string
 */
function od_placeholder_img( $src ) {
	return od_placeholder( 'product' );
}
add_filter( 'woocommerce_placeholder_img_src', 'od_placeholder_img' );

/**
 * Route customers to the theme's auth page instead of the bare Woo login.
 *
 * Only when a page using the auth template exists and the user is logged out.
 */
function od_auth_redirect() {
	if ( ! is_account_page() || is_user_logged_in() ) {
		return;
	}

	// Endpoints like lost-password must keep working.
	if ( WC()->query && WC()->query->get_current_endpoint() ) {
		return;
	}

	if ( ! od_option( 'auth_redirect', false ) ) {
		return;
	}

	$url = od_page_url( 'auth' );

	if ( $url && wp_parse_url( $url, PHP_URL_PATH ) !== wp_parse_url( wc_get_page_permalink( 'myaccount' ), PHP_URL_PATH ) ) {
		wp_safe_redirect( $url );
		exit;
	}
}
add_action( 'template_redirect', 'od_auth_redirect' );

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
function od_variation_dropdown_args( $args ) {
	$args['class'] = trim( ( isset( $args['class'] ) ? $args['class'] : '' ) . ' od-variation-select' );

	return $args;
}
add_filter( 'woocommerce_dropdown_variation_attribute_options_args', 'od_variation_dropdown_args' );

/**
 * Rating breakdown shown above the review list.
 *
 * @param WC_Product $product Product.
 */
function od_reviews_summary( $product ) {
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
	<div class="od-reviews-summary">
		<div class="od-reviews-score">
			<strong><?php echo esc_html( number_format_i18n( (float) $product->get_average_rating(), 1 ) ); ?></strong>
			<?php echo od_stars( (float) $product->get_average_rating() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<small>
				<?php
				printf(
					/* translators: %s: number of reviews */
					esc_html( _n( 'Based on %s review', 'Based on %s reviews', $total, 'ojasvidrapes' ) ),
					esc_html( number_format_i18n( $total ) )
				);
				?>
			</small>
		</div>

		<div class="od-reviews-bars">
			<?php foreach ( $buckets as $stars => $count ) : ?>
				<?php $pct = $counted ? round( ( $count / $counted ) * 100 ) : 0; ?>
				<div class="od-reviews-bar">
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
 *   add_filter( 'od_dequeue_woo_layout', '__return_false' );
 */
function od_dequeue_woo_layout() {
	if ( ! apply_filters( 'od_dequeue_woo_layout', true ) ) {
		return;
	}

	wp_dequeue_style( 'woocommerce-layout' );
	wp_dequeue_style( 'woocommerce-smallscreen' );
}
add_action( 'wp_enqueue_scripts', 'od_dequeue_woo_layout', 99 );

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
function od_woo_block_dark_controls( $content, $block ) {
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

	if ( ! apply_filters( 'od_woo_block_dark_controls', true, $block ) ) {
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
add_filter( 'render_block', 'od_woo_block_dark_controls', 10, 2 );

/**
 * The attribute filters for the shop sidebar.
 *
 * Off unless the shop asks for them. Attributes exist on a product for all
 * sorts of reasons — fabric, blouse length, wash care — and turning every one
 * of them into a sidebar filter invents a way of shopping the shop never
 * wanted. A saree is one design, not a pattern crossed with a colour, so by
 * default nothing is offered to cross.
 *
 * Where they are asked for, the links carry WooCommerce's own filter_pa_*
 * query arguments, which WC_Query turns into a tax query on the shop loop
 * whether or not the Layered Nav widget is anywhere on the page, so this
 * needs no query code of its own. Several values stack: clicking a second
 * one adds it rather than replacing the first.
 *
 * @return array
 */
function od_filter_attributes() {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return array();
	}

	$wanted = (string) od_option( 'filter_attrs', 'none' );

	if ( 'none' === $wanted || '' === $wanted ) {
		return array();
	}

	$base = od_filter_base_url();
	$out  = array();

	foreach ( wc_get_attribute_taxonomies() as $attribute ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute->attribute_name );

		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		if ( 'all' !== $wanted && $taxonomy !== $wanted ) {
			continue;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			)
		);

		if ( ! $terms || is_wp_error( $terms ) ) {
			continue;
		}

		$key    = 'filter_' . sanitize_title( $attribute->attribute_name );
		$chosen = od_filter_chosen( $key );
		$items  = array();

		foreach ( $terms as $term ) {
			$on   = in_array( $term->slug, $chosen, true );
			$next = $on
				? array_values( array_diff( $chosen, array( $term->slug ) ) )
				: array_merge( $chosen, array( $term->slug ) );

			$items[] = array(
				'name'  => $term->name,
				'count' => $term->count,
				'on'    => $on,
				'url'   => $next
					? add_query_arg( $key, implode( ',', $next ), $base )
					: remove_query_arg( $key, $base ),
			);
		}

		$out[] = array(
			'label' => $attribute->attribute_label,
			'terms' => $items,
		);
	}

	return $out;
}

/**
 * The slugs already chosen for one filter.
 *
 * @param string $key Query argument, e.g. filter_pattern.
 * @return array
 */
function od_filter_chosen( $key ) {
	// Reading the current filter state off the URL; nothing is written here.
	if ( empty( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return array();
	}

	$raw = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return array_values( array_filter( array_map( 'sanitize_title', array_map( 'trim', explode( ',', $raw ) ) ) ) );
}

/**
 * The URL filter links are built from: wherever the shopper is now, minus the
 * page number, so narrowing a filter never lands them on page 7 of 3.
 *
 * @return string
 */
function od_filter_base_url() {
	global $wp;

	$base = home_url( add_query_arg( array(), $wp->request ? $wp->request : '' ) );
	$args = array();

	foreach ( array_keys( $_GET ) as $key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = sanitize_key( $key );

		if ( 0 === strpos( $key, 'filter_' ) || in_array( $key, array( 'orderby', 'min_price', 'max_price', 's', 'post_type' ), true ) ) {
			$args[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
	}

	return $args ? add_query_arg( $args, $base ) : $base;
}
