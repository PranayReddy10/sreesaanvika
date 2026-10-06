<?php
/**
 * Round browse rail — categories, patterns, colours, whatever the shop sorts
 * itself by. It takes itself off the page entirely when it sorts by nothing.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$od_terms = od_browse_terms(
	od_option( 'catrail_slugs' ),
	absint( od_option( 'catrail_count' ) ),
	(bool) od_option( 'catrail_top_level' )
);

if ( ! $od_terms ) {
	return;
}
?>
<section class="od-section od-section--tight od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			/* translators: %s: what the shop browses by, e.g. "pattern" */
			sprintf( __( 'Shop by %s', 'ojasvidrapes' ), od_mb_lower( od_browse_label() ) ),
			__( 'Find Your <em>Drape</em>', 'ojasvidrapes' ),
			od_option( 'catrail_text', '' )
		);
		?>

		<div class="od-catrail">
			<?php
			foreach ( $od_terms as $od_term ) :
				$od_thumb_id = get_term_meta( $od_term->term_id, 'thumbnail_id', true );
				$od_img      = $od_thumb_id ? wp_get_attachment_image_url( $od_thumb_id, 'od-category' ) : '';
				?>
				<a class="od-catrail__item" href="<?php echo esc_url( get_term_link( $od_term ) ); ?>">
					<div class="od-catrail__ring">
						<?php if ( $od_img ) : ?>
							<img src="<?php echo esc_url( $od_img ); ?>" alt="" loading="lazy" width="160" height="160" />
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html( mb_substr( $od_term->name, 0, 1 ) ); ?></span>
						<?php endif; ?>
					</div>
					<span class="od-catrail__name"><?php echo esc_html( $od_term->name ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
