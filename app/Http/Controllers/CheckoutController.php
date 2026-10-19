<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\CartService;
use App\Services\OrderMailer;
use App\Services\OrderService;
use App\Services\Payments\Cashfree;
use App\Services\Payments\Gateways;
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
 * Cash on delivery is confirmed on the spot. An online payment is opened
 * against an order that already exists, so a payment can always be matched to
 * something, and the shopper is sent to a confirmation page whose address they
 * could not have guessed.
 *
 * Which gateway opens it is the shop's choice in Settings, and this page does
 * not care which one it is beyond handing the browser the right script.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private CartService $bag,
        private OrderService $orders,
        private OrderMailer $mailer,
        private Razorpay $razorpay,
        private Gateways $gateways,
    ) {
    }

    public function show(): View|RedirectResponse
    {
        $cart = $this->bag->current(false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('bag');
        }

        $address = auth()->user()?->defaultAddress;

        $totals = $this->bag->totals($cart);

        return view('shop.checkout', [
            'cart'    => $cart,
            'totals'  => $totals,
            'address' => $address,
            'codOn'   => $this->codAllowedFor($totals->grandTotal),
            'codWhy'  => $this->codRefusedBecause($totals->grandTotal),
            'online'  => Shop::onlineOn(),
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
            // "razorpay" is still taken for a page that was open before the
            // shop switched gateways; it means the same as "online".
            'method'   => ['required', 'in:online,razorpay,cashfree,cod'],
            'note'     => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex'   => 'A ten-digit Indian mobile number, please.',
            'pincode.regex' => 'A six-digit pincode, please.',
        ]);

        /*
         * Checked again here, not only on the page. The form says which way
         * they want to pay, and a form is a suggestion — somebody can post
         * "cod" at a shop that has switched it off.
         */
        $totals = $this->bag->totals($cart, $data['pincode']);

        $gateway = $this->gateways->active();

        // Whatever the form called it, an order is either cash on delivery or
        // it is this shop's gateway: the order carries the name of whoever is
        // actually going to hold the money, because that is who must refund it.
        $method = $data['method'] === 'cod' ? 'cod' : $gateway->name();

        if ($method !== 'cod' && ! Shop::onlineOn()) {
            return back()->withInput()
                ->withErrors(['method' => 'Card and UPI payments are not available at the moment.']);
        }

        if ($method === 'cod') {
            if ($why = $this->codRefusedBecause($totals->grandTotal)) {
                return back()->withInput()->withErrors(['method' => $why]);
            }

            if (! $this->codAllowedAt($data['pincode'])) {
                return back()->withInput()
                    ->withErrors(['method' => 'Cash on delivery is not offered at that pincode. Please pay online.']);
            }
        }

        try {
            $order = $this->orders->place($cart, $data, $method, $data['note'] ?? null);
        } catch (\RuntimeException $e) {
            // The bag changed under them — a saree sold out while they typed.
            return redirect()->route('bag')->with('bag_error', $e->getMessage());
        }

        if ($method === 'cod') {
            $order->moveTo('confirmed', 'Cash on delivery');
            $this->mailer->placed($order);
            $this->bag->clear($cart);
            $this->bag->forget();

            return redirect()->to($this->confirmationUrl($order));
        }

        if (! $gateway->configured()) {
            return redirect()->route('bag')->with('bag_error', 'Card and UPI payments are not set up yet.');
        }

        try {
            $payment = $gateway->open($order);
        } catch (\Throwable $e) {
            Log::error($gateway->label().' would not open an order', ['order' => $order->number, 'error' => $e->getMessage()]);

            // The order exists but cannot be paid for; let the stock go rather
            // than hold it for a payment that will never come.
            $this->orders->restock($order);
            $order->moveTo('cancelled', 'Payment could not be started');

            return redirect()->route('bag')->with('bag_error', 'We could not reach the payment page. Please try again.');
        }

        /*
         * Which payment this browser opened.
         *
         * A shopper comes back from the bank with an order number in the
         * address bar and nothing else, and an order number is a thing that
         * can be typed. This is what tells the two apart later, so somebody
         * guessing numbers is never shown another person's name and address.
         */
        session(['pay.reference' => $payment->gateway_order_id]);

        if ($gateway->name() === 'cashfree') {
            return view('shop.pay-cashfree', [
                'order'   => $order,
                'payment' => $payment,
                'mode'    => app(Cashfree::class)->mode(),
                'back'    => route('checkout'),
            ]);
        }

        return view('shop.pay', [
            'order'   => $order,
            'payment' => $payment,
            'key'     => $this->razorpay->key(),
            'back'    => route('checkout'),
            'then'    => route('checkout.verify'),
        ]);
    }

    /**
     * Back from Cashfree's page, which proves nothing on its own.
     *
     * The shopper arrives here with an order id in the address and that is
     * all; whether it was paid is asked of Cashfree, over the wire, with the
     * secret key. If it was, the order is marked paid here rather than waiting
     * on the webhook, so the confirmation page is right the moment they see
     * it — and the webhook doing the same thing again changes nothing.
     */
    public function cashfreeReturn(Request $request, string $reference): RedirectResponse
    {
        $payment = app(Cashfree::class)->settle($reference);

        if (! $payment || ! $payment->order) {
            return redirect()->route('checkout')
                ->with('bag_error', 'That payment did not go through. Your bag is as you left it.');
        }

        $order = $payment->order;
        $wasUnpaid = $order->payment_status !== 'paid';

        $this->orders->markPaid($order, 'Paid through Cashfree');

        if ($wasUnpaid) {
            $this->mailer->placed($order);
        }

        $cart = $this->bag->current(false);

        if ($cart) {
            $this->bag->clear($cart);
            $this->bag->forget();
        }

        /*
         * Only the browser that opened this payment is shown the order.
         *
         * Anybody else — a guessed order number, a link forwarded to a friend
         * — is told the payment is recorded and sent to the tracking page,
         * where an email address has to be given before anything is shown.
         */
        if ($request->session()->pull('pay.reference') !== $reference) {
            return redirect()->route('track')
                ->with('bag', 'That payment is recorded. Enter the order number and the email it was placed with to see the order.');
        }

        return redirect()->to($this->confirmationUrl($order));
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
        $this->mailer->placed($order);

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

    private function codAllowedFor(float $total): bool
    {
        return $this->codRefusedBecause($total) === null;
    }

    /** Why cash on delivery is not on offer, in words a shopper can act on. */
    private function codRefusedBecause(float $total): ?string
    {
        if (! Shop::codOn()) {
            return 'Cash on delivery is not available at the moment.';
        }

        $max = Shop::codMax();

        if ($max !== null && $total > $max) {
            return 'Cash on delivery is only for orders up to ' . Shop::money($max)
                . '. Please pay online for this one.';
        }

        return null;
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
