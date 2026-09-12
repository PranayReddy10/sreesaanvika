<?php
/**
 * Theme product gallery — thumbnails, hover zoom and lightbox.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$ss_ids = array();

if ( $product->get_image_id() ) {
	$ss_ids[] = $product->get_image_id();
}

$ss_ids = array_merge( $ss_ids, $product->get_gallery_image_ids() );
$ss_ids = array_values( array_unique( array_filter( $ss_ids ) ) );

$ss_shots = array();

foreach ( $ss_ids as $ss_id ) {
	$ss_shots[] = array(
		'thumb' => wp_get_attachment_image_url( $ss_id, 'ss-thumb' ),
		'large' => wp_get_attachment_image_url( $ss_id, 'ss-product-lg' ),
		'full'  => wp_get_attachment_image_url( $ss_id, 'full' ),
		'alt'   => trim( wp_strip_all_tags( get_post_meta( $ss_id, '_wp_attachment_image_alt', true ) ) ),
	);
}

// Never render an empty stage.
if ( ! $ss_shots ) {
	$ss_placeholder = ss_placeholder( 'product' );

	$ss_shots[] = array(
		'thumb' => $ss_placeholder,
		'large' => $ss_placeholder,
		'full'  => $ss_placeholder,
		'alt'   => $product->get_name(),
	);
}

$ss_first = $ss_shots[0];
$ss_count = count( $ss_shots );
?>
<div class="ss-gallery" data-gallery>

	<?php if ( $ss_count > 1 ) : ?>
		<div class="ss-gallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'sreesaanvika' ); ?>">
			<?php foreach ( $ss_shots as $ss_n => $ss_shot ) : ?>
				<button type="button"
					class="ss-gallery__thumb<?php echo 0 === $ss_n ? ' is-active' : ''; ?>"
					role="tab"
					aria-current="<?php echo 0 === $ss_n ? 'true' : 'false'; ?>"
					data-full="<?php echo esc_url( $ss_shot['full'] ); ?>"
					data-large="<?php echo esc_url( $ss_shot['large'] ); ?>"
					data-alt="<?php echo esc_attr( $ss_shot['alt'] ); ?>">
					<img src="<?php echo esc_url( $ss_shot['thumb'] ); ?>" alt="" loading="lazy" width="90" height="120" />
					<span class="screen-reader-text">
						<?php
						printf(
							/* translators: %d: image number */
							esc_html__( 'View image %d', 'sreesaanvika' ),
							absint( $ss_n + 1 )
						);
						?>
					</span>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="ss-gallery__stage">
		<div class="ss-gallery__frame">
			<img src="<?php echo esc_url( $ss_first['large'] ); ?>"
				alt="<?php echo esc_attr( $ss_first['alt'] ? $ss_first['alt'] : $product->get_name() ); ?>"
				width="1200" height="1600" fetchpriority="high" />

			<div class="ss-gallery__zoom" style="background-image:url(<?php echo esc_url( $ss_first['full'] ); ?>)" aria-hidden="true"></div>
		</div>

		<?php ss_product_badges( $product, 'single' ); ?>

		<div class="ss-gallery__tools">
			<button type="button" class="ss-icon-btn ss-expand" aria-label="<?php esc_attr_e( 'View full size', 'sreesaanvika' ); ?>">
				<?php ss_the_icon( 'expand', 18 ); ?>
			</button>

			<?php if ( ss_option( 'wishlist_on', true ) ) : ?>
				<button type="button" class="ss-icon-btn ss-wishlist-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Add to wishlist', 'sreesaanvika' ); ?>" aria-pressed="false">
					<?php ss_the_icon( 'heart', 18 ); ?>
				</button>
			<?php endif; ?>
		</div>

		<?php if ( $ss_count > 1 ) : ?>
			<button type="button" class="ss-icon-btn ss-gallery__arrow ss-gallery__arrow--prev"
				aria-label="<?php esc_attr_e( 'Previous image', 'sreesaanvika' ); ?>">
				<?php ss_the_icon( 'chevron-left', 18 ); ?>
			</button>

			<button type="button" class="ss-icon-btn ss-gallery__arrow ss-gallery__arrow--next"
				aria-label="<?php esc_attr_e( 'Next image', 'sreesaanvika' ); ?>">
				<?php ss_the_icon( 'chevron-right', 18 ); ?>
			</button>

			<span class="ss-gallery__counter">1 / <?php echo esc_html( $ss_count ); ?></span>
		<?php endif; ?>

		<span class="ss-gallery__hint">
			<?php ss_the_icon( 'zoom', 14 ); ?>
			<?php esc_html_e( 'Hover to zoom · click to enlarge', 'sreesaanvika' ); ?>
		</span>
	</div>
</div>

<div class="ss-lightbox" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Product image viewer', 'sreesaanvika' ); ?>">

	<button type="button" class="ss-icon-btn ss-lightbox__close" aria-label="<?php esc_attr_e( 'Close', 'sreesaanvika' ); ?>">
		<?php ss_the_icon( 'close', 20 ); ?>
	</button>

	<?php if ( $ss_count > 1 ) : ?>
		<button type="button" class="ss-icon-btn ss-lightbox__prev" aria-label="<?php esc_attr_e( 'Previous image', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'chevron-left', 20 ); ?>
		</button>

		<button type="button" class="ss-icon-btn ss-lightbox__next" aria-label="<?php esc_attr_e( 'Next image', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'chevron-right', 20 ); ?>
		</button>
	<?php endif; ?>

	<img class="ss-lightbox__img" src="" alt="" />

	<?php if ( $ss_count > 1 ) : ?>
		<div class="ss-lightbox__strip"></div>
	<?php endif; ?>
</div>
