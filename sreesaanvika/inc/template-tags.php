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
function ss_product_loop( $args = array(), $cols = 0 ) {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return false;
	}

	$cols = $cols ? $cols : absint( ss_option( 'shop_columns', 4 ) );

	$defaults = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => absint( ss_option( 'products_per_section', 8 ) ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'tax_query'           => array(), // phpcs:ignore WordPress.DB.SlowDBQuery
	);

	// Respect the "hide out of stock" catalog setting.
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
