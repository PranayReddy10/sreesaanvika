<?php
/**
 * Template Name: Lookbook
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Season by season, how we style the collection.', 'sreesaanvika' ) );

// Every gallery image attached to a published product becomes a look.
$ss_shots = array();

if ( class_exists( 'WooCommerce' ) ) {
	$ss_query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 18,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	foreach ( $ss_query->posts as $ss_post ) {
		$ss_img = get_the_post_thumbnail_url( $ss_post, 'ss-product-lg' );

		if ( ! $ss_img ) {
			continue;
		}

		$ss_terms = get_the_terms( $ss_post, 'product_cat' );

		$ss_shots[] = array(
			'img'   => $ss_img,
			'url'   => get_permalink( $ss_post ),
			'title' => get_the_title( $ss_post ),
			'cat'   => ( $ss_terms && ! is_wp_error( $ss_terms ) ) ? $ss_terms[0]->name : '',
		);
	}

	wp_reset_postdata();
}
?>

<div class="ss-container ss-section">

	<?php if ( $ss_shots ) : ?>
		<div class="ss-grid ss-grid--3">
			<?php foreach ( $ss_shots as $ss_shot ) : ?>
				<a class="ss-card" href="<?php echo esc_url( $ss_shot['url'] ); ?>" style="display:block">
					<div class="ss-pcard__media">
						<img src="<?php echo esc_url( $ss_shot['img'] ); ?>" alt="<?php echo esc_attr( $ss_shot['title'] ); ?>"
							loading="lazy" class="ss-pcard__img ss-pcard__img--front" />
					</div>

					<div class="ss-pcard__body">
						<?php if ( $ss_shot['cat'] ) : ?>
							<span class="ss-pcard__cat"><?php echo esc_html( $ss_shot['cat'] ); ?></span>
						<?php endif; ?>
						<h3 class="ss-pcard__title"><?php echo esc_html( $ss_shot['title'] ); ?></h3>
						<span class="ss-readmore">
							<?php esc_html_e( 'Shop this look', 'sreesaanvika' ); ?>
							<?php ss_the_icon( 'arrow-right', 15 ); ?>
						</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<?php
		ss_empty_state(
			'palette',
			__( 'The lookbook is being shot', 'sreesaanvika' ),
			__( 'Add products with featured images and they will appear here automatically.', 'sreesaanvika' ),
			home_url( '/' ),
			__( 'Back home', 'sreesaanvika' )
		);
		?>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) {
			echo '<div class="ss-entry" style="margin-top:40px">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
get_footer();
