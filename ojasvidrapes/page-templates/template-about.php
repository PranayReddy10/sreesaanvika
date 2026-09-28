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
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Six generations of looms, one honest price.', 'ojasvidrapes' ) );

$od_story_img = get_the_post_thumbnail_url( get_the_ID(), 'od-product-lg' );

if ( ! $od_story_img ) {
	$od_story_img = od_option( 'band_img' );
}

$od_second_img = od_option( 'promo1_img' );

if ( ! $od_second_img ) {
	$od_second_img = od_option( 'hero1_img' );
}

$od_stats = array(
	array( '6', __( 'Weaving clusters', 'ojasvidrapes' ) ),
	array( '340+', __( 'Artisan families', 'ojasvidrapes' ) ),
	array( '12k', __( 'Happy customers', 'ojasvidrapes' ) ),
	array( '0', __( 'Middlemen', 'ojasvidrapes' ) ),
);

$od_clusters = array(
	array( __( 'Tamil Nadu', 'ojasvidrapes' ), __( 'Kanchipuram', 'ojasvidrapes' ), __( 'Three-shuttle korvai silk with contrast borders, woven on pit looms. Eleven weeks for a bridal piece is normal here.', 'ojasvidrapes' ) ),
	array( __( 'Uttar Pradesh', 'ojasvidrapes' ), __( 'Banaras', 'ojasvidrapes' ), __( 'Katan silk with real zari butis, jangla and shikargah patterns drawn on graph paper before a single thread is thrown.', 'ojasvidrapes' ) ),
	array( __( 'Telangana', 'ojasvidrapes' ), __( 'Pochampally', 'ojasvidrapes' ), __( 'Double ikat, where the yarn is tie-dyed before weaving so the pattern appears as the cloth is made, not after.', 'ojasvidrapes' ) ),
	array( __( 'Madhya Pradesh', 'ojasvidrapes' ), __( 'Chanderi', 'ojasvidrapes' ), __( 'Feather-light cotton-silk with a glassy transparency that comes from never degumming the yarn.', 'ojasvidrapes' ) ),
	array( __( 'Bihar', 'ojasvidrapes' ), __( 'Bhagalpur', 'ojasvidrapes' ), __( 'Tussar silk in its natural gold, spun from cocoons collected after the moth has already left.', 'ojasvidrapes' ) ),
	array( __( 'Gujarat', 'ojasvidrapes' ), __( 'Bhuj', 'ojasvidrapes' ), __( 'Bandhani tied by hand, thousands of knots per saree, each one a fingertip pinch of cloth and a length of thread.', 'ojasvidrapes' ) ),
);

$od_values = array(
	array( 'leaf', __( 'Direct from the loom', 'ojasvidrapes' ), __( 'We buy straight from weaver families and pay upfront, before a piece is sold. No agents, no consignment, no waiting ninety days to be paid for three months of work.', 'ojasvidrapes' ) ),
	array( 'shield', __( 'Certified, or clearly labelled', 'ojasvidrapes' ), __( 'Pure silk carries a Silk Mark or Handloom Mark. Where a piece is blended, powerloom, or uses tested rather than pure zari, the product page says so in plain words.', 'ojasvidrapes' ) ),
	array( 'scissors', __( 'Finished properly', 'ojasvidrapes' ), __( 'Free fall and pico stitching on silk sarees, and blouse pieces cut with a generous margin so your tailor has room to work with.', 'ojasvidrapes' ) ),
	array( 'gift', __( 'Packed like a gift', 'ojasvidrapes' ), __( 'A cotton pouch, a card naming the weaver, and a note on how to care for the cloth — because it should last decades, not seasons.', 'ojasvidrapes' ) ),
);

$od_steps = array(
	array( __( 'We visit the cluster', 'ojasvidrapes' ), __( 'Two or three trips a year to each weaving town. We see the looms, meet the families, and choose pieces in person rather than from a catalogue.', 'ojasvidrapes' ) ),
	array( __( 'We pay on the spot', 'ojasvidrapes' ), __( 'The weaver is paid when we take the cloth, at a price agreed with them. That is the whole reason this business exists.', 'ojasvidrapes' ) ),
	array( __( 'We check every piece', 'ojasvidrapes' ), __( 'Each saree is opened, measured, checked against the light for pulls, and photographed in daylight with no colour correction.', 'ojasvidrapes' ) ),
	array( __( 'We finish it for you', 'ojasvidrapes' ), __( 'Fall and pico where it is needed, pressed, folded in muslin, and packed with the weaver\'s card tucked inside.', 'ojasvidrapes' ) ),
	array( __( 'It reaches your door', 'ojasvidrapes' ), __( 'Dispatched within two days, tracked the whole way, and returnable for a week if it is not what you hoped for.', 'ojasvidrapes' ) ),
);
?>

<div class="od-container od-section">

	<div class="od-about__lede">
		<div class="od-ornament" aria-hidden="true" style="margin-bottom:20px"><?php od_the_icon( 'lotus', 22 ); ?></div>
		<?php
		// The page editor wins; this is what shows before anyone writes anything.
		$od_has_content = false;

		while ( have_posts() ) :
			the_post();

			if ( trim( get_the_content() ) ) {
				$od_has_content = true;
				the_content();
			}
		endwhile;

		if ( ! $od_has_content ) :
			?>
			<p><?php esc_html_e( 'A saree should carry the name of the person who made it.', 'ojasvidrapes' ); ?></p>
			<p>
				<?php esc_html_e( 'Ojasvi Drapes began at a single loom in Kanchipuram, with a frustration that would not go away: the weaver who had spent six months on a saree was seeing a fraction of what it sold for in a city showroom. So we started buying directly, paying upfront, and putting the weaver\'s name on the label.', 'ojasvidrapes' ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="od-about__stats">
		<?php foreach ( $od_stats as $od_stat ) : ?>
			<div class="od-about__stat">
				<b><?php echo esc_html( $od_stat[0] ); ?></b>
				<span><?php echo esc_html( $od_stat[1] ); ?></span>
			</div>
		<?php endforeach; ?>
	</div>

	<section class="od-about__split od-reveal">
		<div class="od-about__media">
			<?php if ( $od_story_img ) : ?>
				<img src="<?php echo esc_url( $od_story_img ); ?>" alt="<?php esc_attr_e( 'A weaver at the loom', 'ojasvidrapes' ); ?>" loading="lazy" />
			<?php endif; ?>
		</div>

		<div class="od-about__text">
			<h2><?php echo od_kses( __( 'What <em>handloom</em> actually means', 'ojasvidrapes' ) ); ?></h2>
			<p><?php esc_html_e( 'It means a person sat at a loom and threw a shuttle by hand, thousands of times, for weeks. It means the selvedge is slightly uneven, the dye lot varies a little from the last one, and no two pieces are quite identical.', 'ojasvidrapes' ); ?></p>
			<p><?php esc_html_e( 'Those are not flaws to apologise for. They are the difference between cloth that was made and cloth that was manufactured — and they are the reason a good saree outlives the person who bought it.', 'ojasvidrapes' ); ?></p>

			<div class="od-about__signature">
				<?php esc_html_e( 'Woven by hand. Priced by honesty.', 'ojasvidrapes' ); ?>
				<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			</div>
		</div>
	</section>

	<section class="od-reveal" style="margin-bottom:clamp(46px,7vw,88px)">
		<?php
		od_section_head(
			__( 'Where the cloth comes from', 'ojasvidrapes' ),
			__( 'Six <em>Clusters</em>', 'ojasvidrapes' ),
			__( 'We work with the same families year after year, in the towns their craft is named after.', 'ojasvidrapes' )
		);
		?>

		<div class="od-about__clusters">
			<?php foreach ( $od_clusters as $od_cluster ) : ?>
				<article class="od-cluster">
					<div class="od-cluster__place">
						<?php od_the_icon( 'pin', 15 ); ?>
						<?php echo esc_html( $od_cluster[0] ); ?>
					</div>
					<h3><?php echo esc_html( $od_cluster[1] ); ?></h3>
					<p><?php echo esc_html( $od_cluster[2] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="od-about__split od-about__split--flip od-reveal">
		<div class="od-about__media">
			<?php if ( $od_second_img ) : ?>
				<img src="<?php echo esc_url( $od_second_img ); ?>" alt="<?php esc_attr_e( 'Detail of a woven border', 'ojasvidrapes' ); ?>" loading="lazy" />
			<?php endif; ?>
		</div>

		<div class="od-about__text">
			<h2><?php echo od_kses( __( 'Why we skip the <em>middleman</em>', 'ojasvidrapes' ) ); ?></h2>
			<p><?php esc_html_e( 'A saree usually passes through an agent, a wholesaler and a retailer before it reaches you. Each one takes a margin, and the weaver — the only person who actually made anything — takes the smallest one.', 'ojasvidrapes' ); ?></p>
			<p><?php esc_html_e( 'We buy at the loom and sell to you. The weaver earns several times more per piece, and you pay less than you would in a showroom for the same cloth. Nobody has to lose for that maths to work; there were simply too many hands in the middle.', 'ojasvidrapes' ); ?></p>
		</div>
	</section>

	<section class="od-reveal" style="margin-bottom:clamp(46px,7vw,88px)">
		<?php od_section_head( __( 'What we stand for', 'ojasvidrapes' ), __( 'Our <em>Promise</em>', 'ojasvidrapes' ) ); ?>

		<div class="od-about__values">
			<?php foreach ( $od_values as $od_value ) : ?>
				<article class="od-value">
					<span class="od-value__icon"><?php od_the_icon( $od_value[0], 22 ); ?></span>
					<h3><?php echo esc_html( $od_value[1] ); ?></h3>
					<p><?php echo esc_html( $od_value[2] ); ?></p>
				</article>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="od-reveal" style="margin-bottom:clamp(46px,7vw,88px)">
		<?php
		od_section_head(
			__( 'From loom to doorstep', 'ojasvidrapes' ),
			__( 'How a Piece <em>Reaches You</em>', 'ojasvidrapes' )
		);
		?>

		<div class="od-about__steps">
			<?php foreach ( $od_steps as $od_step ) : ?>
				<div class="od-step-row">
					<div class="od-step-row__num" aria-hidden="true"></div>
					<div>
						<h3><?php echo esc_html( $od_step[0] ); ?></h3>
						<p><?php echo esc_html( $od_step[1] ); ?></p>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</section>

	<section class="od-about__cta od-reveal">
		<div class="od-ornament" aria-hidden="true" style="margin-bottom:16px"><?php od_the_icon( 'paisley', 22 ); ?></div>
		<h2><?php echo od_kses( __( 'Come and see the <em>Difference</em>', 'ojasvidrapes' ) ); ?></h2>
		<p><?php esc_html_e( 'Every piece on the site was chosen in person, from a loom we have stood beside. Have a look.', 'ojasvidrapes' ); ?></p>

		<div class="od-about__cta-actions">
			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="od-btn od-btn--lg" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					<?php esc_html_e( 'Shop the collection', 'ojasvidrapes' ); ?>
					<?php od_the_icon( 'arrow-right', 16 ); ?>
				</a>
			<?php endif; ?>

			<a class="od-btn od-btn--ghost od-btn--lg" href="<?php echo esc_url( od_page_url( 'contact' ) ); ?>">
				<?php esc_html_e( 'Talk to us', 'ojasvidrapes' ); ?>
			</a>
		</div>
	</section>
</div>

<?php
get_footer();
