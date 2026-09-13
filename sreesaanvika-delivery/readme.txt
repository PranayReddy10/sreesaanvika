=== Sree Saanvika Delivery ===
Contributors: sreesaanvika
Tags: woocommerce, shipping, tracking, delivery, india
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

One courier, tracked end to end. Records the tracking number and delivery
status against each WooCommerce order and shows the shopper where their parcel
is.

== Description ==

Built for a shop that uses a single courier and already sees every order in
that courier's own app. This puts the same information on the website, so a
customer can see it without emailing to ask.

* A Delivery panel on every order: status, consignment number, courier,
  expected date, and a running history.
* A progress line on the thank-you page, in My Account, and under the theme's
  Track Your Order form. Nothing to configure — it appears wherever
  WooCommerce shows an order.
* A REST endpoint your delivery app can push scans to, so "Out for delivery"
  appears on the website the moment it appears in the app.
* A CSV import for the manifest the courier hands back after a pickup, for
  when there is no app to push from.
* Delhivery: with an API token the shop checks on parcels itself, on a
  schedule, and moves the order along with nobody touching it.
* Optional emails to the customer on dispatch and on delivery.
* A Delivery column and two bulk actions on the orders list.

Works with WooCommerce's High-Performance Order Storage.

== Installation ==

1. Upload the plugin and activate it.
2. Go to WooCommerce → Delivery and choose your courier.
3. Copy the endpoint and key into your delivery app's webhook settings, or use
   WooCommerce → Import tracking to upload the courier's CSV manifest.

== Frequently Asked Questions ==

= How does my delivery app talk to the website? =

POST to `/wp-json/sreesaanvika-delivery/v1/shipment` with an `X-SSD-Key`
header. The body needs `order_number` (or `order_id`) and whatever changed —
`status`, `tracking`, `location`, `note`. Anything you leave out is left alone,
so a status push will not wipe a tracking number.

= What if my courier cannot send webhooks? =

Use WooCommerce → Import tracking. It takes a two-column CSV — order number and
consignment number — which is what most couriers give you after a pickup.

= Does it work with a theme other than Sree Saanvika? =

Yes. The panel picks up the theme's colours when they exist and falls back to
its own dark styling when they do not.

== Changelog ==

= 1.1.0 =
* Delhivery: paste an API token and the shop asks them where every parcel is on
  a schedule, with a Check now button.

= 1.0.0 =
* First release.
