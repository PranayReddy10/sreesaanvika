<?php
/**
 * Search results.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header();
?>

<div class="od-container od-section">
	<?php if ( have_posts() ) : ?>
		<div class="od-grid od-grid--3">
			<?php
			while ( have_posts() ) {
				the_post();

				if ( class_exists( 'WooCommerce' ) && 'product' === get_post_type() ) {
					echo '<ul class="products columns-1" style="display:contents">';
					wc_get_template_part( 'content', 'product' );
					echo '</ul>';
				} else {
					od_post_card();
				}
			}
			?>
		</div>

		<?php od_pagination(); ?>
	<?php else : ?>
		<?php
		od_empty_state(
			'search',
			__( 'No matches found', 'ojasvidrapes' ),
			__( 'Try a different spelling, a broader word, or browse the collections instead.', 'ojasvidrapes' ),
			class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			__( 'Browse the shop', 'ojasvidrapes' )
		);
		?>

		<div style="max-width:520px;margin:28px auto 0">
			<?php od_search_form(); ?>
		</div>
	<?php endif; ?>
</div>

<?php
get_footer();
