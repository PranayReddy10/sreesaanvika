<?php

namespace App\Http\Controllers;

use App\Support\Policies;
use App\Support\Seo;
use App\Support\Shop;
use Illuminate\View\View;

/**
 * The pages that are words rather than sarees.
 *
 * Their text lives in Settings, so the shop can change what it promises about
 * returns without anybody opening a file. Where the shop has written nothing,
 * these fall back to wording that is true of the shop as built — an empty
 * returns page is not a neutral default, it is a payment gateway refusing the
 * merchant account.
 */
class PageController extends Controller
{
    /** slug => [heading, settings key, the wording to stand behind otherwise] */
    private const PAGES = [
        'returns'  => ['Returns & refunds', 'returns', [Policies::class, 'returns']],
        'shipping' => ['Delivery', 'shipping_policy', [Policies::class, 'shipping']],
        'terms'    => ['Terms', 'terms', [Policies::class, 'terms']],
        'privacy'  => ['Privacy', 'privacy', [Policies::class, 'privacy']],
        'story'    => ['Our story', 'story', [Policies::class, 'story']],
        'contact'  => ['Contact', null, null],
    ];

    /** The short line under each heading, and what search results show. */
    private const BLURBS = [
        'returns'  => 'Seven days to change your mind, and how to go about it.',
        'shipping' => 'What delivery costs, how long it takes and how to follow it.',
        'terms'    => 'The terms you agree to when you buy from us.',
        'privacy'  => 'What we collect, why, and how to have it deleted.',
        'story'    => 'Why there are a dozen sarees here and not a thousand.',
        'contact'  => 'Telephone, email and WhatsApp — a person answers.',
    ];

    public function __invoke(string $slug): View
    {
        abort_unless(isset(self::PAGES[$slug]), 404);

        [$heading, $key, $fallback] = self::PAGES[$slug];

        $written = $key ? trim(Shop::policy($key)) : '';
        $body = $written !== '' ? $written : ($fallback ? call_user_func($fallback) : '');

        return view('shop.page', [
            'slug'     => $slug,
            'heading'  => $heading,
            'blurb'    => self::BLURBS[$slug] ?? '',
            'body'     => $body,
            'standard' => $written === '' && $body !== '',
            'updated'  => \App\Models\Setting::query()->where('key', $key)->value('updated_at'),
        ]);
    }
}
