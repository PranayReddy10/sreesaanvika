<?php
/**
 * Single post.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// A post laid out in Elementor gets the full canvas, no article card.
	if ( od_elementor_owns_page() ) {
		?>
		<article <?php post_class( 'od-elementor-content' ); ?>>
			<?php the_content(); ?>
		</article>
		<?php
		continue;
	}

	od_page_header( get_the_title() );
	?>

	<div class="od-container od-section">
		<div class="od-layout<?php echo is_active_sidebar( 'sidebar-blog' ) ? '' : ' od-layout--full'; ?>">

			<div class="od-content">
				<article <?php post_class( 'od-entry' ); ?>>
					<div class="od-post__meta" style="margin-bottom:20px">
						<span><?php echo esc_html( get_the_date() ); ?></span>
						<span><?php echo esc_html( get_the_author() ); ?></span>
						<?php
						$od_cats = get_the_category();

						if ( $od_cats ) :
							?>
							<span><?php echo esc_html( $od_cats[0]->name ); ?></span>
						<?php endif; ?>
					</div>

					<?php
					if ( has_post_thumbnail() ) {
						the_post_thumbnail( 'od-hero', array( 'style' => 'margin-bottom:28px' ) );
					}

					the_content();

					wp_link_pages(
						array(
							'before' => '<nav class="od-pagination">',
							'after'  => '</nav>',
						)
					);

					$od_tags = get_the_tags();

					if ( $od_tags ) :
						?>
						<div class="tagcloud" style="margin-top:28px;display:flex;flex-wrap:wrap;gap:8px">
							<?php foreach ( $od_tags as $od_tag ) : ?>
								<a class="od-chip" href="<?php echo esc_url( get_tag_link( $od_tag ) ); ?>">#<?php echo esc_html( $od_tag->name ); ?></a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</article>

				<?php
				$od_prev = get_previous_post();
				$od_next = get_next_post();

				if ( $od_prev || $od_next ) :
					?>
					<nav class="od-postnav" aria-label="<?php esc_attr_e( 'More posts', 'ojasvidrapes' ); ?>">
						<?php if ( $od_prev ) : ?>
							<a class="prev" href="<?php echo esc_url( get_permalink( $od_prev ) ); ?>">
								<small><?php esc_html_e( 'Previous', 'ojasvidrapes' ); ?></small>
								<strong><?php echo esc_html( get_the_title( $od_prev ) ); ?></strong>
							</a>
						<?php else : ?>
							<span></span>
						<?php endif; ?>

						<?php if ( $od_next ) : ?>
							<a class="next" href="<?php echo esc_url( get_permalink( $od_next ) ); ?>">
								<small><?php esc_html_e( 'Next', 'ojasvidrapes' ); ?></small>
								<strong><?php echo esc_html( get_the_title( $od_next ) ); ?></strong>
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
