<?php
/**
 * Journal strip.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $ss_query->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="ss-section ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'The journal', 'sreesaanvika' ),
			__( 'Notes on <em>Craft & Care</em>', 'sreesaanvika' ),
			__( 'Draping guides, weave stories and how to keep silk alive for decades.', 'sreesaanvika' )
		);
		?>

		<div class="ss-grid ss-grid--3">
			<?php
			while ( $ss_query->have_posts() ) {
				$ss_query->the_post();
				ss_post_card();
			}

			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
