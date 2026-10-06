# Putting OJASVI on Hostinger

Written for Hostinger's shared hosting, which is what the shop runs on. It has
no Redis, no Node, no long-running processes and one cron entry, so everything
below is shaped around those four facts.

Allow an hour the first time. After that a deploy is five minutes.

---

## 1. Before you start

In hPanel:

- **Websites → Manage → Advanced → PHP Configuration**: set PHP to **8.3** or
  later, and turn on the extensions `bcmath`, `intl`, `zip`, `gd`, `pdo_mysql`
  and `mbstring`. Set `max_execution_time` to at least 120.
- While you are on that screen, raise **`upload_max_filesize`** and
  **`post_max_size`** to at least **64M** if you intend to upload films of
  sarees. The admin reads the real limit and tells you what it is, so if the
  upload box says "up to about 2 MB" this is the screen that fixes it. Films
  larger than the limit go on a bucket instead, and you paste the address.
- **Databases → MySQL Databases**: make a database and a user, and give the
  user every permission on it. Write down the database name, the user and the
  password — they are not shown again.
- **Emails**: make `care@ojasvidrapes.in` (or whatever you tell customers).
  You will need its password for sending.

---

## 2. Build the assets here, not there

Hostinger has no Node, so the stylesheet and scripts have to be built on your
own machine and uploaded with everything else.

```sh
npm install
npm run build          # writes public/build — commit it
php artisan ojasvi:fonts   # only if you change the fonts
```

`public/build` is deliberately **not** in `.gitignore`. If you forget this
step, the shop goes up with no styling at all.

---

## 3. Where the files go

Two arrangements work. Hostinger's cheaper plans give you one writable
directory — `public_html` — and no way to move the document root, so the
second is the one most shops end up using. It is fully supported.

### Everything inside public_html (what most Hostinger plans allow)

Clone or upload the repository so that `public_html` *is* the application:

```
domains/ojasvidrapes.in/public_html/
├── .htaccess          ← ships with the shop; this is what makes it work
├── app/
├── bootstrap/
├── config/
├── database/
├── public/            ← index.php lives in here
├── resources/
├── routes/
├── storage/
├── vendor/
├── artisan
└── .env
```

Nothing to edit. The `.htaccess` at the top sends every request into
`public/`, where Laravel's front controller is, and refuses `.env`,
`composer.json`, the logs and the whole of `app/`, `config/`, `vendor/` and
the rest. `git pull` keeps working exactly as it is.

It depends on `mod_rewrite`, which Hostinger has. If the shop answers **403**,
that file is missing or is being ignored — see the troubleshooting section.

### The application above the web root (better, where the plan allows it)

If you can move the document root, or put files outside `public_html`, this is
the stronger arrangement because the application is not merely refused, it is
not there at all:

```
/home/uXXXXXXX/
├── ojasvi/              ← the whole application, OUTSIDE public_html
│   ├── app/
│   ├── config/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   └── .env
└── domains/ojasvidrapes.in/public_html/   ← only what is in public/
    ├── build/
    ├── brand/
    ├── index.php
    └── .htaccess
```

Then edit `public_html/index.php` so its two `require` lines point at the
application. Use absolute paths — counting `../` wrong is the most common way
a Laravel site ends up serving its own `.env`:

```php
require '/home/uXXXXXXX/ojasvi/vendor/autoload.php';
$app = require_once '/home/uXXXXXXX/ojasvi/bootstrap/app.php';
```

Some plans also let you point the domain at a subdirectory, under
**Websites → Manage → Advanced**. Setting the root to `public_html/public`
gives you this arrangement with nothing moved and nothing edited.

### Either way, check it

Open `https://ojasvidrapes.in/.env` in a browser. You want a **403 or 404**.

If you can see the file — your database password, your Razorpay secret and
your `APP_KEY` — stop. Fix the layout, then change every one of those secrets,
because they have been readable by anybody who asked for them.

### Composer

If SSH is available (Business plans and up):

```sh
cd ~/ojasvi
composer install --no-dev --optimize-autoloader
```

`--no-dev` matters: the testing tools are not wanted on a live site and they
drag in packages the shop never runs.

If SSH is not available, run that command on your own machine and upload the
`vendor` folder with the rest. It is large but it only changes when
dependencies do.

**If Composer refuses with "Your lock file does not contain a compatible set
of packages"**, it is telling you the lock was built against a newer PHP than
the server runs. `composer.json` pins the resolution to PHP 8.3 so this does
not happen:

```json
"config": {
    "platform": {
        "php": "8.3.0"
    }
}
```

If you ever raise the server's PHP and want newer packages, change that number
to match and run `composer update` **on a machine running at least that PHP**,
then commit the new `composer.lock`. Never run `composer update` on a machine
with a different PHP from the server's and ship the result — that is exactly
how the lock and the server fall out of step. `composer install` is always
safe; it only reads the lock.

---

## 4. The .env

Copy `.env.example` to `.env` on the server and fill it in.

```ini
APP_NAME=OJASVI
APP_ENV=production
APP_DEBUG=false          # never true on a live shop
APP_URL=https://ojasvidrapes.in

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=uXXXXXXX_ojasvi
DB_USERNAME=uXXXXXXX_ojasvi
DB_PASSWORD=...

# Shared hosting has no Redis, and no daemon to run a worker.
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=care@ojasvidrapes.in
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=care@ojasvidrapes.in

RAZORPAY_KEY=rzp_test_...
RAZORPAY_SECRET=...
RAZORPAY_WEBHOOK_SECRET=...

ADMIN_EMAIL=you@ojasvidrapes.in
ADMIN_PASSWORD=something-long-and-not-this
```

`ADMIN_PASSWORD` must be a real password of at least 8 characters. Left blank,
seeding makes no admin at all and says so — which is deliberate: an account
with an empty password is one nobody can ever sign in to, because the login
form will not submit an empty field. If you would rather not keep the password
in `.env`, leave it out and make the account afterwards with
`php artisan ojasvi:admin` (below).

Then, over SSH or through hPanel's terminal:

```sh
cd ~/ojasvi
php artisan key:generate
php artisan migrate --force        # makes the tables — always first
php artisan db:seed --force        # puts the first row in: your admin account
php artisan storage:link
```

The order matters and only one way round works: migrating makes the tables,
seeding puts rows in them. Run `db:seed` first and it will tell you so rather
than failing with a SQL error.

If `migrate` says **"Base table or view not found"**, that is this mistake —
run `php artisan migrate --force` and then seed again.

### Adding an admin, or changing a password

```sh
php artisan ojasvi:admin
```

It asks for the email address and then the password, without echoing it. An
account with that address has its password replaced; if there is none, one is
made. It is the way back in when nobody can sign in, and it works whether or
not seeding was ever run.

To do it in one line — on your own machine, not a shared server, where the
shell keeps a history:

```sh
php artisan ojasvi:admin --email=you@ojasvidrapes.in --password=... --name="Your name"
```

**`storage:link` will not run on Hostinger.** It answers

```
Call to undefined function Illuminate\Filesystem\exec()
```

because the host disables PHP's `symlink()`, so Laravel falls back to `exec()`,
which is disabled too. Nothing is wrong with the shop; make the link from the
shell instead, where neither restriction applies:

```sh
cd ~/domains/ojasvidrapes.in/public_html/public
ln -s ../storage/app/public storage
```

Check it with `ls -la storage` — it should point at `../storage/app/public`.

Photographs and films are uploaded to `storage/app/public` and served through
that link. Without it, every product image is a broken square.

**If symlinks are forbidden altogether,** or the web server will not follow
one, skip them entirely. In `.env`:

```ini
SHOP_UPLOADS_IN_PUBLIC=true
```

Uploads then go straight to `public/uploads`, which needs no link. Move what
is already there and clear the cached config:

```sh
mkdir -p public/uploads
cp -a storage/app/public/. public/uploads/
php artisan config:clear && php artisan config:cache
```

The paths recorded against each saree are relative to whichever of the two is
in use, so nothing in the database changes — only the files move.

### Permissions

```sh
chmod -R 775 ~/ojasvi/storage ~/ojasvi/bootstrap/cache
```

---

## 5. The one cron entry

hPanel → **Advanced → Cron Jobs**. One entry, every minute:

```
* * * * * cd /home/uXXXXXXX/ojasvi && php artisan schedule:run >> /dev/null 2>&1
```

That single line does all of it. The scheduler runs:

- `ojasvi:release-unpaid` every fifteen minutes — gives back the stock behind
  orders that were started online and never paid for.
- `queue:work --stop-when-empty` every minute — sends the emails. There is no
  supervisor on shared hosting, so the queue is worked in short bursts
  instead; an email may take up to a minute to leave, which is fine.

If the cron entry is missing, the shop still sells but **no email is ever
sent** and abandoned orders hold their stock forever. Check it first when
something seems stuck.

---

## 6. Razorpay

In the Razorpay dashboard:

1. **Account & Settings → API Keys** — generate keys. Use the `rzp_test_` pair
   until you have put a real order through end to end.
2. **Account & Settings → Webhooks** — add one pointing at
   `https://ojasvidrapes.in/webhooks/razorpay`, with the events
   `payment.captured`, `payment.failed`, `order.paid` and `refund.processed`.
   Copy the webhook secret into `RAZORPAY_WEBHOOK_SECRET`.

The webhook matters more than it looks. A shopper whose phone dies on the
bank's page has still paid; the webhook is how the shop finds out. Without it,
orders will sit unpaid that were in fact paid for, and the release job will
cancel them.

To check it is working: place a test order, pay it, and look in the admin —
the order should say **Paid**. If it says unpaid, the webhook is not arriving.

---

## 6b. Delhivery (optional)

The shop works without this — the tracking number is typed in by hand under
**Mark as sent**, which is what most shops this size do. Set it up and the
booking, the number and the tracking happen by themselves.

In `.env`:

```ini
DELHIVERY_TOKEN=...
DELHIVERY_BASE=https://track.delhivery.com
# Exactly as your warehouse is named in the Delhivery panel. A booking is
# refused outright if this does not match, character for character.
DELHIVERY_PICKUP_NAME=OJASVI Hyderabad
```

Then, in the admin, an order gains a **Book with Delhivery** button. It gets a
tracking number, marks the order packed, and the hourly job follows the parcel
from then on: the customer is emailed when it is picked up, the order is
marked delivered when it arrives, and a cash order counts as paid at that
moment because that is when the money changed hands.

Nothing about the courier can stop an order being dealt with. If their API is
down, the button says so and tells you to book it in their panel and type the
number in — and everything else works exactly as before.

---

## 7. Going live

```sh
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Run these **after** the `.env` is final. If you change `.env` later, run
`php artisan config:clear` or the old values stay in force — this catches
everybody at least once.

Then, in hPanel, turn on the free SSL certificate and force HTTPS.

Last, in the admin at `/admin`:

- **Shop → Settings → Delivery & payment** — the free-delivery figure, the
  flat delivery charge, how long you take to post, and the two switches for
  how people may pay. The free-delivery figure is quoted all over the shop,
  so it must be the one checkout actually charges; it is.
- **Shop → Settings → The shop** — the name, the telephone number, and who
  else should be emailed when an order comes in. Everybody with an admin
  account is told anyway.
- **Shop → Delivery areas** — your pincodes and rates. Leave the catch-all
  zone (the one with no pincodes) **last**: a zone with no pincodes matches
  everything, so anything after it is never reached.
- **Catalogue → Sarees** — your pieces, their shades and their photographs.
  Each one has a **Films** tab: fifteen to thirty seconds of it being worn,
  held portrait, does more for a sale than another photograph. They play
  muted as the shopper scrolls to them, and she turns the sound on if she
  wants it.
- **Storefront → Films** — the reel along the bottom of the front page. A film
  can be in the reel, on a saree's page, or both.

  Two kinds, and the difference matters. A film you **upload** is played by the
  shop: it starts on its own, muted, as the shopper scrolls to it. An
  **Instagram reel** — paste the link straight from *Share → Copy link* — shows
  as a still with a play button, because Instagram does not let any website
  start its reels by itself. Nothing of Instagram's is fetched until she taps,
  which keeps the page quick and keeps Instagram out of the shop's traffic.

  For a reel, add a cover frame under *The frame to show first*: Instagram does
  not hand one out, and without it the still is the saree's own photograph or a
  plain panel.
- **Storefront → Front page** — the rows of the home page, in order.

---

## 8. Being found

None of this needs a developer. It is all in the admin, under **Shop →
Settings → Found on Google** and **Analytics**.

1. **Search Console.** Add the site at
   [search.google.com/search-console](https://search.google.com/search-console),
   choose the **HTML tag** method, and paste what it gives you into the Google
   Search Console box. Save, then go back and press Verify. Afterwards, submit
   your sitemap — the address is on that same screen, and it keeps itself up
   to date as you add sarees.

   Search Console is also where you see what people typed into Google before
   they found you, which is worth more than any other number you will see.

2. **Google Analytics.** Make a GA4 property at analytics.google.com, take the
   `G-XXXXXXXXXX` from Admin → Data streams, and paste it in. The shop reports
   views, adds to bag, checkouts and purchases by itself — there is nothing to
   configure in Analytics beyond turning on Enhanced Measurement if you want
   scroll depth too.

3. **Google Merchant Center** — the one worth the hour. Free listings put your
   sarees in the Shopping tab at no cost. Create an account, verify the domain
   (it is already verified if you did Search Console), then Products → Feeds →
   add a **scheduled fetch** pointing at the feed address shown in Settings.
   Set it to fetch daily. One entry per shade, so somebody searching for an
   indigo Kanjivaram is shown the indigo one.

4. **Meta.** The same feed works as a Meta catalogue: Commerce Manager →
   Catalogue → Data sources → Scheduled feed. That is what lets you tag a
   saree in an Instagram post. The pixel id goes in the Analytics tab and is
   what makes advertising measurable.

5. **Check the policy pages read the way you would say it.** Under
   **Storefront → Pages**. Returns, delivery, terms and privacy are written
   for you already and are true of the shop as built, but they are in our
   words. Razorpay reads them during approval, and so will your customers.
   Those six pages cannot be deleted or taken down, for that reason; any page
   you add yourself can be.

6. **Bring the figures back into the admin** (optional). Analysis can show
   visitors and what people typed into Google, beside the shop's own numbers.
   It needs a Google service account, because nobody is sitting at the admin
   when the figures are fetched:

   1. console.cloud.google.com → **IAM & Admin → Service accounts → Create**.
      No roles are needed on the project itself.
   2. On that account: **Keys → Add key → JSON**. Keep the file; Google will
      not show it again.
   3. Enable two APIs on the project: **Google Analytics Data API** and
      **Search Console API**.
   4. Analytics → **Admin → Property access management** → add the service
      account's address as a **Viewer**.
   5. Search Console → **Settings → Users and permissions** → add the same
      address.
   6. Paste the whole JSON file into **Settings → Analytics → Reading the
      figures back**, with the Analytics property number (a number, not the
      G- code) and the Search Console property spelled exactly as Search
      Console spells it — `sc-domain:ojasvidrapes.in` or
      `https://ojasvidrapes.in/`.

   The figures are kept for half an hour before being asked for again. Search
   Console's own numbers run about three days behind, so that panel stops
   three days ago and says so.

7. **Turn off "Hide the whole shop from search engines"** on the day you open,
   if you turned it on while building. Nothing else on this list matters until
   you do.

Give it a fortnight. A new domain is not ranked quickly however good the
markup is, and the shop's first visitors will come from Instagram and from
people you tell.

---

## Deploying a change afterwards

```sh
# on your own machine
npm run build
git add -A && git commit -m "..." && git push

# on the server
cd ~/ojasvi
git pull                       # or upload the changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Put the shop in maintenance mode first if a migration will take a while:

```sh
php artisan down --secret=some-long-string   # you can still browse via /some-long-string
php artisan up
```

---

## When something is wrong

**403 Forbidden on the home page.** The commonest first-deploy fault, and it
means the web server found no page to serve at the top of `public_html` —
Laravel's `index.php` is inside `public/`, not at the root.

Check, in this order:

1. Is `.htaccess` actually there? `ls -la ~/domains/ojasvidrapes.in/public_html/.htaccess`.
   An upload by FTP will silently skip dotfiles unless you turn on "show
   hidden files". If it is missing, pull again or upload it by hand.
2. Does `https://ojasvidrapes.in/public/` load the shop? If it does, the files
   are all fine and only the rewrite is not happening — which is the same
   answer: the `.htaccess` is missing or being ignored.
3. Still 403 with the file in place? `AllowOverride` may be off for the
   directory. Ask Hostinger support to enable `.htaccess` overrides, or point
   the domain at `public_html/public` under **Websites → Manage → Advanced**,
   which needs no rewriting at all.

**Every product photograph is a broken square, or 403.** `php artisan
storage:link` has not been run, or the symlink it makes is not being followed.
Run it, and if the images are still refused, add `Options +FollowSymLinks` at
the top of `public_html/.htaccess`.

**Photographs are broken squares, and you want to know why.** Run

```sh
php artisan ojasvi:photos
```

It says where uploads are kept, whether every file is actually there, whether
`public/storage` exists and can be read through, and — when the shop's own
side is right — the exact address to open in a browser so the web server can
say what it objects to. A 403 there is a permission or a symbolic link not
being followed; a 404 is an address.

 Look at **Storefront → Films** in the
admin: the Plays column says which ones are not working and why. Two things
cause it, and both look identical on the page.

- *The file is missing.* It was uploaded somewhere the shop is no longer
  looking — usually after the `storage` link was changed or
  `SHOP_UPLOADS_IN_PUBLIC` was turned on without moving what was already
  there. See step 4. Upload the film again and it will be put in the right
  place.
- *HEVC — most browsers cannot play it.* The film came off an iPhone, which
  records HEVC unless told otherwise. It plays on that phone and on almost
  nothing else. Export it as MP4 (H.264) and upload that; on the phone,
  Settings → Camera → Formats → **Most Compatible** fixes it for everything
  filmed from then on. New uploads in this format are now refused with that
  explanation, so this only affects films uploaded before.

Either way the page itself no longer shows black: a film that will not play
shows its still, or a plain panel, with no play button on it.

**A blank white page.** Look in `storage/logs/laravel-*.log`. Nine times in
ten it is a permission on `storage/`, or `APP_KEY` never generated.

**Images are broken squares.** The `storage` symlink is missing or points
somewhere wrong. See step 4.

**The shop has no styling.** `public/build` was not uploaded, or `npm run
build` was never run. See step 2.

**No emails.** Check the cron entry (step 5), then
`php artisan queue:work --once` by hand and read what it says. Hostinger needs
port 465 with SSL, not 587.

**Orders say unpaid after a successful payment.** The Razorpay webhook is not
reaching the shop. Check the URL in the dashboard and that
`RAZORPAY_WEBHOOK_SECRET` matches exactly.

**The admin password does not work.** Almost always `ADMIN_PASSWORD` was
blank in `.env` when `db:seed` ran, so the account was made with an empty
password and no password will open it. Set one:

```sh
php artisan ojasvi:admin
```

Give it the address in `ADMIN_EMAIL` and a new password. Then sign in at
`/admin`. (Seeding now refuses to make such an account, so this only affects
sites first seeded before that.)

**A setting changed in the admin has no effect.** `php artisan config:clear`,
then cache again.

**HTTP 429, a bare "This page isn't working".** Too many requests in too
short a time, refused by Hostinger's own limiter — not by the shop, which
never answers 429 anywhere. The empty page is the giveaway: the shop's own
errors are rendered pages.

It clears by itself in a minute or two. What matters is what spent the
requests:

- **An admin tab left open.** This was the shop's own fault until the
  dashboard stopped polling (one request every 5 seconds for the chart,
  another every 30 for a notification bell nothing filled — around 840 an
  hour from a tab nobody was looking at). If you are seeing 429 on a site
  that has not pulled that change, pull it.
- **A page asking for forty files.** `public/.htaccess` now tells browsers
  how long they may keep the stylesheet, the fonts and the photographs, so a
  second page view asks for almost nothing. If you have replaced that file
  with an older one, those headers go with it.
- **Something else on the account.** The limit is per address and per
  account, so another site, a backup plugin, or a crawler on the same hosting
  spends the same allowance.
- **You, with a loop.** A refresh held down, or a script polling the site.

If it keeps happening with none of those true, Hostinger support can say what
the limit is on your plan and what tripped it — they can see the refusals and
you cannot.
