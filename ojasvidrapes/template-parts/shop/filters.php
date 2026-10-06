<?php
/**
 * Shop filter sidebar.
 *
 * Uses registered widgets when the shop sidebar has any, and otherwise
 * renders a built-in set of filters so a fresh install is not empty.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( is_active_sidebar( 'sidebar-shop' ) ) {
	echo '<div class="od-filters">';
	dynamic_sidebar( 'sidebar-shop' );
	echo '</div>';
	return;
}

$od_shop_url = wc_get_page_permalink( 'shop' );

/**
 * Current query values, so the form round-trips.
 */
$od_min = isset( $_GET['min_price'] ) ? absint( $_GET['min_price'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$od_max = isset( $_GET['max_price'] ) ? absint( $_GET['max_price'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

// A sensible ceiling drawn from the most expensive published product.
$od_ceiling = (int) get_transient( 'od_price_ceiling' );

if ( ! $od_ceiling ) {
	$od_top = wc_get_products(
		array(
			'limit'   => 1,
			'orderby' => 'meta_value_num',
			'meta_key' => '_price', // phpcs:ignore WordPress.DB.SlowDBQuery
			'order'   => 'DESC',
			'status'  => 'publish',
		)
	);

	$od_ceiling = ( $od_top && $od_top[0]->get_price() ) ? (int) ceil( (float) $od_top[0]->get_price() / 500 ) * 500 : 25000;
	$od_ceiling = max( 1000, $od_ceiling );

	set_transient( 'od_price_ceiling', $od_ceiling, HOUR_IN_SECONDS * 6 );
}

$od_min = $od_min ? $od_min : 0;
$od_max = $od_max ? $od_max : $od_ceiling;
?>
<div class="od-filters">

	<?php
	/* ---- Browse (categories, when that is how this shop sorts itself) ---- */
	$od_browse_tax = od_browse_taxonomy();

	$od_cats = $od_browse_tax ? get_terms(
		array(
			'taxonomy'   => $od_browse_tax,
			'hide_empty' => true,
			'parent'     => 0,
		)
	) : array();

	if ( $od_cats && ! is_wp_error( $od_cats ) ) :
		$od_current = is_tax( $od_browse_tax ) ? get_queried_object_id() : 0;
		?>
		<div class="od-filter">
			<button type="button" class="od-filter__head" aria-expanded="true">
				<?php echo esc_html( od_browse_label() ); ?>
				<?php od_the_icon( 'chevron-down', 14 ); ?>
			</button>

			<div class="od-filter__body">
				<ul class="od-filter__list">
					<?php
					foreach ( $od_cats as $od_cat ) :
						$od_cat_link = get_term_link( $od_cat );

						if ( is_wp_error( $od_cat_link ) ) {
							continue;
						}
						?>
						<li>
							<a href="<?php echo esc_url( $od_cat_link ); ?>"
								<?php echo $od_current === $od_cat->term_id ? 'style="color:var(--od-gold)"' : ''; ?>>
								<?php echo esc_html( $od_cat->name ); ?>
								<span class="count"><?php echo esc_html( $od_cat->count ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endif; ?>

	<?php
	/*
	 * ---- Attributes: pattern, colour, fabric, whatever the shop keeps ----
	 *
	 * A shop with one kind of stock sorts itself by these rather than by
	 * category, so each attribute that has terms becomes its own filter.
	 * The links use WooCommerce's own filter_pa_* query arguments, which
	 * WC_Query applies to the shop loop without any widget being active, and
	 * several values are comma-separated so they stack.
	 */
	foreach ( od_filter_attributes() as $od_attr ) :
		?>
		<div class="od-filter">
			<button type="button" class="od-filter__head" aria-expanded="true">
				<?php echo esc_html( $od_attr['label'] ); ?>
				<?php od_the_icon( 'chevron-down', 14 ); ?>
			</button>

			<div class="od-filter__body">
				<ul class="od-filter__list od-filter__list--check">
					<?php foreach ( $od_attr['terms'] as $od_term ) : ?>
						<li>
							<a class="od-filter__opt<?php echo $od_term['on'] ? ' is-on' : ''; ?>"
								href="<?php echo esc_url( $od_term['url'] ); ?>"
								<?php echo $od_term['on'] ? 'aria-current="true"' : ''; ?>>
								<span class="od-filter__box" aria-hidden="true"></span>
								<?php echo esc_html( $od_term['name'] ); ?>
								<span class="count"><?php echo esc_html( $od_term['count'] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	<?php endforeach; ?>

	<?php /* ---- Price ---- */ ?>
	<div class="od-filter">
		<button type="button" class="od-filter__head" aria-expanded="true">
			<?php esc_html_e( 'Price', 'ojasvidrapes' ); ?>
			<?php od_the_icon( 'chevron-down', 14 ); ?>
		</button>

		<div class="od-filter__body">
			<form method="get" action="<?php echo esc_url( $od_shop_url ); ?>">
				<div class="od-price-slider" data-price-slider>
					<div class="od-price-slider__track">
						<div class="od-price-slider__range"></div>
						<input type="range" class="od-price-min" min="0" max="<?php echo esc_attr( $od_ceiling ); ?>"
							step="100" value="<?php echo esc_attr( $od_min ); ?>"
							aria-label="<?php esc_attr_e( 'Minimum price', 'ojasvidrapes' ); ?>" />
						<input type="range" class="od-price-max" min="0" max="<?php echo esc_attr( $od_ceiling ); ?>"
							step="100" value="<?php echo esc_attr( $od_max ); ?>"
							aria-label="<?php esc_attr_e( 'Maximum price', 'ojasvidrapes' ); ?>" />
					</div>

					<div class="od-price-slider__values">
						<strong data-out-min></strong>
						<strong data-out-max></strong>
					</div>
				</div>

				<input type="hidden" name="min_price" value="<?php echo esc_attr( $od_min ); ?>" />
				<input type="hidden" name="max_price" value="<?php echo esc_attr( $od_max ); ?>" />

				<button type="submit" class="od-btn od-btn--sm od-btn--block" style="margin-top:14px">
					<?php esc_html_e( 'Apply', 'ojasvidrapes' ); ?>
				</button>
			</form>
		</div>
	</div>

	<?php
	/* ---- Attribute filters (colour, size, fabric, occasion) ---- */
	$od_attribute_taxonomies = function_exists( 'wc_get_attribute_taxonomies' ) ? wc_get_attribute_taxonomies() : array();

	foreach ( $od_attribute_taxonomies as $od_attribute ) :
		$od_tax   = wc_attribute_taxonomy_name( $od_attribute->attribute_name );
		$od_terms = get_terms( array( 'taxonomy' => $od_tax, 'hide_empty' => true ) );

		if ( ! $od_terms || is_wp_error( $od_terms ) ) {
			continue;
		}

		$od_is_color = (bool) preg_match( '/color|colour|shade/i', $od_attribute->attribute_name );
		$od_is_size  = (bool) preg_match( '/size/i', $od_attribute->attribute_name );
		$od_active   = isset( $_GET[ 'filter_' . $od_attribute->attribute_name ] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? array_map( 'sanitize_title', explode( ',', sanitize_text_field( wp_unslash( $_GET[ 'filter_' . $od_attribute->attribute_name ] ) ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: array();
		?>
		<div class="od-filter">
			<button type="button" class="od-filter__head" aria-expanded="true">
				<?php echo esc_html( $od_attribute->attribute_label ? $od_attribute->attribute_label : $od_attribute->attribute_name ); ?>
				<?php od_the_icon( 'chevron-down', 14 ); ?>
			</button>

			<div class="od-filter__body">
				<?php if ( $od_is_color ) : ?>
					<div class="od-filter__swatches">
						<?php foreach ( $od_terms as $od_term ) : ?>
							<a class="od-swatch<?php echo in_array( $od_term->slug, $od_active, true ) ? ' is-active' : ''; ?>"
								href="<?php echo esc_url( add_query_arg( 'filter_' . $od_attribute->attribute_name, $od_term->slug, $od_shop_url ) ); ?>"
								style="background-color:<?php echo esc_attr( od_color_hex( $od_term->name, $od_term->term_id ) ); ?>"
								title="<?php echo esc_attr( $od_term->name ); ?>">
								<span class="screen-reader-text"><?php echo esc_html( $od_term->name ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				<?php elseif ( $od_is_size ) : ?>
					<div class="od-filter__sizes">
						<?php foreach ( $od_terms as $od_term ) : ?>
							<a class="od-size-chip<?php echo in_array( $od_term->slug, $od_active, true ) ? ' is-active' : ''; ?>"
								href="<?php echo esc_url( add_query_arg( 'filter_' . $od_attribute->attribute_name, $od_term->slug, $od_shop_url ) ); ?>">
								<?php echo esc_html( $od_term->name ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<ul class="od-filter__list">
						<?php foreach ( array_slice( $od_terms, 0, 12 ) as $od_term ) : ?>
							<li>
								<a href="<?php echo esc_url( add_query_arg( 'filter_' . $od_attribute->attribute_name, $od_term->slug, $od_shop_url ) ); ?>"
									<?php echo in_array( $od_term->slug, $od_active, true ) ? 'style="color:var(--od-gold)"' : ''; ?>>
									<?php echo esc_html( $od_term->name ); ?>
									<span class="count"><?php echo esc_html( $od_term->count ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		</div>
	<?php endforeach; ?>

	<?php /* ---- Rating ---- */ ?>
	<div class="od-filter">
		<button type="button" class="od-filter__head" aria-expanded="true">
			<?php esc_html_e( 'Customer rating', 'ojasvidrapes' ); ?>
			<?php od_the_icon( 'chevron-down', 14 ); ?>
		</button>

		<div class="od-filter__body">
			<ul class="od-filter__list">
				<?php for ( $od_stars = 4; $od_stars >= 1; $od_stars-- ) : ?>
					<li>
						<a href="<?php echo esc_url( add_query_arg( 'rating_filter', $od_stars, $od_shop_url ) ); ?>">
							<?php echo od_stars( $od_stars ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span><?php esc_html_e( '& up', 'ojasvidrapes' ); ?></span>
						</a>
					</li>
				<?php endfor; ?>
			</ul>
		</div>
	</div>

	<a class="od-btn od-btn--ghost od-btn--sm od-btn--block" href="<?php echo esc_url( $od_shop_url ); ?>">
		<?php esc_html_e( 'Clear all filters', 'ojasvidrapes' ); ?>
	</a>
</div>
