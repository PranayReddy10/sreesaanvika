# Sree Saanvika — Website Feature Guide

**sreesaanvika.in** · Handloom sarees, jewellery and women's wear
Prepared for the Sree Saanvika team · Theme v1.5.0

---

## 1. At a glance

| | |
| --- | --- |
| **Platform** | WordPress + WooCommerce |
| **What is delivered** | 1 custom theme + 2 companion plugins, as importable ZIP files |
| **Homepage sections** | 16, each switchable and re-orderable |
| **Ready-made pages** | 12, created by one button |
| **Product page** | Custom gallery, per-colour photo sets, swatches, zoom, lightbox |
| **Offers** | Buy X get Y free · quantity breaks · Complete the look · free-shipping meter |
| **Delivery** | Delhivery, tracked end to end on the customer's own order page |
| **Editing** | Customizer for everything; Elementor optional for the homepage |
| **Mobile** | Designed for the phone first — no horizontal scroll at 390px anywhere |

### The three files

| File | What it is | Version |
| --- | --- | --- |
| `sreesaanvika.zip` | The theme — the whole shop front | 1.5.0 |
| `sreesaanvika-delivery.zip` | Courier tracking on every order | 1.1.0 |
| `sreesaanvika-offers.zip` | Offers, quantity breaks, Complete the look | 1.2.0 |

Install the theme under **Appearance → Themes → Add New → Upload Theme**, and each
plugin under **Plugins → Add New → Upload Plugin**.

---

## 2. The look

### Colours

The shop is dark by design — aubergine and antique gold, so silk photographs
glow against the background instead of competing with white.

| Role | Hex | Where it is used |
| --- | --- | --- |
| Aubergine (background) | `#140a12` | The page itself |
| Surface | `#21121d` | Cards, panels, the cart |
| Antique gold | `#d9a441` | Prices, buttons, headings, the logo |
| Gold light | `#f0d08a` | Gradient highlights |
| Kumkum maroon | `#7b1e3b` | Offer tickets, badges, accents |
| Marigold | `#e8952f` | Sale flags, secondary accents |
| Ivory text | `#f4eaee` | Body copy |

Every one of these is a colour picker in the Customizer. Four curated presets
are one click away: **Aubergine & Gold** (default), **Midnight Peacock**,
**Espresso & Copper**, and **Temple Ink & Emerald**.

### Typography

- **Playfair Display** for headings — a high-contrast serif that suits jewellery
  and silk.
- **Jost** for everything else — clean, modern, and readable at small sizes.
- **Cormorant Garamond** for editorial touches.
- Base size, heading font, corner rounding and page width are all Customizer
  settings.

### Logo and site icon

A gold medallion carrying the Sree Saanvika **S**, drawn as SVG so it stays
sharp from a browser tab to a billboard. Shipped as a full lockup, a medallion
on its own, and a complete favicon set (16px through 512px, plus an Apple
touch icon). Upload your own under Settings → General → Site Icon at any time
and yours takes over.

### The loading screen

A gold medallion curtain while a page loads, which comes back down when a
shopper follows a link — so moving around the shop feels like one piece rather
than a series of white flashes.

**Customizer → Sree Saanvika Options → Loading Screen** controls all of it: on
or off, how long it stays (2 seconds by default), whether it shows between
pages, whether a returning shopper sees it only once per visit, and the name
printed on it. It is built so it can never trap anybody — it clears itself even
with JavaScript switched off, and a shopper who has asked their device for
reduced motion gets it briefly and without the moving parts.

---

## 3. The homepage

Sixteen sections, each with its own on/off switch and its own place in the
order:

Hero slider · Trust strip · Category mosaic · Category rail · New arrivals ·
Saree edit · Jewellery edit · Offer banners · Deal of the day · Lookbook ·
Bestsellers · The story band · Reviews · Instagram grid · Journal · Newsletter

**The hero** is a three-slide slider with its own eyebrow, headline, body,
button and image per slide, auto-advance optional.

**Categories** are yours to choose — pick any number by hand, or let the theme
take the busiest. The mosaic packs neatly whether you show four categories or
fourteen.

**Product sections** show as many products as you like, with a *Load more*
button that fetches the next set without a page reload.

### Two ways to edit it

- **Keep the theme homepage.** Everything lives in the Customizer — hero slides,
  banners, which sections show and in what order. Nothing to install, and it
  stays fast.
- **Rebuild it in Elementor.** One button creates a real Elementor page holding
  the same sections, seeded with your current settings, so you can drag, drop
  and restyle visually. Every section is also available as an Elementor widget.

---

## 4. Shop and product pages

### The product gallery

- Thumbnail rail, hover-to-zoom, and a full-screen lightbox with keyboard and
  swipe support.
- **Per-colour photo sets.** A saree shot in green and in red is one product
  with two sets of photos. Pick a colour and the whole gallery switches — stage
  image, thumbnails, zoom layer and lightbox. Photos are read straight from
  each colour's variation, so there is nothing to attach twice.
- **Image swatches.** A colour with photos shows one instead of a flat circle,
  which is far easier to choose between two similar greens.

### Choosing and buying

- Colour swatches and size chips generated from the product's own attributes.
- Quantity stepper that stops at the real stock and says why, rather than
  silently doing nothing.
- The price follows the chosen variation **and** the quantity — three at ₹1,200
  reads ₹3,600, with the old price struck through and the discount percentage
  beside it.
- **Buy it now** goes straight to checkout; **Add to bag** adds without leaving
  the page.
- Delivery PIN-code check, size guide, low-stock meter, wishlist and compare.

### Browsing

Filter sidebar with categories, a dual-handle price slider, colour swatches,
fabric, occasion and rating. Grid or list view, sorting, quick view from the
grid, and product cards with a hover image, badges, stock meter and live colour
swatches.

---

## 5. Offers and promotions

All of it runs **without a promo code**. There is nothing for a shopper to
type, and nothing for them to miss.

### Buy 2 Get 1 Free — and anything like it

Pick the products an offer covers, by hand or by whole category, with an
exclusion list. When enough of them are in the cart, **the cheapest ones come
off the total on their own**.

> Three sarees at ₹3,999, ₹2,999 and ₹1,200 → the ₹1,200 one is free.
> Six sarees → two free. (Or one, if you would rather cap it.)

Buy 3 get 1 works the same way. So does 50% off the cheapest instead of free.

### Quantity breaks

The other shape of offer: **2 for 10% off, 3 for 15%, 5 for 20%**. Counted per
product, so it catches the shopper buying a pair of the *same* saree — which
the offer above deliberately does not. The breaks show as chips on the product
page, and a shopper always gets the best one their quantity earns.

### Complete the look

On **any product**, pick the pieces that go with it — the jhumkas for a saree,
the bangles, a matching blouse.

Under the product they read as **this piece + match + match**, each with a tick
box, a running total, and one button that puts the whole look in the bag.
Unticking a piece re-totals instantly. A piece already in the bag says so
rather than going in twice.

Give the pairing a percentage and it becomes a real offer — the matching pieces
are discounted whenever the product they were chosen for is in the same cart.
Tick *show this both ways* and the saree turns up beside the jewellery too.

The cart carries the same idea as **Complete your look** — a tile grid on a
desktop, and a compact list on a phone.

### Countdown and banners

Give an offer an end time and a countdown appears — hours, minutes, seconds —
on every product it covers and at the top of the cart. The cart banner keeps a
live count: *Add 1 more to get one free*, then *1 item free — you are saving
₹1,200.00*.

### Free-shipping meter

*Add ₹340 more for free shipping*, with a bar that fills and turns green on
**Free shipping unlocked**. It shows in the bag panel, at the top of the cart
and above checkout — the three places a shopper is deciding whether to add one
more thing. The figure comes from WooCommerce's own free-shipping rule where
one is set up, so there is only ever one number to maintain.

### How it holds together

- Offers and pairings never stack on the same line, and no line can go below
  zero.
- Everything is worked out on the server, so it holds with JavaScript off and
  cannot be applied twice by a page refresh.
- The offer is recorded on the order line, so months later it is clear why a
  saree went out at nothing.

---

## 6. Cart, checkout and account

- A cart designed for the theme — not WooCommerce's default blocks — with the
  free-shipping meter, a savings line, and clear marks on any discounted line.
- Three-step checkout indicator, styled fields, and a switch on the welcome
  screen to move between the theme's cart and WooCommerce's block version.
- Sign in and sign up on one styled page.
- My Account: orders, addresses, downloads, details — all in the theme's look.
- A slide-out bag panel from any page, with the free-shipping meter inside it.

---

## 7. Delivery and tracking

Built for a shop that uses **one courier — Delhivery** — and already sees every
order in the courier's own app. This puts the same information on the website,
so customers stop emailing to ask.

### What the shop sees

A **Delivery** panel on every order: status, consignment number, courier,
expected date, and a running history of every scan. A Delivery column on the
orders list, and bulk actions to mark a batch dispatched or delivered.

### What the customer sees

A progress line — **Order placed → Packed → Dispatched → In transit → Out for
delivery → Delivered** — on the thank-you page, in My Account, and under the
Track Your Order form. Tracking number, courier, expected date, and every scan
with its location and time.

They can also track without logging in: order number plus the email or phone
from the order.

### Three ways the status gets in

1. **Delhivery answers for itself.** Paste a Delhivery API token, pick an
   interval, and the shop asks them where every parcel still in flight is and
   moves the order along with nobody touching it. There is a **Check now**
   button too.
2. **A push from your delivery app.** A secure address the app can post each
   scan to, so the website says *Out for delivery* the moment the app does.
3. **The courier's CSV manifest.** Upload the two-column file Delhivery hands
   back after a pickup and every tracking number lands at once.

Delhivery's own wording is mapped onto the six stages above. Anything
unfamiliar is recorded as a note rather than guessed at.

### Told without being asked

Optional emails to the customer on **dispatch** (with the tracking link) and on
**delivery**, in the shop's own styling. Optionally mark the WooCommerce order
Completed when the parcel lands.

---

## 8. Pages and policies

One button on **Appearance → Sree Saanvika** creates all twelve pages and
builds a menu from your product categories. It never overwrites anything you
already have.

Compare · Wishlist · Sign In · Lookbook · Our Story · Contact · Track Your
Order · FAQs · Privacy Policy · Terms & Conditions · Shipping Policy · Return &
Refund Policy

### The legal pages are written, not blank

All four arrive with a full draft written for an Indian e-commerce business —
GST, cash on delivery, the Digital Personal Data Protection Act 2023, the
Consumer Protection (E-Commerce) Rules 2020, and a named grievance officer as
those rules require.

Your business name, GSTIN, address, email, phone, return window, shipping rates
and COD limit are typed **once** in the Customizer and flow into every
document. Each page gets a contents rail and a highlights strip so a customer
can find the answer without reading the whole thing.

---

## 9. Found on Google

- Proper page titles and descriptions, with sensible defaults everywhere.
- Open Graph and Twitter cards, so a shared link shows the product photo.
- **Structured data** for products (price, availability, rating, brand, GTIN),
  breadcrumbs, the organisation and the site search — the mark-up Google uses
  to show a price and stars directly in results.
- Cart, checkout, account and search pages are kept out of Google's index and
  out of the sitemap, where they only ever waste crawl budget.
- Defers entirely to Yoast, Rank Math or SEO Framework if you install one, so
  nothing is ever printed twice.

---

## 10. Running the shop

Everything is in the Customizer, under **Sree Saanvika Options** — eleven
sections:

Colours & Palette · Header & Top Bar · Hero Slider · Homepage Sections ·
Promo Banners · Shop & Product Page · Footer & Contact · Social Links ·
Policies & Legal · Loading Screen · Typography

Plus, on **Appearance → Sree Saanvika**: one-click setup, a cart/checkout style
switch, the Elementor homepage builder, a **Repair page templates** tool for
when a page loses its design, and shortcuts into the offers and delivery
screens.

Product-level panels sit on the product editor itself: **Colour galleries** and
**Complete the look**.

---

## 11. Under the bonnet

| | |
| --- | --- |
| Requires | WordPress 6.0+, PHP 7.4+, WooCommerce 7.0+ |
| Order storage | Works with WooCommerce High-Performance Order Storage |
| Page builder | Elementor optional, never required |
| Translation | Every string translatable; RTL stylesheet included |
| Accessibility | Keyboard navigation, screen-reader labels, reduced-motion support |
| Without JavaScript | Prices, discounts and the loading screen all still behave |
| Child theme | Supported and documented — your changes survive updates |

The theme ships **63 inline SVG icons** and **16 WooCommerce template
overrides**, so there are no icon fonts to load and no third-party UI kit to
keep up to date.

---

## 12. What you receive

- `sreesaanvika.zip` — the theme, v1.5.0
- `sreesaanvika-delivery.zip` — delivery tracking, v1.1.0
- `sreesaanvika-offers.zip` — offers and Complete the look, v1.2.0
- A full README inside the theme covering setup, every feature, and
  troubleshooting

---

### Sree Saanvika

18-3-490/1, Aliyabad, Near Phool Bagh, Chaman, Charminar, Falaknuma,
Hyderabad, Telangana 500053

**support@sreesaanvika.in** · **+91 73869 12300** · **sreesaanvika.in**
