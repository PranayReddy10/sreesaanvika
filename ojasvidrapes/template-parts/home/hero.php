<?php
/**
 * Homepage hero slider.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_slides = array();

for ( $od_i = 1; $od_i <= 3; $od_i++ ) {
	$od_title = od_option( "hero{$od_i}_title", '' );
	$od_img   = od_option( "hero{$od_i}_img", '' );

	/*
	 * A picture on its own is a slide. Requiring a headline used to mean that
	 * setting only the background image did nothing at all, with no hint as to
	 * why, so the slide is kept whenever either one is filled in.
	 */
	if ( ! $od_title && ! $od_img ) {
		continue;
	}

	$od_url = od_option( "hero{$od_i}_url", '' );

	if ( ! $od_url && class_exists( 'WooCommerce' ) ) {
		$od_url = wc_get_page_permalink( 'shop' );
	}

	$od_slides[] = array(
		'eyebrow' => od_option( "hero{$od_i}_eyebrow", '' ),
		'title'   => $od_title,
		'text'    => od_option( "hero{$od_i}_text", '' ),
		'btn'     => od_option( "hero{$od_i}_btn", __( 'Shop now', 'ojasvidrapes' ) ),
		'url'     => $od_url ? $od_url : home_url( '/' ),
		'img'     => $od_img,
		'align'   => od_option( "hero{$od_i}_align", 'left' ),
	);
}

if ( ! $od_slides ) {
	return;
}
?>
<section class="od-hero" data-hero data-autoplay="<?php echo od_option( 'hero_autoplay', true ) ? '1' : '0'; ?>"
	data-speed="<?php echo esc_attr( absint( od_option( 'hero_speed', 6 ) ) ); ?>"
	aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured collections', 'ojasvidrapes' ); ?>">

	<div class="od-hero__viewport">
		<?php foreach ( $od_slides as $od_n => $od_slide ) : ?>
			<div class="od-hero__slide od-hero__slide--<?php echo esc_attr( $od_slide['align'] ); ?><?php echo 0 === $od_n ? ' is-active' : ''; ?>"
				role="group" aria-roledescription="slide"
				aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: total slides */ __( 'Slide %1$d of %2$d', 'ojasvidrapes' ), $od_n + 1, count( $od_slides ) ) ); ?>">

				<div class="od-hero__bg"<?php echo $od_slide['img'] ? od_bg_style( $od_slide['img'] ) : ''; ?>></div>

				<div class="od-container">
					<div class="od-hero__content">
						<?php if ( $od_slide['eyebrow'] ) : ?>
							<span class="od-hero__eyebrow"><?php echo esc_html( $od_slide['eyebrow'] ); ?></span>
						<?php endif; ?>

						<?php if ( $od_slide['title'] ) : ?>
							<h1 class="od-hero__title"><?php echo od_kses( $od_slide['title'] ); ?></h1>
						<?php endif; ?>

						<?php if ( $od_slide['text'] ) : ?>
							<p class="od-hero__text"><?php echo esc_html( $od_slide['text'] ); ?></p>
						<?php endif; ?>

						<div class="od-hero__cta">
							<a class="od-btn od-btn--lg" href="<?php echo esc_url( $od_slide['url'] ); ?>">
								<?php echo esc_html( $od_slide['btn'] ); ?>
								<?php od_the_icon( 'arrow-right', 16 ); ?>
							</a>
							<a class="od-btn od-btn--outline-light od-btn--lg" href="<?php echo esc_url( od_page_url( 'lookbook' ) ); ?>">
								<?php esc_html_e( 'View lookbook', 'ojasvidrapes' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( count( $od_slides ) > 1 ) : ?>
		<button type="button" class="od-icon-btn od-hero__nav od-hero__nav--prev"
			aria-label="<?php esc_attr_e( 'Previous slide', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'chevron-left', 20 ); ?>
		</button>

		<button type="button" class="od-icon-btn od-hero__nav od-hero__nav--next"
			aria-label="<?php esc_attr_e( 'Next slide', 'ojasvidrapes' ); ?>">
			<?php od_the_icon( 'chevron-right', 20 ); ?>
		</button>

		<div class="od-hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose a slide', 'ojasvidrapes' ); ?>">
			<?php foreach ( $od_slides as $od_n => $od_slide ) : ?>
				<button type="button" class="od-hero__dot<?php echo 0 === $od_n ? ' is-active' : ''; ?>" role="tab"
					aria-selected="<?php echo 0 === $od_n ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'ojasvidrapes' ), $od_n + 1 ) ); ?>"></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
