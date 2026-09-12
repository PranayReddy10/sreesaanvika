<?php
/**
 * Two offer banners.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_banners = array();

for ( $ss_i = 1; $ss_i <= 2; $ss_i++ ) {
	$ss_title = ss_option( "promo{$ss_i}_title", '' );

	if ( ! $ss_title ) {
		continue;
	}

	$ss_banners[] = array(
		'off'   => ss_option( "promo{$ss_i}_off", '' ),
		'title' => $ss_title,
		'text'  => ss_option( "promo{$ss_i}_text", '' ),
		'url'   => ss_option( "promo{$ss_i}_url", '' ),
		'img'   => ss_option( "promo{$ss_i}_img", '' ),
	);
}

if ( ! $ss_banners ) {
	return;
}
?>
<section class="ss-section ss-section--tight ss-reveal">
	<div class="ss-container">
		<div class="ss-grid ss-grid--2">
			<?php foreach ( $ss_banners as $ss_banner ) : ?>
				<article class="ss-promo">
					<div class="ss-promo__bg"<?php echo $ss_banner['img'] ? ss_bg_style( $ss_banner['img'] ) : ''; ?>></div>

					<div class="ss-promo__content">
						<?php if ( $ss_banner['off'] ) : ?>
							<span class="ss-promo__off"><?php echo esc_html( $ss_banner['off'] ); ?></span>
						<?php endif; ?>

						<h3><?php echo esc_html( $ss_banner['title'] ); ?></h3>

						<?php if ( $ss_banner['text'] ) : ?>
							<p><?php echo esc_html( $ss_banner['text'] ); ?></p>
						<?php endif; ?>

						<a class="ss-btn" href="<?php echo esc_url( $ss_banner['url'] ? $ss_banner['url'] : home_url( '/' ) ); ?>">
							<?php esc_html_e( 'Shop the offer', 'sreesaanvika' ); ?>
							<?php ss_the_icon( 'arrow-right', 16 ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
