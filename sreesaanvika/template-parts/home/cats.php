<?php
/**
 * Category mosaic.
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
		'number'     => 6,
		'parent'     => 0,
		'orderby'    => 'count',
		'order'      => 'DESC',
		'exclude'    => array( get_option( 'default_product_cat' ) ),
	)
);

if ( ! $ss_terms || is_wp_error( $ss_terms ) ) {
	return;
}

/**
 * Tile patterns chosen so every row of the 6-column grid fills exactly,
 * whatever number of categories came back.
 */
$ss_patterns = array(
	1 => array( 'w6 ss-cat--h2' ),
	2 => array( 'w3', 'w3' ),
	3 => array( 'w4 ss-cat--h2', 'w2', 'w2' ),
	4 => array( 'w3', 'w3', 'w3', 'w3' ),
	5 => array( 'w4 ss-cat--h2', 'w2', 'w2', 'w3', 'w3' ),
	6 => array( 'w4 ss-cat--h2', 'w2', 'w2', 'w2', 'w2', 'w2' ),
);

$ss_count = count( $ss_terms );
$ss_sizes = isset( $ss_patterns[ $ss_count ] ) ? $ss_patterns[ $ss_count ] : $ss_patterns[6];
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'The collections', 'sreesaanvika' ),
			__( 'Curated for <em>Every Celebration</em>', 'sreesaanvika' ),
			__( 'Weddings, festivals, workdays and the quiet evenings in between.', 'sreesaanvika' )
		);
		?>

		<div class="ss-cats">
			<?php
			foreach ( $ss_terms as $ss_n => $ss_term ) :
				$ss_thumb_id = get_term_meta( $ss_term->term_id, 'thumbnail_id', true );
				$ss_img      = $ss_thumb_id ? wp_get_attachment_image_url( $ss_thumb_id, 'ss-hero' ) : '';
				$ss_size     = isset( $ss_sizes[ $ss_n ] ) ? $ss_sizes[ $ss_n ] : 'w2';
				?>
				<article class="ss-cat ss-cat--<?php echo esc_attr( $ss_size ); ?>">
					<div class="ss-cat__img"<?php echo $ss_img ? ss_bg_style( $ss_img ) : ''; ?>></div>

					<div class="ss-cat__body">
						<span class="ss-cat__count">
							<?php
							printf(
								/* translators: %s: number of products */
								esc_html( _n( '%s piece', '%s pieces', $ss_term->count, 'sreesaanvika' ) ),
								esc_html( number_format_i18n( $ss_term->count ) )
							);
							?>
						</span>
						<h3 class="ss-cat__title"><?php echo esc_html( $ss_term->name ); ?></h3>
						<span class="ss-cat__link">
							<?php esc_html_e( 'Explore', 'sreesaanvika' ); ?>
							<?php ss_the_icon( 'arrow-right', 15 ); ?>
						</span>
					</div>

					<a class="ss-cat__stretch" href="<?php echo esc_url( get_term_link( $ss_term ) ); ?>">
						<span class="screen-reader-text"><?php echo esc_html( $ss_term->name ); ?></span>
					</a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
