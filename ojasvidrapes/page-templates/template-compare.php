<?php
/**
 * Template Name: Compare Products
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Put your shortlist side by side — fabric, price, work and availability in one view.', 'ojasvidrapes' ) );

$od_products = function_exists( 'wc_get_product' ) ? od_get_list_products( 'compare' ) : array();
$od_max      = absint( od_option( 'compare_max', 4 ) );
?>

<div class="od-container od-section" data-list-page="compare">

	<?php if ( ! $od_products ) : ?>
		<?php
		od_empty_state(
			'compare',
			__( 'Nothing to compare yet', 'ojasvidrapes' ),
			sprintf(
				/* translators: %d: maximum number of products */
				__( 'Tap the compare icon on any product card to line up to %d pieces here.', 'ojasvidrapes' ),
				$od_max
			),
			class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			__( 'Browse the shop', 'ojasvidrapes' )
		);
		?>
	<?php else : ?>

		<div class="od-compare-wrap">
			<table class="od-compare">
				<thead>
					<tr>
						<th scope="col">
							<span class="screen-reader-text"><?php esc_html_e( 'Attribute', 'ojasvidrapes' ); ?></span>
							<p style="color:var(--od-muted);font-size:.86rem;margin:0">
								<?php
								printf(
									/* translators: 1: number selected, 2: maximum */
									esc_html__( '%1$d of %2$d slots used', 'ojasvidrapes' ),
									count( $od_products ),
									absint( $od_max )
								);
								?>
							</p>
						</th>

						<?php foreach ( $od_products as $od_product ) : ?>
							<th scope="col">
								<button type="button" class="od-compare__remove" data-id="<?php echo esc_attr( $od_product->get_id() ); ?>"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Remove %s from compare', 'ojasvidrapes' ), $od_product->get_name() ) ); ?>">
									&times;
								</button>

								<div class="od-compare__product">
									<a class="od-compare__thumb" href="<?php echo esc_url( $od_product->get_permalink() ); ?>">
										<?php echo wp_kses_post( $od_product->get_image( 'od-product' ) ); ?>
									</a>

									<h3 class="od-compare__name">
										<a href="<?php echo esc_url( $od_product->get_permalink() ); ?>"><?php echo esc_html( $od_product->get_name() ); ?></a>
									</h3>

									<?php if ( $od_product->is_purchasable() && $od_product->is_in_stock() ) : ?>
										<?php if ( $od_product->is_type( 'simple' ) ) : ?>
											<button type="button" class="od-btn od-btn--sm od-ajax-add" data-id="<?php echo esc_attr( $od_product->get_id() ); ?>">
												<?php esc_html_e( 'Add to bag', 'ojasvidrapes' ); ?>
											</button>
										<?php else : ?>
											<a class="od-btn od-btn--sm" href="<?php echo esc_url( $od_product->get_permalink() ); ?>">
												<?php esc_html_e( 'Select options', 'ojasvidrapes' ); ?>
											</a>
										<?php endif; ?>
									<?php else : ?>
										<span class="od-badge od-badge--soldout"><?php esc_html_e( 'Sold out', 'ojasvidrapes' ); ?></span>
									<?php endif; ?>
								</div>
							</th>
						<?php endforeach; ?>

						<?php if ( count( $od_products ) < $od_max ) : ?>
							<th scope="col">
								<div class="od-compare__add">
									<?php od_the_icon( 'plus', 34 ); ?>
									<p style="margin:0"><?php esc_html_e( 'Add another product', 'ojasvidrapes' ); ?></p>
									<a class="od-btn od-btn--ghost od-btn--sm" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
										<?php esc_html_e( 'Browse', 'ojasvidrapes' ); ?>
									</a>
								</div>
							</th>
						<?php endif; ?>
					</tr>
				</thead>

				<tbody>
					<?php foreach ( od_compare_rows() as $od_key => $od_label ) : ?>
						<?php
						// Skip a row when no product in the set has a value for it.
						$od_values = array();

						foreach ( $od_products as $od_product ) {
							$od_values[] = od_compare_value( $od_product, $od_key );
						}

						$od_has_data = false;

						foreach ( $od_values as $od_value ) {
							if ( false === strpos( $od_value, 'od-no">&mdash;' ) ) {
								$od_has_data = true;
								break;
							}
						}

						if ( ! $od_has_data ) {
							continue;
						}
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $od_label ); ?></th>

							<?php foreach ( $od_values as $od_value ) : ?>
								<td><?php echo $od_value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<?php endforeach; ?>

							<?php if ( count( $od_products ) < $od_max ) : ?>
								<td></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
			<a class="od-btn od-btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Keep shopping', 'ojasvidrapes' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) {
			echo '<div class="od-entry" style="margin-top:34px">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
get_footer();
