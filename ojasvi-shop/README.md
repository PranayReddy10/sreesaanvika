# OJASVI

The shop at [ojasvidrapes.in](https://ojasvidrapes.in): handwoven sarees, sold
one design at a time.

Laravel 13, MySQL, Filament for the admin, Livewire for the bag, Razorpay and
cash on delivery for the money. Built to run on Hostinger's shared hosting,
which has no Redis, no Node and no long-running processes — every choice below
that looks conservative is because of one of those three.

```sh
composer install
npm install && npm run build
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed                       # your admin account
php artisan db:seed --class=DemoSeeder    # a shop full of example sarees
php artisan serve
```

The admin is at `/admin`. The account it makes comes from `ADMIN_EMAIL` and
`ADMIN_PASSWORD` in `.env`.

To put it on a server, read [DEPLOYMENT.md](DEPLOYMENT.md).

---

## What the shop is

**There are no categories.** The shop sells sarees and nothing else, so a
category menu would be a menu of one. A saree is one design, in one or two
shades; fabric, weave, occasion and shade are facts about it, not a hierarchy
it sits in, so they narrow the listing as query parameters and never appear in
a web address. `Category` still exists in the database as *Collections* — a
way to point an offer at a group of designs — and is hidden from the shop by
default.

**A saree lives at `/saree/{slug}` and nowhere else.** One listing, at
`/sarees`. A path that promises a hierarchy the shop does not have is one that
breaks the day it changes.

---

## The decisions worth knowing before you change anything

**Every figure is worked out on the server, twice.** Once for the page, and
again from the products when the order is written. Nothing the browser sends
about price, discount or delivery is believed. A test posts a form claiming an
order is worth one rupee and checks it is ignored.

**An order is a record, not a view.** Each line keeps its own copy of the
name, the design code and the price, so a saree renamed or repriced next month
cannot rewrite what somebody bought today.

**Stock is taken when the order is written, not when the payment lands.** Two
shoppers must not both be sold the last Patola because one was slower through
the bank. The price of that is orders started and abandoned, so
`ojasvi:release-unpaid` gives their stock back after ninety minutes — never a
cash order, which is unpaid by design, and never a paid one.

**The webhook is the authority, not the browser.** A shopper whose phone dies
on the bank's page has still paid. `/webhooks/razorpay` can mark an order paid
on its own; both paths end in the same `markPaid()`, which does nothing the
second time.

**Adding to the bag answers a POST with a redirect, never a page.** The bag
itself is Livewire, so quantities change over the wire and the page is never
re-entered — there is nothing for the back button to re-submit and nothing for
the browser's cache to restore wrongly. The first build of this shop fought
that bug for a week. A test drives back and forward over it.

**Nothing about email may break a checkout.** The order is written and the
money is taken before any email is attempted, so a wrong SMTP password costs
the shop an email and never a sale.

**The shop makes no third-party request on a page view.** Fonts are fetched
once by `php artisan ojasvi:fonts` and served from our own domain; Filament's
default avatar, which asks ui-avatars.com on every admin page, is replaced by
one drawn here.

---

## Where things are

```
app/
├── Console/Commands/
│   ├── FetchFonts.php           ojasvi:fonts — self-host the typefaces
│   └── ReleaseUnpaidOrders.php  ojasvi:release-unpaid — give stock back
├── Filament/                    the admin: sarees, orders, offers, settings
├── Http/Controllers/            the shop a customer sees
├── Livewire/Bag.php             the bag
├── Models/
├── Services/
│   ├── CartService.php          every rule about what is in a bag
│   ├── OfferEngine.php          buy-two-get-one and quantity breaks; pure
│   ├── OrderService.php         turning a bag into an order
│   ├── OrderMailer.php          every email about an order
│   └── Payments/Razorpay.php    the gateway; the secret never leaves here
└── Support/Shop.php             what every page knows about the shop
```

`config/shop.php` holds the fallbacks. Anything the shop owner should be able
to change lives in **Settings** in the admin instead.

---

## The admin

| | |
|---|---|
| **Catalogue** | Sarees (with shades and a gallery per shade), Collections, Fabric & weave |
| **Selling** | Orders, Offers, Coupons |
| **Storefront** | Front page (the rows of the home page, reorderable), Reviews |
| **Shop** | Customers, Delivery areas, Mailing list, Analysis, Settings |

Orders can be read and moved along but never invented: an order typed into an
admin has no payment behind it and no stock taken for it.

The front page is rows in the database, so the shop can rearrange and retitle
its own home page. That is the lesson of the WordPress build this replaced,
where every change meant a developer.

---

## Being found, and knowing what happened

`/sitemap.xml`, `/robots.txt` and `/feed/google.xml` are built on request —
the catalogue is small enough that a cached file would only be one more thing
to go quietly stale. The feed is read by both Google Merchant Center and Meta
commerce, with one entry per shade.

Most of the SEO work is about keeping pages *out* of the index. A narrowed
listing is `noindex, follow` and points its canonical at the plain listing; a
saree is one page however many shades it has; the bag, the checkout and
anybody's account are `noindex, nofollow`.

Search Console, Analytics, Meta and Ads ids all live in **Settings**, because
the person pasting a verification tag at eleven at night is the shop owner.
Nothing is loaded until its id is set, and a nonsense id is ignored rather
than printed.

**Analysis** (`/admin/insights`) answers what Google Analytics cannot: which
saree is looked at four hundred times and bought twice, and what people
searched for and were shown nothing. For a twelve-design shop those two lists
are worth more than every chart in Analytics, and neither can be had without
the shop's own database. Searches are recorded without a user, an address or a
session — what the shop was asked for, never who asked.

---

## Tests

```sh
php artisan test
```

117 of them. Most of the storefront and admin ones do nothing cleverer than
open a page, which is the point: a Blade template or a Filament schema is only
checked when it renders, and Filament resolves closure arguments by name, so
a perfectly valid `fn (string $s) => ...` fails only when somebody opens the
page. The rest are about money — whether the shop can be made to give away a
saree, or to charge for one it cannot send.

The things worth reading first:

- `CheckoutTest` — the money path, including the webhook arriving twice.
- `OfferEngineTest` — the cheapest qualifying piece is the one given away.
- `CartServiceTest` — what happens when somebody asks for more than there is.
