<?php
/**
 * Reusable markup helpers.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * The brand lockup used in the header, drawer and footer.
 *
 * @param string $size "sm"|"md".
 */
function ss_brand( $size = 'md' ) {
	$name = get_bloginfo( 'name' );
	$tag  = ss_option( 'brand_tagline', __( 'Heritage Weaves', 'sreesaanvika' ) );

	echo '<a class="ss-brand ss-brand--' . esc_attr( $size ) . '" href="' . esc_url( home_url( '/' ) ) . '" rel="home">';

	if ( has_custom_logo() ) {
		$id  = get_theme_mod( 'custom_logo' );
		$img = wp_get_attachment_image( $id, 'full', false, array( 'alt' => esc_attr( $name ) ) );
		echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		// Split the name so the second word can take the gold gradient.
		$parts = preg_split( '/\s+/', trim( $name ), 2 );
		$first = isset( $parts[0] ) ? $parts[0] : 'Sree';
		$rest  = isset( $parts[1] ) ? $parts[1] : '';

		echo '<span class="ss-brand__mark" aria-hidden="true">' . esc_html( mb_substr( $first, 0, 1 ) ) . '</span>';
		echo '<span class="ss-brand__text">';
		echo '<span class="ss-brand__name">' . esc_html( $first );

		if ( $rest ) {
			echo ' <em>' . esc_html( $rest ) . '</em>';
		}

		echo '</span>';

		if ( $tag ) {
			echo '<span class="ss-brand__tag">' . esc_html( $tag ) . '</span>';
		}

		echo '</span>';
	}

	echo '</a>';
}

/**
 * Section heading block.
 *
 * @param string $eyebrow Small label above the heading.
 * @param string $title   Heading.
 * @param string $text    Supporting copy.
 * @param string $align   "center"|"left".
 */
function ss_section_head( $eyebrow, $title, $text = '', $align = 'center' ) {
	$class = 'ss-section-head' . ( 'left' === $align ? ' ss-section-head--left' : '' );

	echo '<div class="' . esc_attr( $class ) . '">';

	if ( $eyebrow ) {
		echo '<span class="ss-eyebrow">' . esc_html( $eyebrow ) . '</span>';
	}

	echo '<h2>' . ss_kses( $title ) . '</h2>';

	if ( 'center' === $align ) {
		echo '<div class="ss-ornament" aria-hidden="true">' . ss_icon( 'lotus', 22 ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	if ( $text ) {
		echo '<p>' . esc_html( $text ) . '</p>';
	}

	echo '</div>';
}

/**
 * Breadcrumb trail.
 */
function ss_breadcrumbs() {
	if ( is_front_page() ) {
		return;
	}

	$sep   = '<span class="sep" aria-hidden="true">&#47;</span>';
	$items = array( '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'sreesaanvika' ) . '</a>' );

	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
		$shop_id = wc_get_page_id( 'shop' );

		if ( $shop_id > 0 && ! is_shop() ) {
			$items[] = '<a href="' . esc_url( get_permalink( $shop_id ) ) . '">' . esc_html( get_the_title( $shop_id ) ) . '</a>';
		}

		if ( is_product_category() || is_product_tag() ) {
			$items[] = esc_html( single_term_title( '', false ) );
		} elseif ( is_product() ) {
			$terms = get_the_terms( get_the_ID(), 'product_cat' );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$term    = array_shift( $terms );
				$items[] = '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $term->name ) . '</a>';
			}

			$items[] = esc_html( get_the_title() );
		} elseif ( is_shop() ) {
			$items[] = esc_html( get_the_title( $shop_id ) );
		} else {
			$items[] = esc_html( get_the_title() );
		}
	} elseif ( is_singular() ) {
		if ( is_singular( 'post' ) ) {
			$cats = get_the_category();
			if ( $cats ) {
				$items[] = '<a href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
			}
		}

		$parent_id = wp_get_post_parent_id( get_the_ID() );
		if ( $parent_id ) {
			$items[] = '<a href="' . esc_url( get_permalink( $parent_id ) ) . '">' . esc_html( get_the_title( $parent_id ) ) . '</a>';
		}

		$items[] = esc_html( get_the_title() );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$items[] = esc_html( single_term_title( '', false ) );
	} elseif ( is_search() ) {
		/* translators: %s: search term */
		$items[] = esc_html( sprintf( __( 'Search: %s', 'sreesaanvika' ), get_search_query() ) );
	} elseif ( is_author() ) {
		$items[] = esc_html( get_the_author() );
	} elseif ( is_archive() ) {
		$items[] = esc_html( get_the_archive_title() );
	} elseif ( is_404() ) {
		$items[] = esc_html__( 'Page not found', 'sreesaanvika' );
	} else {
		$items[] = esc_html( wp_get_document_title() );
	}

	echo '<nav class="ss-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'sreesaanvika' ) . '">';
	echo implode( ' ' . $sep . ' ', $items ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '</nav>';
}

/**
 * Page header with title and breadcrumb.
 *
 * @param string $title Optional title override.
 * @param string $sub   Optional subtitle.
 */
function ss_page_header( $title = '', $sub = '' ) {
	if ( ! $title ) {
		if ( is_home() && ! is_front_page() ) {
			$title = get_the_title( get_option( 'page_for_posts' ) );
		} elseif ( is_search() ) {
			/* translators: %s: search term */
			$title = sprintf( __( 'Search results for “%s”', 'sreesaanvika' ), get_search_query() );
		} elseif ( is_archive() ) {
			$title = get_the_archive_title();
		} elseif ( is_404() ) {
			$title = __( '404', 'sreesaanvika' );
		} else {
			$title = get_the_title();
		}
	}

	echo '<header class="ss-pagehead"><div class="ss-container">';
	echo '<h1>' . wp_kses_post( $title ) . '</h1>';

	if ( $sub ) {
		echo '<p class="ss-pagehead__sub">' . esc_html( $sub ) . '</p>';
	}

	ss_breadcrumbs();
	echo '</div></header>';
}

/**
 * A single USP item.
 *
 * @param string $icon  Icon key.
 * @param string $title Title.
 * @param string $text  Text.
 */
function ss_usp_item( $icon, $title, $text ) {
	echo '<div class="ss-usp__item">';
	echo '<span class="ss-usp__icon">' . ss_icon( $icon, 22 ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<div><h4>' . esc_html( $title ) . '</h4><p>' . esc_html( $text ) . '</p></div>';
	echo '</div>';
}

/**
 * Social links row.
 *
 * @param string $class Extra class.
 */
function ss_socials( $class = '' ) {
	$nets = array( 'instagram', 'facebook', 'youtube', 'whatsapp', 'pinterest' );
	$out  = '';

	foreach ( $nets as $net ) {
		$url = ss_option( 'social_' . $net, '' );

		if ( ! $url ) {
			continue;
		}

		$out .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( ucfirst( $net ) ) . '">' . ss_icon( $net, 17 ) . '</a>';
	}

	if ( ! $out ) {
		return;
	}

	echo '<div class="ss-socials ' . esc_attr( $class ) . '">' . $out . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Pagination for archives.
 */
function ss_pagination() {
	$links = paginate_links(
		array(
			'type'      => 'list',
			'mid_size'  => 1,
			'prev_text' => ss_icon( 'arrow-left', 16 ) . '<span class="screen-reader-text">' . esc_html__( 'Previous', 'sreesaanvika' ) . '</span>',
			'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next', 'sreesaanvika' ) . '</span>' . ss_icon( 'arrow-right', 16 ),
		)
	);

	if ( ! $links ) {
		return;
	}

	echo '<nav class="ss-pagination" aria-label="' . esc_attr__( 'Pagination', 'sreesaanvika' ) . '">';
	echo wp_kses_post( str_replace( array( "<ul class='page-numbers'>", '</ul>', '<li>', '</li>' ), '', $links ) );
	echo '</nav>';
}

/**
 * Post card used on the blog and homepage journal strip.
 */
function ss_post_card() {
	?>
	<article <?php post_class( 'ss-card ss-post' ); ?>>
		<?php if ( has_post_thumbnail() ) : ?>
			<a class="ss-post__thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
				<?php the_post_thumbnail( 'ss-blog', array( 'loading' => 'lazy' ) ); ?>
			</a>
		<?php endif; ?>

		<div class="ss-post__body">
			<div class="ss-post__meta">
				<span><?php echo esc_html( get_the_date() ); ?></span>
				<?php
				$cats = get_the_category();
				if ( $cats ) :
					?>
					<span><?php echo esc_html( $cats[0]->name ); ?></span>
				<?php endif; ?>
			</div>

			<h3 class="ss-post__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
			<p class="ss-post__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>

			<a class="ss-readmore" href="<?php the_permalink(); ?>">
				<?php esc_html_e( 'Read the story', 'sreesaanvika' ); ?>
				<?php ss_the_icon( 'arrow-right', 15 ); ?>
			</a>
		</div>
	</article>
	<?php
}

/**
 * The search form used in the overlay and the 404 page.
 *
 * @param string $class Extra class.
 */
function ss_search_form( $class = '' ) {
	?>
	<form role="search" method="get" class="ss-searchform <?php echo esc_attr( $class ); ?>" action="<?php echo esc_url( home_url( '/' ) ); ?>">
		<label class="screen-reader-text" for="ss-search-field"><?php esc_html_e( 'Search for:', 'sreesaanvika' ); ?></label>
		<input type="search" id="ss-search-field" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"
			placeholder="<?php esc_attr_e( 'Search sarees, jewellery, dresses…', 'sreesaanvika' ); ?>" autocomplete="off" />
		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<input type="hidden" name="post_type" value="product" />
		<?php endif; ?>
		<button type="submit" class="ss-icon-btn" aria-label="<?php esc_attr_e( 'Search', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'search', 19 ); ?>
		</button>
	</form>
	<?php
}

/**
 * Render a countdown block.
 *
 * @param string $end Datetime string.
 */
function ss_countdown( $end ) {
	if ( ! $end ) {
		// Default to midnight three days out so the block is never empty.
		$end = gmdate( 'Y-m-d H:i', current_time( 'timestamp' ) + 3 * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	}

	$units = array(
		'days'  => __( 'Days', 'sreesaanvika' ),
		'hours' => __( 'Hours', 'sreesaanvika' ),
		'mins'  => __( 'Mins', 'sreesaanvika' ),
		'secs'  => __( 'Secs', 'sreesaanvika' ),
	);

	echo '<div class="ss-countdown" data-countdown="' . esc_attr( $end ) . '">';

	foreach ( $units as $key => $label ) {
		echo '<div class="ss-countdown__unit">';
		echo '<span class="ss-countdown__num" data-unit="' . esc_attr( $key ) . '">00</span>';
		echo '<span class="ss-countdown__lbl">' . esc_html( $label ) . '</span>';
		echo '</div>';
	}

	echo '</div>';
}

/**
 * Print an "empty state" panel.
 *
 * @param string $icon  Icon key.
 * @param string $title Title.
 * @param string $text  Body copy.
 * @param string $url   Button URL.
 * @param string $label Button label.
 */
function ss_empty_state( $icon, $title, $text, $url = '', $label = '' ) {
	echo '<div class="ss-empty-state">';
	echo ss_icon( $icon, 72 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo '<h3>' . esc_html( $title ) . '</h3>';
	echo '<p>' . esc_html( $text ) . '</p>';

	if ( $url && $label ) {
		echo '<a class="ss-btn" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}

	echo '</div>';
}

/**
 * Render a WooCommerce product loop from query args.
 *
 * @param array $args  WP_Query args merged over sensible product defaults.
 * @param int   $cols  Columns.
 * @return bool True when something was rendered.
 */
function ss_product_loop( $args = array(), $cols = 0, $more = array() ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	$cols = $cols ? $cols : absint( ss_option( 'shop_columns', 4 ) );
	$per  = isset( $args['posts_per_page'] ) ? (int) $args['posts_per_page'] : absint( ss_option( 'products_per_section' ) );

	$defaults = ss_product_query_defaults();

	$defaults['posts_per_page'] = $per;

	// Counting rows is only worth it when a Load more button needs to know.
	$defaults['no_found_rows'] = empty( $more );

	$query = new WP_Query( array_merge( $defaults, $args ) );

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return false;
	}

	echo '<ul class="products columns-' . esc_attr( $cols ) . '">';

	while ( $query->have_posts() ) {
		$query->the_post();
		wc_get_template_part( 'content', 'product' );
	}

	echo '</ul>';

	if ( $more && $query->max_num_pages > 1 && $per > 0 ) {
		ss_load_more_button(
			array_merge(
				$more,
				array(
					'page'    => 1,
					'per'     => absint( ss_option( 'loadmore_step' ) ) ? absint( ss_option( 'loadmore_step' ) ) : $per,
					'columns' => $cols,
					'pages'   => $query->max_num_pages,
					'total'   => $query->found_posts,
				)
			)
		);
	}

	wp_reset_postdata();

	return true;
}

/**
 * Query args that select products in a category by slug.
 *
 * @param string|array $slugs Category slug(s).
 * @return array
 */
function ss_cat_query( $slugs ) {
	return array(
		'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => (array) $slugs,
			),
		),
	);
}

/**
 * Product categories for a homepage section.
 *
 * A comma or newline separated list of slugs wins, and keeps the shop owner's
 * order. With nothing listed the busiest categories are used, which is what a
 * fresh install needs.
 *
 * @param string $slugs     Chosen slugs, in display order.
 * @param int    $count     How many to show when choosing automatically.
 * @param bool   $top_level Restrict the automatic pick to top-level terms.
 * @return WP_Term[]
 */
/**
 * Products chosen by hand, in the order they were chosen.
 *
 * @param string $ids   Comma separated product ids.
 * @param int    $count How many to fall back to when nothing is chosen.
 * @return WC_Product[]
 */
function ss_picked_products( $ids = '', $count = 6 ) {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$count  = max( 1, absint( $count ) );
	$chosen = array_values( array_filter( array_map( 'absint', preg_split( '/[,\r\n\s]+/', (string) $ids ) ) ) );

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => $chosen ? count( $chosen ) : $count,
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	);

	if ( $chosen ) {
		// post__in on its own returns them in date order; this keeps the order
		// the shop dragged them into.
		$args['post__in'] = $chosen;
		$args['orderby']  = 'post__in';
	} else {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	$query = new WP_Query( $args );
	$out   = array();

	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post );

		if ( $product && $product->is_visible() ) {
			$out[] = $product;
		}
	}

	wp_reset_postdata();

	return $out;
}

/**
 * A product's picture, or the theme's placeholder.
 *
 * @param WC_Product $product Product.
 * @param string     $size    Image size.
 * @return string
 */
function ss_product_image_url( $product, $size = 'ss-product' ) {
	if ( ! $product instanceof WC_Product ) {
		return ss_placeholder( 'product' );
	}

	$id  = $product->get_image_id();
	$url = $id ? wp_get_attachment_image_url( $id, $size ) : '';

	return $url ? $url : ss_placeholder( 'product' );
}

function ss_category_terms( $slugs = '', $count = 6, $top_level = true ) {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$chosen = array_filter( array_map( 'trim', preg_split( '/[,\r\n]+/', (string) $slugs ) ) );

	if ( $chosen ) {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
				'slug'       => array_map( 'sanitize_title', $chosen ),
			)
		);

		if ( ! $terms || is_wp_error( $terms ) ) {
			return array();
		}

		// get_terms ignores the order of the slug list, so restore it.
		$by_slug = array();

		foreach ( $terms as $term ) {
			$by_slug[ $term->slug ] = $term;
		}

		$ordered = array();

		foreach ( $chosen as $slug ) {
			$slug = sanitize_title( $slug );

			if ( isset( $by_slug[ $slug ] ) ) {
				$ordered[] = $by_slug[ $slug ];
			}
		}

		return $ordered;
	}

	$args = array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => max( 1, absint( $count ) ),
		'orderby'    => 'count',
		'order'      => 'DESC',
		'exclude'    => array( get_option( 'default_product_cat' ) ),
	);

	if ( $top_level ) {
		$args['parent'] = 0;
	}

	$terms = get_terms( $args );

	return ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
}

/**
 * Tile sizes for the category mosaic, for any number of tiles.
 *
 * The grid is six columns wide, so each row has to add up to six. Small counts
 * get a hand-tuned pattern; beyond that the tiles are laid out in rows of
 * three twos or two threes so nothing is ever left with a hole beside it.
 *
 * @param int $count How many tiles.
 * @return string[] One size class per tile.
 */
function ss_category_tile_sizes( $count ) {
	$count = max( 1, (int) $count );

	$patterns = array(
		1 => array( 'w6 ss-cat--h2' ),
		2 => array( 'w3', 'w3' ),
		3 => array( 'w4 ss-cat--h2', 'w2', 'w2' ),
		4 => array( 'w3', 'w3', 'w3', 'w3' ),
		5 => array( 'w4 ss-cat--h2', 'w2', 'w2', 'w3', 'w3' ),
		6 => array( 'w4 ss-cat--h2', 'w2', 'w2', 'w2', 'w2', 'w2' ),
	);

	if ( isset( $patterns[ $count ] ) ) {
		return $patterns[ $count ];
	}

	// Lead with the hero pattern, then fill whole rows with the remainder.
	$sizes     = $patterns[5];
	$remaining = $count - 5;

	while ( $remaining > 0 ) {
		if ( 0 === $remaining % 3 || $remaining > 4 ) {
			$take = 3;
			$size = 'w2';
		} elseif ( 0 === $remaining % 2 ) {
			$take = 2;
			$size = 'w3';
		} else {
			// A single leftover tile spans the row rather than leaving a hole.
			$take = 1;
			$size = 'w6';
		}

		for ( $i = 0; $i < $take && $remaining > 0; $i++ ) {
			$sizes[]    = $size;
			$remaining--;
		}
	}

	return $sizes;
}

/**
 * The product queries the homepage sections use, keyed so the browser can ask
 * for the next page without ever sending raw query arguments.
 *
 * @return array
 */
function ss_product_sections() {
	return apply_filters(
		'ss_product_sections',
		array(
			'new'         => array( 'orderby' => 'date', 'order' => 'DESC' ),
			'bestsellers' => array( 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'sarees'      => array_merge( ss_cat_query( array( 'sarees', 'saree', 'silk-sarees' ) ), array( 'orderby' => 'popularity' ) ),
			'jewel'       => array_merge( ss_cat_query( array( 'jewellery', 'jewelry', 'temple-jewellery' ) ), array( 'orderby' => 'date' ) ),
			'dresses'     => array_merge( ss_cat_query( array( 'dresses', 'dress', 'lehengas' ) ), array( 'orderby' => 'date' ) ),
		)
	);
}

/**
 * Query args for an Elementor Product Grid widget, rebuilt from validated
 * request values rather than trusted from the browser.
 *
 * @param string $source   One of the widget's source options.
 * @param string $category Category slug, or empty.
 * @return array
 */
function ss_product_source_args( $source, $category = '' ) {
	$args = array();

	switch ( $source ) {
		case 'best':
			$args = array( 'meta_key' => 'total_sales', 'orderby' => 'meta_value_num', 'order' => 'DESC' ); // phpcs:ignore WordPress.DB.SlowDBQuery
			break;

		case 'sale':
			$ids  = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();
			$args = array( 'post__in' => $ids ? $ids : array( 0 ), 'orderby' => 'date' );
			break;

		case 'featured':
			$args = array(
				'tax_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery
					array( 'taxonomy' => 'product_visibility', 'field' => 'name', 'terms' => 'featured' ),
				),
			);
			break;

		case 'rated':
			$args = array( 'meta_key' => '_wc_average_rating', 'orderby' => 'meta_value_num', 'order' => 'DESC' ); // phpcs:ignore WordPress.DB.SlowDBQuery
			break;

		case 'random':
			$args = array( 'orderby' => 'rand' );
			break;

		default:
			$args = array( 'orderby' => 'date', 'order' => 'DESC' );
	}

	if ( $category ) {
		$cat = ss_cat_query( sanitize_title( $category ) );

		$args['tax_query'] = isset( $args['tax_query'] ) // phpcs:ignore WordPress.DB.SlowDBQuery
			? array_merge( $args['tax_query'], $cat['tax_query'] )
			: $cat['tax_query'];
	}

	return $args;
}

/**
 * Render just the <li> items for a page of products.
 *
 * @param array $args Query args.
 * @param int   $page Page number.
 * @param int   $per  Items per page.
 * @return array{html:string,more:bool}
 */
function ss_product_items( $args, $page, $per ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return array( 'html' => '', 'more' => false );
	}

	$query = new WP_Query(
		array_merge(
			ss_product_query_defaults(),
			$args,
			array(
				'posts_per_page' => max( 1, (int) $per ),
				'paged'          => max( 1, (int) $page ),
				'no_found_rows'  => false,
			)
		)
	);

	if ( ! $query->have_posts() ) {
		wp_reset_postdata();
		return array( 'html' => '', 'more' => false );
	}

	ob_start();

	while ( $query->have_posts() ) {
		$query->the_post();
		wc_get_template_part( 'content', 'product' );
	}

	$html = ob_get_clean();
	$more = $query->max_num_pages > max( 1, (int) $page );

	wp_reset_postdata();

	return array( 'html' => $html, 'more' => $more );
}

/**
 * The visibility and stock rules every product query in the theme shares.
 *
 * @return array
 */
function ss_product_query_defaults() {
	$defaults = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'tax_query'           => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
		$defaults['tax_query'][] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => 'outofstock',
			'operator' => 'NOT IN',
		);
	}

	$defaults['tax_query'][] = array(
		'taxonomy' => 'product_visibility',
		'field'    => 'name',
		'terms'    => 'exclude-from-catalog',
		'operator' => 'NOT IN',
	);

	return $defaults;
}

/**
 * The "Load more" button under a product grid.
 *
 * @param array $data Button data attributes.
 */
function ss_load_more_button( $data ) {
	if ( ! ss_option( 'loadmore' ) ) {
		return;
	}

	$attrs = '';

	foreach ( $data as $key => $value ) {
		$attrs .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
	}

	printf(
		'<div class="ss-loadmore"><button type="button" class="ss-btn ss-btn--ghost ss-btn--lg ss-loadmore__btn"%s>%s%s</button></div>',
		$attrs, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		esc_html__( 'Load more', 'sreesaanvika' ),
		ss_icon( 'chevron-down', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
}
