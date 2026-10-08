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
            ->newestFirst()
            ->take(max(12, $wanted))
            ->get();

        /*
         * Asked for by name rather than sieved out of the pool above.
         *
         * Ticking "Feature it" on a saree has to put it on the front page,
         * and filtering the newest dozen only did that for a saree that was
         * also among the newest dozen — so featuring anything older than that
         * appeared to do nothing at all.
         */
        $featured = Product::published()
            ->with(['images', 'colourways'])
            ->where('is_featured', true)
            ->newestFirst()
            ->take(max(5, $wanted))
            ->get();

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
