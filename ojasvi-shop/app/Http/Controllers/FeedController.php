<?php

namespace App\Http\Controllers;

use App\Models\Colourway;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Http\Response;

/**
 * The product feed.
 *
 * One file, read by both Google Merchant Center and Meta commerce, which is
 * what puts the shop's sarees in free Google Shopping listings and lets them
 * be tagged in an Instagram post. For a small shop this is the highest-value
 * marketing work there is, and it costs a query.
 *
 * One entry per shade, not per design: somebody searching for an indigo
 * Kanjivaram should be shown the indigo one, and a feed that offers them "two
 * shades available" is a feed that gets ignored.
 */
class FeedController extends Controller
{
    public function google(): Response
    {
        if (Seo::hiddenFromSearch()) {
            abort(404);
        }

        $products = Product::published()
            ->with(['images', 'colourways' => fn ($q) => $q->where('is_visible', true)->orderBy('position')])
            ->orderBy('id')
            ->get();

        $items = [];

        foreach ($products as $product) {
            if ($product->colourways->isEmpty()) {
                $items[] = $this->entry($product, null);

                continue;
            }

            foreach ($product->colourways as $shade) {
                $items[] = $this->entry($product, $shade);
            }
        }

        return response(
            view('seo.google-feed', ['items' => $items])->render(),
            200,
            ['Content-Type' => 'application/xml; charset=UTF-8'],
        );
    }

    private function entry(Product $product, ?Colourway $shade): array
    {
        $images = $product->imagesFor($shade);
        $stock = $product->stockFor($shade);

        return [
            // Stable and unique: Merchant Center keeps the history of a listing
            // against this, so it must not change when a saree is renamed.
            'id'          => $shade
                ? ($shade->sku ?: $product->id . '-' . $shade->id)
                : ($product->sku ?: (string) $product->id),
            'item_group'  => $product->sku ?: (string) $product->id,
            'title'       => $shade && $product->colourways->count() > 1
                ? "{$product->name} — {$shade->name}"
                : $product->name,
            'description' => trim(strip_tags((string) ($product->short_description ?: $product->description)))
                ?: $product->name,
            'link'        => route('product', $product->slug) . ($shade ? '?shade=' . urlencode($shade->name) : ''),
            'image'       => $images->first()?->url,
            'extra'       => $images->slice(1, 10)->map(fn ($i) => $i->url)->values()->all(),
            'price'       => number_format($product->fullPriceFor($shade), 2, '.', ''),
            'sale'        => $product->onSale($shade)
                ? number_format($product->priceFor($shade), 2, '.', '')
                : null,
            // Google will not list a product it cannot tell is buyable.
            'availability' => $product->canOrder(1, $shade)
                ? 'in_stock'
                : ($stock !== null && $stock <= 0 ? 'out_of_stock' : 'in_stock'),
            'colour'      => $shade?->name,
            'weight'      => $product->weight_g,
            'brand'       => \App\Support\Shop::name(),
            // Google's own taxonomy. 1604 is Apparel & Accessories > Clothing.
            'category'    => '1604',
            'type'        => 'Sarees',
        ];
    }
}
