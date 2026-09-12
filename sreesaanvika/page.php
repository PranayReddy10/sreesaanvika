<?php
/**
 * Single page.
 *
 * Three shapes:
 *  - built with Elementor → the builder owns the whole content area
 *  - a WooCommerce cart / checkout / account page → wide, card-less wrapper
 *  - anything else → the standard article card
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ss_is_shop_page = class_exists( 'WooCommerce' ) && ( is_cart() || is_checkout() || is_account_page() );
$ss_is_builder   = ss_elementor_owns_page();

while ( have_posts() ) :
	the_post();

	if ( $ss_is_builder ) :
		/*
		 * No container and no card — Elementor sections manage their own
		 * width. the_content() runs unconditionally so the editor preview
		 * always finds a content wrapper to load into.
		 */
		?>
		<div <?php post_class( 'ss-elementor-content' ); ?>>
			<?php the_content(); ?>
		</div>
		<?php
	else :

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
	endif;

endwhile;

get_footer();
