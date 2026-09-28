<?php
/**
 * Journal strip.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_query = new WP_Query(
	array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

if ( ! $od_query->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="od-section od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'The journal', 'ojasvidrapes' ),
			__( 'Notes on <em>Craft & Care</em>', 'ojasvidrapes' ),
			__( 'Draping guides, weave stories and how to keep silk alive for decades.', 'ojasvidrapes' )
		);
		?>

		<div class="od-grid od-grid--3">
			<?php
			while ( $od_query->have_posts() ) {
				$od_query->the_post();
				od_post_card();
			}

			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
