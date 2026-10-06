<?php
/**
 * Courier presets.
 *
 * The shop uses one courier, so this is only here to save typing a tracking
 * URL and to get the name right on the customer's order page. Anything not
 * listed is handled by "Other", where the URL is typed in by hand.
 *
 * @package OjasviDrapesDelivery
 */

defined( 'ABSPATH' ) || exit;

/**
 * Known couriers: slug => name and tracking URL pattern.
 *
 * {tracking} is replaced with the consignment number.
 *
 * @return array<string,array{name:string,url:string}>
 */
function odd_couriers() {
	$couriers = array(
		'delhivery'  => array(
			'name' => 'Delhivery',
			'url'  => 'https://www.delhivery.com/track/package/{tracking}',
		),
		'bluedart'   => array(
			'name' => 'Blue Dart',
			'url'  => 'https://www.bluedart.com/web/guest/trackdartresult?trackFor=0&trackNo={tracking}',
		),
		'dtdc'       => array(
			'name' => 'DTDC',
			'url'  => 'https://www.dtdc.in/tracking/shipment-tracking.asp?strCnno={tracking}',
		),
		'xpressbees' => array(
			'name' => 'XpressBees',
			'url'  => 'https://www.xpressbees.com/shipment/tracking?awbNo={tracking}',
		),
		'ecom'       => array(
			'name' => 'Ecom Express',
			'url'  => 'https://ecomexpress.in/tracking/?awb_field={tracking}',
		),
		'shadowfax'  => array(
			'name' => 'Shadowfax',
			'url'  => 'https://track.shadowfax.in/#/consumer-tracking/{tracking}',
		),
		'ekart'      => array(
			'name' => 'Ekart',
			'url'  => 'https://ekartlogistics.com/shipmenttrack/{tracking}',
		),
		'shiprocket' => array(
			'name' => 'Shiprocket',
			'url'  => 'https://www.shiprocket.in/shipment-tracking/{tracking}',
		),
		'trackon'    => array(
			'name' => 'Trackon',
			'url'  => 'https://trackon.in/Tracking/{tracking}',
		),
		'indiapost'  => array(
			'name' => 'India Post',
			'url'  => 'https://www.indiapost.gov.in/_layouts/15/DOP.Portal.Tracking/TrackConsignment.aspx?consignment={tracking}',
		),
		'other'      => array(
			'name' => __( 'Other', 'ojasvidrapes-delivery' ),
			'url'  => '',
		),
	);

	return apply_filters( 'odd_couriers', $couriers );
}

/**
 * The delivery stages a parcel moves through.
 *
 * @return array<string,string> slug => label.
 */
function odd_statuses() {
	return apply_filters(
		'odd_statuses',
		array(
			'pending'    => __( 'Order placed', 'ojasvidrapes-delivery' ),
			'packed'     => __( 'Packed', 'ojasvidrapes-delivery' ),
			'dispatched' => __( 'Dispatched', 'ojasvidrapes-delivery' ),
			'transit'    => __( 'In transit', 'ojasvidrapes-delivery' ),
			'out'        => __( 'Out for delivery', 'ojasvidrapes-delivery' ),
			'delivered'  => __( 'Delivered', 'ojasvidrapes-delivery' ),
			'failed'     => __( 'Delivery attempt failed', 'ojasvidrapes-delivery' ),
			'returned'   => __( 'Returned to us', 'ojasvidrapes-delivery' ),
			'cancelled'  => __( 'Cancelled', 'ojasvidrapes-delivery' ),
		)
	);
}

/**
 * The stages shown as a progress line, in order.
 *
 * The unhappy endings — failed, returned, cancelled — are deliberately not on
 * the line; they replace it.
 *
 * @return string[]
 */
function odd_progress_stages() {
	return apply_filters( 'odd_progress_stages', array( 'pending', 'packed', 'dispatched', 'transit', 'out', 'delivered' ) );
}

/**
 * Is this one of the endings that stops the progress line?
 *
 * @param string $status Status slug.
 * @return bool
 */
function odd_is_unhappy( $status ) {
	return in_array( $status, array( 'failed', 'returned', 'cancelled' ), true );
}

/**
 * A readable label for a status slug.
 *
 * @param string $status Status slug.
 * @return string
 */
function odd_status_label( $status ) {
	$all = odd_statuses();

	return isset( $all[ $status ] ) ? $all[ $status ] : ucfirst( str_replace( '_', ' ', (string) $status ) );
}
