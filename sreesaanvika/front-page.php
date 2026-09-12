<?php
/**
 * Homepage.
 *
 * Renders the storefront sections in the order set in the Customizer. If the
 * front page was laid out in Elementor, the builder owns the page instead and
 * the theme sections step aside.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ss_elementor_owns_page() ) {

	/*
	 * Elementor built this page. Print the content and nothing else — and
	 * print it unconditionally, because a page whose layout lives in
	 * Elementor's own meta has an empty post_content, and the editor preview
	 * needs this wrapper to exist before it can load.
	 */
	while ( have_posts() ) {
		the_post();
		echo '<div class="ss-elementor-content">';
		the_content();
		echo '</div>';
	}
} else {

	$ss_sections = array(
		'hero'        => true,
		'usp'         => ss_option( 'sec_usp' ),
		'catrail'     => ss_option( 'sec_catrail' ),
		'cats'        => ss_option( 'sec_cats' ),
		'new'         => ss_option( 'sec_new' ),
		'promo'       => ss_option( 'sec_promo' ),
		'bestsellers' => ss_option( 'sec_bestsellers' ),
		'deal'        => ss_option( 'sec_deal' ),
		'sarees'      => ss_option( 'sec_sarees' ),
		'jewel'       => ss_option( 'sec_jewel' ),
		'lookbook'    => ss_option( 'sec_lookbook' ),
		'band'        => ss_option( 'sec_band' ),
		'reviews'     => ss_option( 'sec_reviews' ),
		'blog'        => ss_option( 'sec_blog' ),
		'gram'        => ss_option( 'sec_gram' ),
		'newsletter'  => ss_option( 'sec_newsletter' ),
	);

	foreach ( $ss_sections as $ss_name => $ss_enabled ) {
		if ( $ss_enabled ) {
			get_template_part( 'template-parts/home/' . $ss_name );
		}
	}

	// A static page assigned as the front page keeps its own content, below
	// the storefront sections, so anything typed in the editor is not lost.
	if ( is_page() && have_posts() ) {
		while ( have_posts() ) {
			the_post();

			if ( trim( get_the_content() ) ) {
				echo '<div class="ss-section"><div class="ss-container ss-container--narrow ss-entry">';
				the_content();
				echo '</div></div>';
			}
		}
	}
}

get_footer();
