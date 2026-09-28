<?php
/**
 * Template Name: Policy / Legal Page
 *
 * Long-form document layout: a contents rail that tracks the reader, a
 * highlights strip for the facts most people actually came for, and the body
 * set for reading rather than skimming.
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$od_updated = od_option( 'policy_updated' );

	if ( ! $od_updated ) {
		$od_updated = get_the_modified_date( get_option( 'date_format' ) );
	}

	// Add anchors to the headings and collect them for the contents rail.
	$od_content = apply_filters( 'the_content', get_the_content() );
	list( $od_body, $od_toc ) = od_legal_anchor_headings( $od_content );

	$od_highlights = od_legal_highlights( get_post_field( 'post_name' ) );

	od_page_header( get_the_title() );
	?>

	<div class="od-container od-section">

		<?php if ( $od_highlights ) : ?>
			<div class="od-legal-highlights">
				<?php foreach ( $od_highlights as $od_item ) : ?>
					<div class="od-legal-highlight">
						<?php od_the_icon( $od_item['icon'], 22 ); ?>
						<div>
							<strong><?php echo esc_html( $od_item['title'] ); ?></strong>
							<span><?php echo esc_html( $od_item['text'] ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="od-layout" style="--od-aside:280px">

			<article <?php post_class( 'od-legal' ); ?>>
				<p class="od-legal__updated">
					<?php od_the_icon( 'clock', 15 ); ?>
					<?php
					printf(
						/* translators: %s: date */
						esc_html__( 'Last updated %s', 'ojasvidrapes' ),
						esc_html( $od_updated )
					);
					?>
				</p>

				<div class="od-legal__body">
					<?php echo wp_kses_post( $od_body ); ?>
				</div>

				<div class="od-legal__foot">
					<h3><?php esc_html_e( 'Still have a question?', 'ojasvidrapes' ); ?></h3>
					<p>
						<?php esc_html_e( 'Our team answers within a few hours, every day except Sunday. Nothing here is meant to be a runaround — if something is unclear, ask us.', 'ojasvidrapes' ); ?>
					</p>

					<div class="od-legal__foot-actions">
						<a class="od-btn" href="<?php echo esc_url( od_page_url( 'contact' ) ); ?>">
							<?php esc_html_e( 'Contact us', 'ojasvidrapes' ); ?>
						</a>

						<?php
						$od_phone = od_option( 'footer_phone' );

						if ( $od_phone ) :
							?>
							<a class="od-btn od-btn--ghost" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $od_phone ) ); ?>">
								<?php od_the_icon( 'phone', 16 ); ?>
								<?php echo esc_html( $od_phone ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</article>

			<aside class="od-sidebar">
				<?php if ( $od_toc ) : ?>
					<nav class="od-widget od-legal-toc" aria-label="<?php esc_attr_e( 'On this page', 'ojasvidrapes' ); ?>">
						<h3 class="od-widget__title"><?php esc_html_e( 'On this page', 'ojasvidrapes' ); ?></h3>
						<ol>
							<?php foreach ( $od_toc as $od_item ) : ?>
								<li>
									<a href="#<?php echo esc_attr( $od_item['id'] ); ?>"><?php echo esc_html( $od_item['title'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>

				<div class="od-widget">
					<h3 class="od-widget__title"><?php esc_html_e( 'Our other policies', 'ojasvidrapes' ); ?></h3>
					<ul>
						<?php
						$od_current = get_post_field( 'post_name' );

						foreach ( od_legal_pages() as $od_slug => $od_page ) :
							if ( $od_slug === $od_current ) {
								continue;
							}
							?>
							<li><a href="<?php echo esc_url( od_page_url( $od_slug ) ); ?>"><?php echo esc_html( $od_page['title'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</aside>
		</div>
	</div>

	<?php
endwhile;

get_footer();
