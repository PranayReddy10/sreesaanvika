<?php
/**
 * Lookbook strip — pulls the newest product gallery images.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_shots = array();

if ( class_exists( 'WooCommerce' ) ) {
	$od_query = new WP_Query(
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

	foreach ( $od_query->posts as $od_post ) {
		$od_shots[] = array(
			'img'   => get_the_post_thumbnail_url( $od_post, 'od-product-lg' ),
			'url'   => get_permalink( $od_post ),
			'label' => get_the_title( $od_post ),
		);
	}

	wp_reset_postdata();
}

if ( count( $od_shots ) < 3 ) {
	return;
}
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'Styled by us', 'ojasvidrapes' ),
			__( 'The <em>Lookbook</em>', 'ojasvidrapes' ),
			__( 'How our team drapes the season — shot on real women, no retouching.', 'ojasvidrapes' )
		);
		?>

		<div class="od-look">
			<?php foreach ( $od_shots as $od_shot ) : ?>
				<a class="od-look__cell" href="<?php echo esc_url( $od_shot['url'] ); ?>">
					<img src="<?php echo esc_url( $od_shot['img'] ); ?>" alt="<?php echo esc_attr( $od_shot['label'] ); ?>" loading="lazy" />
					<span class="od-look__tag"><?php echo esc_html( wp_trim_words( $od_shot['label'], 4, '' ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="od-text-center" style="margin-top:34px">
			<a class="od-btn od-btn--ghost" href="<?php echo esc_url( od_page_url( 'lookbook' ) ); ?>">
				<?php esc_html_e( 'See the full lookbook', 'ojasvidrapes' ); ?>
			</a>
		</div>
	</div>
</section>
