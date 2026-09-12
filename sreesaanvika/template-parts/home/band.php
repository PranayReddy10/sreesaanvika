<?php
/**
 * Full-width story band.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_title = ss_option( 'band_title', '' );

if ( ! $ss_title ) {
	return;
}

$ss_img = ss_option( 'band_img', '' );
?>
<section class="ss-band ss-reveal"<?php echo $ss_img ? ss_bg_style( $ss_img ) : ''; ?>>
	<div class="ss-container ss-container--narrow">
		<div class="ss-ornament" aria-hidden="true" style="margin-bottom:20px"><?php ss_the_icon( 'paisley', 24 ); ?></div>

		<h2 style="font-size:clamp(1.6rem,3.6vw,2.8rem)"><?php echo esc_html( $ss_title ); ?></h2>

		<?php
		$ss_text = ss_option( 'band_text', '' );

		if ( $ss_text ) :
			?>
			<p style="color:var(--ss-text-soft);font-size:1.06rem;max-width:660px;margin:0 auto 28px">
				<?php echo esc_html( $ss_text ); ?>
			</p>
		<?php endif; ?>

		<a class="ss-btn ss-btn--ghost ss-btn--lg" href="<?php echo esc_url( ss_page_url( 'about' ) ); ?>">
			<?php esc_html_e( 'Read our story', 'sreesaanvika' ); ?>
		</a>
	</div>
</section>
