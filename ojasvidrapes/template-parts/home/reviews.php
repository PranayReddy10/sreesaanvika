<?php
/**
 * Customer reviews.
 *
 * Real WooCommerce reviews when they exist, otherwise a curated set so the
 * section never looks broken on a fresh install.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_quotes = array();

if ( class_exists( 'WooCommerce' ) ) {
	$od_comments = get_comments(
		array(
			'post_type'   => 'product',
			'status'      => 'approve',
			'number'      => 3,
			'meta_key'    => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => array( '4', '5' ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare' => 'IN',
		)
	);

	foreach ( $od_comments as $od_comment ) {
		$od_quotes[] = array(
			'stars' => (int) get_comment_meta( $od_comment->comment_ID, 'rating', true ),
			'text'  => wp_trim_words( $od_comment->comment_content, 34 ),
			'name'  => $od_comment->comment_author,
			'city'  => get_the_title( $od_comment->comment_post_ID ),
		);
	}
}

if ( ! $od_quotes ) {
	$od_quotes = array(
		array(
			'stars' => 5,
			'text'  => __( 'The Kanchipuram I ordered for my sister\'s wedding arrived in four days, beautifully packed with the weaver\'s name on the tag. The zari is real — my mother checked!', 'ojasvidrapes' ),
			'name'  => __( 'Lakshmi Narayanan', 'ojasvidrapes' ),
			'city'  => __( 'Chennai', 'ojasvidrapes' ),
		),
		array(
			'stars' => 5,
			'text'  => __( 'I have bought three sarees and a temple haaram now. The colours are exactly as photographed, which almost never happens online. Free fall and pico stitching was a lovely surprise.', 'ojasvidrapes' ),
			'name'  => __( 'Ananya Deshmukh', 'ojasvidrapes' ),
			'city'  => __( 'Pune', 'ojasvidrapes' ),
		),
		array(
			'stars' => 5,
			'text'  => __( 'Ordered the wrong shade by mistake — the return pickup came the next morning and the exchange shipped the same week. Genuinely good service.', 'ojasvidrapes' ),
			'name'  => __( 'Fatima Sheikh', 'ojasvidrapes' ),
			'city'  => __( 'Hyderabad', 'ojasvidrapes' ),
		),
	);
}
?>
<section class="od-section od-reveal" style="background:var(--od-bg-alt)">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'From our customers', 'ojasvidrapes' ),
			__( 'Worn & <em>Loved</em>', 'ojasvidrapes' ),
			__( 'Over 12,000 women across India have shopped with us.', 'ojasvidrapes' )
		);
		?>

		<div class="od-quotes">
			<?php foreach ( $od_quotes as $od_quote ) : ?>
				<figure class="od-quote">
					<div class="od-quote__stars" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating */ __( '%d out of 5 stars', 'ojasvidrapes' ), $od_quote['stars'] ) ); ?>">
						<?php echo esc_html( str_repeat( '★', max( 1, (int) $od_quote['stars'] ) ) ); ?>
					</div>

					<blockquote class="od-quote__text" style="margin:0;padding:0;border:0;background:none">
						<?php echo esc_html( $od_quote['text'] ); ?>
					</blockquote>

					<figcaption class="od-quote__who">
						<span class="od-quote__avatar" aria-hidden="true"><?php echo esc_html( od_initials( $od_quote['name'] ) ); ?></span>
						<span>
							<strong class="od-quote__name"><?php echo esc_html( $od_quote['name'] ); ?></strong>
							<span class="od-quote__city"><?php echo esc_html( $od_quote['city'] ); ?></span>
						</span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
