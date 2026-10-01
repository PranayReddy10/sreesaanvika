<?php
/**
 * Homepage.
 *
 * Renders the storefront sections in the order set in the Customizer. If the
 * front page was laid out in Elementor, the builder owns the page instead and
 * the theme sections step aside — unless "Homepage layout" has been set to the
 * theme's sections outright, which is what od_front_page_source() decides.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( 'elementor' === od_front_page_source() ) {

	/*
	 * Elementor built this page. Print the content and nothing else — and
	 * print it unconditionally, because a page whose layout lives in
	 * Elementor's own meta has an empty post_content, and the editor preview
	 * needs this wrapper to exist before it can load.
	 */
	while ( have_posts() ) {
		the_post();
		echo '<div class="od-elementor-content">';
		the_content();
		echo '</div>';
	}
} else {

	$od_sections = array(
		'hero'        => true,
		'usp'         => od_option( 'sec_usp' ),
		'catrail'     => od_option( 'sec_catrail' ),
		'cats'        => od_option( 'sec_cats' ),
		'new'         => od_option( 'sec_new' ),
		'promo'       => od_option( 'sec_promo' ),
		'bestsellers' => od_option( 'sec_bestsellers' ),
		'deal'        => od_option( 'sec_deal' ),
		'sarees'      => od_option( 'sec_sarees' ),
		'jewel'       => od_option( 'sec_jewel' ),
		'lookbook'    => od_option( 'sec_lookbook' ),
		'band'        => od_option( 'sec_band' ),
		'reviews'     => od_option( 'sec_reviews' ),
		'blog'        => od_option( 'sec_blog' ),
		'gram'        => od_option( 'sec_gram' ),
		'newsletter'  => od_option( 'sec_newsletter' ),
	);

	foreach ( $od_sections as $od_name => $od_enabled ) {
		if ( $od_enabled ) {
			get_template_part( 'template-parts/home/' . $od_name );
		}
	}

	// A static page assigned as the front page keeps its own content, below
	// the storefront sections, so anything typed in the editor is not lost.
	if ( is_page() && have_posts() ) {
		while ( have_posts() ) {
			the_post();

			if ( trim( get_the_content() ) ) {
				echo '<div class="od-section"><div class="od-container od-container--narrow od-entry">';
				the_content();
				echo '</div></div>';
			}
		}
	}
}

get_footer();
