<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * One saree.
 *
 * The page a shopper decides on, so everything they need in order to decide is
 * here and nothing else is: the photographs, the shades, what it costs, what
 * it is made of, when it will arrive, and what other people said.
 */
class ProductController extends Controller
{
    public function __invoke(Request $request, Product $product): View
    {
        abort_unless($product->status === 'published', 404);

        $product->load([
            'images',
            'colourways' => fn ($q) => $q->where('is_visible', true)->orderBy('position'),
            'attributeValues.attribute',
            'matches.images',
            'matches.colourways',
            'approvedReviews.user',
        ]);

        // Counted without touching updated_at: a view is not an edit, and a
        // shop sorting by "recently changed" should not see every page the
        // public looked at.
        DB::table('products')->where('id', $product->id)->increment('views');

        $chosen = $product->colourways->firstWhere('name', $request->query('shade'))
            ?? $product->colourways->first();

        $offers = Offer::live()->with(['products:id', 'categories:id'])->get()
            ->filter(fn (Offer $offer) => $offer->covers($product))
            ->values();

        $alsoLike = Product::published()
            ->whereKeyNot($product->id)
            ->whereHas('attributeValues', fn ($q) => $q->whereIn(
                'attribute_values.id',
                $product->attributeValues->pluck('id')
            ))
            ->with(['images', 'colourways'])
            ->inRandomOrder()
            ->take(4)
            ->get();

        return view('shop.product', [
            'product'  => $product,
            'chosen'   => $chosen,
            'offers'   => $offers,
            'alsoLike' => $alsoLike,
            'specs'    => $product->attributeValues
                ->groupBy(fn ($v) => $v->attribute?->name ?? 'Other')
                ->map(fn ($values) => $values->pluck('value')->implode(', ')),
        ]);
    }
}
