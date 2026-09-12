<?php
/**
 * Template Name: About / Our Story
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Six generations of looms, one honest price.', 'sreesaanvika' ) );

$ss_stats = array(
	array( '6', __( 'Weaving clusters', 'sreesaanvika' ) ),
	array( '340+', __( 'Artisan families', 'sreesaanvika' ) ),
	array( '12k', __( 'Happy customers', 'sreesaanvika' ) ),
	array( '0', __( 'Middlemen', 'sreesaanvika' ) ),
);

$ss_values = array(
	array( 'leaf', __( 'Direct from the loom', 'sreesaanvika' ), __( 'We buy straight from weaver families in Kanchipuram, Banaras, Pochampally, Chanderi, Bhagalpur and Bhuj. No agents, no markups on the way.', 'sreesaanvika' ) ),
	array( 'shield', __( 'Certified authentic', 'sreesaanvika' ), __( 'Every silk saree carries a Silk Mark or Handloom Mark. If a piece is blended or powerloom, we say so on the label.', 'sreesaanvika' ) ),
	array( 'scissors', __( 'Finished for you', 'sreesaanvika' ), __( 'Free fall and pico stitching on silk sarees, and blouse pieces cut with a generous margin so your tailor has room to work.', 'sreesaanvika' ) ),
	array( 'gift', __( 'Packed like a gift', 'sreesaanvika' ), __( 'Cotton pouch, weaver card and a note on how to care for the weave — because it should last decades, not seasons.', 'sreesaanvika' ) ),
);
?>

<div class="ss-container ss-section">
	<div class="ss-grid ss-grid--4" style="margin-bottom:clamp(36px,5vw,64px)">
		<?php foreach ( $ss_stats as $ss_stat ) : ?>
			<div class="ss-card" style="padding:28px 22px;text-align:center">
				<div style="font-family:var(--ss-font-head);font-size:2.6rem;line-height:1;background:var(--ss-gold-grad);-webkit-background-clip:text;background-clip:text;color:transparent">
					<?php echo esc_html( $ss_stat[0] ); ?>
				</div>
				<div style="font-size:.78rem;letter-spacing:.16em;text-transform:uppercase;color:var(--ss-muted);margin-top:8px">
					<?php echo esc_html( $ss_stat[1] ); ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) {
			echo '<div class="ss-entry" style="margin-bottom:clamp(36px,5vw,64px)">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>

	<?php ss_section_head( __( 'What we stand for', 'sreesaanvika' ), __( 'Our <em>Promise</em>', 'sreesaanvika' ) ); ?>

	<div class="ss-grid ss-grid--2">
		<?php foreach ( $ss_values as $ss_value ) : ?>
			<div class="ss-card" style="padding:30px 28px">
				<span class="ss-usp__icon" style="margin-bottom:16px"><?php ss_the_icon( $ss_value[0], 22 ); ?></span>
				<h3 style="font-size:1.25rem"><?php echo esc_html( $ss_value[1] ); ?></h3>
				<p style="color:var(--ss-muted);margin:0"><?php echo esc_html( $ss_value[2] ); ?></p>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<?php
get_footer();
