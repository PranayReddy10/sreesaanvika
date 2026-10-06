<?php
/**
 * Featured drapes.
 *
 * A handful of pieces given room, above the grid that holds the rest. A saree
 * is nine yards of one continuous design, and a card cropped to a thumbnail
 * throws most of that away — so each one here gets a tall portrait frame, its
 * design code, its weave note and its price, and the rows alternate side so
 * the page reads like a lookbook rather than a list.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WooCommerce' ) ) {
	return;
}

$od_picks = od_picked_products( od_option( 'featured_products' ), absint( od_option( 'featured_count' ) ) );

if ( ! $od_picks ) {
	return;
}
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			od_option( 'featured_eyebrow' ),
			od_option( 'featured_title' ),
			od_option( 'featured_text' )
		);
		?>

		<div class="od-features">
			<?php
			foreach ( $od_picks as $od_n => $od_product ) :
				$od_img   = od_product_image_url( $od_product, 'od-hero' );
				$od_note  = wp_strip_all_tags( $od_product->get_short_description() );
				$od_sku   = $od_product->get_sku();
				$od_link  = $od_product->get_permalink();
				$od_alt   = 1 === $od_n % 2 ? ' od-feature--flip' : '';
				?>
				<article class="od-feature<?php echo esc_attr( $od_alt ); ?>">

					<a class="od-feature__media" href="<?php echo esc_url( $od_link ); ?>" tabindex="-1" aria-hidden="true">
						<?php if ( $od_img ) : ?>
							<img src="<?php echo esc_url( $od_img ); ?>" alt="" loading="lazy" decoding="async" />
						<?php endif; ?>
					</a>

					<div class="od-feature__body">
						<span class="od-feature__index" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $od_n + 1 ) ); ?></span>

						<?php if ( $od_sku ) : ?>
							<span class="od-feature__sku">
								<?php
								/* translators: %s: the shop's own design code */
								printf( esc_html__( 'Design %s', 'ojasvidrapes' ), esc_html( $od_sku ) );
								?>
							</span>
						<?php endif; ?>

						<h3 class="od-feature__title">
							<a href="<?php echo esc_url( $od_link ); ?>"><?php echo esc_html( $od_product->get_name() ); ?></a>
						</h3>

						<?php if ( $od_note ) : ?>
							<p class="od-feature__note"><?php echo esc_html( wp_trim_words( $od_note, 28 ) ); ?></p>
						<?php endif; ?>

						<div class="od-feature__price"><?php echo wp_kses_post( $od_product->get_price_html() ); ?></div>

						<a class="od-btn od-btn--outline" href="<?php echo esc_url( $od_link ); ?>">
							<?php esc_html_e( 'See this drape', 'ojasvidrapes' ); ?>
							<?php od_the_icon( 'arrow-right', 15 ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
