<?php
/**
 * Template Name: Compare Products
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Put your shortlist side by side — fabric, price, work and availability in one view.', 'sreesaanvika' ) );

$ss_products = function_exists( 'wc_get_product' ) ? ss_get_list_products( 'compare' ) : array();
$ss_max      = absint( ss_option( 'compare_max', 4 ) );
?>

<div class="ss-container ss-section" data-list-page="compare">

	<?php if ( ! $ss_products ) : ?>
		<?php
		ss_empty_state(
			'compare',
			__( 'Nothing to compare yet', 'sreesaanvika' ),
			sprintf(
				/* translators: %d: maximum number of products */
				__( 'Tap the compare icon on any product card to line up to %d pieces here.', 'sreesaanvika' ),
				$ss_max
			),
			class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			__( 'Browse the shop', 'sreesaanvika' )
		);
		?>
	<?php else : ?>

		<div class="ss-compare-wrap">
			<table class="ss-compare">
				<thead>
					<tr>
						<th scope="col">
							<span class="screen-reader-text"><?php esc_html_e( 'Attribute', 'sreesaanvika' ); ?></span>
							<p style="color:var(--ss-muted);font-size:.86rem;margin:0">
								<?php
								printf(
									/* translators: 1: number selected, 2: maximum */
									esc_html__( '%1$d of %2$d slots used', 'sreesaanvika' ),
									count( $ss_products ),
									absint( $ss_max )
								);
								?>
							</p>
						</th>

						<?php foreach ( $ss_products as $ss_product ) : ?>
							<th scope="col">
								<button type="button" class="ss-compare__remove" data-id="<?php echo esc_attr( $ss_product->get_id() ); ?>"
									aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Remove %s from compare', 'sreesaanvika' ), $ss_product->get_name() ) ); ?>">
									&times;
								</button>

								<div class="ss-compare__product">
									<a class="ss-compare__thumb" href="<?php echo esc_url( $ss_product->get_permalink() ); ?>">
										<?php echo wp_kses_post( $ss_product->get_image( 'ss-product' ) ); ?>
									</a>

									<h3 class="ss-compare__name">
										<a href="<?php echo esc_url( $ss_product->get_permalink() ); ?>"><?php echo esc_html( $ss_product->get_name() ); ?></a>
									</h3>

									<?php if ( $ss_product->is_purchasable() && $ss_product->is_in_stock() ) : ?>
										<?php if ( $ss_product->is_type( 'simple' ) ) : ?>
											<button type="button" class="ss-btn ss-btn--sm ss-ajax-add" data-id="<?php echo esc_attr( $ss_product->get_id() ); ?>">
												<?php esc_html_e( 'Add to bag', 'sreesaanvika' ); ?>
											</button>
										<?php else : ?>
											<a class="ss-btn ss-btn--sm" href="<?php echo esc_url( $ss_product->get_permalink() ); ?>">
												<?php esc_html_e( 'Select options', 'sreesaanvika' ); ?>
											</a>
										<?php endif; ?>
									<?php else : ?>
										<span class="ss-badge ss-badge--soldout"><?php esc_html_e( 'Sold out', 'sreesaanvika' ); ?></span>
									<?php endif; ?>
								</div>
							</th>
						<?php endforeach; ?>

						<?php if ( count( $ss_products ) < $ss_max ) : ?>
							<th scope="col">
								<div class="ss-compare__add">
									<?php ss_the_icon( 'plus', 34 ); ?>
									<p style="margin:0"><?php esc_html_e( 'Add another product', 'sreesaanvika' ); ?></p>
									<a class="ss-btn ss-btn--ghost ss-btn--sm" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
										<?php esc_html_e( 'Browse', 'sreesaanvika' ); ?>
									</a>
								</div>
							</th>
						<?php endif; ?>
					</tr>
				</thead>

				<tbody>
					<?php foreach ( ss_compare_rows() as $ss_key => $ss_label ) : ?>
						<?php
						// Skip a row when no product in the set has a value for it.
						$ss_values = array();

						foreach ( $ss_products as $ss_product ) {
							$ss_values[] = ss_compare_value( $ss_product, $ss_key );
						}

						$ss_has_data = false;

						foreach ( $ss_values as $ss_value ) {
							if ( false === strpos( $ss_value, 'ss-no">&mdash;' ) ) {
								$ss_has_data = true;
								break;
							}
						}

						if ( ! $ss_has_data ) {
							continue;
						}
						?>
						<tr>
							<th scope="row"><?php echo esc_html( $ss_label ); ?></th>

							<?php foreach ( $ss_values as $ss_value ) : ?>
								<td><?php echo $ss_value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<?php endforeach; ?>

							<?php if ( count( $ss_products ) < $ss_max ) : ?>
								<td></td>
							<?php endif; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div style="margin-top:26px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
			<a class="ss-btn ss-btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<?php esc_html_e( 'Keep shopping', 'sreesaanvika' ); ?>
			</a>
		</div>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) {
			echo '<div class="ss-entry" style="margin-top:34px">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
get_footer();
