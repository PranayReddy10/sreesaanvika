<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Providers\ShopConfigProvider;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payments\Cashfree;
use App\Services\Payments\Gateways;
use App\Support\Shop;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Paying through Cashfree.
 *
 * The same question the Razorpay tests ask, asked of the other gateway: can
 * the shop be made to give away a saree? Everything that decides whether an
 * order is paid is checked against Cashfree over the wire with the secret key,
 * so each test here is a way somebody might try to skip that — a return
 * address typed by hand, a webhook with no signature, the same webhook sent
 * twice — plus the ordinary mistakes that cost real money: rupees sent as
 * paise, a refund counted twice, a shop that switches gateway and can no
 * longer refund last week's orders.
 */
class CashfreePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);

        Setting::put('pay_gateway', 'cashfree', 'string', 'payments');
        Setting::put('cashfree_app_id', 'TEST_APP_ID', 'string', 'payments');
        Setting::put('cashfree_secret', 'test-secret-key', 'secret', 'payments');
        Setting::put('cashfree_mode', 'sandbox', 'string', 'payments');

        // Settings are read into config at boot, and the shop booted before
        // these rows existed.
        (new ShopConfigProvider(app()))->boot();
    }

    /* ---------------------------------------------------- choosing a gateway */

    public function test_the_shop_uses_the_gateway_it_has_been_given(): void
    {
        $this->assertSame('cashfree', app(Gateways::class)->active()->name());
        $this->assertSame('Cashfree', Shop::gatewayName());
        $this->assertTrue(Shop::onlineOn());
    }

    public function test_a_shop_that_has_chosen_nothing_is_still_on_razorpay(): void
    {
        Setting::put('pay_gateway', '', 'string', 'payments');

        $this->assertSame('razorpay', app(Gateways::class)->active()->name());
    }

    public function test_half_a_set_of_keys_is_not_a_gateway(): void
    {
        config(['services.cashfree.secret' => '']);

        $this->assertFalse(app(Gateways::class)->ready());
        $this->assertFalse(Shop::onlineOn());

        // And the shopper is told so rather than shown a payment page.
        $this->get('/checkout?');
        $this->bagWithASaree();
        $this->get('/checkout')->assertOk()->assertDontSee('UPI, card, net banking or wallet');
    }

    public function test_the_shops_own_terms_name_the_gateway_taking_the_money(): void
    {
        $this->assertStringContainsString('handled by Cashfree', \App\Support\Policies::terms());

        Setting::put('pay_gateway', 'razorpay', 'string', 'payments');

        $this->assertStringContainsString('handled by Razorpay', \App\Support\Policies::terms());
    }

    /* ------------------------------------------------------ opening a payment */

    public function test_placing_an_order_opens_a_cashfree_order_and_shows_its_page(): void
    {
        Http::fake([
            '*/pg/orders' => Http::response($this->created(), 200),
        ]);

        $this->bagWithASaree();

        $response = $this->post('/checkout', $this->address(['method' => 'online']));

        $response->assertOk()->assertSee('session_abc123', false);
        // The browser is told which Cashfree to talk to, and nothing else.
        $response->assertSee('"sandbox"', false);
        $response->assertDontSee('test-secret-key', false);

        $order = Order::latest('id')->first();
        $this->assertSame('cashfree', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);

        $payment = $order->payments()->first();
        $this->assertSame('cashfree', $payment->gateway);
        $this->assertSame($order->number, $payment->gateway_order_id);
        $this->assertSame('created', $payment->status);
        $this->assertSame('session_abc123', $payment->payload['payment_session_id']);
    }

    /** The mistake that charges a shopper a hundred times over. */
    public function test_the_amount_is_sent_in_rupees_not_paise(): void
    {
        Http::fake(['*/pg/orders' => Http::response($this->created(), 200)]);

        $this->bagWithASaree();
        $this->post('/checkout', $this->address(['method' => 'online']));

        $order = Order::latest('id')->first();

        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://sandbox.cashfree.com/pg/orders'
                && $request['order_amount'] === round((float) $order->grand_total, 2)
                && $request['order_currency'] === 'INR';
        });
    }

    public function test_the_keys_and_the_addresses_cashfree_is_given(): void
    {
        Http::fake(['*/pg/orders' => Http::response($this->created(), 200)]);

        $this->bagWithASaree();
        $this->post('/checkout', $this->address(['method' => 'online']));

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-client-id', 'TEST_APP_ID')
                && $request->hasHeader('x-client-secret', 'test-secret-key')
                && $request->hasHeader('x-api-version', '2023-08-01')
                // Cashfree fills this in itself, so the placeholder is literal.
                && str_contains($request['order_meta']['return_url'], '{order_id}')
                && $request['order_meta']['notify_url'] === route('webhooks.cashfree');
        });
    }

    public function test_a_second_attempt_at_the_same_order_gets_its_own_reference(): void
    {
        Http::fake(['*/pg/orders' => Http::response($this->created(), 200)]);

        $order = $this->anUnpaidOnlineOrder();
        app(Cashfree::class)->open($order);
        app(Cashfree::class)->open($order);

        // Cashfree refuses an order id it has seen before, and a shopper
        // coming back to pay is ordinary.
        $this->assertSame(
            [$order->number, $order->number.'-2'],
            $order->payments()->orderBy('id')->pluck('gateway_order_id')->all(),
        );
    }

    /* ------------------------------------------------- coming back from the bank */

    public function test_the_order_is_paid_only_because_cashfree_says_so(): void
    {
        Mail::fake();

        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        Http::fake([
            '*/pg/orders/'.$order->number => Http::response(['order_status' => 'PAID'], 200),
            '*/pg/orders/'.$order->number.'/payments' => Http::response([[
                'cf_payment_id' => '88991122',
                'payment_status' => 'SUCCESS',
                'payment_method' => ['upi' => ['channel' => 'collect']],
            ]], 200),
        ]);

        $this->withSession(['pay.reference' => $order->number])
            ->get('/checkout/cashfree/'.$order->number)
            ->assertRedirectContains('/order/'.$order->number);

        $this->assertSame('paid', $order->fresh()->payment_status);
        Mail::assertQueued(\App\Mail\OrderPlaced::class, 1);

        $payment = $order->payments()->latest('id')->first();
        $this->assertSame('captured', $payment->status);
        $this->assertSame('88991122', $payment->gateway_payment_id);
        $this->assertSame('upi', $payment->method);
    }

    /** The address bar is an input, not evidence. */
    public function test_an_order_cashfree_calls_unpaid_is_not_paid_however_the_shopper_arrives(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        Http::fake([
            '*/pg/orders/'.$order->number => Http::response(['order_status' => 'ACTIVE'], 200),
        ]);

        $this->withSession(['pay.reference' => $order->number])
            ->get('/checkout/cashfree/'.$order->number)
            ->assertRedirect(route('checkout'));

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_somebody_who_did_not_make_the_payment_is_not_shown_the_order(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        Http::fake([
            '*/pg/orders/'.$order->number => Http::response(['order_status' => 'PAID'], 200),
            '*/pg/orders/'.$order->number.'/payments' => Http::response([], 200),
        ]);

        // No session: an order number somebody has typed or been forwarded.
        $response = $this->get('/checkout/cashfree/'.$order->number);

        $response->assertRedirect(route('track'));
        $response->assertSessionMissing('bag_error');

        // The payment is still recorded — it is the shopper's name and
        // address that are withheld, not the money.
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    /* ----------------------------------------------------------- the webhook */

    public function test_a_webhook_without_a_matching_signature_is_refused(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        $this->cashfreeWebhook($this->successEvent($order->number), 'not-the-secret')
            ->assertStatus(400);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_a_webhook_with_no_signature_at_all_is_refused(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        $body = json_encode($this->successEvent($order->number));

        $this->call('POST', '/webhooks/cashfree', [], [], [], ['CONTENT_TYPE' => 'application/json'], $body)
            ->assertStatus(400);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_a_genuine_webhook_pays_the_order_and_writes_to_the_shopper(): void
    {
        Mail::fake();

        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        $this->cashfreeWebhook($this->successEvent($order->number))->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('captured', $order->payments()->latest('id')->first()->status);

        /*
         * Both of them, and this is the whole point of the webhook: this is
         * the path taken when the shopper's phone died on the bank's page, so
         * if the shop is not told here it is never told at all.
         */
        Mail::assertQueued(\App\Mail\OrderPlaced::class, 1);
        Mail::assertQueued(\App\Mail\NewOrderForShop::class);
    }

    /** Cashfree retries, and a shopper must not be thanked twice. */
    public function test_the_same_webhook_twice_changes_nothing_the_second_time(): void
    {
        Mail::fake();

        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);

        $this->cashfreeWebhook($this->successEvent($order->number))->assertOk();
        $this->cashfreeWebhook($this->successEvent($order->number))->assertOk();

        Mail::assertQueued(\App\Mail\OrderPlaced::class, 1);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_failed_payment_is_written_down_and_the_order_left_alone(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $payment = $this->aPaymentFor($order);

        $this->cashfreeWebhook([
            'type' => 'PAYMENT_FAILED_WEBHOOK',
            'data' => [
                'order'   => ['order_id' => $order->number],
                'payment' => [
                    'cf_payment_id'   => '5551212',
                    'payment_status'  => 'FAILED',
                    'payment_message' => 'The bank declined the card.',
                ],
            ],
        ])->assertOk();

        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertSame('The bank declined the card.', $payment->failure_reason);

        // Their bag and their stock are still theirs to try again with.
        $this->assertSame('pending', $order->fresh()->status);
    }

    /** A card refused and then a UPI that worked, arriving in that order. */
    public function test_a_late_failure_does_not_unpay_a_paid_order(): void
    {
        Mail::fake();

        $order = $this->anUnpaidOnlineOrder();
        $payment = $this->aPaymentFor($order);

        $this->cashfreeWebhook($this->successEvent($order->number))->assertOk();
        $this->cashfreeWebhook([
            'type' => 'PAYMENT_FAILED_WEBHOOK',
            'data' => [
                'order'   => ['order_id' => $order->number],
                'payment' => ['payment_status' => 'FAILED', 'payment_message' => 'Cancelled'],
            ],
        ])->assertOk();

        $this->assertSame('captured', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_refund_is_recorded_once_however_often_it_is_announced(): void
    {
        Mail::fake();

        $order = $this->anUnpaidOnlineOrder();
        $this->aPaymentFor($order);
        $this->cashfreeWebhook($this->successEvent($order->number));

        $refund = [
            'type' => 'REFUND_STATUS_WEBHOOK',
            'data' => [
                'order'  => ['order_id' => $order->number],
                'refund' => [
                    'cf_refund_id'  => 'refund_1',
                    'refund_status' => 'SUCCESS',
                    'refund_amount' => 500.0,
                ],
            ],
        ];

        $this->cashfreeWebhook($refund)->assertOk();
        $this->cashfreeWebhook($refund)->assertOk();

        $this->assertSame('500.00', $order->fresh()->refunded_total);
        $this->assertSame('partially_refunded', $order->fresh()->payment_status);
    }

    /* ----------------------------------------------------------- refunding */

    public function test_a_refund_goes_back_through_whoever_took_the_money(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $payment = $this->aPaymentFor($order);
        $payment->update(['status' => 'captured', 'gateway_payment_id' => '7788']);

        Http::fake([
            '*/pg/orders/*/refunds' => Http::response(['cf_refund_id' => 'refund_9', 'refund_status' => 'PENDING'], 200),
        ]);

        app(Gateways::class)->named($payment->gateway)->refund($payment, 250.0);

        Http::assertSent(function ($request) use ($order) {
            return $request->url() === 'https://sandbox.cashfree.com/pg/orders/'.$order->number.'/refunds'
                && $request['refund_amount'] === 250.0;
        });
    }

    public function test_the_gateway_a_payment_went_through_is_the_one_asked_for_it_later(): void
    {
        $order = $this->anUnpaidOnlineOrder();
        $payment = $order->payments()->create([
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_OLD',
            'amount' => $order->grand_total,
            'status' => 'captured',
        ]);

        // The shop has since moved to Cashfree, but Razorpay is holding this
        // money and Razorpay is who must give it back.
        $this->assertSame('razorpay', app(Gateways::class)->named($payment->gateway)->name());
        $this->assertSame('cashfree', app(Gateways::class)->active()->name());
    }

    /* ------------------------------------------------------------ the admin */

    public function test_the_shop_sets_its_own_keys_and_they_are_kept_encrypted(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm([
                'shop_name'       => 'OJASVI',
                'pay_gateway'     => 'cashfree',
                'cashfree_mode'   => 'production',
                'cashfree_app_id' => 'LIVE_APP_ID',
                'cashfree_secret' => 'live-secret-key',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('live-secret-key', Setting::get('cashfree_secret'));

        // Encrypted in the table, not merely hidden in the form.
        $row = Setting::where('key', 'cashfree_secret')->first();
        $this->assertNotSame('live-secret-key', $row->value);
        $this->assertSame('secret', $row->type);

        (new ShopConfigProvider(app()))->boot();
        $this->assertSame('live-secret-key', config('services.cashfree.secret'));
        $this->assertSame('production', app(Cashfree::class)->mode());
        $this->assertSame('https://api.cashfree.com/pg', app(Cashfree::class)->base());
    }

    public function test_a_secret_is_never_filled_back_into_the_form(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner2@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->assertSet('data.cashfree_secret', '')
            ->assertSet('data.cashfree_app_id', 'TEST_APP_ID');
    }

    public function test_saving_something_else_does_not_wipe_the_keys(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner3@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm(['shop_name' => 'OJASVI Drapes'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('test-secret-key', Setting::get('cashfree_secret'));
    }

    /* ------------------------------------------------------------- fixtures */

    private function address(array $override = []): array
    {
        return array_merge([
            'name' => 'Lakshmi Prasad',
            'email' => 'lakshmi@example.in',
            'phone' => '9000000001',
            'line1' => '12-3, Sai Nagar',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500081',
            'method' => 'online',
        ], $override);
    }

    private function bagWithASaree(): Product
    {
        $product = Product::published()->where('stock', '>=', 2)->firstOrFail();

        app(CartService::class)->add($product, 1);

        return $product;
    }

    private function anUnpaidOnlineOrder(): Order
    {
        $this->bagWithASaree();

        return app(OrderService::class)->place(
            app(CartService::class)->current(),
            $this->address(),
            'cashfree',
        );
    }

    private function aPaymentFor(Order $order): Payment
    {
        return $order->payments()->create([
            'gateway'          => 'cashfree',
            'gateway_order_id' => $order->number,
            'amount'           => $order->grand_total,
            'status'           => 'created',
            'payload'          => ['payment_session_id' => 'session_abc123'],
        ]);
    }

    /** @return array<string, mixed> */
    private function created(): array
    {
        return [
            'cf_order_id'        => 2149460581,
            'order_status'       => 'ACTIVE',
            'payment_session_id' => 'session_abc123',
        ];
    }

    /** @return array<string, mixed> */
    private function successEvent(string $reference): array
    {
        return [
            'type' => 'PAYMENT_SUCCESS_WEBHOOK',
            'data' => [
                'order'   => ['order_id' => $reference],
                'payment' => [
                    'cf_payment_id'  => '12345678',
                    'payment_status' => 'SUCCESS',
                    'payment_amount' => 1000.0,
                    'payment_method' => ['netbanking' => ['channel' => 'link']],
                ],
            ],
        ];
    }

    /**
     * Signed the way Cashfree signs: the timestamp and the raw body together,
     * so a payload kept from last week cannot be replayed tonight.
     */
    private function cashfreeWebhook(array $payload, ?string $secret = null): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload);
        $timestamp = (string) now()->timestamp;
        $signature = base64_encode(
            hash_hmac('sha256', $timestamp.$body, $secret ?? 'test-secret-key', true),
        );

        return $this->call(
            'POST',
            '/webhooks/cashfree',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WEBHOOK_TIMESTAMP' => $timestamp,
            ],
            $body,
        );
    }
}
