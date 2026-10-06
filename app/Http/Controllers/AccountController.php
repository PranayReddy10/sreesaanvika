<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(): View
    {
        return view('shop.account.index', [
            'orders' => auth()->check()
                ? auth()->user()->orders()->with('items')->latest()->take(20)->get()
                : collect(),
        ]);
    }

    public function wishlist(): View
    {
        return view('shop.account.wishlist', [
            'items' => auth()->check()
                ? auth()->user()->wishlistItems()->with('product.images', 'product.colourways')->get()
                : collect(),
        ]);
    }

    /** Save a saree, or unsave it. One button, both ways. */
    public function save(Product $product): \Illuminate\Http\RedirectResponse
    {
        $existing = auth()->user()->wishlistItems()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();

            return back()->with('bag', 'Taken off your saved list.');
        }

        auth()->user()->wishlistItems()->create(['product_id' => $product->id]);

        return back()->with('bag', 'Saved.');
    }

    public function track(): View
    {
        return view('shop.track', ['order' => null, 'looked' => false]);
    }

    /**
     * An order number plus the email or phone it was placed with.
     *
     * Both, deliberately: an order number alone is guessable, and a parcel's
     * address should not be readable by anyone who can count.
     */
    public function find(Request $request): View
    {
        $data = $request->validate([
            'number'  => ['required', 'string', 'max:40'],
            'contact' => ['required', 'string', 'max:190'],
        ]);

        $contact = trim($data['contact']);

        $order = Order::with(['items', 'shipment'])
            ->where('number', strtoupper(trim($data['number'])))
            ->where(fn ($q) => $q->where('email', $contact)->orWhere('phone', preg_replace('/\D/', '', $contact)))
            ->first();

        return view('shop.track', ['order' => $order, 'looked' => true]);
    }
}
