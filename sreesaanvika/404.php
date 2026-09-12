<?php
/**
 * 404.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<div class="ss-container ss-section ss-text-center">
	<div style="max-width:640px;margin-inline:auto">
		<div class="ss-ornament" aria-hidden="true" style="margin-bottom:18px"><?php ss_the_icon( 'paisley', 26 ); ?></div>

		<p style="font-family:var(--ss-font-head);font-size:clamp(4rem,14vw,9rem);line-height:1;margin:0;background:var(--ss-gold-grad);-webkit-background-clip:text;background-clip:text;color:transparent">
			404
		</p>

		<h1 style="font-size:clamp(1.5rem,3.4vw,2.4rem)"><?php esc_html_e( 'This thread came loose', 'sreesaanvika' ); ?></h1>

		<p style="color:var(--ss-muted);margin-bottom:28px">
			<?php esc_html_e( 'The page you were looking for has moved or never existed. Let us help you find your way back.', 'sreesaanvika' ); ?>
		</p>

		<div style="max-width:460px;margin:0 auto 28px">
			<?php ss_search_form(); ?>
		</div>

		<div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
			<a class="ss-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back home', 'sreesaanvika' ); ?></a>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="ss-btn ss-btn--ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					<?php esc_html_e( 'Shop all', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php
if ( class_exists( 'WooCommerce' ) ) {
	echo '<div class="ss-container ss-section ss-section--tight">';
	ss_section_head( __( 'While you are here', 'sreesaanvika' ), __( 'Popular <em>Right Now</em>', 'sreesaanvika' ) );
	ss_product_loop( array( 'orderby' => 'popularity', 'posts_per_page' => 4 ), 4 );
	echo '</div>';
}

get_footer();
