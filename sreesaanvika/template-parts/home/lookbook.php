<?php
/**
 * Lookbook strip — pulls the newest product gallery images.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_shots = array();

if ( class_exists( 'WooCommerce' ) ) {
	$ss_query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'orderby'        => 'rand',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'EXISTS',
				),
			),
			'no_found_rows'  => true,
		)
	);

	foreach ( $ss_query->posts as $ss_post ) {
		$ss_shots[] = array(
			'img'   => get_the_post_thumbnail_url( $ss_post, 'ss-product-lg' ),
			'url'   => get_permalink( $ss_post ),
			'label' => get_the_title( $ss_post ),
		);
	}

	wp_reset_postdata();
}

if ( count( $ss_shots ) < 3 ) {
	return;
}
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Styled by us', 'sreesaanvika' ),
			__( 'The <em>Lookbook</em>', 'sreesaanvika' ),
			__( 'How our team drapes the season — shot on real women, no retouching.', 'sreesaanvika' )
		);
		?>

		<div class="ss-look">
			<?php foreach ( $ss_shots as $ss_shot ) : ?>
				<a class="ss-look__cell" href="<?php echo esc_url( $ss_shot['url'] ); ?>">
					<img src="<?php echo esc_url( $ss_shot['img'] ); ?>" alt="<?php echo esc_attr( $ss_shot['label'] ); ?>" loading="lazy" />
					<span class="ss-look__tag"><?php echo esc_html( wp_trim_words( $ss_shot['label'], 4, '' ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="ss-text-center" style="margin-top:34px">
			<a class="ss-btn ss-btn--ghost" href="<?php echo esc_url( ss_page_url( 'lookbook' ) ); ?>">
				<?php esc_html_e( 'See the full lookbook', 'sreesaanvika' ); ?>
			</a>
		</div>
	</div>
</section>
