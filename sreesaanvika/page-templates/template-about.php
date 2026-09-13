<?php
/**
 * Template Name: About / Our Story
 *
 * An editorial page rather than a stack of cards: a lede, the numbers, the
 * story beside a picture, where the cloth actually comes from, what we stand
 * for, and how a piece reaches the customer.
 *
 * Anything typed into the page editor replaces the default story text, so the
 * shop owner can rewrite it without touching the template.
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Six generations of looms, one honest price.', 'sreesaanvika' ) );

$ss_story_img = get_the_post_thumbnail_url( get_the_ID(), 'ss-product-lg' );

if ( ! $ss_story_img ) {
	$ss_story_img = ss_option( 'band_img' );
}

$ss_second_img = ss_option( 'promo1_img' );

if ( ! $ss_second_img ) {
	$ss_second_img = ss_option( 'hero1_img' );
}

$ss_stats = array(
	array( '6', __( 'Weaving clusters', 'sreesaanvika' ) ),
	array( '340+', __( 'Artisan families', 'sreesaanvika' ) ),
	array( '12k', __( 'Happy customers', 'sreesaanvika' ) ),
	array( '0', __( 'Middlemen', 'sreesaanvika' ) ),
);

$ss_clusters = array(
	array( __( 'Tamil Nadu', 'sreesaanvika' ), __( 'Kanchipuram', 'sreesaanvika' ), __( 'Three-shuttle korvai silk with contrast borders, woven on pit looms. Eleven weeks for a bridal piece is normal here.', 'sreesaanvika' ) ),
	array( __( 'Uttar Pradesh', 'sreesaanvika' ), __( 'Banaras', 'sreesaanvika' ), __( 'Katan silk with real zari butis, jangla and shikargah patterns drawn on graph paper before a single thread is thrown.', 'sreesaanvika' ) ),
	array( __( 'Telangana', 'sreesaanvika' ), __( 'Pochampally', 'sreesaanvika' ), __( 'Double ikat, where the yarn is tie-dyed before weaving so the pattern appears as the cloth is made, not after.', 'sreesaanvika' ) ),
	array( __( 'Madhya Pradesh', 'sreesaanvika' ), __( 'Chanderi', 'sreesaanvika' ), __( 'Feather-light cotton-silk with a glassy transparency that comes from never degumming the yarn.', 'sreesaanvika' ) ),
	array( __( 'Bihar', 'sreesaanvika' ), __( 'Bhagalpur', 'sreesaanvika' ), __( 'Tussar silk in its natural gold, spun from cocoons collected after the moth has already left.', 'sreesaanvika' ) ),
	array( __( 'Gujarat', 'sreesaanvika' ), __( 'Bhuj', 'sreesaanvika' ), __( 'Bandhani tied by hand, thousands of knots per saree, each one a fingertip pinch of cloth and a length of thread.', 'sreesaanvika' ) ),
);

$ss_values = array(
	array( 'leaf', __( 'Direct from the loom', 'sreesaanvika' ), __( 'We buy straight from weaver families and pay upfront, before a piece is sold. No agents, no consignment, no waiting ninety days to be paid for three months of work.', 'sreesaanvika' ) ),
	array( 'shield', __( 'Certified, or clearly labelled', 'sreesaanvika' ), __( 'Pure silk carries a Silk Mark or Handloom Mark. Where a piece is blended, powerloom, or uses tested rather than pure zari, the product page says so in plain words.', 'sreesaanvika' ) ),
	array( 'scissors', __( 'Finished properly', 'sreesaanvika' ), __( 'Free fall and pico stitching on silk sarees, and blouse pieces cut with a generous margin so your tailor has room to work with.', 'sreesaanvika' ) ),
	array( 'gift', __( 'Packed like a gift', 'sreesaanvika' ), __( 'A cotton pouch, a card naming the weaver, and a note on how to care for the cloth — because it should last decades, not seasons.', 'sreesaanvika' ) ),
);

$ss_steps = array(
	array( __( 'We visit the cluster', 'sreesaanvika' ), __( 'Two or three trips a year to each weaving town. We see the looms, meet the families, and choose pieces in person rather than from a catalogue.', 'sreesaanvika' ) ),
	array( __( 'We pay on the spot', 'sreesaanvika' ), __( 'The weaver is paid when we take the cloth, at a price agreed with them. That is the whole reason this business exists.', 'sreesaanvika' ) ),
	array( __( 'We check every piece', 'sreesaanvika' ), __( 'Each saree is opened, measured, checked against the light for pulls, and photographed in daylight with no colour correction.', 'sreesaanvika' ) ),
	array( __( 'We finish it for you', 'sreesaanvika' ), __( 'Fall and pico where it is needed, pressed, folded in muslin, and packed with the weaver\'s card tucked inside.', 'sreesaanvika' ) ),
	array( __( 'It reaches your door', 'sreesaanvika' ), __( 'Dispatched within two days, tracked the whole way, and returnable for a week if it is not what you hoped for.', 'sreesaanvika' ) ),
);
?>

<div class="ss-container ss-section">

	<div class="ss-about__lede">
		<div class="ss-ornament" aria-hidden="true" style="margin-bottom:20px"><?php ss_the_icon( 'lotus', 22 ); ?></div>
		<?php
		// The page editor wins; this is what shows before anyone writes anything.
		$ss_has_content = false;

		while ( have_posts() ) :
			the_post();

			if ( trim( get_the_content() ) ) {
				$ss_has_content = true;
				the_content();
			}
		endwhile;

		if ( ! $ss_has_content ) :
			?>
			<p><?php esc_html_e( 'A saree should carry the name of the person who made it.', 'sreesaanvika' ); ?></p>
			<p>
				<?php esc_html_e( 'Sree Saanvika began at a single loom in Kanchipuram, with a frustration that would not go away: the weaver who had spent six months on a saree was seeing a fraction of what it sold for in a city showroom. So we started buying directly, paying upfront, and putting the weaver\'s name on the label.', 'sreesaanvika' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="ss-about__stats">
		<?php foreach ( $ss_stats as $ss_stat ) : ?>
			<div class="ss-about__stat">
				<b><?php echo esc_html( $ss_stat[0] ); ?></b>
				<span><?php echo esc_html( $ss_stat[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<section class="ss-about__split ss-reveal">
		<div class="ss-about__media">
			<?php if ( $ss_story_img ) : ?>
				<img src="<?php echo esc_url( $ss_story_img ); ?>" alt="<?php esc_attr_e( 'A weaver at the loom', 'sreesaanvika' ); ?>" loading="lazy" />
			<?php endif; ?>
		</div>

		<div class="ss-about__text">
			<h2><?php echo ss_kses( __( 'What <em>handloom</em> actually means', 'sreesaanvika' ) ); ?></h2>
			<p><?php esc_html_e( 'It means a person sat at a loom and threw a shuttle by hand, thousands of times, for weeks. It means the selvedge is slightly uneven, the dye lot varies a little from the last one, and no two pieces are quite identical.', 'sreesaanvika' ); ?></p>
			<p><?php esc_html_e( 'Those are not flaws to apologise for. They are the difference between cloth that was made and cloth that was manufactured — and they are the reason a good saree outlives the person who bought it.', 'sreesaanvika' ); ?></p>

			<div class="ss-about__signature">
				<?php esc_html_e( 'Woven by hand. Priced by honesty.', 'sreesaanvika' ); ?>
				<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</div>
		</div>
	</section>

	<section class="ss-reveal" style="margin-bottom:clamp(46px,7vw,88px)">
		<?php
		ss_section_head(
			__( 'Where the cloth comes from', 'sreesaanvika' ),
			__( 'Six <em>Clusters</em>', 'sreesaanvika' ),
			__( 'We work with the same families year after year, in the towns their craft is named after.', 'sreesaanvika' )
		);
		?>

		<div class="ss-about__clusters">
			<?php foreach ( $ss_clusters as $ss_cluster ) : ?>
				<article class="ss-cluster">
					<div class="ss-cluster__place">
						<?php ss_the_icon( 'pin', 15 ); ?>
						<?php echo esc_html( $ss_cluster[0] ); ?>
					</div>
					<h3><?php echo esc_html( $ss_cluster[1] ); ?></h3>
					<p><?php echo esc_html( $ss_cluster[2] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="ss-about__split ss-about__split--flip ss-reveal">
		<div class="ss-about__media">
			<?php if ( $ss_second_img ) : ?>
				<img src="<?php echo esc_url( $ss_second_img ); ?>" alt="<?php esc_attr_e( 'Detail of a woven border', 'sreesaanvika' ); ?>" loading="lazy" />
			<?php endif; ?>
		</div>

		<div class="ss-about__text">
			<h2><?php echo ss_kses( __( 'Why we skip the <em>middleman</em>', 'sreesaanvika' ) ); ?></h2>
			<p><?php esc_html_e( 'A saree usually passes through an agent, a wholesaler and a retailer before it reaches you. Each one takes a margin, and the weaver — the only person who actually made anything — takes the smallest one.', 'sreesaanvika' ); ?></p>
			<p><?php esc_html_e( 'We buy at the loom and sell to you. The weaver earns several times more per piece, and you pay less than you would in a showroom for the same cloth. Nobody has to lose for that maths to work; there were simply too many hands in the middle.', 'sreesaanvika' ); ?></p>
		</div>
	</section>

	<section class="ss-reveal" style="margin-bottom:clamp(46px,7vw,88px)">
		<?php ss_section_head( __( 'What we stand for', 'sreesaanvika' ), __( 'Our <em>Promise</em>', 'sreesaanvika' ) ); ?>

		<div class="ss-about__values">
			<?php foreach ( $ss_values as $ss_value ) : ?>
				<article class="ss-value">
					<span class="ss-value__icon"><?php ss_the_icon( $ss_value[0], 22 ); ?></span>
					<h3><?php echo esc_html( $ss_value[1] ); ?></h3>
					<p><?php echo esc_html( $ss_value[2] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="ss-reveal" style="margin-bottom:clamp(46px,7vw,88px)">
		<?php
		ss_section_head(
			__( 'From loom to doorstep', 'sreesaanvika' ),
			__( 'How a Piece <em>Reaches You</em>', 'sreesaanvika' )
		);
		?>

		<div class="ss-about__steps">
			<?php foreach ( $ss_steps as $ss_step ) : ?>
				<div class="ss-step-row">
					<div class="ss-step-row__num" aria-hidden="true"></div>
					<div>
						<h3><?php echo esc_html( $ss_step[0] ); ?></h3>
						<p><?php echo esc_html( $ss_step[1] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="ss-about__cta ss-reveal">
		<div class="ss-ornament" aria-hidden="true" style="margin-bottom:16px"><?php ss_the_icon( 'paisley', 22 ); ?></div>
		<h2><?php echo ss_kses( __( 'Come and see the <em>Difference</em>', 'sreesaanvika' ) ); ?></h2>
		<p><?php esc_html_e( 'Every piece on the site was chosen in person, from a loom we have stood beside. Have a look.', 'sreesaanvika' ); ?></p>

		<div class="ss-about__cta-actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="ss-btn ss-btn--lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					<?php esc_html_e( 'Shop the collection', 'sreesaanvika' ); ?>
					<?php ss_the_icon( 'arrow-right', 16 ); ?>
				</a>
			<?php endif; ?>

			<a class="ss-btn ss-btn--ghost ss-btn--lg" href="<?php echo esc_url( ss_page_url( 'contact' ) ); ?>">
				<?php esc_html_e( 'Talk to us', 'sreesaanvika' ); ?>
			</a>
		</div>
	</section>
</div>

<?php
get_footer();
