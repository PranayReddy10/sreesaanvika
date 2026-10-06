<?php
/**
 * Instagram-style grid built from recent product imagery.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

$od_handle = od_option( 'gram_handle', 'ojasvidrapes' );
$od_url    = od_option( 'social_instagram', '' );
$od_url    = $od_url ? $od_url : 'https://instagram.com/' . rawurlencode( $od_handle );

$od_images = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'post_status'    => 'inherit',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);

if ( count( $od_images ) < 6 ) {
	return;
}
?>
<section class="od-section od-section--tight od-reveal">
	<div class="od-container">
		<?php
		od_section_head(
			__( 'Follow along', 'ojasvidrapes' ),
			'@' . $od_handle,
			__( 'Tag us in your drape — we reshare our favourites every week.', 'ojasvidrapes' )
		);
		?>

		<div class="od-gram">
			<?php foreach ( $od_images as $od_image ) : ?>
				<a class="od-gram__cell" href="<?php echo esc_url( $od_url ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo wp_get_attachment_image( $od_image->ID, 'od-category', false, array( 'loading' => 'lazy', 'alt' => '' ) ); ?>
					<?php od_the_icon( 'instagram', 26 ); ?>
					<span class="screen-reader-text"><?php esc_html_e( 'Open our Instagram', 'ojasvidrapes' ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
