<?php
/**
 * Instagram-style grid built from recent product imagery.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

$ss_handle = ss_option( 'gram_handle', 'sreesaanvika' );
$ss_url    = ss_option( 'social_instagram', '' );
$ss_url    = $ss_url ? $ss_url : 'https://instagram.com/' . rawurlencode( $ss_handle );

$ss_images = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

if ( count( $ss_images ) < 6 ) {
	return;
}
?>
<section class="ss-section ss-section--tight ss-reveal">
	<div class="ss-container">
		<?php
		ss_section_head(
			__( 'Follow along', 'sreesaanvika' ),
			'@' . $ss_handle,
			__( 'Tag us in your drape — we reshare our favourites every week.', 'sreesaanvika' )
		);
		?>

		<div class="ss-gram">
			<?php foreach ( $ss_images as $ss_image ) : ?>
				<a class="ss-gram__cell" href="<?php echo esc_url( $ss_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo wp_get_attachment_image( $ss_image->ID, 'ss-category', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
					<?php ss_the_icon( 'instagram', 26 ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Open our Instagram', 'sreesaanvika' ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
