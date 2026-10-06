<?php
/**
 * Template Name: Lookbook
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Season by season, how we style the collection.', 'ojasvidrapes' ) );

// Every gallery image attached to a published product becomes a look.
$od_shots = array();

if ( class_exists( 'WooCommerce' ) ) {
	$od_query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 18,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
		)
	);

	foreach ( $od_query->posts as $od_post ) {
		$od_img = get_the_post_thumbnail_url( $od_post, 'od-product-lg' );

		if ( ! $od_img ) {
			continue;
		}

		$od_terms = get_the_terms( $od_post, 'product_cat' );

		$od_shots[] = array(
			'img'   => $od_img,
			'url'   => get_permalink( $od_post ),
			'title' => get_the_title( $od_post ),
			'cat'   => ( $od_terms && ! is_wp_error( $od_terms ) ) ? $od_terms[0]->name : '',
		);
	}

	wp_reset_postdata();
}
?>

<div class="od-container od-section">

	<?php if ( $od_shots ) : ?>
		<div class="od-grid od-grid--3">
			<?php foreach ( $od_shots as $od_shot ) : ?>
				<a class="od-card" href="<?php echo esc_url( $od_shot['url'] ); ?>" style="display:block">
					<div class="od-pcard__media">
						<img src="<?php echo esc_url( $od_shot['img'] ); ?>" alt="<?php echo esc_attr( $od_shot['title'] ); ?>"
							loading="lazy" class="od-pcard__img od-pcard__img--front" />
					</div>

					<div class="od-pcard__body">
						<?php if ( $od_shot['cat'] ) : ?>
							<span class="od-pcard__cat"><?php echo esc_html( $od_shot['cat'] ); ?></span>
						<?php endif; ?>
						<h3 class="od-pcard__title"><?php echo esc_html( $od_shot['title'] ); ?></h3>
						<span class="od-readmore">
							<?php esc_html_e( 'Shop this look', 'ojasvidrapes' ); ?>
							<?php od_the_icon( 'arrow-right', 15 ); ?>
						</span>
					</div>
				</a>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<?php
		od_empty_state(
			'palette',
			__( 'The lookbook is being shot', 'ojasvidrapes' ),
			__( 'Add products with featured images and they will appear here automatically.', 'ojasvidrapes' ),
			home_url( '/' ),
			__( 'Back home', 'ojasvidrapes' )
		);
		?>
	<?php endif; ?>

	<?php
	while ( have_posts() ) :
		the_post();

		if ( trim( get_the_content() ) ) {
			echo '<div class="od-entry" style="margin-top:40px">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</div>

<?php
get_footer();
