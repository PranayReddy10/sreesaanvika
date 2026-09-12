<?php
/**
 * Homepage.
 *
 * Renders the storefront sections in the order set in the Customizer. If a
 * static page is assigned as the front page, its own content is appended
 * below the sections so the editor is never lost.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ss_sections = array(
	'hero'        => true,
	'usp'         => ss_option( 'sec_usp', true ),
	'catrail'     => ss_option( 'sec_catrail', true ),
	'cats'        => ss_option( 'sec_cats', true ),
	'new'         => ss_option( 'sec_new', true ),
	'promo'       => ss_option( 'sec_promo', true ),
	'bestsellers' => ss_option( 'sec_bestsellers', true ),
	'deal'        => ss_option( 'sec_deal', true ),
	'sarees'      => ss_option( 'sec_sarees', true ),
	'jewel'       => ss_option( 'sec_jewel', true ),
	'lookbook'    => ss_option( 'sec_lookbook', true ),
	'band'        => ss_option( 'sec_band', true ),
	'reviews'     => ss_option( 'sec_reviews', true ),
	'blog'        => ss_option( 'sec_blog', true ),
	'gram'        => ss_option( 'sec_gram', true ),
	'newsletter'  => ss_option( 'sec_newsletter', true ),
);

foreach ( $ss_sections as $ss_name => $ss_enabled ) {
	if ( $ss_enabled ) {
		get_template_part( 'template-parts/home/' . $ss_name );
	}
}

// A page assigned as the front page still gets its own content, below the
// storefront sections, so the editor is never lost.
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

get_footer();
