<?php
/**
 * Search results.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header();
?>

<div class="ss-container ss-section">
	<?php if ( have_posts() ) : ?>
		<div class="ss-grid ss-grid--3">
			<?php
			while ( have_posts() ) {
				the_post();

				if ( class_exists( 'WooCommerce' ) && 'product' === get_post_type() ) {
					echo '<ul class="products columns-1" style="display:contents">';
					wc_get_template_part( 'content', 'product' );
					echo '</ul>';
				} else {
					ss_post_card();
				}
			}
			?>
		</div>

		<?php ss_pagination(); ?>
	<?php else : ?>
		<?php
		ss_empty_state(
			'search',
			__( 'No matches found', 'sreesaanvika' ),
			__( 'Try a different spelling, a broader word, or browse the collections instead.', 'sreesaanvika' ),
			class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			__( 'Browse the shop', 'sreesaanvika' )
		);
		?>

		<div style="max-width:520px;margin:28px auto 0">
			<?php ss_search_form(); ?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
