<?php
/**
 * Fallback template — blog index and any archive without a more specific file.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header();
?>

<div class="ss-container ss-section">
	<div class="ss-layout<?php echo is_active_sidebar( 'sidebar-blog' ) ? '' : ' ss-layout--full'; ?>">

		<div class="ss-content">
			<?php if ( have_posts() ) : ?>
				<div class="ss-grid ss-grid--2">
					<?php
					while ( have_posts() ) {
						the_post();
						ss_post_card();
					}
					?>
				</div>

				<?php ss_pagination(); ?>
			<?php else : ?>
				<?php
				ss_empty_state(
					'search',
					__( 'Nothing here yet', 'sreesaanvika' ),
					__( 'No posts have been published in this section so far.', 'sreesaanvika' ),
					home_url( '/' ),
					__( 'Back home', 'sreesaanvika' )
				);
				?>
			<?php endif; ?>
		</div>

		<?php get_sidebar(); ?>
	</div>
</div>

<?php
get_footer();
