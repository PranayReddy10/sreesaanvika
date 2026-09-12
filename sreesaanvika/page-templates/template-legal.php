<?php
/**
 * Template Name: Policy / Legal Page
 *
 * Long-form document layout: a contents rail that tracks the reader, a
 * highlights strip for the facts most people actually came for, and the body
 * set for reading rather than skimming.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ss_updated = ss_option( 'policy_updated' );

	if ( ! $ss_updated ) {
		$ss_updated = get_the_modified_date( get_option( 'date_format' ) );
	}

	// Add anchors to the headings and collect them for the contents rail.
	$ss_content = apply_filters( 'the_content', get_the_content() );
	list( $ss_body, $ss_toc ) = ss_legal_anchor_headings( $ss_content );

	$ss_highlights = ss_legal_highlights( get_post_field( 'post_name' ) );

	ss_page_header( get_the_title() );
	?>

	<div class="ss-container ss-section">

		<?php if ( $ss_highlights ) : ?>
			<div class="ss-legal-highlights">
				<?php foreach ( $ss_highlights as $ss_item ) : ?>
					<div class="ss-legal-highlight">
						<?php ss_the_icon( $ss_item['icon'], 22 ); ?>
						<div>
							<strong><?php echo esc_html( $ss_item['title'] ); ?></strong>
							<span><?php echo esc_html( $ss_item['text'] ); ?></span>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="ss-layout" style="--ss-aside:280px">

			<article <?php post_class( 'ss-legal' ); ?>>
				<p class="ss-legal__updated">
					<?php ss_the_icon( 'clock', 15 ); ?>
					<?php
					printf(
						/* translators: %s: date */
						esc_html__( 'Last updated %s', 'sreesaanvika' ),
						esc_html( $ss_updated )
					);
					?>
				</p>

				<div class="ss-legal__body">
					<?php echo wp_kses_post( $ss_body ); ?>
				</div>

				<div class="ss-legal__foot">
					<h3><?php esc_html_e( 'Still have a question?', 'sreesaanvika' ); ?></h3>
					<p>
						<?php esc_html_e( 'Our team answers within a few hours, every day except Sunday. Nothing here is meant to be a runaround — if something is unclear, ask us.', 'sreesaanvika' ); ?>
					</p>

					<div class="ss-legal__foot-actions">
						<a class="ss-btn" href="<?php echo esc_url( ss_page_url( 'contact' ) ); ?>">
							<?php esc_html_e( 'Contact us', 'sreesaanvika' ); ?>
						</a>

						<?php
						$ss_phone = ss_option( 'footer_phone' );

						if ( $ss_phone ) :
							?>
							<a class="ss-btn ss-btn--ghost" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $ss_phone ) ); ?>">
								<?php ss_the_icon( 'phone', 16 ); ?>
								<?php echo esc_html( $ss_phone ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</article>

			<aside class="ss-sidebar">
				<?php if ( $ss_toc ) : ?>
					<nav class="ss-widget ss-legal-toc" aria-label="<?php esc_attr_e( 'On this page', 'sreesaanvika' ); ?>">
						<h3 class="ss-widget__title"><?php esc_html_e( 'On this page', 'sreesaanvika' ); ?></h3>
						<ol>
							<?php foreach ( $ss_toc as $ss_item ) : ?>
								<li>
									<a href="#<?php echo esc_attr( $ss_item['id'] ); ?>"><?php echo esc_html( $ss_item['title'] ); ?></a>
								</li>
							<?php endforeach; ?>
						</ol>
					</nav>
				<?php endif; ?>

				<div class="ss-widget">
					<h3 class="ss-widget__title"><?php esc_html_e( 'Our other policies', 'sreesaanvika' ); ?></h3>
					<ul>
						<?php
						$ss_current = get_post_field( 'post_name' );

						foreach ( ss_legal_pages() as $ss_slug => $ss_page ) :
							if ( $ss_slug === $ss_current ) {
								continue;
							}
							?>
							<li><a href="<?php echo esc_url( ss_page_url( $ss_slug ) ); ?>"><?php echo esc_html( $ss_page['title'] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			</aside>
		</div>
	</div>

	<?php
endwhile;

get_footer();
