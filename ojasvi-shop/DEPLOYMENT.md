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
cd ojasvi-shop
npm install
npm run build          # writes public/build — commit it
php artisan ojasvi:fonts   # only if you change the fonts
```

`public/build` is deliberately **not** in `.gitignore`. If you forget this
step, the shop goes up with no styling at all.

---

## 3. Upload

The important part: **`public/` is the document root, and nothing else should
be reachable from the web.**

Upload so that the server looks like this:

```
/home/uXXXXXXX/
├── ojasvi/              ← the whole application, OUTSIDE public_html
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── artisan
│   └── .env
└── domains/ojasvidrapes.in/public_html/   ← only what is in public/
    ├── build/
    ├── brand/
    ├── fonts/
    ├── index.php
    └── .htaccess
```

Then edit `public_html/index.php` and point its two `require` lines at the
application folder:

```php
require __DIR__.'/../../../ojasvi/vendor/autoload.php';
$app = require_once __DIR__.'/../../../ojasvi/bootstrap/app.php';
```

Count the `../` carefully against your own paths. Getting this wrong is the
single most common way a Laravel site on shared hosting ends up serving its
own `.env` to the public.

If your plan will not let you put the application outside `public_html`, put it
in `public_html/ojasvi/` and add this to `public_html/.htaccess` **before**
anything else:

```apache
RewriteEngine On
RewriteRule ^ojasvi/(?!public/) - [F,L]
```

That is a second-best arrangement. Prefer the first.

### Composer

If SSH is available (Business plans and up):

```sh
cd ~/ojasvi
composer install --no-dev --optimize-autoloader
```

If it is not, run that command on your own machine and upload the `vendor`
folder with the rest. It is large but it only changes when dependencies do.

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

Then, over SSH or through hPanel's terminal:

```sh
cd ~/ojasvi
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force        # makes your admin account
php artisan storage:link
```

`storage:link` has to point at the real document root. If it refuses or the
symlink does not work on your plan, make the folder by hand instead:

```sh
ln -s ~/ojasvi/storage/app/public ~/domains/ojasvidrapes.in/public_html/storage
```

Photographs are uploaded to `storage/app/public` and served through that link.
Without it, every product image is a broken square.

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

- **Shop → Settings** — the name, the telephone number, the free-delivery
  figure, the bar across the top. The free-delivery figure is quoted all over
  the shop, so it must be the one checkout actually charges.
- **Shop → Delivery areas** — your pincodes and rates. Leave the catch-all
  zone (the one with no pincodes) **last**: a zone with no pincodes matches
  everything, so anything after it is never reached.
- **Catalogue → Sarees** — your pieces, their shades and their photographs.
- **Storefront → Front page** — the rows of the home page, in order.

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

**A setting changed in the admin has no effect.** `php artisan config:clear`,
then cache again.

**HTTP 429.** Too many requests in too short a time, from the host's own
limiter rather than from the shop. It passes; if it keeps happening, look at
what is making repeated requests — a tab left open on a page that polls, or a
plugin on another site sharing the account.
