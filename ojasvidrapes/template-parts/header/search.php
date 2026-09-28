<?php
/**
 * Full-screen search overlay.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_suggestions = array(
	__( 'Kanchipuram silk', 'ojasvidrapes' ),
	__( 'Banarasi saree', 'ojasvidrapes' ),
	__( 'Temple jewellery', 'ojasvidrapes' ),
	__( 'Anarkali suit', 'ojasvidrapes' ),
	__( 'Chikankari kurta', 'ojasvidrapes' ),
	__( 'Bridal lehenga', 'ojasvidrapes' ),
);
?>
<div class="od-search-overlay" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Search', 'ojasvidrapes' ); ?>">

	<button type="button" class="od-icon-btn od-search-close" data-close
		aria-label="<?php esc_attr_e( 'Close search', 'ojasvidrapes' ); ?>">
		<?php od_the_icon( 'close', 19 ); ?>
	</button>

	<div class="od-search-overlay__inner">
		<?php od_search_form(); ?>

		<div class="od-search-overlay__hint">
			<span><?php esc_html_e( 'Popular:', 'ojasvidrapes' ); ?></span>
			<?php foreach ( $od_suggestions as $od_term ) : ?>
				<a class="od-chip" href="<?php echo esc_url( add_query_arg( array( 's' => rawurlencode( $od_term ), 'post_type' => 'product' ), home_url( '/' ) ) ); ?>">
					<?php echo esc_html( $od_term ); ?>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="od-live-results" role="region" aria-live="polite"></div>
	</div>
</div>
