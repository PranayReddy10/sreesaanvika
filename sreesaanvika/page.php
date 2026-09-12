<?php
/**
 * Single page.
 *
 * WooCommerce's cart, checkout and account pages render through the page
 * loop, so they get a wide, card-less wrapper instead of the article shell.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ss_is_shop_page = class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() || is_account_page() );

while ( have_posts() ) :
	the_post();

	ss_page_header( get_the_title() );
	?>

	<div class="ss-container<?php echo $ss_is_shop_page ? ' ss-container--wide' : ''; ?> ss-section">
		<?php if ( $ss_is_shop_page ) : ?>

			<div <?php post_class( 'ss-woo-page' ); ?>>
				<?php the_content(); ?>
			</div>

		<?php else : ?>

			<div class="ss-layout ss-layout--full">
				<article <?php post_class( 'ss-entry' ); ?>>
					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'ss-hero', array( 'style' => 'margin-bottom:28px' ) );
					}

					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="ss-pagination">',
							'after'  => '</nav>',
						)
					);
					?>
				</article>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>

		<?php endif; ?>
	</div>

	<?php
endwhile;

get_footer();
