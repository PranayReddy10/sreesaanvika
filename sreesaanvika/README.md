# Sree Saanvika

A dark-luxe WordPress + WooCommerce theme built for **sreesaanvika.in** — handloom
sarees, temple jewellery and festive dresses. Deep aubergine, antique gold and
marigold throughout: there is no white background anywhere in the theme.

---

## Installing

1. In WordPress go to **Appearance → Themes → Add New → Upload Theme**.
2. Choose `sreesaanvika.zip` and press **Install Now**, then **Activate**.
3. Install and activate **WooCommerce** if you have not already — the shop,
   cart, product pages, compare and wishlist all depend on it.
4. Go to **Appearance → Sree Saanvika** and press **Run one-click setup**.
   That creates the Compare, Wishlist, Sign In, Lookbook, Our Story, Contact
   and FAQs pages and builds a primary menu from your product categories.
   It never overwrites a page or menu you already have.
5. Open the **Customizer → Sree Saanvika Options** to set your hero slides,
   banners, colours, contact details and social links.

Requires WordPress 6.0+, PHP 7.4+ and WooCommerce 7.0+.

---

## What's in it

### Homepage
Sixteen sections, each of which can be switched off individually in
**Customizer → Sree Saanvika Options → Homepage — Sections**:

| Section | What it shows |
| --- | --- |
| Hero slider | Up to 3 slides, Ken Burns backgrounds, swipe on touch |
| Trust strip | Shipping, certification, returns, support |
| Category rail | Round gold-ringed category circles |
| Category mosaic | Asymmetric tile grid from your product categories |
| New arrivals | Newest products |
| Offer banners | Two configurable promo panels |
| Best sellers | Ordered by `total_sales` |
| Deal of the day | On-sale products with a live countdown |
| Saree spotlight | Products in the `sarees` category |
| Jewellery spotlight | Products in the `jewellery` category |
| Lookbook strip | Editorial image grid |
| Story band | Full-bleed parallax band |
| Reviews | Real WooCommerce reviews, with a curated fallback |
| Journal | Latest blog posts |
| Instagram grid | Recent media |
| Newsletter | AJAX sign-up, stored in an option or piped to your list plugin |

### Product detail page
- Custom gallery: vertical thumbnail rail, hover magnifier, full-screen
  lightbox with a filmstrip, keyboard arrows and swipe.
- Colour swatches and size chips generated from the product's attributes.
  The native WooCommerce `<select>` stays in the DOM (visually hidden) so
  validation, price updates and the no-JS fallback all keep working.
- Price block with the saving spelled out, an offers panel, a low-stock
  meter, a PIN-code delivery checker, trust badges and a share row.
- Tabs for Description, Specifications, Care & Handling, Shipping & Returns
  and Reviews (with a star-distribution breakdown).
- Sticky add-to-cart bar on mobile, and a slide-in size guide.

### Shop
- Grid and list views, remembered per visitor.
- Filter sidebar: categories, a dual-handle price slider, colour swatches,
  size chips, any other attribute and a rating filter. Registered widgets in
  the *Shop Filters Sidebar* replace the built-in set when present.
- Product cards: second-image hover swap, badges (sale %, new, bestseller,
  trending, sold out), swatches, stock meter, AJAX add to bag, and hover
  buttons for wishlist, compare and quick view.

### Other pages
- **Cart** — card-based rows, live quantity updates, coupon box, savings
  line, free-shipping meter and a sticky summary. Empty state shows your
  wishlist.
- **Checkout** — three-step indicator, two-column layout, dark payment box.
- **Sign in / Sign up** — split-screen page template with tabbed panes,
  password reveal, a strength meter and AJAX submission. The WooCommerce
  My Account login is styled to match.
- **Compare** — sticky-header table across price, rating, availability,
  fabric, colours, occasion, work, blouse, length, weight and wash care,
  plus a floating compare bar.
- **Wishlist** — cookie-backed for guests, user meta for members, merged
  automatically on login.
- **Track Order** (WooCommerce's order lookup, styled, with support details
  beside it), Lookbook, Our Story, Contact, FAQs, 404, search, blog, archive
  and single-post templates.

---

## Editing the homepage

There are two ways, and you pick one.

### A — keep the theme homepage (fastest)

The storefront homepage is assembled in PHP from Customizer options. Edit it at
**Appearance → Customize → Sree Saanvika Options**:

| What you want to change | Where |
| --- | --- |
| Hero slides — text, buttons, images, alignment | Homepage — Hero Slider |
| Which sections show, and how many products each | Homepage — Sections |
| The two offer banners, countdown, story band | Homepage — Offer Banners |
| Colours, fonts, corner rounding, page width | Colours & Palette, Typography |
| Announcement bar, brand tagline | Header & Top Bar |
| Address, phone, socials, Instagram handle | Footer |

Section order is fixed in this mode. Nothing extra loads, so it stays fast.

### B — rebuild it in Elementor (drag and drop)

**Appearance → Sree Saanvika → Build an Elementor copy of the homepage.**

That creates a real Elementor page holding the same sections in the same
order, seeded with your current Customizer values, so it looks identical the
moment you open it — then you can drag, drop, restyle and reorder freely.

A new page is always created; your current homepage is never overwritten. Tick
the box on that screen to make it the homepage straight away, or leave it
unticked, review the page, and switch later under **Settings → Reading**. To go
back to the theme homepage, set Settings → Reading back to your old page.

Every section is also available on its own, under the **Sree Saanvika**
category in the Elementor widget panel:

| Widget | What it is |
| --- | --- |
| Hero Slider | Full-bleed slides with eyebrow, gilded title, two buttons |
| Product Grid | Newest / best sellers / on sale / featured / top rated / random, optionally filtered to one category |
| Category Rail | Round gold-ringed category circles |
| Category Mosaic | The asymmetric tile grid |
| Offer Banner | One promo panel, with an optional countdown |
| Lookbook Strip | Editorial image grid, from a gallery or your products |
| Story Band | Full-bleed band with a centred message |
| Trust Strip | Shipping / returns / support icons |
| Testimonials | Written by hand, or pulled from WooCommerce reviews |
| Instagram Grid | From a gallery or your newest media |
| Newsletter | The AJAX sign-up form |
| Section Heading | The eyebrow + gilded title + lotus ornament block |

Two notes on using them:

- Put the **Hero Slider** and the **Story Band** in a section set to
  *Full Width* with *no gap* — both are designed to bleed edge to edge.
- Widgets output the bare component with no width wrapper of their own, so
  Elementor's section controls own the width and vertical spacing.

---

## Customizer reference

Everything lives under **Sree Saanvika Options**:

- **Colours & Palette** — seven colour pickers plus four curated presets
  (Aubergine & Gold, Midnight Peacock, Espresso & Copper, Temple Ink &
  Emerald). Values are emitted as CSS custom properties, so a change
  recolours the whole theme.
- **Header & Top Bar** — brand tagline, scrolling announcements, phone
  number, sticky header toggle.
- **Homepage — Hero Slider** — three slides with eyebrow, title (use `<em>`
  to gild a word), text, button, link, background image and alignment.
- **Homepage — Sections** — a switch per section, and products per section.
- **Homepage — Offer Banners** — two banners, the countdown end time and the
  story band.
- **Shop & Product Page** — columns, per page, sidebar, hover swap, swatches,
  quick view, wishlist, compare, compare limit, gallery choice, sticky buy
  bar, PIN checker, free-shipping threshold, low-stock threshold and the
  offer lines.
- **Footer** — about text, address, phone, email, hours, copyright, five
  social URLs and the Instagram handle.
- **Typography** — heading font, base size, corner rounding, content width.

---

## Tips

- Add `mega` as a CSS class on a top-level menu item (Appearance → Menus →
  Screen Options → CSS Classes) to turn its dropdown into a four-column mega
  menu. `hot` and `new` add a small flag to the item.
- Colour swatches read the product's **Color** / **Colour** / **Shade**
  attribute and map ~40 common Indian textile colour names to hex. To pin an
  exact shade, add a term meta named `ss_color` holding a hex value.
- Product images look best portrait at 3:4 — 1200 × 1600 or larger keeps the
  zoom sharp.
- Set a category image under **Products → Categories** to fill the homepage
  mosaic and the round rail.
- The newsletter form stores addresses in the `ss_newsletter_list` option.
  Hook `ss_newsletter_signup` (`do_action( 'ss_newsletter_signup', $email )`)
  to hand them to Mailchimp, Brevo or similar instead.
- Compare rows are filterable: `add_filter( 'ss_compare_rows', ... )`.

---

## Child theme

Overriding a template is the safe way to change markup. Create
`wp-content/themes/sreesaanvika-child/style.css`:

```css
/*
Theme Name: Sree Saanvika Child
Template: sreesaanvika
Version: 1.0.0
*/
```

…and a `functions.php`:

```php
<?php
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'ss-child', get_stylesheet_uri(), array( 'ss-main' ), '1.0.0' );
} );
```

Copy any file from the parent theme into the child at the same path to
override it — including anything under `woocommerce/`.

---

## Structure

```
sreesaanvika/
├── style.css                 Theme header + safety-net base styles
├── functions.php             Setup, enqueues, menus, widgets
├── rtl.css                   Right-to-left overrides
├── screenshot.png
├── assets/
│   ├── css/  main.css, shop.css, editor.css
│   ├── js/   theme.js, shop.js, customizer.js
│   └── images/ SVG placeholders
├── inc/
│   ├── helpers.php           Options, colour map, small utilities
│   ├── icons.php             Inline SVG icon set
│   ├── nav-walker.php        Desktop + drawer walkers
│   ├── customizer.php        All theme options
│   ├── dynamic-css.php       Options → CSS custom properties
│   ├── template-tags.php     Reusable markup helpers
│   ├── ajax.php              Quick view, search, cart, auth, newsletter
│   ├── compare-wishlist.php  List storage and the compare table data
│   ├── woocommerce.php       Shop integration and cart fragments
│   ├── demo-content.php      One-click page + menu setup
│   └── tgm-notice.php        Welcome screen and admin notices
├── template-parts/
│   ├── header/  drawer, search overlay, cart panel
│   ├── home/    the 16 homepage sections
│   └── shop/    filter sidebar
├── page-templates/           compare, wishlist, auth, lookbook, about,
│                             contact, faq
└── woocommerce/              Template overrides
```

---

## Troubleshooting

### The homepage sections show in the Customizer but not on the live site

Fixed in 1.0.1. `get_theme_mod()` does not know about the default registered on
a Customizer setting — it only returns the default handed to it. Inside the
Customizer preview WordPress filters `theme_mod_*` and returns the registered
default, so the hero, banners and footer details appeared there and rendered
empty everywhere else. All defaults now live in `inc/defaults.php`, which both
`ss_option()` and the Customizer read from.

If a section is still missing after updating:

- **It has no data yet.** Sections return early rather than render an empty
  block: the category mosaic and rail need product categories, "Deal of the
  day" needs at least one on-sale product, the Instagram grid needs six images
  in the media library, and the lookbook strip needs three products with
  featured images.
- **It is switched off.** Customizer → Sree Saanvika Options → Homepage —
  Sections.
- **A cache is serving the old page.** Purge your page cache and CDN. The
  Customizer preview always bypasses both, which is why it can look right while
  the live page does not.
- **The front page is built with Elementor.** Then Elementor owns the page and
  the theme sections step aside by design — see below.

### The product page columns look squeezed or off to one side

Fixed in 1.0.2. WooCommerce's `woocommerce-layout.css` floats the product
columns and pins them to `width: 48%` each. Against this theme's grid that
collapsed the gallery to roughly 300px and left a large empty gap beside it.
The theme now dequeues Woo's two layout stylesheets — it lays all of those
screens out itself — and keeps a defensive reset in case a plugin or a
combined-CSS cache reintroduces them. To keep Woo's layout instead:

```php
add_filter( 'ss_dequeue_woo_layout', '__return_false' );
```

Also fixed in 1.0.2: the shop stylesheet only loaded on shop screens, which
left the homepage product grids, the mini-cart and any Elementor product
widget unstyled. It now loads wherever WooCommerce is active.

### Form fields render as white boxes

Fixed in 1.0.5. **Elementor**, not WooCommerce, was the cause. Elementor's
Site Settings → Theme Style → Form Fields emits

```css
.elementor-kit-8 input:not([type="button"]):not([type="submit"]) { background-color: #FFFFFF; }
```

which loads after the theme and outranks a plain `input[type="text"]`. It sets
only background and colour, which is why the padding and the labels still
looked themed while the boxes went white. The theme now marks its field
background, colour and border important, scoped to real form controls, with
the focus and WooCommerce validation states marked the same way so they keep
working. You can also clear the colours under Elementor → Site Settings →
Theme Style → Form Fields; both routes work and they do not conflict.

### The checkout page still uses WooCommerce's own blocks

WooCommerce 8.3+ builds the Cart and Checkout pages out of **blocks** rather
than the old `[woocommerce_checkout]` shortcode. Blocks never load the theme's
`cart.php` / `form-checkout.php` templates and ship a light palette of their
own. You have two ways out, and 1.0.4 does both.

**Use the theme's own cart and checkout** — the free-shipping meter, the
savings line and the three-step indicator. One click at
**Appearance → Sree Saanvika → Cart & Checkout style**. It swaps the block for
the WooCommerce shortcode on both pages, saving the block markup first so the
same screen can switch you back.

**Or keep the blocks.** WooCommerce Blocks ships a full dark treatment behind a
`has-dark-controls` class on the block wrapper, normally toggled per block in
the editor as "Dark mode inputs". The theme declares
`add_theme_support( 'dark-editor-style' )` so new blocks default to it, and
adds the class at render time to blocks that already exist, so pages built
before the theme was installed are fixed without re-saving them. The theme
palette is layered on top. To opt out:

```php
add_filter( 'ss_woo_block_dark_controls', '__return_false' );
```

### A page overlaps itself on a phone

Fixed in 1.0.5. Contact, FAQ and Track Order set their sidebar width with an
inline `grid-template-columns`, and an inline style outranks any media query —
so those pages kept a 300–380px sidebar inside a 390px screen. The width is now
passed as a `--ss-aside` custom property, leaving the media query free to
collapse the grid to one column.

### A category page shows a blank band above the products on a phone

Fixed in 1.0.5. The inline shop-layout CSS made the filter sidebar sticky with
`.ss-shop-layout > .ss-shop-sidebar`, which outranked the `position: fixed`
that takes it out of the flow below 1024px. The panel stayed in the grid,
translated off-screen but still holding a full-width row. That sticky rule is
now inside a `min-width: 1025px` query.

### The trust strip runs off the screen on a phone

Fixed in 1.0.3, and only affected the Elementor version. An Elementor
responsive control with no per-device default applies its desktop value at
every width, so the four boxes stayed four across on a 390px screen. The
Trust Strip, Testimonials and Instagram widgets now ship tablet and mobile
defaults. If you had already placed one of those widgets, open it and set
**Columns** on the tablet and mobile tabs.

### A title shows the literal text `<em>`

Fixed in 1.0.3. Some widget defaults were run through `esc_html__()`, which
turned the `<em>` markers into visible text. New widgets are correct. A widget
already on your page keeps the old stored value — retype the title and the
gilding comes back.

### Elementor

The theme yields to Elementor wherever the builder is in charge:

- A page, post or front page laid out in Elementor renders through
  `the_content()` alone — no theme container, no article card, no storefront
  sections. `the_content()` runs unconditionally on those templates, which is
  what the editor's preview iframe needs in order to load.
- With Elementor Pro, `header` and `footer` are registered as Theme Builder
  locations. Build one and it replaces the theme's own. The theme keeps
  ownership of single, archive and every WooCommerce template.
- Inside the editor preview the sticky header and the fixed panels are pinned
  back into the normal flow so they stop covering the widgets you are editing.

**If you still get "Can't Edit? Enable Safe Mode":** that panel means the editor
preview did not finish loading, and the cause is usually the server rather than
the theme. Work through these in order:

1. Enable **Safe Mode** from that panel. If the editor then loads, the problem
   is a plugin or a server limit, not the theme — Elementor will say which.
2. Raise PHP limits: `memory_limit` 256M or more, `max_execution_time` 300,
   `max_input_vars` 3000. Elementor → System Info lists the current values.
3. Confirm the WordPress REST API is reachable — Tools → Site Health flags it
   when a security plugin, ModSecurity or a firewall rule is blocking
   `/wp-json/`.
4. Elementor → Tools → **Regenerate CSS & Data**, then hard-reload.
5. If your host serves the site through a proxy or CDN, bypass it for
   `/wp-admin/` and for URLs carrying `elementor-preview`.

---

## Licence

GNU General Public License v2 or later.
