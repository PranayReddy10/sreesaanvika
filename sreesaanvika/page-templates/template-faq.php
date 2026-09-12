<?php
/**
 * Template Name: FAQ
 *
 * @package SreeSaanvika
 */

defined( 'ABSPATH' ) || exit;

get_header();

ss_page_header( get_the_title(), __( 'Sizes, shipping, returns and how to care for a handloom.', 'sreesaanvika' ) );

$ss_groups = array(
	__( 'Orders & shipping', 'sreesaanvika' ) => array(
		array( __( 'How long does delivery take?', 'sreesaanvika' ), __( 'Metro cities receive orders in 2–4 business days, and the rest of India in 4–7. You will get a tracking link by SMS and email the moment your parcel leaves our studio.', 'sreesaanvika' ) ),
		array( __( 'Is shipping free?', 'sreesaanvika' ), __( 'Shipping is free across India on orders above ₹2,999. Below that a flat ₹99 applies. International rates are calculated at checkout.', 'sreesaanvika' ) ),
		array( __( 'Do you offer cash on delivery?', 'sreesaanvika' ), __( 'Yes, on orders up to ₹15,000 in serviceable PIN codes. Enter your PIN code on any product page to check.', 'sreesaanvika' ) ),
		array( __( 'Can I change my address after ordering?', 'sreesaanvika' ), __( 'Message us within 12 hours of placing the order and we will update it before the parcel is handed over to the courier.', 'sreesaanvika' ) ),
	),
	__( 'Returns & exchanges', 'sreesaanvika' ) => array(
		array( __( 'What is your return window?', 'sreesaanvika' ), __( 'Seven days from delivery on unworn pieces with the tags intact. Free reverse pickup wherever our courier reaches.', 'sreesaanvika' ) ),
		array( __( 'How long do refunds take?', 'sreesaanvika' ), __( 'Once the piece reaches us and passes a quick check, refunds are issued within 5–7 business days to the original payment method.', 'sreesaanvika' ) ),
		array( __( 'What cannot be returned?', 'sreesaanvika' ), __( 'Custom-stitched blouses, altered garments and pierced jewellery are final sale for hygiene and fit reasons.', 'sreesaanvika' ) ),
	),
	__( 'Product & authenticity', 'sreesaanvika' ) => array(
		array( __( 'Is the zari real?', 'sreesaanvika' ), __( 'On every saree listed as pure zari, yes — tested silver-gilt thread. Where we use tested or imitation zari, the product page and the label say so plainly.', 'sreesaanvika' ) ),
		array( __( 'Will the colour match the photos?', 'sreesaanvika' ), __( 'We shoot in daylight with no colour correction, but screens differ and handloom dye lots vary slightly. A small variation is the mark of a hand-dyed weave, not a defect.', 'sreesaanvika' ) ),
		array( __( 'Do sarees come with a blouse piece?', 'sreesaanvika' ), __( 'Most come with an unstitched blouse piece of 0.8 metres. Nine-yard sarees do not. Each product page lists exactly what is included.', 'sreesaanvika' ) ),
		array( __( 'Do you do fall and pico?', 'sreesaanvika' ), __( 'Free on all silk sarees. Choose the option at checkout and add two days to the delivery estimate.', 'sreesaanvika' ) ),
	),
	__( 'Account & payment', 'sreesaanvika' ) => array(
		array( __( 'Which payment methods do you accept?', 'sreesaanvika' ), __( 'UPI, all major credit and debit cards, netbanking, wallets, EMI on select cards, and cash on delivery.', 'sreesaanvika' ) ),
		array( __( 'Do I need an account to order?', 'sreesaanvika' ), __( 'No, guest checkout works fine. An account simply keeps your orders, addresses and wishlist in one place.', 'sreesaanvika' ) ),
	),
);
?>

<div class="ss-container ss-section">
	<div class="ss-layout">

		<div>
			<?php foreach ( $ss_groups as $ss_group => $ss_items ) : ?>
				<h2 style="font-size:1.5rem;margin-bottom:18px"><?php echo esc_html( $ss_group ); ?></h2>

				<div class="ss-filters" style="margin-bottom:36px">
					<?php foreach ( $ss_items as $ss_item ) : ?>
						<div class="ss-filter is-collapsed">
							<button type="button" class="ss-filter__head" aria-expanded="false">
								<?php echo esc_html( $ss_item[0] ); ?>
								<?php ss_the_icon( 'chevron-down', 14 ); ?>
							</button>

							<div class="ss-filter__body">
								<p style="color:var(--ss-text-soft);margin:0"><?php echo esc_html( $ss_item[1] ); ?></p>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>

			<?php
			while ( have_posts() ) :
				the_post();

				if ( trim( get_the_content() ) ) {
					echo '<div class="ss-entry">';
					the_content();
					echo '</div>';
				}
			endwhile;
			?>
		</div>

		<aside class="ss-sidebar">
			<div class="ss-widget">
				<h3 class="ss-widget__title"><?php esc_html_e( 'Still stuck?', 'sreesaanvika' ); ?></h3>
				<p style="color:var(--ss-muted);font-size:.92rem">
					<?php esc_html_e( 'Our team answers within a few hours, every day except Sunday.', 'sreesaanvika' ); ?>
				</p>
				<a class="ss-btn ss-btn--block" href="<?php echo esc_url( ss_page_url( 'contact' ) ); ?>">
					<?php esc_html_e( 'Contact us', 'sreesaanvika' ); ?>
				</a>
				<a class="ss-btn ss-btn--ghost ss-btn--block" style="margin-top:10px" href="<?php echo esc_url( ss_page_url( 'track' ) ); ?>">
					<?php esc_html_e( 'Track my order', 'sreesaanvika' ); ?>
				</a>
			</div>
		</aside>
	</div>
</div>

<?php
get_footer();
