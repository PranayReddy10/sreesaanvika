<?php
/**
 * Theme product gallery — thumbnails, hover zoom and lightbox.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! $product instanceof WC_Product ) {
	return;
}

$od_ids = array();

if ( $product->get_image_id() ) {
	$od_ids[] = $product->get_image_id();
}

$od_ids = array_merge( $od_ids, $product->get_gallery_image_ids() );
$od_ids = array_values( array_unique( array_filter( $od_ids ) ) );

$od_shots = array();

foreach ( $od_ids as $od_id ) {
	$od_shots[] = array(
		'thumb' => wp_get_attachment_image_url( $od_id, 'od-thumb' ),
		'large' => wp_get_attachment_image_url( $od_id, 'od-product-lg' ),
		'full'  => wp_get_attachment_image_url( $od_id, 'full' ),
		'alt'   => trim( wp_strip_all_tags( get_post_meta( $od_id, '_wp_attachment_image_alt', true ) ) ),
	);
}

// Never render an empty stage.
if ( ! $od_shots ) {
	$od_placeholder = od_placeholder( 'product' );

	$od_shots[] = array(
		'thumb' => $od_placeholder,
		'large' => $od_placeholder,
		'full'  => $od_placeholder,
		'alt'   => $product->get_name(),
	);
}

$od_first = $od_shots[0];
$od_count = count( $od_shots );

/*
 * Per-colour image sets, if the shop owner attached any. The chrome below is
 * rendered even for a one-image product, because picking a colour can swap in
 * a set of four — is-single just hides it until then.
 */
$od_colorsets = function_exists( 'od_color_galleries' ) ? od_color_galleries( $product ) : array();
$od_single    = $od_count < 2 && ! $od_colorsets;
?>
<div class="od-gallery<?php echo $od_single ? ' is-single' : ''; ?>" data-gallery
	<?php if ( $od_colorsets ) : ?>
		data-color-galleries="<?php echo esc_attr( wp_json_encode( $od_colorsets ) ); ?>"
	<?php endif; ?>>

	<div class="od-gallery__thumbs" role="tablist" aria-label="<?php esc_attr_e( 'Product images', 'ojasvidrapes' ); ?>">
		<?php foreach ( $od_shots as $od_n => $od_shot ) : ?>
			<button type="button"
				class="od-gallery__thumb<?php echo 0 === $od_n ? ' is-active' : ''; ?>"
				role="tab"
				aria-current="<?php echo 0 === $od_n ? 'true' : 'false'; ?>"
				data-full="<?php echo esc_url( $od_shot['full'] ); ?>"
				data-large="<?php echo esc_url( $od_shot['large'] ); ?>"
				data-alt="<?php echo esc_attr( $od_shot['alt'] ); ?>">
				<img src="<?php echo esc_url( $od_shot['thumb'] ); ?>" alt="" loading="lazy" width="90" height="120" />
				<span class="screen-reader-text">
					<?php
					printf(
						/* translators: %d: image number */
						esc_html__( 'View image %d', 'ojasvidrapes' ),
						absint( $od_n + 1 )
					);
					?>
				</span>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="od-gallery__stage">
		<div class="od-gallery__frame">
			<img src="<?php echo esc_url( $od_first['large'] ); ?>"
				alt="<?php echo esc_attr( $od_first['alt'] ? $od_first['alt'] : $product->get_name() ); ?>"
				width="1200" height="1600" fetchpriority="high" />

			<div class="od-gallery__zoom" style="background-image:url(<?php echo esc_url( $od_first['full'] ); ?>)" aria-hidden="true"></div>
		</div>

		<?php od_product_badges( $product, 'single' ); ?>

		<div class="od-gallery__tools">
			<button type="button" class="od-icon-btn od-expand" aria-label="<?php esc_attr_e( 'View full size', 'ojasvidrapes' ); ?>">
				<?php od_the_icon( 'expand', 18 ); ?>
			</button>

			<?php if ( od_option( 'wishlist_on', true ) ) : ?>
				<button type="button" class="od-icon-btn od-wishlist-btn" data-id="<?php echo esc_attr( $product->get_id() ); ?>"
					aria-label="<?php esc_attr_e( 'Add to wishlist', 'ojasvidrapes' ); ?>" aria-pressed="false">
					<?php od_the_icon( 'heart', 18 ); ?>
				</button>
			<?php endif; ?>
		</div>

		<button type="button" class="od-icon-btn od-gallery__arrow od-gallery__arrow--prev"
			aria-label="<?php esc_attr_e( 'Previous image', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'chevron-left', 18 ); ?>
		</button>

		<button type="button" class="od-icon-btn od-gallery__arrow od-gallery__arrow--next"
			aria-label="<?php esc_attr_e( 'Next image', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'chevron-right', 18 ); ?>
		</button>

		<span class="od-gallery__counter">1 / <?php echo esc_html( $od_count ); ?></span>

		<span class="od-gallery__hint">
			<?php od_the_icon( 'zoom', 14 ); ?>
			<?php esc_html_e( 'Hover to zoom · click to enlarge', 'ojasvidrapes' ); ?>
		</span>
	</div>
</div>

<div class="od-lightbox<?php echo $od_single ? ' is-single' : ''; ?>" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Product image viewer', 'ojasvidrapes' ); ?>">

	<button type="button" class="od-icon-btn od-lightbox__close" aria-label="<?php esc_attr_e( 'Close', 'ojasvidrapes' ); ?>">
		<?php od_the_icon( 'close', 20 ); ?>
	</button>

	<button type="button" class="od-icon-btn od-lightbox__prev" aria-label="<?php esc_attr_e( 'Previous image', 'ojasvidrapes' ); ?>">
		<?php od_the_icon( 'chevron-left', 20 ); ?>
	</button>

	<button type="button" class="od-icon-btn od-lightbox__next" aria-label="<?php esc_attr_e( 'Next image', 'ojasvidrapes' ); ?>">
		<?php od_the_icon( 'chevron-right', 20 ); ?>
	</button>

	<img class="od-lightbox__img" src="" alt="" />

	<div class="od-lightbox__strip"></div>
</div>
