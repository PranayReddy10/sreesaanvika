<?php

namespace App\Http\Controllers;

use App\Support\Shop;
use Illuminate\View\View;

/**
 * The pages that are words rather than sarees.
 *
 * Their text lives in Settings, so the shop can change what it promises about
 * returns without anybody opening a file.
 */
class PageController extends Controller
{
    /** slug => [heading, the settings key its text comes from] */
    private const PAGES = [
        'returns'  => ['Returns', 'returns'],
        'shipping' => ['Delivery', 'shipping_policy'],
        'terms'    => ['Terms', 'terms'],
        'privacy'  => ['Privacy', 'privacy'],
        'story'    => ['Our story', 'story'],
        'contact'  => ['Contact', null],
    ];

    public function __invoke(string $slug): View
    {
        abort_unless(isset(self::PAGES[$slug]), 404);

        [$heading, $key] = self::PAGES[$slug];

        return view('shop.page', [
            'slug'    => $slug,
            'heading' => $heading,
            'body'    => $key ? Shop::policy($key) : '',
        ]);
    }
}
