<?php
/**
 * Per-colour galleries.
 *
 * WooCommerce only swaps a single image when a variation is chosen, which is
 * no use for a saree photographed in two colourways with four shots each. This
 * lets the shop owner attach a set of images to each colour of a product, and
 * the front end then shows only that colour's photos when it is selected.
 *
 * Images are stored per product rather than per variation, because a colour
 * usually spans several variations (Green/S, Green/M …) and nobody wants to
 * attach the same four photos six times.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

const OD_COLOR_GALLERY_META = '_od_color_galleries';

/**
 * The saved colour → attachment ids map for a product.
 *
 * @param int $product_id Product id.
 * @return array<string,int[]>
 */
function od_color_gallery_ids( $product_id ) {
	$saved = get_post_meta( $product_id, OD_COLOR_GALLERY_META, true );

	if ( ! is_array( $saved ) ) {
		return array();
	}

	$out = array();

	foreach ( $saved as $slug => $ids ) {
		$ids = array_values( array_filter( array_map( 'absint', (array) $ids ) ) );

		if ( $ids ) {
			$out[ sanitize_title( $slug ) ] = $ids;
		}
	}

	return $out;
}

/**
 * Every image attached to one variation: its own, then its gallery.
 *
 * WooCommerce and several gallery plugins now let a variation carry more than
 * one photo. Reading whatever is already there is the whole point — nobody
 * should have to attach the same four images twice.
 *
 * @param WC_Product_Variation $variation Variation.
 * @return int[]
 */
function od_variation_image_ids( $variation ) {
	$ids = array();

	if ( is_callable( array( $variation, 'get_image_id' ) ) ) {
		$ids[] = (int) $variation->get_image_id();
	}

	if ( is_callable( array( $variation, 'get_gallery_image_ids' ) ) ) {
		$ids = array_merge( $ids, array_map( 'absint', (array) $variation->get_gallery_image_ids() ) );
	}

	/*
	 * Where the gallery is not exposed through the product object, read the
	 * meta directly — core's own key first, then the keys used by the popular
	 * variation-gallery plugins.
	 */
	$keys = apply_filters(
		'od_variation_gallery_meta_keys',
		array(
			'_product_image_gallery',
			'_wc_additional_variation_images',
			'_woo_variation_gallery_images',
			'_wc_variation_gallery_images',
			'rtwpvg_images',
		)
	);

	foreach ( $keys as $key ) {
		$saved = get_post_meta( $variation->get_id(), $key, true );

		if ( ! $saved ) {
			continue;
		}

		$ids = array_merge( $ids, array_map( 'absint', is_array( $saved ) ? $saved : explode( ',', (string) $saved ) ) );
	}

	return array_values( array_unique( array_filter( $ids ) ) );
}

/**
 * Colour → attachment ids taken from the product's own variations.
 *
 * Read straight from the variation's own image and gallery, so a shop that has
 * already filled those in gets working colour swatches and a colour-aware
 * gallery with nothing more to do. The panel below is only an override.
 *
 * @param WC_Product $product Product.
 * @return array<string,int[]>
 */
function od_color_variation_images( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return array();
	}

	// Product cards ask once per swatch; loading every variation each time is
	// far too much work for a grid of twelve.
	static $cache = array();

	$cache_key = $product->get_id();

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	$taxonomy = od_color_taxonomy( $product );

	if ( ! $taxonomy ) {
		$cache[ $cache_key ] = array();

		return array();
	}

	$key = 'attribute_' . sanitize_title( $taxonomy );
	$out = array();

	foreach ( $product->get_children() as $child_id ) {
		$variation = wc_get_product( $child_id );

		if ( ! $variation ) {
			continue;
		}

		$attributes = $variation->get_attributes();
		$slug       = '';

		// Variation attributes are keyed with or without the prefix by version.
		foreach ( array( $key, sanitize_title( $taxonomy ) ) as $candidate ) {
			if ( ! empty( $attributes[ $candidate ] ) ) {
				$slug = sanitize_title( $attributes[ $candidate ] );
				break;
			}
		}

		$images = od_variation_image_ids( $variation );

		if ( ! $slug || ! $images ) {
			continue;
		}

		if ( ! isset( $out[ $slug ] ) ) {
			$out[ $slug ] = array();
		}

		foreach ( $images as $image_id ) {
			if ( ! in_array( $image_id, $out[ $slug ], true ) ) {
				$out[ $slug ][] = $image_id;
			}
		}
	}

	$cache[ $cache_key ] = $out;

	return $out;
}

/**
 * The colour galleries of a product, resolved to image URLs.
 *
 * @param WC_Product $product Product.
 * @return array<string,array<int,array<string,string>>>
 */
function od_color_galleries( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return array();
	}

	$galleries = array();
	$sets      = od_color_gallery_ids( $product->get_id() ) + od_color_variation_images( $product );

	foreach ( $sets as $slug => $ids ) {
		$shots = array();

		foreach ( $ids as $id ) {
			$large = wp_get_attachment_image_url( $id, 'od-product-lg' );

			if ( ! $large ) {
				continue;
			}

			$shots[] = array(
				'thumb' => wp_get_attachment_image_url( $id, 'od-thumb' ),
				'large' => $large,
				'full'  => wp_get_attachment_image_url( $id, 'full' ),
				'alt'   => trim( wp_strip_all_tags( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) ),
			);
		}

		if ( $shots ) {
			$galleries[ $slug ] = $shots;
		}
	}

	return $galleries;
}

/**
 * The swatch image for one colour: its first gallery shot, if it has one.
 *
 * @param WC_Product $product Product.
 * @param string     $slug    Colour term slug.
 * @param string     $size    Image size.
 * @return string
 */
function od_color_swatch_image( $product, $slug, $size = 'od-thumb' ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	$ids  = od_color_gallery_ids( $product->get_id() ) + od_color_variation_images( $product );
	$slug = sanitize_title( $slug );

	if ( empty( $ids[ $slug ][0] ) ) {
		return '';
	}

	return (string) wp_get_attachment_image_url( $ids[ $slug ][0], $size );
}

/**
 * Which attribute on this product holds its colours.
 *
 * @param WC_Product $product Product.
 * @return string Taxonomy name, or an empty string.
 */
function od_color_taxonomy( $product ) {
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	foreach ( array_keys( $product->get_attributes() ) as $name ) {
		if ( preg_match( '/color|colour|shade/i', $name ) ) {
			return $name;
		}
	}

	return '';
}

/* -------------------------------------------------------------------------
 * Admin
 * ---------------------------------------------------------------------- */

/**
 * Add the colour gallery panel to the product editor.
 */
function od_color_gallery_metabox() {
	add_meta_box(
		'od-color-galleries',
		__( 'Colour galleries', 'ojasvidrapes' ),
		'od_color_gallery_panel',
		'product',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'od_color_gallery_metabox' );

/**
 * Render the panel.
 *
 * @param WP_Post $post Product post.
 */
function od_color_gallery_panel( $post ) {
	$product = function_exists( 'wc_get_product' ) ? wc_get_product( $post->ID ) : null;

	if ( ! $product ) {
		return;
	}

	$terms = od_product_color_terms( $product );

	if ( ! $terms ) {
		echo '<p>' . esc_html__( 'Add a Colour attribute to this product and save it, then come back here to give each colour its own photos.', 'ojasvidrapes' ) . '</p>';
		return;
	}

	$saved   = od_color_gallery_ids( $post->ID );
	$derived = od_color_variation_images( $product );

	wp_nonce_field( 'od_color_gallery', 'od_color_gallery_nonce' );
	?>
	<p class="description" style="margin-bottom:14px">
		<?php esc_html_e( 'The shop already takes each colour\'s photos from that colour\'s variation — its image and its gallery. There is nothing to fill in here unless you want a colour to show different photos on the shop than on its variation.', 'ojasvidrapes' ); ?>
	</p>

	<div class="od-cg">
		<?php foreach ( $terms as $term ) : ?>
			<?php
			$override = isset( $saved[ $term->slug ] ) ? $saved[ $term->slug ] : array();
			$from_var = isset( $derived[ $term->slug ] ) ? $derived[ $term->slug ] : array();
			$showing  = $override ? $override : $from_var;
			?>
			<div class="od-cg__row<?php echo $override ? ' is-override' : ''; ?>" data-color="<?php echo esc_attr( $term->slug ); ?>">
				<div class="od-cg__label">
					<span class="od-cg__dot" style="background:<?php echo esc_attr( od_color_hex( $term->name, $term->term_id ) ); ?>"></span>
					<strong><?php echo esc_html( $term->name ); ?></strong>

					<span class="od-cg__source">
						<?php
						if ( $override ) {
							esc_html_e( 'using the photos set here', 'ojasvidrapes' );
						} elseif ( $from_var ) {
							printf(
								/* translators: %d: number of images */
								esc_html( _n( 'using %d photo from this colour\'s variation', 'using %d photos from this colour\'s variation', count( $from_var ), 'ojasvidrapes' ) ),
								count( $from_var )
							);
						} else {
							esc_html_e( 'no photos yet — add one to the variation, or here', 'ojasvidrapes' );
						}
						?>
					</span>
				</div>

				<div class="od-cg__images">
					<?php foreach ( $showing as $id ) : ?>
						<?php $thumb = wp_get_attachment_image_url( $id, 'thumbnail' ); ?>
						<?php if ( $thumb ) : ?>
							<span class="od-cg__img<?php echo $override ? '' : ' is-inherited'; ?>" data-id="<?php echo absint( $id ); ?>">
								<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
								<?php if ( $override ) : ?>
									<button type="button" class="od-cg__remove" aria-label="<?php esc_attr_e( 'Remove', 'ojasvidrapes' ); ?>">&times;</button>
								<?php endif; ?>
							</span>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>

				<p>
					<button type="button" class="button od-cg__add">
						<?php $override ? esc_html_e( 'Add images', 'ojasvidrapes' ) : esc_html_e( 'Show different photos here', 'ojasvidrapes' ); ?>
					</button>

					<?php if ( $override ) : ?>
						<button type="button" class="button-link od-cg__clear">
							<?php
							$from_var
								? esc_html_e( 'Go back to the variation photos', 'ojasvidrapes' )
								: esc_html_e( 'Clear', 'ojasvidrapes' );
							?>
						</button>
					<?php endif; ?>
				</p>

				<input type="hidden" class="od-cg__value" name="od_color_gallery[<?php echo esc_attr( $term->slug ); ?>]"
					value="<?php echo esc_attr( implode( ',', $override ) ); ?>" />
			</div>
		<?php endforeach; ?>
	</div>
	<?php
}

/**
 * Save the panel.
 *
 * @param int $post_id Product id.
 */
function od_color_gallery_save( $post_id ) {
	if ( ! isset( $_POST['od_color_gallery_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['od_color_gallery_nonce'] ) ), 'od_color_gallery' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( empty( $_POST['od_color_gallery'] ) || ! is_array( $_POST['od_color_gallery'] ) ) {
		delete_post_meta( $post_id, OD_COLOR_GALLERY_META );
		return;
	}

	$clean = array();

	foreach ( wp_unslash( $_POST['od_color_gallery'] ) as $slug => $raw ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $raw ) ) ) );

		if ( $ids ) {
			$clean[ sanitize_title( $slug ) ] = $ids;
		}
	}

	if ( $clean ) {
		update_post_meta( $post_id, OD_COLOR_GALLERY_META, $clean );
	} else {
		delete_post_meta( $post_id, OD_COLOR_GALLERY_META );
	}
}
add_action( 'woocommerce_process_product_meta', 'od_color_gallery_save' );
add_action( 'save_post_product', 'od_color_gallery_save' );

/**
 * Media picker assets for the panel.
 *
 * @param string $hook Current admin page.
 */
function od_color_gallery_assets( $hook ) {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'product' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'od-color-gallery', OD_URI . '/assets/js/admin-color-gallery.js', array( 'jquery' ), OD_VERSION, true );

	wp_localize_script(
		'od-color-gallery',
		'odCG',
		array(
			'title'    => __( 'Choose images for this colour', 'ojasvidrapes' ),
			'button'   => __( 'Use these images', 'ojasvidrapes' ),
			'reverted' => __( 'Saved — this colour goes back to its variation photos when you update the product.', 'ojasvidrapes' ),
		)
	);

	wp_add_inline_style(
		'woocommerce_admin_styles',
		'.od-cg__row{padding:14px 0;border-bottom:1px solid #e0e0e0}'
		. '.od-cg__row:last-child{border-bottom:0}'
		. '.od-cg__label{display:flex;align-items:center;gap:8px;margin-bottom:8px}'
		. '.od-cg__dot{width:16px;height:16px;border-radius:50%;border:1px solid rgba(0,0,0,.2);display:inline-block}'
		. '.od-cg__source{color:#787c82;font-size:12px;font-style:italic}'
		. '.od-cg__images{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px}'
		. '.od-cg__images:empty{display:none}'
		. '.od-cg__img{position:relative;width:64px;height:64px;border-radius:4px;overflow:hidden;border:1px solid #ddd}'
		. '.od-cg__img.is-inherited{opacity:.62}'
		. '.od-cg__note{color:#787c82;font-size:12px;margin-left:8px}'
		. '.od-cg__img img{width:100%;height:100%;object-fit:cover;display:block}'
		. '.od-cg__remove{position:absolute;top:0;right:0;width:20px;height:20px;border:0;background:rgba(0,0,0,.65);color:#fff;cursor:pointer;line-height:1;padding:0}'
	);
}
add_action( 'admin_enqueue_scripts', 'od_color_gallery_assets' );
