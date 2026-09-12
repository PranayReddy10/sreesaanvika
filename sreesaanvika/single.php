<?php
/**
 * Single post.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// A post laid out in Elementor gets the full canvas, no article card.
	if ( ss_elementor_owns_page() ) {
		?>
		<article <?php post_class( 'ss-elementor-content' ); ?>>
			<?php the_content(); ?>
		</article>
		<?php
		continue;
	}

	ss_page_header( get_the_title() );
	?>

	<div class="ss-container ss-section">
		<div class="ss-layout<?php echo is_active_sidebar( 'sidebar-blog' ) ? '' : ' ss-layout--full'; ?>">

			<div class="ss-content">
				<article <?php post_class( 'ss-entry' ); ?>>
					<div class="ss-post__meta" style="margin-bottom:20px">
						<span><?php echo esc_html( get_the_date() ); ?></span>
						<span><?php echo esc_html( get_the_author() ); ?></span>
						<?php
						$ss_cats = get_the_category();

						if ( $ss_cats ) :
							?>
							<span><?php echo esc_html( $ss_cats[0]->name ); ?></span>
						<?php endif; ?>
					</div>

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

					$ss_tags = get_the_tags();

					if ( $ss_tags ) :
						?>
						<div class="tagcloud" style="margin-top:28px;display:flex;flex-wrap:wrap;gap:8px">
							<?php foreach ( $ss_tags as $ss_tag ) : ?>
								<a class="ss-chip" href="<?php echo esc_url( get_tag_link( $ss_tag ) ); ?>">#<?php echo esc_html( $ss_tag->name ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</article>

				<?php
				$ss_prev = get_previous_post();
				$ss_next = get_next_post();

				if ( $ss_prev || $ss_next ) :
					?>
					<nav class="ss-postnav" aria-label="<?php esc_attr_e( 'More posts', 'sreesaanvika' ); ?>">
						<?php if ( $ss_prev ) : ?>
							<a class="prev" href="<?php echo esc_url( get_permalink( $ss_prev ) ); ?>">
								<small><?php esc_html_e( 'Previous', 'sreesaanvika' ); ?></small>
								<strong><?php echo esc_html( get_the_title( $ss_prev ) ); ?></strong>
							</a>
						<?php else : ?>
							<span></span>
						<?php endif; ?>

						<?php if ( $ss_next ) : ?>
							<a class="next" href="<?php echo esc_url( get_permalink( $ss_next ) ); ?>">
								<small><?php esc_html_e( 'Next', 'sreesaanvika' ); ?></small>
								<strong><?php echo esc_html( get_the_title( $ss_next ) ); ?></strong>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>

				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>

			<?php get_sidebar(); ?>
		</div>
	</div>

	<?php
endwhile;

get_footer();
