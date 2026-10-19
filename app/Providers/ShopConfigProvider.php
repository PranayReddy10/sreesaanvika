<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

/**
 * The settings a shopkeeper can change without opening a file.
 *
 * Where the photographs are kept, how the shop sends email, and who takes its
 * payments. All three lived only in .env, which on shared hosting means a file
 * manager, a text editor and a line typed without a typo — and getting one
 * wrong means a shop that silently stops sending order confirmations, or one
 * that cannot be paid.
 *
 * What is set here wins over .env, and only when it is filled in: a shop that
 * has configured nothing in the admin behaves exactly as before. Applied at
 * boot, because Laravel builds its mailer and its disks on first use and both
 * come after this.
 */
class ShopConfigProvider extends ServiceProvider
{
    public function boot(): void
    {
        /*
         * Before the tables exist there is nothing to read, and this runs on
         * `migrate` as well as on a page. Any failure here must be silent and
         * harmless: the shop then runs on .env, which is what it did before
         * any of this existed.
         */
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }

            $this->applyStorage();
            $this->applyMail();
            $this->applyPayments();
        } catch (\Throwable) {
            // A shop with an unreachable database has larger problems, and
            // they are not this provider's to report.
        }
    }

    /** DigitalOcean Spaces, when the admin has been given the keys. */
    private function applyStorage(): void
    {
        if (Setting::get('storage_driver') !== 'spaces') {
            return;
        }

        $key = trim((string) Setting::get('spaces_key'));
        $secret = trim((string) Setting::get('spaces_secret'));
        $bucket = trim((string) Setting::get('spaces_bucket'));

        // Half-filled is not configured. Switching the disk over with no
        // credentials would make every photograph on the shop disappear.
        if ($key === '' || $secret === '' || $bucket === '') {
            return;
        }

        $region = trim((string) Setting::get('spaces_region')) ?: 'blr1';
        $url = rtrim(trim((string) Setting::get('spaces_url')), '/');

        config(['filesystems.disks.public' => [
            'driver'     => 's3',
            'key'        => $key,
            'secret'     => $secret,
            'region'     => $region,
            'bucket'     => $bucket,
            'endpoint'   => trim((string) Setting::get('spaces_endpoint'))
                ?: "https://{$region}.digitaloceanspaces.com",
            'url'        => $url ?: null,
            'visibility' => 'public',
            /*
             * Loud, both of them.
             *
             * A bucket that refuses a photograph is not a condition to carry on
             * through: with these off, Flysystem answers false, Filament shows
             * "failed to upload" and nothing is written anywhere — so the shop
             * has an upload that will not work and a log with nothing in it.
             * Nothing on the storefront does any reading over the wire (an
             * address is built from the path, not fetched), so this costs the
             * shopper nothing.
             */
            'throw'      => true,
            'report'     => true,
        ]]);

        /*
         * Half-finished uploads stay on this server.
         *
         * Livewire keeps a file FilePond is still processing on the default
         * disk, and the default disk here is `public` — the very one just
         * pointed at the Space. So switching storage over in the admin sent
         * every upload to DigitalOcean twice: once to a livewire-tmp folder
         * while the shopkeeper was still filling the form, and again when it
         * was saved. The first of those is the upload that fails, before
         * anything has been stored and with nothing to show for it but
         * "failed to upload".
         *
         * A shop that has set this itself is left alone.
         */
        config([
            'livewire.temporary_file_upload.disk' => config('livewire.temporary_file_upload.disk') ?: 'local',
        ]);
    }

    /**
     * The payment gateway's keys, when the admin has been given them.
     *
     * Each gateway is filled in on its own, and only a complete pair is used:
     * half a set of keys is not a gateway, and letting it through would turn a
     * mistyped secret into a checkout that opens a payment page which fails on
     * the shopper. Whatever is not filled in here is left to .env, so a shop
     * that has always kept its keys there carries on untouched.
     */
    private function applyPayments(): void
    {
        $razorpayKey = trim((string) Setting::get('razorpay_key'));
        $razorpaySecret = trim((string) Setting::get('razorpay_secret'));

        if ($razorpayKey !== '' && $razorpaySecret !== '') {
            config([
                'services.razorpay.key'    => $razorpayKey,
                'services.razorpay.secret' => $razorpaySecret,
            ]);
        }

        $webhook = trim((string) Setting::get('razorpay_webhook_secret'));

        // On its own: the webhook secret is a different string from the API
        // secret, and a shop may well set it weeks later.
        if ($webhook !== '') {
            config(['services.razorpay.webhook_secret' => $webhook]);
        }

        $appId = trim((string) Setting::get('cashfree_app_id'));
        $cashfreeSecret = trim((string) Setting::get('cashfree_secret'));

        if ($appId !== '' && $cashfreeSecret !== '') {
            config([
                'services.cashfree.app_id' => $appId,
                'services.cashfree.secret' => $cashfreeSecret,
            ]);
        }

        $mode = trim((string) Setting::get('cashfree_mode'));

        if ($mode !== '') {
            config(['services.cashfree.mode' => $mode === 'sandbox' ? 'sandbox' : 'production']);
        }
    }

    /** The shop's own mail server, when the admin has been given one. */
    private function applyMail(): void
    {
        $host = trim((string) Setting::get('mail_host'));

        if ($host === '') {
            return;
        }

        $encryption = trim((string) Setting::get('mail_encryption')) ?: 'ssl';
        $port = (int) Setting::get('mail_port') ?: ($encryption === 'tls' ? 587 : 465);

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => trim((string) Setting::get('mail_username')) ?: null,
            'mail.mailers.smtp.password' => (string) Setting::get('mail_password') ?: null,
            // Symfony's mailer wants null rather than "none" for a plain
            // connection.
            'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
            /*
             * Only SSL is encrypted from the first byte, and that is what
             * `smtps` means. TLS on 587 is a plain connection that is upgraded
             * with STARTTLS once the server says it can — which Symfony does by
             * itself — so asking for `smtps` there makes the shop's mail server
             * hang up on a TLS handshake it was not expecting.
             */
            'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
        ]);

        $from = trim((string) Setting::get('mail_from'));

        if ($from !== '') {
            config([
                'mail.from.address' => $from,
                'mail.from.name' => trim((string) Setting::get('mail_from_name'))
                    ?: (string) Setting::get('shop_name', config('mail.from.name')),
            ]);
        }
    }
}
