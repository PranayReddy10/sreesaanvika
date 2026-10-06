<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\View\View;

/**
 * The pages that are words rather than sarees.
 *
 * All of them live in the pages table and are written in the admin, under
 * Storefront → Pages. Six come with the shop and cannot be deleted — the
 * footer and the checkout link to them by name — and the shop may add as many
 * more as it likes.
 */
class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = Page::visible()->where('slug', $slug)->firstOrFail();

        return view('shop.page', [
            'slug'    => $page->slug,
            'heading' => $page->title,
            'blurb'   => (string) $page->blurb,
            'body'    => (string) $page->body,
            'updated' => $page->updated_at,
        ]);
    }
}
