<?php
/**
 * Template Name: Wishlist
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Everything you have saved, kept safe across your devices.', 'ojasvidrapes' ) );

$od_ids = function_exists( 'wc_get_product' ) ? od_get_list( 'wishlist' ) : array();
?>

<div class="od-container od-section" data-list-page="wishlist">

	<?php if ( ! $od_ids ) : ?>
		<?php
		od_empty_state(
			'heart',
			__( 'Your wishlist is empty', 'ojasvidrapes' ),
			__( 'Tap the heart on any product to keep it here while you decide.', 'ojasvidrapes' ),
			class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			__( 'Find something you love', 'ojasvidrapes' )
		);
		?>
	<?php else : ?>

		<div class="od-between" style="margin-bottom:24px;flex-wrap:wrap">
			<p style="margin:0;color:var(--od-muted)">
				<?php
				printf(
					/* translators: %s: number of saved items */
					esc_html( _n( '%s piece saved', '%s pieces saved', count( $od_ids ), 'ojasvidrapes' ) ),
					esc_html( number_format_i18n( count( $od_ids ) ) )
				);
				?>
			</p>

			<?php if ( ! is_user_logged_in() ) : ?>
				<a class="od-btn od-btn--ghost od-btn--sm" href="<?php echo esc_url( class_exists( 'WooCommerce' ) ? wc_get_page_permalink( 'myaccount' ) : od_page_url( 'auth' ) ); ?>">
					<?php esc_html_e( 'Sign in to save these permanently', 'ojasvidrapes' ); ?>
				</a>
			<?php endif; ?>
		</div>

		<?php
		od_product_loop(
			array(
				'post__in'       => $od_ids,
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
			echo '<div class="od-entry" style="margin-top:34px">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
if ( class_exists( 'WooCommerce' ) ) {
	echo '<div class="od-container od-section od-section--tight">';
	od_section_head( __( 'You may also like', 'ojasvidrapes' ), __( 'Picked for <em>You</em>', 'ojasvidrapes' ) );
	od_product_loop( array( 'orderby' => 'rand', 'posts_per_page' => 4 ), 4 );
	echo '</div>';
}

get_footer();
