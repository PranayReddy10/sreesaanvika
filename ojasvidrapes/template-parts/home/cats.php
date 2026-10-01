<?php
/**
 * The collections mosaic.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

/*
 * The mosaic shows either categories or a hand-picked set of products. Both
 * end up as the same tiles — a picture, a line above the name and a link —
 * so the section is built once from a common shape.
 */
$od_tiles = array();

/*
 * A shop that does not sort itself has no terms to tile, so the mosaic shows
 * pieces instead of falling silent.
 */
$od_source = od_option( 'cats_source', 'categories' );

if ( ! od_has_browse() ) {
	$od_source = 'products';
}

if ( 'products' === $od_source ) {
	foreach ( od_picked_products( od_option( 'cats_products' ), absint( od_option( 'cats_count' ) ) ) as $od_product ) {
		$od_tiles[] = array(
			'title' => $od_product->get_name(),
			'meta'  => wp_strip_all_tags( $od_product->get_price_html() ),
			'img'   => od_product_image_url( $od_product, 'od-hero' ),
			'url'   => $od_product->get_permalink(),
			'cta'   => __( 'View', 'ojasvidrapes' ),
		);
	}
} else {
	foreach ( od_browse_terms( od_option( 'cats_slugs' ), absint( od_option( 'cats_count' ) ), true ) as $od_term ) {
		$od_thumb_id = get_term_meta( $od_term->term_id, 'thumbnail_id', true );

		$od_tiles[] = array(
			'title' => $od_term->name,
			/* translators: %s: number of products */
			'meta'  => sprintf( _n( '%s piece', '%s pieces', $od_term->count, 'ojasvidrapes' ), number_format_i18n( $od_term->count ) ),
			'img'   => $od_thumb_id ? wp_get_attachment_image_url( $od_thumb_id, 'od-hero' ) : '',
			'url'   => get_term_link( $od_term ),
			'cta'   => __( 'Explore', 'ojasvidrapes' ),
		);
	}
}

if ( ! $od_tiles ) {
	return;
}

$od_sizes = od_category_tile_sizes( count( $od_tiles ) );
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'The collections', 'ojasvidrapes' ),
			__( 'Curated for <em>Every Celebration</em>', 'ojasvidrapes' ),
			__( 'Weddings, festivals, workdays and the quiet evenings in between.', 'ojasvidrapes' )
		);
		?>

		<div class="od-cats">
			<?php
			foreach ( $od_tiles as $od_n => $od_tile ) :
				$od_size = isset( $od_sizes[ $od_n ] ) ? $od_sizes[ $od_n ] : 'w2';
				?>
				<article class="od-cat od-cat--<?php echo esc_attr( $od_size ); ?>">
					<div class="od-cat__img"<?php echo $od_tile['img'] ? od_bg_style( $od_tile['img'] ) : ''; ?>></div>

					<div class="od-cat__body">
						<?php if ( $od_tile['meta'] ) : ?>
							<span class="od-cat__count"><?php echo esc_html( $od_tile['meta'] ); ?></span>
						<?php endif; ?>

						<h3 class="od-cat__title"><?php echo esc_html( $od_tile['title'] ); ?></h3>

						<span class="od-cat__link">
							<?php echo esc_html( $od_tile['cta'] ); ?>
							<?php od_the_icon( 'arrow-right', 15 ); ?>
						</span>
					</div>

					<a class="od-cat__stretch" href="<?php echo esc_url( $od_tile['url'] ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $od_tile['title'] ); ?></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
