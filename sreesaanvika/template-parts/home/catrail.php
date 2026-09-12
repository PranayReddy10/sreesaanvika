<?php
/**
 * Round category rail.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$ss_terms = get_terms(
	array(
		'taxonomy'   => 'product_cat',
		'hide_empty' => false,
		'number'     => 10,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'exclude'    => array( get_option( 'default_product_cat' ) ),
	)
);

if ( ! $ss_terms || is_wp_error( $ss_terms ) ) {
	return;
}
?>
<section class="ss-section ss-section--tight ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Shop by category', 'sreesaanvika' ),
			__( 'Find Your <em>Drape</em>', 'sreesaanvika' ),
			__( 'From nine-yard Kanjivarams to everyday cottons and festive jewellery.', 'sreesaanvika' )
		);
		?>

		<div class="ss-catrail">
			<?php
			foreach ( $ss_terms as $ss_term ) :
				$ss_thumb_id = get_term_meta( $ss_term->term_id, 'thumbnail_id', true );
				$ss_img      = $ss_thumb_id ? wp_get_attachment_image_url( $ss_thumb_id, 'ss-category' ) : '';
				?>
				<a class="ss-catrail__item" href="<?php echo esc_url( get_term_link( $ss_term ) ); ?>">
					<div class="ss-catrail__ring">
						<?php if ( $ss_img ) : ?>
							<img src="<?php echo esc_url( $ss_img ); ?>" alt="" loading="lazy" width="160" height="160" />
						<?php else : ?>
							<span aria-hidden="true"><?php echo esc_html( mb_substr( $ss_term->name, 0, 1 ) ); ?></span>
						<?php endif; ?>
					</div>
					<span class="ss-catrail__name"><?php echo esc_html( $ss_term->name ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
