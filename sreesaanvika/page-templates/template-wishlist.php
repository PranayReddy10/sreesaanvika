<?php
/**
 * Template Name: Wishlist
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Everything you have saved, kept safe across your devices.', 'sreesaanvika' ) );

$ss_ids = function_exists( 'wc_get_product' ) ? ss_get_list( 'wishlist' ) : array();
?>

<div class="ss-container ss-section" data-list-page="wishlist">

	<?php if ( ! $ss_ids ) : ?>
		<?php
		ss_empty_state(
			'heart',
			__( 'Your wishlist is empty', 'sreesaanvika' ),
			__( 'Tap the heart on any product to keep it here while you decide.', 'sreesaanvika' ),
			class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			__( 'Find something you love', 'sreesaanvika' )
		);
		?>
	<?php else : ?>

		<div class="ss-between" style="margin-bottom:24px;flex-wrap:wrap">
			<p style="margin:0;color:var(--ss-muted)">
				<?php
				printf(
					/* translators: %s: number of saved items */
					esc_html( _n( '%s piece saved', '%s pieces saved', count( $ss_ids ), 'sreesaanvika' ) ),
					esc_html( number_format_i18n( count( $ss_ids ) ) )
				);
				?>
			</p>

			<?php if ( ! is_user_logged_in() ) : ?>
				<a class="ss-btn ss-btn--ghost ss-btn--sm" href="<?php echo esc_url( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : ss_page_url( 'auth' ) ); ?>">
					<?php esc_html_e( 'Sign in to save these permanently', 'sreesaanvika' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php
		ss_product_loop(
			array(
				'post__in'       => $ss_ids,
				'orderby'        => 'post__in',
				'posts_per_page' => -1,
			)
		);
		?>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) {
			echo '<div class="ss-entry" style="margin-top:34px">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
if ( class_exists( 'WooCommerce' ) ) {
	echo '<div class="ss-container ss-section ss-section--tight">';
	ss_section_head( __( 'You may also like', 'sreesaanvika' ), __( 'Picked for <em>You</em>', 'sreesaanvika' ) );
	ss_product_loop( array( 'orderby' => 'rand', 'posts_per_page' => 4 ), 4 );
	echo '</div>';
}

get_footer();
