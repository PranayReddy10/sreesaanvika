<?php
/**
 * One-click setup: creates the pages the theme expects and assigns menus.
 *
 * Nothing runs automatically — the shop owner presses the button on the
 * theme's welcome screen.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * The pages the theme creates, keyed by template slug.
 *
 * @return array
 */
function ss_setup_pages() {
	return array(
		'compare'  => array(
			'title'    => __( 'Compare', 'sreesaanvika' ),
			'template' => 'page-templates/template-compare.php',
			'content'  => '',
		),
		'wishlist' => array(
			'title'    => __( 'Wishlist', 'sreesaanvika' ),
			'template' => 'page-templates/template-wishlist.php',
			'content'  => '',
		),
		'auth'     => array(
			'title'    => __( 'Sign In', 'sreesaanvika' ),
			'template' => 'page-templates/template-auth.php',
			'content'  => '',
		),
		'lookbook' => array(
			'title'    => __( 'Lookbook', 'sreesaanvika' ),
			'template' => 'page-templates/template-lookbook.php',
			'content'  => '',
		),
		'about'    => array(
			'title'    => __( 'Our Story', 'sreesaanvika' ),
			'template' => 'page-templates/template-about.php',
			'content'  => __( 'Sree Saanvika began at a single loom in Kanchipuram, with a simple frustration: the weaver who spent six months on a saree was seeing a fraction of what it sold for in a city showroom. We started buying directly, paying upfront, and putting the weaver\'s name on the label.', 'sreesaanvika' ),
		),
		'contact'  => array(
			'title'    => __( 'Contact', 'sreesaanvika' ),
			'template' => 'page-templates/template-contact.php',
			'content'  => '',
		),
		'track'    => array(
			'title'    => __( 'Track Your Order', 'sreesaanvika' ),
			'template' => 'page-templates/template-track.php',
			'content'  => '',
		),
		'faq'      => array(
			'title'    => __( 'FAQs', 'sreesaanvika' ),
			'template' => 'page-templates/template-faq.php',
			'content'  => '',
		),
	);
}

/**
 * The policy pages, added to the setup list with their default copy.
 *
 * They are kept separate because they all share one template and their bodies
 * come from inc/legal-content.php.
 *
 * @return array
 */
function ss_setup_legal_pages() {
	$pages = array();

	foreach ( ss_legal_pages() as $slug => $page ) {
		$pages[ $slug ] = array(
			'title'    => $page['title'],
			'template' => 'page-templates/template-legal.php',
			'content'  => ss_legal_body( $slug ),
		);
	}

	return $pages;
}

/**
 * Create any missing theme page.
 *
 * @return array Created page titles.
 */
function ss_create_pages() {
	$created = array();

	$pages = array_merge( ss_setup_pages(), ss_setup_legal_pages() );

	// How many of the pages we create use each template.
	$template_use = array();

	foreach ( $pages as $page ) {
		$key                  = $page['template'];
		$template_use[ $key ] = isset( $template_use[ $key ] ) ? $template_use[ $key ] + 1 : 1;
	}

	foreach ( $pages as $slug => $page ) {
		// A page with this slug already exists — leave it alone.
		if ( get_page_by_path( $slug ) ) {
			continue;
		}

		/*
		 * Only fall back to a template check for templates used by exactly one
		 * page. The four policy pages all share template-legal.php, so a
		 * template check would find Privacy Policy and then skip Terms,
		 * Shipping and Returns.
		 */
		if ( 1 === $template_use[ $page['template'] ] ) {
			$existing = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'draft', 'pending' ),
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value'     => $page['template'], // phpcs:ignore WordPress.DB.SlowDBQuery
					'no_found_rows'  => true,
				)
			);

			if ( $existing ) {
				continue;
			}
		}

		$id = wp_insert_post(
			array(
				'post_title'   => $page['title'],
				'post_name'    => $slug,
				'post_content' => $page['content'],
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'meta_input'   => array( '_wp_page_template' => $page['template'] ),
			)
		);

		if ( $id && ! is_wp_error( $id ) ) {
			$created[] = $page['title'];
		}
	}

	ss_flush_page_urls();

	return $created;
}

/**
 * Slugs a shop owner is likely to have used for each theme page.
 *
 * Setup creates "about"; someone who wrote their own page first may well have
 * called it "about-us" or "our-story", and that page is then sitting on the
 * default template wondering why it looks nothing like the theme's.
 *
 * @return array<string,string[]>
 */
function ss_page_aliases() {
	return array(
		'about'                => array( 'about', 'about-us', 'our-story', 'story', 'aboutus' ),
		'contact'              => array( 'contact', 'contact-us', 'contactus', 'get-in-touch' ),
		'track'                => array( 'track', 'track-order', 'track-your-order', 'order-tracking', 'track-my-order' ),
		'faq'                  => array( 'faq', 'faqs', 'help', 'questions' ),
		'compare'              => array( 'compare', 'compare-products' ),
		'wishlist'             => array( 'wishlist', 'my-wishlist', 'favourites', 'favorites' ),
		'auth'                 => array( 'auth', 'sign-in', 'signin', 'login', 'sign-up', 'register' ),
		'lookbook'             => array( 'lookbook', 'look-book', 'gallery' ),
		'privacy-policy'       => array( 'privacy-policy', 'privacy', 'privacy-notice' ),
		'terms-and-conditions' => array( 'terms-and-conditions', 'terms', 'terms-conditions', 'terms-of-service', 'terms-of-use' ),
		'shipping-policy'      => array( 'shipping-policy', 'shipping', 'shipping-delivery', 'delivery' ),
		'returns'              => array( 'returns', 'return-policy', 'return-refund-policy', 'refund-policy', 'refunds', 'returns-refunds' ),
	);
}

/**
 * Put every theme page back on its theme template.
 *
 * A page created before the theme was installed, or one whose template was
 * changed by an importer or a page builder, renders through page.php and looks
 * like a plain post — the commonest reason a rebuilt template appears not to
 * have taken effect.
 *
 * @return array{fixed:array,missing:array}
 */
function ss_repair_templates() {
	$pages   = array_merge( ss_setup_pages(), ss_setup_legal_pages() );
	$aliases = ss_page_aliases();

	$fixed   = array();
	$missing = array();

	foreach ( $pages as $slug => $page ) {
		$candidates = isset( $aliases[ $slug ] ) ? $aliases[ $slug ] : array( $slug );
		$found      = null;

		foreach ( $candidates as $candidate ) {
			$post = get_page_by_path( $candidate );

			if ( $post instanceof WP_Post ) {
				$found = $post;
				break;
			}
		}

		// Fall back to the title, for a page named right but slugged oddly.
		if ( ! $found ) {
			$by_title = get_posts(
				array(
					'post_type'      => 'page',
					'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page' => 1,
					'title'          => $page['title'],
					'no_found_rows'  => true,
				)
			);

			if ( $by_title ) {
				$found = $by_title[0];
			}
		}

		if ( ! $found ) {
			$missing[] = $page['title'];
			continue;
		}

		if ( get_page_template_slug( $found->ID ) === $page['template'] ) {
			continue;
		}

		update_post_meta( $found->ID, '_wp_page_template', $page['template'] );

		$fixed[] = $found->post_title;
	}

	ss_flush_page_urls();

	return array(
		'fixed'   => $fixed,
		'missing' => $missing,
	);
}

/**
 * Run the repair from the welcome screen.
 */
function ss_run_repair() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'sreesaanvika' ) );
	}

	check_admin_referer( 'ss_repair' );

	set_transient( 'ss_repair_done', ss_repair_templates(), 60 );

	wp_safe_redirect( admin_url( 'themes.php?page=sreesaanvika&ss-repair=1' ) );
	exit;
}
add_action( 'admin_post_ss_repair_templates', 'ss_run_repair' );

/**
 * Build a primary menu from the shop, categories and theme pages.
 *
 * @return bool
 */
function ss_create_menu() {
	$name = __( 'Primary Menu', 'sreesaanvika' );
	$menu = wp_get_nav_menu_object( $name );

	if ( $menu ) {
		// Never rewrite a menu the shop owner has already built.
		return false;
	}

	$menu_id = wp_create_nav_menu( $name );

	if ( is_wp_error( $menu_id ) ) {
		return false;
	}

	wp_update_nav_menu_item(
		$menu_id,
		0,
		array(
			'menu-item-title'  => __( 'Home', 'sreesaanvika' ),
			'menu-item-url'    => home_url( '/' ),
			'menu-item-status' => 'publish',
		)
	);

	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );

		if ( $shop_id > 0 ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => __( 'Shop All', 'sreesaanvika' ),
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $shop_id,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
	}

	// Top-level product categories, with their children nested underneath.
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => 0,
			'number'     => 5,
			'exclude'    => array( get_option( 'default_product_cat' ) ),
		)
	);

	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$parent_item = wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => $term->name,
					'menu-item-object'    => 'product_cat',
					'menu-item-object-id' => $term->term_id,
					'menu-item-type'      => 'taxonomy',
					'menu-item-status'    => 'publish',
				)
			);

			$children = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'parent'     => $term->term_id,
					'number'     => 8,
				)
			);

			if ( $children && ! is_wp_error( $children ) && ! is_wp_error( $parent_item ) ) {
				foreach ( $children as $child ) {
					wp_update_nav_menu_item(
						$menu_id,
						0,
						array(
							'menu-item-title'     => $child->name,
							'menu-item-object'    => 'product_cat',
							'menu-item-object-id' => $child->term_id,
							'menu-item-type'      => 'taxonomy',
							'menu-item-parent-id' => $parent_item,
							'menu-item-status'    => 'publish',
						)
					);
				}
			}
		}
	}

	// Theme pages.
	foreach ( array( 'lookbook', 'about', 'contact' ) as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page ) {
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => get_the_title( $page ),
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $page->ID,
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
				)
			);
		}
	}

	$locations = get_theme_mod( 'nav_menu_locations', array() );

	$locations['primary'] = $menu_id;
	$locations['mobile']  = $menu_id;

	set_theme_mod( 'nav_menu_locations', $locations );

	return true;
}

/**
 * Run the whole setup from the welcome screen.
 */
function ss_run_setup() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'sreesaanvika' ) );
	}

	check_admin_referer( 'ss_setup' );

	$pages = ss_create_pages();
	$menu  = ss_create_menu();

	set_transient(
		'ss_setup_done',
		array(
			'pages' => $pages,
			'menu'  => $menu,
		),
		60
	);

	update_option( 'ss_setup_complete', 1 );

	wp_safe_redirect( admin_url( 'themes.php?page=sreesaanvika&ss-setup=1' ) );
	exit;
}
add_action( 'admin_post_ss_run_setup', 'ss_run_setup' );
