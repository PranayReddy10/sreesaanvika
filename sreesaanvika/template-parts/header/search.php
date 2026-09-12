<?php
/**
 * Full-screen search overlay.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_suggestions = array(
	__( 'Kanchipuram silk', 'sreesaanvika' ),
	__( 'Banarasi saree', 'sreesaanvika' ),
	__( 'Temple jewellery', 'sreesaanvika' ),
	__( 'Anarkali suit', 'sreesaanvika' ),
	__( 'Chikankari kurta', 'sreesaanvika' ),
	__( 'Bridal lehenga', 'sreesaanvika' ),
);
?>
<div class="ss-search-overlay" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Search', 'sreesaanvika' ); ?>">

	<button type="button" class="ss-icon-btn ss-search-close" data-close
		aria-label="<?php esc_attr_e( 'Close search', 'sreesaanvika' ); ?>">
		<?php ss_the_icon( 'close', 19 ); ?>
	</button>

	<div class="ss-search-overlay__inner">
		<?php ss_search_form(); ?>

		<div class="ss-search-overlay__hint">
			<span><?php esc_html_e( 'Popular:', 'sreesaanvika' ); ?></span>
			<?php foreach ( $ss_suggestions as $ss_term ) : ?>
				<a class="ss-chip" href="<?php echo esc_url( add_query_arg( array( 's' => rawurlencode( $ss_term ), 'post_type' => 'product' ), home_url( '/' ) ) ); ?>">
					<?php echo esc_html( $ss_term ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="ss-live-results" role="region" aria-live="polite"></div>
	</div>
</div>
