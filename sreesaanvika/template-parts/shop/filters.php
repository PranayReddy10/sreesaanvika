<?php
/**
 * Shop filter sidebar.
 *
 * Uses registered widgets when the shop sidebar has any, and otherwise
 * renders a built-in set of filters so a fresh install is not empty.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( is_active_sidebar( 'sidebar-shop' ) ) {
	echo '<div class="ss-filters">';
	dynamic_sidebar( 'sidebar-shop' );
	echo '</div>';
	return;
}

$ss_shop_url = wc_get_page_permalink( 'shop' );

/**
 * Current query values, so the form round-trips.
 */
$ss_min = isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$ss_max = isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// A sensible ceiling drawn from the most expensive published product.
$ss_ceiling = (int) get_transient( 'ss_price_ceiling' );

if ( ! $ss_ceiling ) {
	$ss_top = wc_get_products(
		array(
			'limit'   => 1,
			'orderby' => 'meta_value_num',
			'meta_key' => '_price', // phpcs:ignore WordPress.DB.SlowDBQuery
			'order'   => 'DESC',
			'status'  => 'publish',
		)
	);

	$ss_ceiling = ( $ss_top && $ss_top[0]->get_price() ) ? (int) ceil( (float) $ss_top[0]->get_price() / 500 ) * 500 : 25000;
	$ss_ceiling = max( 1000, $ss_ceiling );

	set_transient( 'ss_price_ceiling', $ss_ceiling, HOUR_IN_SECONDS * 6 );
}

$ss_min = $ss_min ? $ss_min : 0;
$ss_max = $ss_max ? $ss_max : $ss_ceiling;
?>
<div class="ss-filters">

	<?php
	/* ---- Categories ---- */
	$ss_cats = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
		)
	);

	if ( $ss_cats && ! is_wp_error( $ss_cats ) ) :
		$ss_current = is_product_category() ? get_queried_object_id() : 0;
		?>
		<div class="ss-filter">
			<button type="button" class="ss-filter__head" aria-expanded="true">
				<?php esc_html_e( 'Category', 'sreesaanvika' ); ?>
				<?php ss_the_icon( 'chevron-down', 14 ); ?>
			</button>

			<div class="ss-filter__body">
				<ul class="ss-filter__list">
					<?php foreach ( $ss_cats as $ss_cat ) : ?>
						<li>
							<a href="<?php echo esc_url( get_term_link( $ss_cat ) ); ?>"
								<?php echo $ss_current === $ss_cat->term_id ? 'style="color:var(--ss-gold)"' : ''; ?>>
								<?php echo esc_html( $ss_cat->name ); ?>
								<span class="count"><?php echo esc_html( $ss_cat->count ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<?php /* ---- Price ---- */ ?>
	<div class="ss-filter">
		<button type="button" class="ss-filter__head" aria-expanded="true">
			<?php esc_html_e( 'Price', 'sreesaanvika' ); ?>
			<?php ss_the_icon( 'chevron-down', 14 ); ?>
		</button>

		<div class="ss-filter__body">
			<form method="get" action="<?php echo esc_url( $ss_shop_url ); ?>">
				<div class="ss-price-slider" data-price-slider>
					<div class="ss-price-slider__track">
						<div class="ss-price-slider__range"></div>
						<input type="range" class="ss-price-min" min="0" max="<?php echo esc_attr( $ss_ceiling ); ?>"
							step="100" value="<?php echo esc_attr( $ss_min ); ?>"
							aria-label="<?php esc_attr_e( 'Minimum price', 'sreesaanvika' ); ?>" />
						<input type="range" class="ss-price-max" min="0" max="<?php echo esc_attr( $ss_ceiling ); ?>"
							step="100" value="<?php echo esc_attr( $ss_max ); ?>"
							aria-label="<?php esc_attr_e( 'Maximum price', 'sreesaanvika' ); ?>" />
					</div>

					<div class="ss-price-slider__values">
						<strong data-out-min></strong>
						<strong data-out-max></strong>
					</div>
				</div>

				<input type="hidden" name="min_price" value="<?php echo esc_attr( $ss_min ); ?>" />
				<input type="hidden" name="max_price" value="<?php echo esc_attr( $ss_max ); ?>" />

				<button type="submit" class="ss-btn ss-btn--sm ss-btn--block" style="margin-top:14px">
					<?php esc_html_e( 'Apply', 'sreesaanvika' ); ?>
				</button>
			</form>
		</div>
	</div>

	<?php
	/* ---- Attribute filters (colour, size, fabric, occasion) ---- */
	$ss_attribute_taxonomies = function_exists( 'wc_get_attribute_taxonomies' ) ? wc_get_attribute_taxonomies() : array();

	foreach ( $ss_attribute_taxonomies as $ss_attribute ) :
		$ss_tax   = wc_attribute_taxonomy_name( $ss_attribute->attribute_name );
		$ss_terms = get_terms( array( 'taxonomy' => $ss_tax, 'hide_empty' => true ) );

		if ( ! $ss_terms || is_wp_error( $ss_terms ) ) {
			continue;
		}

		$ss_is_color = (bool) preg_match( '/color|colour|shade/i', $ss_attribute->attribute_name );
		$ss_is_size  = (bool) preg_match( '/size/i', $ss_attribute->attribute_name );
		$ss_active   = isset( $_GET[ 'filter_' . $ss_attribute->attribute_name ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET[ 'filter_' . $ss_attribute->attribute_name ] ) ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: array();
		?>
		<div class="ss-filter">
			<button type="button" class="ss-filter__head" aria-expanded="true">
				<?php echo esc_html( $ss_attribute->attribute_label ? $ss_attribute->attribute_label : $ss_attribute->attribute_name ); ?>
				<?php ss_the_icon( 'chevron-down', 14 ); ?>
			</button>

			<div class="ss-filter__body">
				<?php if ( $ss_is_color ) : ?>
					<div class="ss-filter__swatches">
						<?php foreach ( $ss_terms as $ss_term ) : ?>
							<a class="ss-swatch<?php echo in_array( $ss_term->slug, $ss_active, true ) ? ' is-active' : ''; ?>"
								href="<?php echo esc_url( add_query_arg( 'filter_' . $ss_attribute->attribute_name, $ss_term->slug, $ss_shop_url ) ); ?>"
								style="background-color:<?php echo esc_attr( ss_color_hex( $ss_term->name, $ss_term->term_id ) ); ?>"
								title="<?php echo esc_attr( $ss_term->name ); ?>">
								<span class="screen-reader-text"><?php echo esc_html( $ss_term->name ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php elseif ( $ss_is_size ) : ?>
					<div class="ss-filter__sizes">
						<?php foreach ( $ss_terms as $ss_term ) : ?>
							<a class="ss-size-chip<?php echo in_array( $ss_term->slug, $ss_active, true ) ? ' is-active' : ''; ?>"
								href="<?php echo esc_url( add_query_arg( 'filter_' . $ss_attribute->attribute_name, $ss_term->slug, $ss_shop_url ) ); ?>">
								<?php echo esc_html( $ss_term->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<ul class="ss-filter__list">
						<?php foreach ( array_slice( $ss_terms, 0, 12 ) as $ss_term ) : ?>
							<li>
								<a href="<?php echo esc_url( add_query_arg( 'filter_' . $ss_attribute->attribute_name, $ss_term->slug, $ss_shop_url ) ); ?>"
									<?php echo in_array( $ss_term->slug, $ss_active, true ) ? 'style="color:var(--ss-gold)"' : ''; ?>>
									<?php echo esc_html( $ss_term->name ); ?>
									<span class="count"><?php echo esc_html( $ss_term->count ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php /* ---- Rating ---- */ ?>
	<div class="ss-filter">
		<button type="button" class="ss-filter__head" aria-expanded="true">
			<?php esc_html_e( 'Customer rating', 'sreesaanvika' ); ?>
			<?php ss_the_icon( 'chevron-down', 14 ); ?>
		</button>

		<div class="ss-filter__body">
			<ul class="ss-filter__list">
				<?php for ( $ss_stars = 4; $ss_stars >= 1; $ss_stars-- ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'rating_filter', $ss_stars, $ss_shop_url ) ); ?>">
							<?php echo ss_stars( $ss_stars ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( '& up', 'sreesaanvika' ); ?></span>
						</a>
					</li>
				<?php endfor; ?>
			</ul>
		</div>
	</div>

	<a class="ss-btn ss-btn--ghost ss-btn--sm ss-btn--block" href="<?php echo esc_url( $ss_shop_url ); ?>">
		<?php esc_html_e( 'Clear all filters', 'sreesaanvika' ); ?>
	</a>
</div>
