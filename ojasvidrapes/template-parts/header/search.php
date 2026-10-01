<?php
/**
 * Full-screen search overlay.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

// One per line in the Customizer. A shop that sells one kind of thing should
// not be suggesting the other kinds it has never stocked.
$od_suggestions = array_filter( array_map( 'trim', preg_split( '/[\r\n]+/', (string) od_option( 'search_terms' ) ) ) );
?>
<div class="od-search-overlay" aria-hidden="true" role="dialog" aria-modal="true"
	aria-label="<?php esc_attr_e( 'Search', 'ojasvidrapes' ); ?>">

	<button type="button" class="od-icon-btn od-search-close" data-close
		aria-label="<?php esc_attr_e( 'Close search', 'ojasvidrapes' ); ?>">
		<?php od_the_icon( 'close', 19 ); ?>
	</button>

	<div class="od-search-overlay__inner">
		<?php od_search_form(); ?>

		<div class="od-search-overlay__hint"<?php echo $od_suggestions ? '' : ' hidden'; ?>>
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
