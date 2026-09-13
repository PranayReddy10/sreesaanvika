<?php
/**
 * AJAX endpoints.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

/**
 * Verify the shared nonce, or die with a JSON error.
 */
function ss_check_nonce() {
	if ( ! check_ajax_referer( 'ss_nonce', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => __( 'Your session expired. Please refresh the page.', 'sreesaanvika' ) ), 403 );
	}
}

/**
 * Toggle wishlist / compare.
 */
function ss_ajax_toggle_list() {
	ss_check_nonce();

	$id   = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$type = isset( $_POST['type'] ) && 'compare' === $_POST['type'] ? 'compare' : 'wishlist';

	if ( ! $id || 'product' !== get_post_type( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'That product could not be found.', 'sreesaanvika' ) ), 404 );
	}

	$result = ss_toggle_list( $id, $type );

	$thumbs = array();

	if ( 'compare' === $type ) {
		foreach ( $result['ids'] as $pid ) {
			$thumbs[] = array(
				'id'    => $pid,
				'thumb' => get_the_post_thumbnail_url( $pid, 'ss-thumb' ) ? get_the_post_thumbnail_url( $pid, 'ss-thumb' ) : ss_placeholder(),
				'name'  => get_the_title( $pid ),
			);
		}
	}

	wp_send_json_success(
		array(
			'action' => $result['action'],
			'count'  => count( $result['ids'] ),
			'ids'    => $result['ids'],
			'full'   => $result['full'],
			'max'    => absint( ss_option( 'compare_max', 4 ) ),
			'items'  => $thumbs,
		)
	);
}
add_action( 'wp_ajax_ss_toggle_list', 'ss_ajax_toggle_list' );
add_action( 'wp_ajax_nopriv_ss_toggle_list', 'ss_ajax_toggle_list' );

/**
 * Quick view markup for a product.
 */
function ss_ajax_quickview() {
	ss_check_nonce();

	$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

	if ( ! $id || ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_error( array( 'message' => __( 'That product could not be found.', 'sreesaanvika' ) ), 404 );
	}

	$product = wc_get_product( $id );

	if ( ! $product || 'publish' !== get_post_status( $id ) ) {
		wp_send_json_error( array( 'message' => __( 'That product could not be found.', 'sreesaanvika' ) ), 404 );
	}

	// Woo templates rely on these globals.
	$GLOBALS['post']    = get_post( $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	$GLOBALS['product'] = $product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	setup_postdata( $GLOBALS['post'] );

	ob_start();
	wc_get_template( 'single-product/quick-view.php', array( 'product' => $product ) );
	$html = ob_get_clean();

	wp_reset_postdata();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_ss_quickview', 'ss_ajax_quickview' );
add_action( 'wp_ajax_nopriv_ss_quickview', 'ss_ajax_quickview' );

/**
 * Live search suggestions for the search overlay.
 */
function ss_ajax_live_search() {
	ss_check_nonce();

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

	if ( mb_strlen( $term ) < 2 ) {
		wp_send_json_success( array( 'results' => array() ) );
	}

	$post_types = class_exists( 'WooCommerce' ) ? array( 'product' ) : array( 'post', 'page' );

	$query = new WP_Query(
		array(
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'posts_per_page'      => 6,
			's'                   => $term,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	$results = array();

	foreach ( $query->posts as $post ) {
		$item = array(
			'title' => get_the_title( $post ),
			'url'   => get_permalink( $post ),
			'thumb' => get_the_post_thumbnail_url( $post, 'ss-thumb' ),
			'price' => '',
		);

		if ( ! $item['thumb'] ) {
			$item['thumb'] = ss_placeholder();
		}

		if ( function_exists( 'wc_get_product' ) && 'product' === $post->post_type ) {
			$product = wc_get_product( $post->ID );

			if ( $product ) {
				$item['price'] = wp_strip_all_tags( $product->get_price_html() );
			}
		}

		$results[] = $item;
	}

	wp_reset_postdata();

	wp_send_json_success( array( 'results' => $results ) );
}
add_action( 'wp_ajax_ss_live_search', 'ss_ajax_live_search' );
add_action( 'wp_ajax_nopriv_ss_live_search', 'ss_ajax_live_search' );

/**
 * Add to cart from a card or the quick view, without a page reload.
 */
function ss_ajax_add_to_cart() {
	ss_check_nonce();

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_error( array( 'message' => __( 'The cart is unavailable right now.', 'sreesaanvika' ) ), 400 );
	}

	$id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
	$qty      = isset( $_POST['qty'] ) ? max( 1, absint( $_POST['qty'] ) ) : 1;
	$var_id   = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0;
	$variation = array();

	if ( isset( $_POST['variation'] ) && is_array( $_POST['variation'] ) ) {
		foreach ( wp_unslash( $_POST['variation'] ) as $key => $value ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$variation[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}
	}

	$product = $id ? wc_get_product( $id ) : null;

	if ( ! $product ) {
		wp_send_json_error( array( 'message' => __( 'That product could not be found.', 'sreesaanvika' ) ), 404 );
	}

	// Variable products need their options chosen first.
	if ( $product->is_type( 'variable' ) && ! $var_id ) {
		wp_send_json_error(
			array(
				'message'  => __( 'Please choose the available options first.', 'sreesaanvika' ),
				'redirect' => $product->get_permalink(),
			),
			400
		);
	}

	$added = WC()->cart->add_to_cart( $id, $qty, $var_id, $variation );

	if ( ! $added ) {
		$notices = wc_get_notices( 'error' );
		wc_clear_notices();

		$message = __( 'That product could not be added to your bag.', 'sreesaanvika' );

		if ( ! empty( $notices[0]['notice'] ) ) {
			$message = wp_strip_all_tags( $notices[0]['notice'] );
		}

		wp_send_json_error( array( 'message' => $message ), 400 );
	}

	WC_AJAX::get_refreshed_fragments();
}
add_action( 'wp_ajax_ss_add_to_cart', 'ss_ajax_add_to_cart' );
add_action( 'wp_ajax_nopriv_ss_add_to_cart', 'ss_ajax_add_to_cart' );

/**
 * Newsletter sign-up.
 *
 * Stores the address as an option-backed list so the form works out of the
 * box; a mailing-list plugin can hook `ss_newsletter_signup` to take over.
 */
function ss_ajax_newsletter() {
	ss_check_nonce();

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'sreesaanvika' ) ), 400 );
	}

	$list = get_option( 'ss_newsletter_list', array() );
	$list = is_array( $list ) ? $list : array();

	if ( ! in_array( $email, $list, true ) ) {
		$list[] = $email;
		update_option( 'ss_newsletter_list', array_slice( $list, -5000 ), false );
	}

	do_action( 'ss_newsletter_signup', $email );

	wp_send_json_success( array( 'message' => __( 'Welcome to the family! Watch your inbox for the first drop.', 'sreesaanvika' ) ) );
}
add_action( 'wp_ajax_ss_newsletter', 'ss_ajax_newsletter' );
add_action( 'wp_ajax_nopriv_ss_newsletter', 'ss_ajax_newsletter' );

/**
 * Register a customer from the sign-up form.
 */
function ss_ajax_register() {
	ss_check_nonce();

	if ( is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'You are already signed in.', 'sreesaanvika' ) ), 400 );
	}

	if ( ! get_option( 'users_can_register' ) && ! ( function_exists( 'wc_registration_enabled' ) && wc_registration_enabled() ) ) {
		wp_send_json_error( array( 'message' => __( 'Registration is currently closed.', 'sreesaanvika' ) ), 403 );
	}

	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
	$last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
	$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Please enter a valid email address.', 'sreesaanvika' ) ), 400 );
	}

	if ( strlen( $pass ) < 8 ) {
		wp_send_json_error( array( 'message' => __( 'Your password needs at least 8 characters.', 'sreesaanvika' ) ), 400 );
	}

	if ( email_exists( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'An account already exists with that email. Try signing in instead.', 'sreesaanvika' ) ), 400 );
	}

	if ( function_exists( 'wc_create_new_customer' ) ) {
		$user_id = wc_create_new_customer( $email, '', $pass, array( 'first_name' => $first, 'last_name' => $last ) );
	} else {
		$user_id = wp_create_user( $email, $pass, $email );
	}

	if ( is_wp_error( $user_id ) ) {
		wp_send_json_error( array( 'message' => wp_strip_all_tags( $user_id->get_error_message() ) ), 400 );
	}

	if ( $first || $last ) {
		wp_update_user(
			array(
				'ID'           => $user_id,
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => trim( $first . ' ' . $last ),
			)
		);
	}

	if ( $phone ) {
		update_user_meta( $user_id, 'billing_phone', $phone );
	}

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true );
	do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );

	wp_send_json_success(
		array(
			'message'  => __( 'Account created. Welcome to Sree Saanvika!', 'sreesaanvika' ),
			'redirect' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ),
		)
	);
}
add_action( 'wp_ajax_nopriv_ss_register', 'ss_ajax_register' );
add_action( 'wp_ajax_ss_register', 'ss_ajax_register' );

/**
 * Sign in from the auth page.
 */
function ss_ajax_login() {
	ss_check_nonce();

	$user = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
	$pass = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$keep = ! empty( $_POST['remember'] );

	if ( ! $user || ! $pass ) {
		wp_send_json_error( array( 'message' => __( 'Please enter your email and password.', 'sreesaanvika' ) ), 400 );
	}

	$signed = wp_signon(
		array(
			'user_login'    => $user,
			'user_password' => $pass,
			'remember'      => $keep,
		),
		is_ssl()
	);

	if ( is_wp_error( $signed ) ) {
		wp_send_json_error( array( 'message' => __( 'Those details did not match an account. Please try again.', 'sreesaanvika' ) ), 401 );
	}

	wp_set_current_user( $signed->ID );

	wp_send_json_success(
		array(
			'message'  => __( 'Signed in. Taking you to your account…', 'sreesaanvika' ),
			'redirect' => function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' ),
		)
	);
}
add_action( 'wp_ajax_nopriv_ss_login', 'ss_ajax_login' );

/**
 * Return the next page of a product grid.
 *
 * The browser never sends query arguments — only a whitelisted section key, or
 * the source and category a Product Grid widget was configured with, both
 * validated here before any query runs.
 */
function ss_ajax_load_more() {
	ss_check_nonce();

	if ( ! class_exists( 'WooCommerce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Products are unavailable right now.', 'sreesaanvika' ) ), 400 );
	}

	$page    = isset( $_POST['page'] ) ? max( 2, absint( $_POST['page'] ) ) : 2;
	$per     = isset( $_POST['per'] ) ? min( 48, max( 1, absint( $_POST['per'] ) ) ) : 8;
	$section = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';

	$sections = ss_product_sections();

	if ( $section ) {
		if ( ! isset( $sections[ $section ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown section.', 'sreesaanvika' ) ), 400 );
		}

		$args = $sections[ $section ];
	} else {
		$allowed  = array( 'latest', 'best', 'sale', 'featured', 'rated', 'random' );
		$source   = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'latest';
		$source   = in_array( $source, $allowed, true ) ? $source : 'latest';
		$category = isset( $_POST['category'] ) ? sanitize_title( wp_unslash( $_POST['category'] ) ) : '';

		$args = ss_product_source_args( $source, $category );
	}

	// A random order cannot be paged without repeats, so page it by date.
	if ( isset( $args['orderby'] ) && 'rand' === $args['orderby'] ) {
		$args['orderby'] = 'date';
		$args['order']   = 'DESC';
	}

	$result = ss_product_items( $args, $page, $per );

	if ( '' === $result['html'] ) {
		wp_send_json_success( array( 'html' => '', 'more' => false ) );
	}

	wp_send_json_success(
		array(
			'html' => $result['html'],
			'more' => $result['more'],
		)
	);
}
add_action( 'wp_ajax_ss_load_more', 'ss_ajax_load_more' );
add_action( 'wp_ajax_nopriv_ss_load_more', 'ss_ajax_load_more' );
