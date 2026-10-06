<?php
/**
 * Full-width story band.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_title = od_option( 'band_title', '' );

if ( ! $od_title ) {
	return;
}

$od_img = od_option( 'band_img', '' );
?>
<section class="od-band od-reveal"<?php echo $od_img ? od_bg_style( $od_img ) : ''; ?>>
	<div class="od-container od-container--narrow">
		<div class="od-ornament" aria-hidden="true" style="margin-bottom:20px"><?php od_the_icon( 'paisley', 24 ); ?></div>

		<h2 style="font-size:clamp(1.6rem,3.6vw,2.8rem)"><?php echo esc_html( $od_title ); ?></h2>

		<?php
		$od_text = od_option( 'band_text', '' );

		if ( $od_text ) :
			?>
			<p style="color:var(--od-text-soft);font-size:1.06rem;max-width:660px;margin:0 auto 28px">
				<?php echo esc_html( $od_text ); ?>
			</p>
		<?php endif; ?>

		<a class="od-btn od-btn--ghost od-btn--lg" href="<?php echo esc_url( od_page_url( 'about' ) ); ?>">
			<?php esc_html_e( 'Read our story', 'ojasvidrapes' ); ?>
		</a>
	</div>
</section>
