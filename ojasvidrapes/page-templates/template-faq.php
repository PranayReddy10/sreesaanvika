<?php
/**
 * Template Name: FAQ
 *
 * @package OjasviDrapes
 */

defined( 'ABSPATH' ) || exit;

get_header();

od_page_header( get_the_title(), __( 'Sizes, shipping, returns and how to care for a handloom.', 'ojasvidrapes' ) );

$od_groups = array(
	__( 'Orders & shipping', 'ojasvidrapes' ) => array(
		array( __( 'How long does delivery take?', 'ojasvidrapes' ), __( 'Metro cities receive orders in 2–4 business days, and the rest of India in 4–7. You will get a tracking link by SMS and email the moment your parcel leaves our studio.', 'ojasvidrapes' ) ),
		array( __( 'Is shipping free?', 'ojasvidrapes' ), __( 'Shipping is free across India on orders above ₹2,999. Below that a flat ₹99 applies. International rates are calculated at checkout.', 'ojasvidrapes' ) ),
		array( __( 'Do you offer cash on delivery?', 'ojasvidrapes' ), __( 'Yes, on orders up to ₹15,000 in serviceable PIN codes. Enter your PIN code on any product page to check.', 'ojasvidrapes' ) ),
		array( __( 'Can I change my address after ordering?', 'ojasvidrapes' ), __( 'Message us within 12 hours of placing the order and we will update it before the parcel is handed over to the courier.', 'ojasvidrapes' ) ),
	),
	__( 'Returns & exchanges', 'ojasvidrapes' ) => array(
		array( __( 'What is your return window?', 'ojasvidrapes' ), __( 'Seven days from delivery on unworn pieces with the tags intact. Free reverse pickup wherever our courier reaches.', 'ojasvidrapes' ) ),
		array( __( 'How long do refunds take?', 'ojasvidrapes' ), __( 'Once the piece reaches us and passes a quick check, refunds are issued within 5–7 business days to the original payment method.', 'ojasvidrapes' ) ),
		array( __( 'What cannot be returned?', 'ojasvidrapes' ), __( 'A saree that has been cut, stitched with a fall and pico, or had its blouse piece separated cannot be returned — the work cannot be undone.', 'ojasvidrapes' ) ),
	),
	__( 'Product & authenticity', 'ojasvidrapes' ) => array(
		array( __( 'Is the zari real?', 'ojasvidrapes' ), __( 'On every saree listed as pure zari, yes — tested silver-gilt thread. Where we use tested or imitation zari, the product page and the label say so plainly.', 'ojasvidrapes' ) ),
		array( __( 'Will the colour match the photos?', 'ojasvidrapes' ), __( 'We shoot in daylight with no colour correction, but screens differ and handloom dye lots vary slightly. A small variation is the mark of a hand-dyed weave, not a defect.', 'ojasvidrapes' ) ),
		array( __( 'Do sarees come with a blouse piece?', 'ojasvidrapes' ), __( 'Most come with an unstitched blouse piece of 0.8 metres. Nine-yard sarees do not. Each product page lists exactly what is included.', 'ojasvidrapes' ) ),
		array( __( 'Do you do fall and pico?', 'ojasvidrapes' ), __( 'Free on all silk sarees. Choose the option at checkout and add two days to the delivery estimate.', 'ojasvidrapes' ) ),
	),
	__( 'Account & payment', 'ojasvidrapes' ) => array(
		array( __( 'Which payment methods do you accept?', 'ojasvidrapes' ), __( 'UPI, all major credit and debit cards, netbanking, wallets, EMI on select cards, and cash on delivery.', 'ojasvidrapes' ) ),
		array( __( 'Do I need an account to order?', 'ojasvidrapes' ), __( 'No, guest checkout works fine. An account simply keeps your orders, addresses and wishlist in one place.', 'ojasvidrapes' ) ),
	),
);
?>

<div class="od-container od-section">
	<div class="od-layout">

		<div>
			<?php foreach ( $od_groups as $od_group => $od_items ) : ?>
				<h2 style="font-size:1.5rem;margin-bottom:18px"><?php echo esc_html( $od_group ); ?></h2>

				<div class="od-filters" style="margin-bottom:36px">
					<?php foreach ( $od_items as $od_item ) : ?>
						<div class="od-filter is-collapsed">
							<button type="button" class="od-filter__head" aria-expanded="false">
								<?php echo esc_html( $od_item[0] ); ?>
								<?php od_the_icon( 'chevron-down', 14 ); ?>
							</button>

							<div class="od-filter__body">
								<p style="color:var(--od-text-soft);margin:0"><?php echo esc_html( $od_item[1] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<?php
			while ( have_posts() ) :
				the_post();

				if ( trim( get_the_content() ) ) {
					echo '<div class="od-entry">';
					the_content();
					echo '</div>';
				}
			endwhile;
			?>
		</div>

		<aside class="od-sidebar">
			<div class="od-widget">
				<h3 class="od-widget__title"><?php esc_html_e( 'Still stuck?', 'ojasvidrapes' ); ?></h3>
				<p style="color:var(--od-muted);font-size:.92rem">
					<?php esc_html_e( 'Our team answers within a few hours, every day except Sunday.', 'ojasvidrapes' ); ?>
				</p>
				<a class="od-btn od-btn--block" href="<?php echo esc_url( od_page_url( 'contact' ) ); ?>">
					<?php esc_html_e( 'Contact us', 'ojasvidrapes' ); ?>
				</a>
				<a class="od-btn od-btn--ghost od-btn--block" style="margin-top:10px" href="<?php echo esc_url( od_page_url( 'track' ) ); ?>">
					<?php esc_html_e( 'Track my order', 'ojasvidrapes' ); ?>
				</a>
			</div>
		</aside>
	</div>
</div>

<?php
get_footer();
