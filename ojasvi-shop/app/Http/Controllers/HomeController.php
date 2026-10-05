<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The front page, assembled from the rows the shop set up in the admin.
 *
 * Each row says what it is and how many pieces it wants; this fetches them.
 * Nothing about the order or the wording of the page is decided here, which is
 * what lets the shop rearrange its own front page.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sections = Section::visible()->with('slides')->get();

        // One query for the sarees every row might want, rather than one per
        // row — the front page is the page that must be quick.
        $wanted = $sections
            ->map(fn (Section $s) => (int) $s->setting('limit', 8))
            ->max() ?: 8;

        $pool = Product::published()
            ->with(['images', 'colourways'])
            ->latest('published_at')
            ->take(max(12, $wanted))
            ->get();

        $featured = $pool->where('is_featured', true)->values();

        // A shop that has marked nothing as featured still gets a front page.
        if ($featured->isEmpty()) {
            $featured = $pool->take(5)->values();
        }

        return view('shop.home', [
            'sections' => $sections,
            'pool'     => $pool,
            'featured' => $featured,
            'offers'   => Offer::live()->get(),
        ]);
    }
}
