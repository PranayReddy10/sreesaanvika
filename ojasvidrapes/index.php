<?php
/**
 * Fallback template — blog index and any archive without a more specific file.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header();
?>

<div class="od-container od-section">
	<div class="od-layout<?php echo is_active_sidebar( 'sidebar-blog' ) ? '' : ' od-layout--full'; ?>">

		<div class="od-content">
			<?php if ( have_posts() ) : ?>
				<div class="od-grid od-grid--2">
					<?php
					while ( have_posts() ) {
						the_post();
						od_post_card();
					}
					?>
				</div>

				<?php od_pagination(); ?>
			<?php else : ?>
				<?php
				od_empty_state(
					'search',
					__( 'Nothing here yet', 'ojasvidrapes' ),
					__( 'No posts have been published in this section so far.', 'ojasvidrapes' ),
					home_url( '/' ),
					__( 'Back home', 'ojasvidrapes' )
				);
				?>
			<?php endif; ?>
		</div>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
