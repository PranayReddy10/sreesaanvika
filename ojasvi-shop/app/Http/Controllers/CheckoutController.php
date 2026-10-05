<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private CartService $bag)
    {
    }

    public function show(): View|RedirectResponse
    {
        $cart = $this->bag->current(false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('bag');
        }

        return view('shop.checkout', [
            'cart'   => $cart,
            'totals' => $this->bag->totals($cart),
        ]);
    }
}
