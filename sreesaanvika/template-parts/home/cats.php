<?php
/**
 * Category mosaic.
 *
 * @package SreeSaanvika
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
$ss_tiles = array();

if ( 'products' === ss_option( 'cats_source', 'categories' ) ) {
	foreach ( ss_picked_products( ss_option( 'cats_products' ), absint( ss_option( 'cats_count' ) ) ) as $ss_product ) {
		$ss_tiles[] = array(
			'title' => $ss_product->get_name(),
			'meta'  => wp_strip_all_tags( $ss_product->get_price_html() ),
			'img'   => ss_product_image_url( $ss_product, 'ss-hero' ),
			'url'   => $ss_product->get_permalink(),
			'cta'   => __( 'View', 'sreesaanvika' ),
		);
	}
} else {
	foreach ( ss_category_terms( ss_option( 'cats_slugs' ), absint( ss_option( 'cats_count' ) ), true ) as $ss_term ) {
		$ss_thumb_id = get_term_meta( $ss_term->term_id, 'thumbnail_id', true );

		$ss_tiles[] = array(
			'title' => $ss_term->name,
			/* translators: %s: number of products */
			'meta'  => sprintf( _n( '%s piece', '%s pieces', $ss_term->count, 'sreesaanvika' ), number_format_i18n( $ss_term->count ) ),
			'img'   => $ss_thumb_id ? wp_get_attachment_image_url( $ss_thumb_id, 'ss-hero' ) : '',
			'url'   => get_term_link( $ss_term ),
			'cta'   => __( 'Explore', 'sreesaanvika' ),
		);
	}
}

if ( ! $ss_tiles ) {
	return;
}

$ss_sizes = ss_category_tile_sizes( count( $ss_tiles ) );
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'The collections', 'sreesaanvika' ),
			__( 'Curated for <em>Every Celebration</em>', 'sreesaanvika' ),
			__( 'Weddings, festivals, workdays and the quiet evenings in between.', 'sreesaanvika' )
		);
		?>

		<div class="ss-cats">
			<?php
			foreach ( $ss_tiles as $ss_n => $ss_tile ) :
				$ss_size = isset( $ss_sizes[ $ss_n ] ) ? $ss_sizes[ $ss_n ] : 'w2';
				?>
				<article class="ss-cat ss-cat--<?php echo esc_attr( $ss_size ); ?>">
					<div class="ss-cat__img"<?php echo $ss_tile['img'] ? ss_bg_style( $ss_tile['img'] ) : ''; ?>></div>

					<div class="ss-cat__body">
						<?php if ( $ss_tile['meta'] ) : ?>
							<span class="ss-cat__count"><?php echo esc_html( $ss_tile['meta'] ); ?></span>
						<?php endif; ?>

						<h3 class="ss-cat__title"><?php echo esc_html( $ss_tile['title'] ); ?></h3>

						<span class="ss-cat__link">
							<?php echo esc_html( $ss_tile['cta'] ); ?>
							<?php ss_the_icon( 'arrow-right', 15 ); ?>
						</span>
					</div>

					<a class="ss-cat__stretch" href="<?php echo esc_url( $ss_tile['url'] ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $ss_tile['title'] ); ?></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
