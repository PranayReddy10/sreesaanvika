<?php

namespace App\Http\Controllers;

use App\Models\Colourway;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The bag.
 *
 * Adding always answers a POST with a redirect — never with a page — so that
 * a shopper who presses back, or whose browser restores the page from its
 * cache, cannot add the same saree twice. That was the hard-won lesson of the
 * shop's first build, and it is cheap to keep.
 */
class BagController extends Controller
{
    public function __construct(private CartService $bag)
    {
    }

    public function show(): View
    {
        return view('shop.bag');
    }

    public function add(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'colourway_id' => ['nullable', 'integer', 'exists:colourways,id'],
            'quantity'     => ['nullable', 'integer', 'min:1', 'max:20'],
            'then'         => ['nullable', 'in:checkout'],
        ]);

        $product = Product::published()->with('colourways')->findOrFail($data['product_id']);

        $colourway = isset($data['colourway_id'])
            ? Colourway::where('product_id', $product->id)->find($data['colourway_id'])
            : null;

        [$ok, $message] = array_values($this->bag->add($product, (int) ($data['quantity'] ?? 1), $colourway));

        $next = ($data['then'] ?? null) === 'checkout' ? route('checkout') : route('bag');

        if ($request->expectsJson()) {
            return response()->json([
                'ok'       => $ok,
                'message'  => $message ?: __('Added to your bag'),
                'count'    => $this->bag->count(),
                'bag'      => route('bag'),
                'checkout' => $next,
            ], $ok ? 200 : 422);
        }

        return $ok
            ? redirect($next)->with('bag', $message ?: __('Added to your bag'))
            : back()->with('bag_error', $message);
    }
}
