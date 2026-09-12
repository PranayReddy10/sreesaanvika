<?php
/**
 * Customer reviews.
 *
 * Real WooCommerce reviews when they exist, otherwise a curated set so the
 * section never looks broken on a fresh install.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_quotes = array();

if ( class_exists( 'WooCommerce' ) ) {
	$ss_comments = get_comments(
		array(
			'post_type'   => 'product',
			'status'      => 'approve',
			'number'      => 3,
			'meta_key'    => 'rating', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'  => array( '4', '5' ), // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_compare' => 'IN',
		)
	);

	foreach ( $ss_comments as $ss_comment ) {
		$ss_quotes[] = array(
			'stars' => (int) get_comment_meta( $ss_comment->comment_ID, 'rating', true ),
			'text'  => wp_trim_words( $ss_comment->comment_content, 34 ),
			'name'  => $ss_comment->comment_author,
			'city'  => get_the_title( $ss_comment->comment_post_ID ),
		);
	}
}

if ( ! $ss_quotes ) {
	$ss_quotes = array(
		array(
			'stars' => 5,
			'text'  => __( 'The Kanchipuram I ordered for my sister\'s wedding arrived in four days, beautifully packed with the weaver\'s name on the tag. The zari is real — my mother checked!', 'sreesaanvika' ),
			'name'  => __( 'Lakshmi Narayanan', 'sreesaanvika' ),
			'city'  => __( 'Chennai', 'sreesaanvika' ),
		),
		array(
			'stars' => 5,
			'text'  => __( 'I have bought three sarees and a temple haaram now. The colours are exactly as photographed, which almost never happens online. Free fall and pico stitching was a lovely surprise.', 'sreesaanvika' ),
			'name'  => __( 'Ananya Deshmukh', 'sreesaanvika' ),
			'city'  => __( 'Pune', 'sreesaanvika' ),
		),
		array(
			'stars' => 5,
			'text'  => __( 'Ordered an Anarkali two sizes up by mistake — the return pickup came the next morning and the exchange shipped the same week. Genuinely good service.', 'sreesaanvika' ),
			'name'  => __( 'Fatima Sheikh', 'sreesaanvika' ),
			'city'  => __( 'Hyderabad', 'sreesaanvika' ),
		),
	);
}
?>
<section class="ss-section ss-reveal" style="background:var(--ss-bg-alt)">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'From our customers', 'sreesaanvika' ),
			__( 'Worn & <em>Loved</em>', 'sreesaanvika' ),
			__( 'Over 12,000 women across India have shopped with us.', 'sreesaanvika' )
		);
		?>

		<div class="ss-quotes">
			<?php foreach ( $ss_quotes as $ss_quote ) : ?>
				<figure class="ss-quote">
					<div class="ss-quote__stars" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: star rating */ __( '%d out of 5 stars', 'sreesaanvika' ), $ss_quote['stars'] ) ); ?>">
						<?php echo esc_html( str_repeat( '★', max( 1, (int) $ss_quote['stars'] ) ) ); ?>
					</div>

					<blockquote class="ss-quote__text" style="margin:0;padding:0;border:0;background:none">
						<?php echo esc_html( $ss_quote['text'] ); ?>
					</blockquote>

					<figcaption class="ss-quote__who">
						<span class="ss-quote__avatar" aria-hidden="true"><?php echo esc_html( ss_initials( $ss_quote['name'] ) ); ?></span>
						<span>
							<strong class="ss-quote__name"><?php echo esc_html( $ss_quote['name'] ); ?></strong>
							<span class="ss-quote__city"><?php echo esc_html( $ss_quote['city'] ); ?></span>
						</span>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
