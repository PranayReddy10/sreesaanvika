<?php
/**
 * Build an Elementor copy of the theme's homepage.
 *
 * The theme homepage is assembled in PHP from Customizer options, which is
 * fast but not drag-and-droppable. This creates a real Elementor page holding
 * the same sections, seeded with the same settings, so the shop owner can edit
 * the layout visually from that point on.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

/**
 * A unique-enough id for an Elementor element.
 *
 * @return string
 */
function od_el_id() {
	return substr( str_replace( '.', '', uniqid( '', true ) ), -7 );
}

/**
 * Wrap widgets in an Elementor section + column.
 *
 * @param array $widgets  Widget definitions.
 * @param array $settings Section settings.
 * @return array
 */
function od_el_section( $widgets, $settings = array() ) {
	$columns = array();

	// One column per widget when several are passed side by side.
	$count = max( 1, count( $widgets ) );
	$size  = (int) floor( 100 / $count );

	foreach ( $widgets as $widget ) {
		$columns[] = array(
			'id'       => od_el_id(),
			'elType'   => 'column',
			'settings' => array( '_column_size' => $size, '_inline_size' => null ),
			'elements' => array( $widget ),
			'isInner'  => false,
		);
	}

	return array(
		'id'       => od_el_id(),
		'elType'   => 'section',
		'settings' => $settings,
		'elements' => $columns,
		'isInner'  => false,
	);
}

/**
 * A single Elementor widget definition.
 *
 * @param string $type     Widget type name.
 * @param array  $settings Widget settings.
 * @return array
 */
function od_el_widget( $type, $settings = array() ) {
	return array(
		'id'         => od_el_id(),
		'elType'     => 'widget',
		'widgetType' => $type,
		'settings'   => $settings,
		'elements'   => array(),
	);
}

/**
 * Padding settings shorthand for a section.
 *
 * @param int $top    Top padding in px.
 * @param int $bottom Bottom padding in px.
 * @return array
 */
function od_el_padding( $top, $bottom ) {
	return array(
		'padding' => array(
			'unit'     => 'px',
			'top'      => (string) $top,
			'right'    => '0',
			'bottom'   => (string) $bottom,
			'left'     => '0',
			'isLinked' => false,
		),
	);
}

/**
 * Turn a Customizer image URL into the shape an Elementor MEDIA control wants.
 *
 * @param string $url Image URL.
 * @return array
 */
function od_el_media( $url ) {
	if ( ! $url ) {
		return array( 'url' => '', 'id' => '' );
	}

	$id = attachment_url_to_postid( $url );

	return array(
		'url' => $url,
		'id'  => $id ? $id : '',
	);
}

/**
 * The full Elementor document for a copy of the current homepage.
 *
 * Every section mirrors what template-parts/home/ renders today, using the
 * shop's current Customizer values as the starting point.
 *
 * @return array
 */
function od_el_homepage_data() {
	$full  = array( 'layout' => 'full_width', 'gap' => 'no' );
	$boxed = array( 'layout' => 'boxed', 'gap' => 'default' );
	$shop  = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );

	$data = array();

	/* ---- Hero ---- */
	$slides = array();

	for ( $i = 1; $i <= 3; $i++ ) {
		$title = od_option( "hero{$i}_title" );

		if ( ! $title ) {
			continue;
		}

		$url = od_option( "hero{$i}_url" );

		$slides[] = array(
			'_id'       => od_el_id(),
			'eyebrow'   => od_option( "hero{$i}_eyebrow" ),
			'title'     => $title,
			'text'      => od_option( "hero{$i}_text" ),
			'image'     => od_el_media( od_option( "hero{$i}_img" ) ),
			'align'     => od_option( "hero{$i}_align" ),
			'btn_text'  => od_option( "hero{$i}_btn" ),
			'btn_link'  => array( 'url' => $url ? $url : $shop, 'is_external' => '', 'nofollow' => '' ),
			'btn2_text' => __( 'View lookbook', 'ojasvidrapes' ),
			'btn2_link' => array( 'url' => od_page_url( 'lookbook' ), 'is_external' => '', 'nofollow' => '' ),
		);
	}

	if ( $slides ) {
		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-hero',
					array(
						'slides'   => $slides,
						'autoplay' => od_option( 'hero_autoplay' ) ? 'yes' : '',
						'speed'    => absint( od_option( 'hero_speed' ) ),
					)
				),
			),
			$full
		);
	}

	/* ---- Trust strip ---- */
	if ( od_option( 'sec_usp' ) ) {
		$data[] = od_el_section(
			array( od_el_widget( 'od-usp', array() ) ),
			array_merge( $boxed, od_el_padding( 10, 10 ) )
		);
	}

	/* ---- Category rail ---- */
	if ( od_option( 'sec_catrail' ) ) {
		$data[] = od_el_section(
			array( od_el_widget( 'od-category-rail', array() ) ),
			array_merge( $boxed, od_el_padding( 60, 40 ) )
		);
	}

	/* ---- Category mosaic ---- */
	if ( od_option( 'sec_cats' ) ) {
		$data[] = od_el_section(
			array( od_el_widget( 'od-category-mosaic', array( 'count' => 5 ) ) ),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	$per_section = absint( od_option( 'products_per_section' ) );
	$columns     = (string) absint( od_option( 'shop_columns' ) );

	/* ---- New arrivals ---- */
	if ( od_option( 'sec_new' ) ) {
		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-products',
					array(
						'source'      => 'latest',
						'count'       => $per_section,
						'columns'     => $columns,
						'eyebrow'     => __( 'Fresh off the loom', 'ojasvidrapes' ),
						'title'       => __( 'New <em>Arrivals</em>', 'ojasvidrapes' ),
						'subtitle'    => __( 'The newest weaves, added this week.', 'ojasvidrapes' ),
						'button_text' => __( 'View all new arrivals', 'ojasvidrapes' ),
						'button_link' => array( 'url' => $shop, 'is_external' => '', 'nofollow' => '' ),
					)
				),
			),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	/* ---- Two offer banners side by side ---- */
	if ( od_option( 'sec_promo' ) ) {
		$banners = array();

		for ( $i = 1; $i <= 2; $i++ ) {
			$title = od_option( "promo{$i}_title" );

			if ( ! $title ) {
				continue;
			}

			$url = od_option( "promo{$i}_url" );

			$banners[] = od_el_widget(
				'od-promo',
				array(
					'big'      => od_option( "promo{$i}_off" ),
					'title'    => $title,
					'text'     => od_option( "promo{$i}_text" ),
					'image'    => od_el_media( od_option( "promo{$i}_img" ) ),
					'btn_text' => __( 'Shop the offer', 'ojasvidrapes' ),
					'btn_link' => array( 'url' => $url ? $url : $shop, 'is_external' => '', 'nofollow' => '' ),
				)
			);
		}

		if ( $banners ) {
			$data[] = od_el_section( $banners, array_merge( $boxed, od_el_padding( 20, 40 ) ) );
		}
	}

	/* ---- Best sellers ---- */
	if ( od_option( 'sec_bestsellers' ) ) {
		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-products',
					array(
						'source'   => 'best',
						'count'    => $per_section,
						'columns'  => $columns,
						'eyebrow'  => __( 'Loved by our customers', 'ojasvidrapes' ),
						'title'    => __( 'Best <em>Sellers</em>', 'ojasvidrapes' ),
						'subtitle' => __( 'The pieces that keep going out of stock — and keep coming back.', 'ojasvidrapes' ),
					)
				),
			),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	/* ---- Saree spotlight ---- */
	if ( od_option( 'sec_sarees' ) ) {
		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-products',
					array(
						'source'   => 'random',
						'category' => 'sarees',
						'count'    => $per_section,
						'columns'  => $columns,
						'eyebrow'  => __( 'Six yards of grace', 'ojasvidrapes' ),
						'title'    => __( 'The <em>Saree</em> Edit', 'ojasvidrapes' ),
						'subtitle' => __( 'Kanchipuram, Banarasi, Pochampally, Chanderi and Bhagalpuri silks.', 'ojasvidrapes' ),
					)
				),
			),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	/* ---- Jewellery spotlight ---- */
	if ( od_option( 'sec_jewel' ) ) {
		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-products',
					array(
						'source'   => 'latest',
						'category' => 'jewellery',
						'count'    => $per_section,
						'columns'  => $columns,
						'eyebrow'  => __( 'Antique finish, temple craft', 'ojasvidrapes' ),
						'title'    => __( 'The <em>Jewellery</em> Vault', 'ojasvidrapes' ),
						'subtitle' => __( 'Nakshi haarams, jhumkas, vanki and maang tikka.', 'ojasvidrapes' ),
					)
				),
			),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	/* ---- Lookbook ---- */
	if ( od_option( 'sec_lookbook' ) ) {
		$data[] = od_el_section(
			array( od_el_widget( 'od-lookbook', array( 'count' => 5 ) ) ),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	/* ---- Story band ---- */
	if ( od_option( 'sec_band' ) && od_option( 'band_title' ) ) {
		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-band',
					array(
						'title'    => od_option( 'band_title' ),
						'text'     => od_option( 'band_text' ),
						'image'    => od_el_media( od_option( 'band_img' ) ),
						'btn_text' => __( 'Read our story', 'ojasvidrapes' ),
						'btn_link' => array( 'url' => od_page_url( 'about' ), 'is_external' => '', 'nofollow' => '' ),
					)
				),
			),
			$full
		);
	}

	/* ---- Testimonials ---- */
	if ( od_option( 'sec_reviews' ) ) {
		$data[] = od_el_section(
			array( od_el_widget( 'od-testimonials', array() ) ),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	/* ---- Instagram ---- */
	if ( od_option( 'sec_gram' ) ) {
		$handle  = od_option( 'gram_handle' );
		$profile = od_option( 'social_instagram' );

		$data[] = od_el_section(
			array(
				od_el_widget(
					'od-instagram',
					array(
						'title'   => '@' . $handle,
						'profile' => array(
							'url'         => $profile ? $profile : 'https://instagram.com/' . rawurlencode( $handle ),
							'is_external' => 'on',
							'nofollow'    => '',
						),
					)
				),
			),
			array_merge( $boxed, od_el_padding( 40, 40 ) )
		);
	}

	/* ---- Newsletter ---- */
	if ( od_option( 'sec_newsletter' ) ) {
		$data[] = od_el_section(
			array( od_el_widget( 'od-newsletter', array() ) ),
			array_merge( $boxed, od_el_padding( 60, 60 ) )
		);
	}

	return $data;
}

/**
 * Create the Elementor homepage from the welcome screen.
 */
function od_el_build_homepage() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do that.', 'ojasvidrapes' ) );
	}

	check_admin_referer( 'od_elementor_home' );

	if ( ! od_has_elementor() ) {
		set_transient( 'od_el_result', array( 'error' => __( 'Elementor is not active.', 'ojasvidrapes' ) ), 60 );
		wp_safe_redirect( admin_url( 'themes.php?page=ojasvidrapes' ) );
		exit;
	}

	$data = od_el_homepage_data();

	$page_id = wp_insert_post(
		array(
			'post_title'   => __( 'Home', 'ojasvidrapes' ),
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_content' => '',
			'meta_input'   => array(
				'_elementor_edit_mode'     => 'builder',
				'_elementor_template_type' => 'wp-page',
				'_elementor_data'          => wp_slash( wp_json_encode( $data ) ),
				'_wp_page_template'        => 'default',
			),
		)
	);

	if ( is_wp_error( $page_id ) || ! $page_id ) {
		set_transient( 'od_el_result', array( 'error' => __( 'The page could not be created.', 'ojasvidrapes' ) ), 60 );
		wp_safe_redirect( admin_url( 'themes.php?page=ojasvidrapes' ) );
		exit;
	}

	if ( defined( 'ELEMENTOR_VERSION' ) ) {
		update_post_meta( $page_id, '_elementor_version', ELEMENTOR_VERSION );
	}

	$made_home = false;

	if ( ! empty( $_POST['od_set_home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
		$made_home = true;
	}

	// Elementor caches per-page CSS; drop it so the new page is regenerated.
	if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) ) {
		\Elementor\Plugin::$instance->files_manager->clear_cache();
	}

	set_transient(
		'od_el_result',
		array(
			'page_id'   => $page_id,
			'sections'  => count( $data ),
			'made_home' => $made_home,
		),
		60
	);

	wp_safe_redirect( admin_url( 'themes.php?page=ojasvidrapes' ) );
	exit;
}
add_action( 'admin_post_od_build_elementor_home', 'od_el_build_homepage' );
