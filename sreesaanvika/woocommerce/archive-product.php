<?php
/**
 * Shop and product archive.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$ss_title = woocommerce_page_title( false );
$ss_desc  = '';

if ( is_product_category() || is_product_tag() ) {
	$ss_term = get_queried_object();
	$ss_desc = $ss_term && ! empty( $ss_term->description ) ? wp_strip_all_tags( $ss_term->description ) : '';
} elseif ( is_shop() ) {
	$ss_shop_id = wc_get_page_id( 'shop' );
	$ss_page    = $ss_shop_id ? get_post( $ss_shop_id ) : null;
	$ss_desc    = $ss_page ? wp_trim_words( wp_strip_all_tags( $ss_page->post_content ), 34 ) : '';
}

ss_page_header( $ss_title, $ss_desc );

do_action( 'woocommerce_before_main_content' );
?>

<?php if ( ss_woo_has_sidebar() ) : ?>
	<aside class="ss-shop-sidebar">
		<button type="button" class="ss-icon-btn ss-shop-sidebar__close" data-close
			aria-label="<?php esc_attr_e( 'Close filters', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'close', 19 ); ?>
		</button>

		<div class="ss-shop-sidebar__head">
			<h3 style="margin:0"><?php esc_html_e( 'Refine', 'sreesaanvika' ); ?></h3>
		</div>

		<?php get_template_part( 'template-parts/shop/filters' ); ?>
	</aside>
<?php endif; ?>

<div class="ss-shop-results">

	<div class="ss-shop-toolbar">
		<?php woocommerce_result_count(); ?>

		<div class="ss-toolbar__right">
			<button type="button" class="ss-btn ss-btn--sm ss-btn--solid-dark ss-filter-open">
				<?php ss_the_icon( 'filter', 15 ); ?>
				<?php esc_html_e( 'Filters', 'sreesaanvika' ); ?>
			</button>

			<div class="ss-viewtoggle" role="group" aria-label="<?php esc_attr_e( 'Change layout', 'sreesaanvika' ); ?>">
				<button type="button" data-view="grid" class="is-active" aria-label="<?php esc_attr_e( 'Grid view', 'sreesaanvika' ); ?>">
					<?php ss_the_icon( 'grid', 16 ); ?>
				</button>
				<button type="button" data-view="list" aria-label="<?php esc_attr_e( 'List view', 'sreesaanvika' ); ?>">
					<?php ss_the_icon( 'list', 16 ); ?>
				</button>
			</div>

			<?php woocommerce_catalog_ordering(); ?>
		</div>
	</div>

	<?php if ( woocommerce_product_loop() ) : ?>

		<?php
		do_action( 'woocommerce_before_shop_loop' );

		woocommerce_product_loop_start();

		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}

		woocommerce_product_loop_end();

		do_action( 'woocommerce_after_shop_loop' );
		?>

	<?php else : ?>
		<?php
		ss_empty_state(
			'search',
			__( 'Nothing matches those filters', 'sreesaanvika' ),
			__( 'Try widening the price range or clearing a filter — the rest of the collection is waiting.', 'sreesaanvika' ),
			wc_get_page_permalink( 'shop' ),
			__( 'Browse everything', 'sreesaanvika' )
		);
		?>
	<?php endif; ?>

</div>

<?php
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
