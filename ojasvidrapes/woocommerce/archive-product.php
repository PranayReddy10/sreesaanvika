<?php
/**
 * Shop and product archive.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

$od_title = woocommerce_page_title( false );
$od_desc  = '';

if ( is_product_category() || is_product_tag() ) {
	$od_term = get_queried_object();
	$od_desc = $od_term && ! empty( $od_term->description ) ? wp_strip_all_tags( $od_term->description ) : '';
} elseif ( is_shop() ) {
	$od_shop_id = wc_get_page_id( 'shop' );
	$od_page    = $od_shop_id ? get_post( $od_shop_id ) : null;
	$od_desc    = $od_page ? wp_trim_words( wp_strip_all_tags( $od_page->post_content ), 34 ) : '';
}

od_page_header( $od_title, $od_desc );

do_action( 'woocommerce_before_main_content' );
?>

<?php if ( od_woo_has_sidebar() ) : ?>
	<aside class="od-shop-sidebar">
		<button type="button" class="od-icon-btn od-shop-sidebar__close" data-close
			aria-label="<?php esc_attr_e( 'Close filters', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'close', 19 ); ?>
		</button>

		<div class="od-shop-sidebar__head">
			<h3 style="margin:0"><?php esc_html_e( 'Refine', 'ojasvidrapes' ); ?></h3>
		</div>

		<?php get_template_part( 'template-parts/shop/filters' ); ?>
	</aside>
<?php endif; ?>

<div class="od-shop-results">

	<div class="od-shop-toolbar">
		<?php woocommerce_result_count(); ?>

		<div class="od-toolbar__right">
			<button type="button" class="od-btn od-btn--sm od-btn--solid-dark od-filter-open">
				<?php od_the_icon( 'filter', 15 ); ?>
				<?php esc_html_e( 'Filters', 'ojasvidrapes' ); ?>
			</button>

			<div class="od-viewtoggle" role="group" aria-label="<?php esc_attr_e( 'Change layout', 'ojasvidrapes' ); ?>">
				<button type="button" data-view="grid" class="is-active" aria-label="<?php esc_attr_e( 'Grid view', 'ojasvidrapes' ); ?>">
					<?php od_the_icon( 'grid', 16 ); ?>
				</button>
				<button type="button" data-view="list" aria-label="<?php esc_attr_e( 'List view', 'ojasvidrapes' ); ?>">
					<?php od_the_icon( 'list', 16 ); ?>
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
		od_empty_state(
			'search',
			__( 'Nothing matches those filters', 'ojasvidrapes' ),
			__( 'Try widening the price range or clearing a filter — the rest of the collection is waiting.', 'ojasvidrapes' ),
			wc_get_page_permalink( 'shop' ),
			__( 'Browse everything', 'ojasvidrapes' )
		);
		?>
	<?php endif; ?>

</div>

<?php
do_action( 'woocommerce_after_main_content' );

get_footer( 'shop' );
