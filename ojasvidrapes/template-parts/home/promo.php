<?php
/**
 * Two offer banners.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_banners = array();

for ( $od_i = 1; $od_i <= 2; $od_i++ ) {
	$od_title = od_option( "promo{$od_i}_title", '' );

	if ( ! $od_title ) {
		continue;
	}

	$od_banners[] = array(
		'off'   => od_option( "promo{$od_i}_off", '' ),
		'title' => $od_title,
		'text'  => od_option( "promo{$od_i}_text", '' ),
		'url'   => od_option( "promo{$od_i}_url", '' ),
		'img'   => od_option( "promo{$od_i}_img", '' ),
	);
}

if ( ! $od_banners ) {
	return;
}
?>
<section class="od-section od-section--tight od-reveal">
	<div class="od-container">
		<div class="od-grid od-grid--2">
			<?php foreach ( $od_banners as $od_banner ) : ?>
				<article class="od-promo">
					<div class="od-promo__bg"<?php echo $od_banner['img'] ? od_bg_style( $od_banner['img'] ) : ''; ?>></div>

					<div class="od-promo__content">
						<?php if ( $od_banner['off'] ) : ?>
							<span class="od-promo__off"><?php echo esc_html( $od_banner['off'] ); ?></span>
						<?php endif; ?>

						<h3><?php echo esc_html( $od_banner['title'] ); ?></h3>

						<?php if ( $od_banner['text'] ) : ?>
							<p><?php echo esc_html( $od_banner['text'] ); ?></p>
						<?php endif; ?>

						<a class="od-btn" href="<?php echo esc_url( $od_banner['url'] ? $od_banner['url'] : home_url( '/' ) ); ?>">
							<?php esc_html_e( 'Shop the offer', 'ojasvidrapes' ); ?>
							<?php od_the_icon( 'arrow-right', 16 ); ?>
						</a>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
