<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

/**
 * What every page needs to know about the shop.
 *
 * A thin reader over the settings table, so a template asks Shop::name() and
 * never has to know whether that was set in the admin or is still the default
 * from config.
 */
class Shop
{
    public static function name(): string
    {
        return (string) (Setting::get('shop_name') ?: 'OJASVI');
    }

    public static function tagline(): string
    {
        return (string) (Setting::get('tagline') ?: 'Handwoven sarees, chosen one at a time');
    }

    public static function email(): string
    {
        return (string) (Setting::get('email') ?: config('mail.from.address'));
    }

    public static function phone(): string
    {
        return (string) Setting::get('phone');
    }

    public static function whatsapp(): string
    {
        // Digits only: the wa.me link breaks on a space or a plus sign.
        return preg_replace('/\D/', '', (string) Setting::get('whatsapp')) ?? '';
    }

    /**
     * Everybody who should hear when an order comes in.
     *
     * Every admin account, plus any extra addresses typed into Settings. A
     * shop run by two sisters and an accountant needs all three to know, and
     * nobody should have to remember to forward it.
     *
     * @return array<int, string>
     */
    public static function orderRecipients(): array
    {
        $extra = preg_split('/[,;\s]+/', (string) Setting::get('order_emails')) ?: [];

        $admins = \App\Models\User::query()
            ->where('is_admin', true)
            ->whereNull('deleted_at')
            ->pluck('email')
            ->all();

        $all = array_merge($admins, $extra, [self::email()]);

        $clean = [];

        foreach ($all as $address) {
            $address = mb_strtolower(trim((string) $address));

            if ($address !== '' && filter_var($address, FILTER_VALIDATE_EMAIL)) {
                // Keyed, so the same address written three ways is one email.
                $clean[$address] = $address;
            }
        }

        return array_values($clean);
    }

    public static function address(): string
    {
        return (string) Setting::get('address');
    }

    public static function logo(): string
    {
        $uploaded = Setting::get('logo');

        return $uploaded
            ? Storage::disk('public')->url($uploaded)
            : asset('brand/logo-header.png');
    }

    public static function freeShippingFrom(): float
    {
        $value = Setting::get('free_shipping_from');

        return $value !== null && $value !== ''
            ? (float) $value
            : (float) config('shop.free_shipping_from');
    }

    public static function flatShipping(): float
    {
        $value = Setting::get('flat_rate');

        return $value !== null && $value !== ''
            ? (float) $value
            : (float) config('shop.flat_shipping');
    }

    public static function codFee(): float
    {
        $value = Setting::get('cod_fee');

        return $value !== null && $value !== '' ? (float) $value : (float) config('shop.cod_fee');
    }

    public static function codOn(): bool
    {
        $value = Setting::get('cod_on');

        // Unset means on: a shop that has never opened this screen still
        // wants cash on delivery, which is how most of India pays.
        return $value === null ? true : (bool) $value;
    }

    /**
     * Is online payment on?
     *
     * Both the switch and the keys have to be there. A shop that has turned it
     * on without keys would otherwise offer a payment page that cannot open.
     */
    public static function onlineOn(): bool
    {
        $value = Setting::get('online_on');

        return ($value === null || (bool) $value)
            && (bool) (config('services.razorpay.key') && config('services.razorpay.secret'));
    }

    /** Above this, cash on delivery is not offered. Null means no limit. */
    public static function codMax(): ?float
    {
        $value = Setting::get('cod_max');

        return $value === null || $value === '' || (float) $value <= 0 ? null : (float) $value;
    }

    public static function dispatchDays(): int
    {
        return (int) (Setting::get('dispatch_days') ?: 2);
    }

    /** @return array{on: bool, text: string, url: string} */
    public static function bar(): array
    {
        return [
            'on'   => (bool) Setting::get('bar_on', false),
            'text' => (string) Setting::get('bar_text'),
            'url'  => (string) Setting::get('bar_url'),
        ];
    }

    /** @return array<string, string> Only the ones that are filled in. */
    public static function social(): array
    {
        return array_filter([
            'Instagram' => (string) Setting::get('instagram'),
            'Facebook'  => (string) Setting::get('facebook'),
            'YouTube'   => (string) Setting::get('youtube'),
        ]);
    }

    /** ₹1,24,500 — Indian grouping, because that is who the shop sells to. */
    public static function money(float|int|string|null $amount, bool $paise = false): string
    {
        $amount = (float) $amount;
        $rounded = $paise ? number_format($amount, 2, '.', '') : (string) round($amount);

        [$whole, $fraction] = array_pad(explode('.', $rounded), 2, null);

        $negative = str_starts_with($whole, '-');
        $whole = ltrim($whole, '-');

        if (strlen($whole) > 3) {
            $last = substr($whole, -3);
            $rest = substr($whole, 0, -3);
            $whole = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest) . ',' . $last;
        }

        return ($negative ? '-₹' : '₹') . $whole . ($fraction !== null ? '.' . $fraction : '');
    }
}
