<?php
/**
 * Homepage hero slider.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_slides = array();

for ( $ss_i = 1; $ss_i <= 3; $ss_i++ ) {
	$ss_title = ss_option( "hero{$ss_i}_title", '' );

	if ( ! $ss_title ) {
		continue;
	}

	$ss_url = ss_option( "hero{$ss_i}_url", '' );

	if ( ! $ss_url && class_exists( 'WooCommerce' ) ) {
		$ss_url = wc_get_page_permalink( 'shop' );
	}

	$ss_slides[] = array(
		'eyebrow' => ss_option( "hero{$ss_i}_eyebrow", '' ),
		'title'   => $ss_title,
		'text'    => ss_option( "hero{$ss_i}_text", '' ),
		'btn'     => ss_option( "hero{$ss_i}_btn", __( 'Shop now', 'sreesaanvika' ) ),
		'url'     => $ss_url ? $ss_url : home_url( '/' ),
		'img'     => ss_option( "hero{$ss_i}_img", '' ),
		'align'   => ss_option( "hero{$ss_i}_align", 'left' ),
	);
}

if ( ! $ss_slides ) {
	return;
}
?>
<section class="ss-hero" data-hero data-autoplay="<?php echo ss_option( 'hero_autoplay', true ) ? '1' : '0'; ?>"
	data-speed="<?php echo esc_attr( absint( ss_option( 'hero_speed', 6 ) ) ); ?>"
	aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Featured collections', 'sreesaanvika' ); ?>">

	<div class="ss-hero__viewport">
		<?php foreach ( $ss_slides as $ss_n => $ss_slide ) : ?>
			<div class="ss-hero__slide ss-hero__slide--<?php echo esc_attr( $ss_slide['align'] ); ?><?php echo 0 === $ss_n ? ' is-active' : ''; ?>"
				role="group" aria-roledescription="slide"
				aria-label="<?php echo esc_attr( sprintf( /* translators: 1: slide number, 2: total slides */ __( 'Slide %1$d of %2$d', 'sreesaanvika' ), $ss_n + 1, count( $ss_slides ) ) ); ?>">

				<div class="ss-hero__bg"<?php echo $ss_slide['img'] ? ss_bg_style( $ss_slide['img'] ) : ''; ?>></div>

				<div class="ss-container">
					<div class="ss-hero__content">
						<?php if ( $ss_slide['eyebrow'] ) : ?>
							<span class="ss-hero__eyebrow"><?php echo esc_html( $ss_slide['eyebrow'] ); ?></span>
						<?php endif; ?>

						<h1 class="ss-hero__title"><?php echo ss_kses( $ss_slide['title'] ); ?></h1>

						<?php if ( $ss_slide['text'] ) : ?>
							<p class="ss-hero__text"><?php echo esc_html( $ss_slide['text'] ); ?></p>
						<?php endif; ?>

						<div class="ss-hero__cta">
							<a class="ss-btn ss-btn--lg" href="<?php echo esc_url( $ss_slide['url'] ); ?>">
								<?php echo esc_html( $ss_slide['btn'] ); ?>
								<?php ss_the_icon( 'arrow-right', 16 ); ?>
							</a>
							<a class="ss-btn ss-btn--outline-light ss-btn--lg" href="<?php echo esc_url( ss_page_url( 'lookbook' ) ); ?>">
								<?php esc_html_e( 'View lookbook', 'sreesaanvika' ); ?>
							</a>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( count( $ss_slides ) > 1 ) : ?>
		<button type="button" class="ss-icon-btn ss-hero__nav ss-hero__nav--prev"
			aria-label="<?php esc_attr_e( 'Previous slide', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'chevron-left', 20 ); ?>
		</button>

		<button type="button" class="ss-icon-btn ss-hero__nav ss-hero__nav--next"
			aria-label="<?php esc_attr_e( 'Next slide', 'sreesaanvika' ); ?>">
			<?php ss_the_icon( 'chevron-right', 20 ); ?>
		</button>

		<div class="ss-hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose a slide', 'sreesaanvika' ); ?>">
			<?php foreach ( $ss_slides as $ss_n => $ss_slide ) : ?>
				<button type="button" class="ss-hero__dot<?php echo 0 === $ss_n ? ' is-active' : ''; ?>" role="tab"
					aria-selected="<?php echo 0 === $ss_n ? 'true' : 'false'; ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %d: slide number */ __( 'Go to slide %d', 'sreesaanvika' ), $ss_n + 1 ) ); ?>"></button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
