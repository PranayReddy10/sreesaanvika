<?php
/**
 * Search engine and social metadata.
 *
 * The theme only takes this over when no dedicated SEO plugin is running.
 * Yoast, Rank Math, SEOPress and All in One SEO all emit the same tags, and
 * two sets of canonical or Open Graph tags is worse than none.
 *
 * WooCommerce already prints Product, Review and Order structured data of its
 * own, so the graph below deliberately leaves products to WooCommerce and
 * enriches its output through a filter instead of competing with it.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a dedicated SEO plugin handling metadata?
 *
 * @return bool
 */
function ss_seo_plugin_active() {
	$found = defined( 'WPSEO_VERSION' )                 // Yoast.
		|| defined( 'RANK_MATH_VERSION' )               // Rank Math.
		|| defined( 'SEOPRESS_VERSION' )                // SEOPress.
		|| defined( 'AIOSEO_VERSION' )                  // All in One SEO.
		|| class_exists( 'The_SEO_Framework\\Load' );    // The SEO Framework.

	return (bool) apply_filters( 'ss_seo_plugin_active', $found );
}

/**
 * Should the theme output metadata at all?
 *
 * @return bool
 */
function ss_seo_enabled() {
	return ss_option( 'seo_enable' ) && ! ss_seo_plugin_active();
}

/**
 * Trim a string down to a usable meta description.
 *
 * @param string $text  Raw text.
 * @param int    $limit Character budget.
 * @return string
 */
function ss_seo_trim( $text, $limit = 155 ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $text ), true );
	$text = preg_replace( '/\s+/u', ' ', $text );
	$text = trim( $text );

	if ( '' === $text ) {
		return '';
	}

	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}

	$cut = mb_substr( $text, 0, $limit );
	$gap = mb_strrpos( $cut, ' ' );

	if ( $gap > 60 ) {
		$cut = mb_substr( $cut, 0, $gap );
	}

	return rtrim( $cut, " ,.;:-" ) . '…';
}

/**
 * The best description available for the current view.
 *
 * @return string
 */
function ss_seo_description() {
	$text = '';

	if ( is_front_page() ) {
		$text = ss_option( 'seo_meta_home' );

		if ( ! $text ) {
			$text = get_bloginfo( 'description' );
		}
	} elseif ( is_singular() ) {
		$post = get_queried_object();

		if ( function_exists( 'wc_get_product' ) && 'product' === get_post_type() ) {
			$product = wc_get_product( get_the_ID() );

			if ( $product ) {
				$text = $product->get_short_description();

				if ( ! $text ) {
					$text = $product->get_description();
				}
			}
		}

		if ( ! $text && $post ) {
			$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : $post->post_content;
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		$text = ( $term && ! empty( $term->description ) ) ? $term->description : '';

		if ( ! $text && $term ) {
			$text = sprintf(
				/* translators: 1: term name, 2: site name */
				__( 'Shop %1$s at %2$s — handloom sarees, temple jewellery and festive dresses, sourced direct from Indian weavers.', 'sreesaanvika' ),
				$term->name,
				get_bloginfo( 'name' )
			);
		}
	} elseif ( is_search() ) {
		$text = sprintf(
			/* translators: %s: search term */
			__( 'Search results for “%s”.', 'sreesaanvika' ),
			get_search_query()
		);
	} elseif ( is_author() ) {
		$text = get_the_author_meta( 'description', get_query_var( 'author' ) );
	}

	if ( ! $text ) {
		$text = ss_option( 'seo_meta_home' );
	}

	return ss_seo_trim( apply_filters( 'ss_seo_description', $text ) );
}

/**
 * The share image for the current view.
 *
 * @return string
 */
function ss_seo_image() {
	$url = '';

	if ( is_singular() && has_post_thumbnail() ) {
		$url = get_the_post_thumbnail_url( get_the_ID(), 'ss-hero' );
	} elseif ( is_product_category() || is_product_tag() ) {
		$term = get_queried_object();

		if ( $term ) {
			$thumb = get_term_meta( $term->term_id, 'thumbnail_id', true );
			$url   = $thumb ? wp_get_attachment_image_url( $thumb, 'ss-hero' ) : '';
		}
	}

	if ( ! $url ) {
		$url = ss_option( 'seo_og_image' );
	}

	if ( ! $url ) {
		$url = ss_option( 'hero1_img' );
	}

	if ( ! $url && has_custom_logo() ) {
		$url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
	}

	return apply_filters( 'ss_seo_image', $url );
}

/**
 * The canonical URL for the current view.
 *
 * @return string
 */
function ss_seo_canonical() {
	if ( is_front_page() ) {
		return home_url( '/' );
	}

	if ( is_singular() ) {
		return get_permalink();
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$link = get_term_link( get_queried_object() );

		return is_wp_error( $link ) ? '' : $link;
	}

	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return get_permalink( wc_get_page_id( 'shop' ) );
	}

	if ( is_home() ) {
		return get_permalink( get_option( 'page_for_posts' ) );
	}

	return '';
}

/**
 * Views that should never be indexed.
 *
 * Cart, checkout, account, compare, wishlist and sign-in are per-visitor and
 * carry no search value; indexing them also splits crawl budget away from the
 * catalogue.
 *
 * @return bool
 */
function ss_seo_is_noindex() {
	$noindex = is_search() || is_404();

	if ( function_exists( 'is_cart' ) ) {
		$noindex = $noindex || is_cart() || is_checkout() || is_account_page();
	}

	foreach ( array( 'compare', 'wishlist', 'auth' ) as $slug ) {
		if ( ss_is_theme_page( $slug ) ) {
			$noindex = true;
		}
	}

	return (bool) apply_filters( 'ss_seo_noindex', $noindex );
}

/**
 * Apply the noindex rule through the core robots filter.
 *
 * This one runs even with an SEO plugin present — the plugin has no way to
 * know which of the theme's page templates are transactional.
 *
 * @param array $robots Robots directives.
 * @return array
 */
function ss_seo_robots( $robots ) {
	if ( ss_seo_is_noindex() ) {
		$robots['noindex']  = true;
		$robots['follow']   = true;
		unset( $robots['index'] );
	}

	return $robots;
}
add_filter( 'wp_robots', 'ss_seo_robots', 20 );

/**
 * Print the head metadata.
 */
function ss_seo_head() {
	if ( ! ss_seo_enabled() ) {
		ss_seo_verification();
		return;
	}

	$description = ss_seo_description();
	$canonical   = ss_seo_canonical();
	$image       = ss_seo_image();
	$title       = wp_get_document_title();
	$type        = is_singular( 'post' ) ? 'article' : ( ( function_exists( 'is_product' ) && is_product() ) ? 'product' : 'website' );

	echo "\n<!-- Sree Saanvika SEO -->\n";

	if ( $description ) {
		printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $description ) );
	}

	if ( $canonical && ! is_paged() ) {
		printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $canonical ) );
	}

	/* Open Graph */
	printf( "<meta property=\"og:site_name\" content=\"%s\" />\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( "<meta property=\"og:type\" content=\"%s\" />\n", esc_attr( $type ) );
	printf( "<meta property=\"og:title\" content=\"%s\" />\n", esc_attr( $title ) );

	if ( $description ) {
		printf( "<meta property=\"og:description\" content=\"%s\" />\n", esc_attr( $description ) );
	}

	if ( $canonical ) {
		printf( "<meta property=\"og:url\" content=\"%s\" />\n", esc_url( $canonical ) );
	}

	printf( "<meta property=\"og:locale\" content=\"%s\" />\n", esc_attr( get_locale() ) );

	if ( $image ) {
		printf( "<meta property=\"og:image\" content=\"%s\" />\n", esc_url( $image ) );
		printf( "<meta property=\"og:image:alt\" content=\"%s\" />\n", esc_attr( $title ) );
	}

	if ( 'article' === $type ) {
		printf( "<meta property=\"article:published_time\" content=\"%s\" />\n", esc_attr( get_the_date( DATE_W3C ) ) );
		printf( "<meta property=\"article:modified_time\" content=\"%s\" />\n", esc_attr( get_the_modified_date( DATE_W3C ) ) );
	}

	if ( 'product' === $type && function_exists( 'wc_get_product' ) ) {
		$product = wc_get_product( get_the_ID() );

		if ( $product ) {
			printf( "<meta property=\"product:price:amount\" content=\"%s\" />\n", esc_attr( wc_get_price_to_display( $product ) ) );
			printf( "<meta property=\"product:price:currency\" content=\"%s\" />\n", esc_attr( get_woocommerce_currency() ) );
			printf( "<meta property=\"product:availability\" content=\"%s\" />\n", esc_attr( $product->is_in_stock() ? 'in stock' : 'out of stock' ) );
		}
	}

	/* Twitter */
	printf( "<meta name=\"twitter:card\" content=\"%s\" />\n", $image ? 'summary_large_image' : 'summary' );

	$handle = ltrim( (string) ss_option( 'seo_twitter' ), '@' );

	if ( $handle ) {
		printf( "<meta name=\"twitter:site\" content=\"@%s\" />\n", esc_attr( $handle ) );
	}

	ss_seo_verification();

	echo "<!-- /Sree Saanvika SEO -->\n\n";
}
add_action( 'wp_head', 'ss_seo_head', 2 );

/**
 * Search console verification tags. Printed whether or not an SEO plugin is
 * active, because they are set here and nowhere else.
 */
function ss_seo_verification() {
	$tags = array(
		'google-site-verification' => ss_option( 'seo_verify_google' ),
		'msvalidate.01'            => ss_option( 'seo_verify_bing' ),
		'facebook-domain-verification' => ss_option( 'seo_verify_facebook' ),
		'p:domain_verify'          => ss_option( 'seo_verify_pinterest' ),
	);

	foreach ( $tags as $name => $value ) {
		if ( $value ) {
			printf( "<meta name=\"%s\" content=\"%s\" />\n", esc_attr( $name ), esc_attr( $value ) );
		}
	}
}

/* -------------------------------------------------------------------------
 * Structured data
 * ---------------------------------------------------------------------- */

/**
 * Build and print the JSON-LD graph.
 */
function ss_seo_schema() {
	if ( ! ss_option( 'seo_schema' ) || ss_seo_plugin_active() ) {
		return;
	}

	$graph = array();
	$home  = home_url( '/' );
	$name  = get_bloginfo( 'name' );

	/* -- Organisation / store -- */
	$org = array(
		'@type'  => ss_option( 'seo_org_type' ),
		'@id'    => $home . '#organization',
		'name'   => $name,
		'url'    => $home,
	);

	$logo = has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : ss_option( 'seo_og_image' );

	if ( $logo ) {
		$org['logo']  = $logo;
		$org['image'] = $logo;
	}

	$phone = ss_option( 'footer_phone' );
	$email = ss_option( 'footer_email' );

	if ( $phone ) {
		$org['telephone'] = $phone;
		$org['contactPoint'] = array(
			array(
				'@type'             => 'ContactPoint',
				'telephone'         => $phone,
				'contactType'       => 'customer support',
				'areaServed'        => 'IN',
				'availableLanguage' => array( 'English', 'Hindi', 'Telugu' ),
			),
		);
	}

	if ( $email ) {
		$org['email'] = $email;
	}

	$address = ss_option( 'footer_address' );

	if ( $address ) {
		$org['address'] = array(
			'@type'          => 'PostalAddress',
			'streetAddress'  => trim( preg_replace( '/\s+/', ' ', $address ) ),
			'addressCountry' => 'IN',
		);
	}

	$socials = array();

	foreach ( array( 'instagram', 'facebook', 'youtube', 'pinterest' ) as $network ) {
		$url = ss_option( 'social_' . $network );

		if ( $url ) {
			$socials[] = $url;
		}
	}

	if ( $socials ) {
		$org['sameAs'] = $socials;
	}

	$graph[] = $org;

	/* -- Website, with the search box target -- */
	$graph[] = array(
		'@type'           => 'WebSite',
		'@id'             => $home . '#website',
		'url'             => $home,
		'name'            => $name,
		'publisher'       => array( '@id' => $home . '#organization' ),
		'inLanguage'      => get_bloginfo( 'language' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => array(
				'@type'       => 'EntryPoint',
				'urlTemplate' => $home . '?s={search_term_string}',
			),
			'query-input' => 'required name=search_term_string',
		),
	);

	/* -- Breadcrumbs -- */
	$crumbs = ss_seo_breadcrumb_list();

	if ( count( $crumbs ) > 1 ) {
		$items = array();

		foreach ( $crumbs as $position => $crumb ) {
			$item = array(
				'@type'    => 'ListItem',
				'position' => $position + 1,
				'name'     => $crumb['name'],
			);

			if ( ! empty( $crumb['url'] ) ) {
				$item['item'] = $crumb['url'];
			}

			$items[] = $item;
		}

		$graph[] = array(
			'@type'           => 'BreadcrumbList',
			'@id'             => ss_seo_canonical() . '#breadcrumb',
			'itemListElement' => $items,
		);
	}

	/* -- Article -- */
	if ( is_singular( 'post' ) ) {
		$article = array(
			'@type'            => 'BlogPosting',
			'@id'              => get_permalink() . '#article',
			'headline'         => ss_seo_trim( get_the_title(), 110 ),
			'datePublished'    => get_the_date( DATE_W3C ),
			'dateModified'     => get_the_modified_date( DATE_W3C ),
			'author'           => array(
				'@type' => 'Person',
				'name'  => get_the_author(),
			),
			'publisher'        => array( '@id' => $home . '#organization' ),
			'mainEntityOfPage' => get_permalink(),
			'description'      => ss_seo_description(),
		);

		$image = ss_seo_image();

		if ( $image ) {
			$article['image'] = $image;
		}

		$graph[] = $article;
	}

	/**
	 * Filter the whole graph before it is printed.
	 *
	 * @param array $graph JSON-LD nodes.
	 */
	$graph = apply_filters( 'ss_seo_schema_graph', $graph );

	if ( ! $graph ) {
		return;
	}

	$payload = array(
		'@context' => 'https://schema.org',
		'@graph'   => $graph,
	);

	// Default json_encode escapes forward slashes, so a "</script>" inside any
	// value cannot break out of the tag.
	echo '<script type="application/ld+json">' . wp_json_encode( $payload ) . "</script>\n";
}
add_action( 'wp_head', 'ss_seo_schema', 3 );

/**
 * The breadcrumb trail as plain data, for the BreadcrumbList node.
 *
 * @return array
 */
function ss_seo_breadcrumb_list() {
	$crumbs = array(
		array(
			'name' => __( 'Home', 'sreesaanvika' ),
			'url'  => home_url( '/' ),
		),
	);

	if ( function_exists( 'is_woocommerce' ) && ( is_woocommerce() || is_cart() || is_checkout() ) ) {
		$shop_id = wc_get_page_id( 'shop' );

		if ( $shop_id > 0 && ! is_shop() ) {
			$crumbs[] = array(
				'name' => get_the_title( $shop_id ),
				'url'  => get_permalink( $shop_id ),
			);
		}

		if ( is_product_category() || is_product_tag() ) {
			$crumbs[] = array(
				'name' => single_term_title( '', false ),
				'url'  => ss_seo_canonical(),
			);
		} elseif ( is_product() ) {
			$terms = get_the_terms( get_the_ID(), 'product_cat' );

			if ( $terms && ! is_wp_error( $terms ) ) {
				$term     = array_shift( $terms );
				$link     = get_term_link( $term );
				$crumbs[] = array(
					'name' => $term->name,
					'url'  => is_wp_error( $link ) ? '' : $link,
				);
			}

			$crumbs[] = array(
				'name' => get_the_title(),
				'url'  => get_permalink(),
			);
		} elseif ( is_shop() ) {
			$crumbs[] = array(
				'name' => get_the_title( $shop_id ),
				'url'  => get_permalink( $shop_id ),
			);
		}

		return $crumbs;
	}

	if ( is_singular() ) {
		$parent = wp_get_post_parent_id( get_the_ID() );

		if ( $parent ) {
			$crumbs[] = array(
				'name' => get_the_title( $parent ),
				'url'  => get_permalink( $parent ),
			);
		}

		$crumbs[] = array(
			'name' => get_the_title(),
			'url'  => get_permalink(),
		);
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$crumbs[] = array(
			'name' => single_term_title( '', false ),
			'url'  => ss_seo_canonical(),
		);
	}

	return $crumbs;
}

/**
 * Enrich WooCommerce's own Product schema.
 *
 * WooCommerce prints a solid Product node already. Google additionally wants
 * a brand, and — for shopping results — a return policy and shipping details,
 * which it can read straight from the theme's settings and policy pages.
 *
 * @param array      $markup  Structured data.
 * @param WC_Product $product Product.
 * @return array
 */
function ss_seo_product_schema( $markup, $product ) {
	if ( ! is_array( $markup ) || ! $product instanceof WC_Product ) {
		return $markup;
	}

	if ( empty( $markup['brand'] ) ) {
		$brand = ss_product_attribute( $product, 'brand' );

		$markup['brand'] = array(
			'@type' => 'Brand',
			'name'  => $brand ? wp_strip_all_tags( $brand ) : get_bloginfo( 'name' ),
		);
	}

	// Fabric and colour make the listing richer in shopping surfaces.
	$material = ss_product_attribute( $product, 'fabric' );

	if ( $material && empty( $markup['material'] ) ) {
		$markup['material'] = wp_strip_all_tags( $material );
	}

	if ( empty( $markup['offers'][0] ) ) {
		return $markup;
	}

	$returns_url = ss_page_url( 'returns' );
	$days        = absint( ss_option( 'returns_window_days' ) );

	if ( $returns_url && $days ) {
		$markup['offers'][0]['hasMerchantReturnPolicy'] = array(
			'@type'                => 'MerchantReturnPolicy',
			'applicableCountry'    => 'IN',
			'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
			'merchantReturnDays'   => $days,
			'returnMethod'         => 'https://schema.org/ReturnByMail',
			'returnFees'           => 'https://schema.org/FreeReturn',
			'merchantReturnLink'   => $returns_url,
		);
	}

	$threshold = (float) ss_option( 'free_ship_threshold' );
	$price     = (float) wc_get_price_to_display( $product );

	$markup['offers'][0]['shippingDetails'] = array(
		'@type'          => 'OfferShippingDetails',
		'shippingRate'   => array(
			'@type'    => 'MonetaryAmount',
			'value'    => ( $threshold > 0 && $price >= $threshold ) ? 0 : (float) ss_option( 'flat_ship_rate' ),
			'currency' => get_woocommerce_currency(),
		),
		'shippingDestination' => array(
			'@type'          => 'DefinedRegion',
			'addressCountry' => 'IN',
		),
		'deliveryTime'   => array(
			'@type'                => 'ShippingDeliveryTime',
			'handlingTime'         => array(
				'@type'    => 'QuantitativeValue',
				'minValue' => 1,
				'maxValue' => 2,
				'unitCode' => 'DAY',
			),
			'transitTime'          => array(
				'@type'    => 'QuantitativeValue',
				'minValue' => 2,
				'maxValue' => 7,
				'unitCode' => 'DAY',
			),
		),
	);

	return $markup;
}
add_filter( 'woocommerce_structured_data_product', 'ss_seo_product_schema', 20, 2 );

/* -------------------------------------------------------------------------
 * Sitemap
 * ---------------------------------------------------------------------- */

/**
 * Keep transactional pages out of the core sitemap.
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type.
 * @return array
 */
function ss_seo_sitemap_exclude( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$exclude = array();

	foreach ( array( 'compare', 'wishlist', 'auth' ) as $slug ) {
		$page = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => 'page-templates/template-' . $slug . '.php', // phpcs:ignore WordPress.DB.SlowDBQuery
				'no_found_rows'  => true,
			)
		);

		if ( $page ) {
			$exclude[] = $page[0];
		}
	}

	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $woo_page ) {
			$id = wc_get_page_id( $woo_page );

			if ( $id > 0 ) {
				$exclude[] = $id;
			}
		}
	}

	if ( $exclude ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? $args['post__not_in'] : array(), $exclude );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'ss_seo_sitemap_exclude', 10, 2 );
