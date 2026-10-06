<?php

namespace App\Support;

use App\Models\Setting;

/**
 * What the shop tells Google, and what it tells the shop's own analytics.
 *
 * Every id here is read from Settings rather than from .env, because the
 * person who sets up Search Console or a Meta pixel is the shop owner, at
 * eleven at night, on a phone — not a developer with a deploy at hand.
 *
 * Nothing is loaded when its id is blank. A shop that has not set up Google
 * Analytics must not pay for a script that does nothing.
 */
class Seo
{
    /* ------------------------------------------------------- how it reads */

    public static function titleSuffix(): string
    {
        return (string) (Setting::get('seo_title_suffix') ?: Shop::name());
    }

    /** The home page's own title — the single most valuable line on the site. */
    public static function homeTitle(): string
    {
        return (string) (Setting::get('seo_home_title')
            ?: Shop::name() . ' — ' . Shop::tagline());
    }

    public static function homeDescription(): string
    {
        return (string) (Setting::get('seo_home_description')
            ?: 'Handwoven silk and cotton sarees from ' . Shop::name()
               . '. Kanjivaram, Banarasi, Chanderi and more, each in one or two shades. '
               . 'Free delivery over ' . Shop::money(Shop::freeShippingFrom()) . ' across India.');
    }

    /** Appended to a saree's own description when it has none of its own. */
    public static function productDescription(string $name, string $short = ''): string
    {
        $line = trim($short) !== '' ? trim($short) : $name;

        return str($line)->limit(140, '')->trim()->toString()
            . ' — ' . Shop::name() . '. Free delivery over ' . Shop::money(Shop::freeShippingFrom()) . '.';
    }

    /* ------------------------------------------------- who the shop is to Google */

    public static function searchConsoleTag(): string
    {
        // Just the content attribute; the shop owner pastes what Google gave
        // them, and we are forgiving about whether they pasted the whole tag.
        $raw = trim((string) Setting::get('seo_google_verification'));

        if ($raw === '') {
            return '';
        }

        if (preg_match('/content=["\']([^"\']+)["\']/', $raw, $m)) {
            return $m[1];
        }

        return $raw;
    }

    public static function bingVerification(): string
    {
        return trim((string) Setting::get('seo_bing_verification'));
    }

    /* ------------------------------------------------------------ analytics */

    /** G-XXXXXXXXXX. Blank means no Google Analytics on any page. */
    public static function ga4(): string
    {
        $id = trim((string) Setting::get('analytics_ga4'));

        return preg_match('/^G-[A-Z0-9]{4,}$/i', $id) ? strtoupper($id) : '';
    }

    public static function metaPixel(): string
    {
        $id = trim((string) Setting::get('analytics_meta_pixel'));

        return preg_match('/^\d{6,}$/', $id) ? $id : '';
    }

    /** AW-XXXXXXXXX, for Google Ads conversion tracking. */
    public static function googleAds(): string
    {
        $id = trim((string) Setting::get('analytics_google_ads'));

        return preg_match('/^AW-[A-Z0-9]{4,}$/i', $id) ? strtoupper($id) : '';
    }

    public static function googleAdsPurchaseLabel(): string
    {
        return trim((string) Setting::get('analytics_google_ads_label'));
    }

    public static function anyAnalytics(): bool
    {
        return self::ga4() !== '' || self::metaPixel() !== '' || self::googleAds() !== '';
    }

    /* ------------------------------------------------------------- indexing */

    /**
     * Is the whole shop hidden from search engines?
     *
     * Deliberately a setting rather than APP_ENV: a shop being built in public
     * wants to be invisible until the day it opens, and that day is the owner's
     * to pick.
     */
    public static function hiddenFromSearch(): bool
    {
        return (bool) Setting::get('seo_hidden', false);
    }

    /**
     * The robots line for this page.
     *
     * The rule: index a page only if it is a page the shop would want somebody
     * to arrive on from Google. A bag, a checkout, somebody's account and a
     * narrowed listing are all real pages and none of them is that — and a
     * narrowed listing indexed separately competes with the listing itself for
     * the same words.
     */
    public static function robotsFor(string $pattern, bool $filtered = false): string
    {
        if (self::hiddenFromSearch()) {
            return 'noindex, nofollow';
        }

        $private = [
            'bag', 'checkout', 'checkout/*', 'order/*', 'account', 'account/*',
            'sign-in', 'join', 'track',
        ];

        foreach ($private as $hidden) {
            if (request()->is($hidden)) {
                return 'noindex, nofollow';
            }
        }

        if ($filtered) {
            // Followed but not indexed: the sarees behind a filter still want
            // to be found, the filtered page itself does not.
            return 'noindex, follow';
        }

        return 'index, follow, max-image-preview:large, max-snippet:-1';
    }

    /* ----------------------------------------------------------- structured */

    /**
     * Who the shop is, said once, on every page.
     *
     * This is what lets Google show the shop's own telephone number and
     * Instagram beside its name rather than guessing them.
     */
    public static function organisation(): array
    {
        $out = [
            '@context' => 'https://schema.org',
            '@type'    => 'Store',
            '@id'      => url('/') . '#shop',
            'name'     => Shop::name(),
            'url'      => url('/'),
            'logo'     => Shop::logo(),
            'image'    => Shop::logo(),
            'description' => self::homeDescription(),
            'currenciesAccepted' => 'INR',
            'paymentAccepted'    => Shop::codOn() ? 'UPI, Card, Net banking, Cash on delivery' : 'UPI, Card, Net banking',
        ];

        if ($phone = Shop::phone()) {
            $out['telephone'] = $phone;
        }

        if ($email = Shop::email()) {
            $out['email'] = $email;
        }

        if ($address = Shop::address()) {
            $out['address'] = [
                '@type'           => 'PostalAddress',
                'streetAddress'   => trim(str_replace("\n", ', ', $address)),
                'addressCountry'  => 'IN',
            ];
        }

        $social = array_values(Shop::social());

        if ($social !== []) {
            $out['sameAs'] = $social;
        }

        return $out;
    }

    /** The site itself, with its search box, so Google can offer one. */
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'WebSite',
            '@id'      => url('/') . '#site',
            'name'     => Shop::name(),
            'url'      => url('/'),
            'potentialAction' => [
                '@type'       => 'SearchAction',
                'target'      => [
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => route('shop') . '?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * @param  array<int, array{name: string, url: string|null}>  $crumbs
     */
    public static function breadcrumbs(array $crumbs): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => collect($crumbs)->values()->map(fn ($crumb, $i) => array_filter([
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => $crumb['name'],
                'item'     => $crumb['url'] ?? null,
            ]))->all(),
        ];
    }

    /**
     * Encoded so a stray angle bracket in a saree's description cannot close
     * the script tag it is sitting inside.
     */
    public static function json(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP,
        );
    }
}
