<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The listing.
 *
 * There is one listing, because there is one kind of thing for sale. What a
 * shopper narrows by — fabric, weave, occasion, shade, price — are facts about
 * a saree, not a hierarchy it sits in, so they are query parameters and never
 * part of the path.
 */
class ShopController extends Controller
{
    private const SORTS = [
        'new'        => 'Newest first',
        'price-low'  => 'Price, low to high',
        'price-high' => 'Price, high to low',
        'popular'    => 'Most looked at',
    ];

    public function __invoke(Request $request): View
    {
        $filters = Attribute::where('is_filterable', true)
            ->with(['values' => fn ($q) => $q->orderBy('position')])
            ->orderBy('position')
            ->get();

        $chosen = [];

        foreach ($filters as $attribute) {
            $value = $request->query($attribute->slug);

            // Checkboxes arrive as an array; the sort form passes the same
            // choices back as a comma list, so both have to be understood or
            // changing the sort silently drops the filters.
            $values = is_array($value)
                ? $value
                : ($value === null || $value === '' ? [] : explode(',', (string) $value));

            $values = array_values(array_filter(array_map('trim', $values), fn ($v) => $v !== ''));

            if ($values !== []) {
                $chosen[$attribute->slug] = $values;
            }
        }

        $query = Product::published()->with(['images', 'colourways']);

        $this->search($query, (string) $request->query('q', ''));
        $this->narrow($query, $filters, $chosen);
        $this->price($query, $request);

        if ($request->query('on') === 'offer') {
            // On offer means a struck-through price today, not a sale that
            // ended last month.
            $query->whereNotNull('sale_price')
                ->where(fn ($q) => $q->whereNull('sale_ends_at')->orWhere('sale_ends_at', '>=', now()));
        }

        $this->sort($query, (string) $request->query('sort', ''));

        $products = $query->paginate(24)->withQueryString();

        return view('shop.index', [
            'products' => $products,
            'filters'  => $filters,
            'chosen'   => $chosen,
            'sorts'    => self::SORTS,
            'sort'     => $request->query('sort', ''),
            'q'        => (string) $request->query('q', ''),
            'onOffer'  => $request->query('on') === 'offer',
            'bounds'   => [
                'min' => (float) Product::published()->min('price'),
                'max' => (float) Product::published()->max('price'),
            ],
        ]);
    }

    /**
     * A plain LIKE across the words a shopper would type.
     *
     * Deliberately not full-text: on shared MySQL the index would need
     * maintaining and a catalogue this size does not need it. Each word has to
     * match something, so "indigo kanjivaram" narrows rather than widens.
     */
    private function search(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        foreach (preg_split('/\s+/', $term) ?: [] as $word) {
            $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $word) . '%';

            $query->where(fn (Builder $q) => $q
                ->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('short_description', 'like', $like)
                ->orWhereHas('attributeValues', fn (Builder $v) => $v->where('value', 'like', $like))
                ->orWhereHas('colourways', fn (Builder $c) => $c->where('name', 'like', $like)));
        }
    }

    /**
     * Within one heading the choices are "or"; between headings they are
     * "and" — a shopper picking two shades wants either, but picking a shade
     * and a fabric wants both.
     *
     * @param  array<string, array<int, string>>  $chosen
     */
    private function narrow(Builder $query, $filters, array $chosen): void
    {
        foreach ($chosen as $slug => $values) {
            $attribute = $filters->firstWhere('slug', $slug);

            if (! $attribute) {
                continue;
            }

            $ids = $attribute->values->whereIn('slug', $values)->pluck('id');

            if ($ids->isEmpty()) {
                continue;
            }

            $query->whereHas('attributeValues', fn (Builder $q) => $q->whereIn('attribute_values.id', $ids));
        }
    }

    private function price(Builder $query, Request $request): void
    {
        // Compared against what is actually charged, not the ticket price: a
        // saree marked down into a shopper's budget has to appear in it.
        $paid = 'COALESCE(sale_price, price)';

        if (($min = $request->query('min')) !== null && $min !== '') {
            $query->whereRaw("{$paid} >= ?", [(float) $min]);
        }

        if (($max = $request->query('max')) !== null && $max !== '') {
            $query->whereRaw("{$paid} <= ?", [(float) $max]);
        }
    }

    private function sort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price-low'  => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price-high' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'popular'    => $query->orderByDesc('views'),
            // The default is what the shop would hand you: the pieces it has
            // chosen to put forward, then the newest.
            default      => $query->orderByDesc('is_featured')->orderByDesc('published_at'),
        };
    }
}
