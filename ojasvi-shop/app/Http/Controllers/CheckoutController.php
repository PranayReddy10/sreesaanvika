<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\Razorpay;
use App\Support\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Checkout.
 *
 * One page, one form, and the order written on the server from the bag — never
 * from what the form says anything costs.
 *
 * Cash on delivery is confirmed on the spot. Razorpay is opened against an
 * order that already exists, so a payment can always be matched to something,
 * and the shopper is sent to a confirmation page whose address they could not
 * have guessed.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CartService $bag,
        private OrderService $orders,
        private Razorpay $razorpay,
    ) {
    }

    public function show(): View|RedirectResponse
    {
        $cart = $this->bag->current(false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('bag');
        }

        $address = auth()->user()?->defaultAddress;

        return view('shop.checkout', [
            'cart'    => $cart,
            'totals'  => $this->bag->totals($cart),
            'address' => $address,
            'codOn'   => Shop::codOn(),
            'online'  => $this->razorpay->configured(),
        ]);
    }

    public function place(Request $request): RedirectResponse|View
    {
        $cart = $this->bag->current(false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('bag');
        }

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'max:190'],
            'phone'    => ['required', 'string', 'regex:/^[6-9]\d{9}$/'],
            'line1'    => ['required', 'string', 'max:190'],
            'line2'    => ['nullable', 'string', 'max:190'],
            'landmark' => ['nullable', 'string', 'max:190'],
            'city'     => ['required', 'string', 'max:90'],
            'state'    => ['required', 'string', 'max:90'],
            'pincode'  => ['required', 'string', 'regex:/^\d{6}$/'],
            'method'   => ['required', 'in:razorpay,cod'],
            'note'     => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex'   => 'A ten-digit Indian mobile number, please.',
            'pincode.regex' => 'A six-digit pincode, please.',
        ]);

        if ($data['method'] === 'cod' && ! $this->codAllowedAt($data['pincode'])) {
            return back()
                ->withInput()
                ->withErrors(['method' => 'Cash on delivery is not offered at that pincode.']);
        }

        try {
            $order = $this->orders->place($cart, $data, $data['method'], $data['note'] ?? null);
        } catch (\RuntimeException $e) {
            // The bag changed under them — a saree sold out while they typed.
            return redirect()->route('bag')->with('bag_error', $e->getMessage());
        }

        if ($data['method'] === 'cod') {
            $order->moveTo('confirmed', 'Cash on delivery');
            $this->bag->clear($cart);
            $this->bag->forget();

            return redirect()->to($this->confirmationUrl($order));
        }

        if (! $this->razorpay->configured()) {
            return redirect()->route('bag')->with('bag_error', 'Card and UPI payments are not set up yet.');
        }

        try {
            $payment = $this->razorpay->open($order);
        } catch (\Throwable $e) {
            Log::error('Razorpay would not open an order', ['order' => $order->number, 'error' => $e->getMessage()]);

            // The order exists but cannot be paid for; let the stock go rather
            // than hold it for a payment that will never come.
            $this->orders->restock($order);
            $order->moveTo('cancelled', 'Payment could not be started');

            return redirect()->route('bag')->with('bag_error', 'We could not reach the payment page. Please try again.');
        }

        return view('shop.pay', [
            'order'   => $order,
            'payment' => $payment,
            'key'     => $this->razorpay->key(),
            'back'    => route('checkout'),
            'then'    => route('checkout.verify'),
        ]);
    }

    /** What the browser hands back when the Razorpay window closes. */
    public function verify(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'order'               => ['required', 'string'],
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id'   => ['required', 'string'],
            'razorpay_signature'  => ['required', 'string'],
        ]);

        $order = Order::where('number', $data['order'])->firstOrFail();

        $payment = $this->razorpay->verify($order, $data);

        if (! $payment) {
            return $this->answer($request, false, route('checkout'), 'We could not confirm that payment. Nothing has been charged twice — please try again.');
        }

        $this->orders->markPaid($order);

        $cart = $this->bag->current(false);

        if ($cart) {
            $this->bag->clear($cart);
            $this->bag->forget();
        }

        return $this->answer($request, true, $this->confirmationUrl($order));
    }

    /** The shopper closed the window, or the bank said no. */
    public function failed(Request $request): RedirectResponse
    {
        $order = Order::where('number', $request->string('order'))->first();

        if ($order && $order->payment_status === 'unpaid') {
            $order->payments()->latest('id')->first()?->update([
                'status'         => 'failed',
                'failure_reason' => $request->string('reason')->limit(190)->toString() ?: 'The payment was not completed.',
            ]);
        }

        return redirect()->route('checkout')
            ->with('bag_error', 'The payment did not go through. Your bag is as you left it.');
    }

    public function confirmation(Request $request, Order $order): View
    {
        // A signed address: an order number alone must not show somebody's
        // name, address and phone number to whoever can count upwards.
        abort_unless($request->hasValidSignature(), 403);

        $order->load(['items', 'shipment']);

        return view('shop.confirmed', ['order' => $order]);
    }

    private function confirmationUrl(Order $order): string
    {
        return \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'order.confirmed',
            now()->addDays(7),
            ['order' => $order->number],
        );
    }

    private function codAllowedAt(string $pincode): bool
    {
        if (! Shop::codOn()) {
            return false;
        }

        $zone = ShippingZone::active()->get()->first(fn (ShippingZone $z) => $z->covers($pincode));

        // No zone covers it: fall back to the shop's own setting rather than
        // refusing an order because nobody has drawn a map yet.
        return $zone ? (bool) $zone->cod_allowed : true;
    }

    private function answer(Request $request, bool $ok, string $url, ?string $message = null): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'url' => $url, 'message' => $message], $ok ? 200 : 422);
        }

        return $ok
            ? redirect()->to($url)
            : redirect()->to($url)->with('bag_error', $message);
    }
}
