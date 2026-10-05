<?php

namespace Tests\Feature;

use App\Models\Colourway;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * The money path.
 *
 * Everything here is about one question: can the shop be made to give away a
 * saree, or to charge for one it cannot send? Each test is a way somebody
 * might try.
 */
class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

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
            'method' => 'cod',
        ], $override);
    }

    private function bagWith(Product $product, int $quantity = 1, ?Colourway $colourway = null): void
    {
        app(CartService::class)->add($product, $quantity, $colourway);
    }

    private function aSaree(int $minimumStock = 4): Product
    {
        return Product::published()->where('stock', '>=', $minimumStock)->firstOrFail();
    }

    /** The seeded catalogue already has orders, so count only what a test adds. */
    private function assertNothingWasOrdered(callable $during): void
    {
        $before = Order::count();

        $during();

        $this->assertSame($before, Order::count());
    }

    public function test_an_empty_bag_cannot_be_checked_out(): void
    {
        $this->get('/checkout')->assertRedirect('/bag');
        $this->post('/checkout', $this->address())->assertRedirect('/bag');
    }

    public function test_a_cash_order_is_written_confirmed_and_takes_its_stock(): void
    {
        $product = $this->aSaree(6);
        $before = $product->stock;

        $this->bagWith($product, 2);

        $response = $this->post('/checkout', $this->address());

        $order = Order::latest('id')->firstOrFail();

        $response->assertRedirectContains("/order/{$order->number}");

        $this->assertSame('confirmed', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('cod', $order->payment_method);
        $this->assertSame($before - 2, $product->fresh()->stock);

        // The bag is emptied, or a refresh would sell the same thing again.
        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_the_checkout_page_shows_the_figure_it_is_about_to_charge(): void
    {
        // The shop's first build quoted free delivery on one page and charged
        // for it on the next. Whatever the page says, the order must match.
        Setting::put('cod_fee', '49', 'money', 'shipping');

        $product = $this->aSaree();
        $this->bagWith($product);

        $this->get('/checkout')->assertOk()->assertSee('Cash on delivery');

        // Read before the order empties the bag.
        $quoted = app(CartService::class)->totals(null, '500081')->grandTotal;

        $this->post('/checkout', $this->address());

        $order = Order::latest('id')->firstOrFail();

        $this->assertEquals(
            round($quoted + 49, 2),
            (float) $order->grand_total,
            'The cash-on-delivery fee has to be in the figure the shopper was shown.',
        );
    }

    public function test_the_order_copies_what_was_bought_rather_than_pointing_at_it(): void
    {
        $product = $this->aSaree();
        $this->bagWith($product);
        $this->post('/checkout', $this->address());

        $line = Order::latest('id')->firstOrFail()->items->first();

        $product->update(['name' => 'Renamed next month', 'price' => 999999]);

        $line->refresh();

        $this->assertNotSame('Renamed next month', $line->name);
        $this->assertNotEquals(999999, (float) $line->unit_price);
    }

    public function test_the_total_is_worked_out_on_the_server_not_taken_from_the_form(): void
    {
        $product = $this->aSaree();
        $this->bagWith($product);

        // A form claiming the order is worth a rupee. The server must ignore
        // every one of these.
        $this->post('/checkout', $this->address([
            'grand_total' => 1,
            'items_total' => 1,
            'shipping_total' => -500,
        ]));

        $order = Order::latest('id')->firstOrFail();

        $this->assertEquals($product->priceFor(), (float) $order->items_total);
        $this->assertGreaterThan(1, (float) $order->grand_total);
        $this->assertGreaterThanOrEqual(0, (float) $order->shipping_total);
    }

    public function test_an_order_for_more_than_there_is_is_refused(): void
    {
        $product = $this->aSaree();
        $this->bagWith($product, 2);

        // Sold out between the bag and the button.
        $product->update(['stock' => 0]);

        $this->assertNothingWasOrdered(fn () => $this->post('/checkout', $this->address())
            ->assertRedirect('/bag')
            ->assertSessionHas('bag_error'));
    }

    public function test_an_unpublished_saree_cannot_be_bought(): void
    {
        $product = $this->aSaree();
        $this->bagWith($product);

        $product->update(['status' => 'draft']);

        $this->assertNothingWasOrdered(fn () => $this->post('/checkout', $this->address())
            ->assertRedirect('/bag'));
    }

    public function test_a_bad_phone_or_pincode_is_sent_back(): void
    {
        $this->bagWith($this->aSaree());

        $this->assertNothingWasOrdered(function () {
            $this->post('/checkout', $this->address(['phone' => '12345']))
                ->assertSessionHasErrors('phone');

            $this->post('/checkout', $this->address(['pincode' => '50']))
                ->assertSessionHasErrors('pincode');
        });
    }

    public function test_cash_on_delivery_is_refused_where_the_shop_does_not_offer_it(): void
    {
        $this->bagWith($this->aSaree());

        // The seeded North East zone does not take cash.
        $this->assertNothingWasOrdered(fn () => $this
            ->post('/checkout', $this->address(['pincode' => '781001', 'state' => 'Assam', 'city' => 'Guwahati']))
            ->assertSessionHasErrors('method'));
    }

    public function test_cash_on_delivery_can_be_switched_off_entirely(): void
    {
        Setting::put('cod_on', '0', 'bool', 'shipping');

        $this->bagWith($this->aSaree());

        $this->post('/checkout', $this->address())->assertSessionHasErrors('method');
    }

    public function test_the_confirmation_page_cannot_be_read_without_its_signature(): void
    {
        $this->bagWith($this->aSaree());
        $this->post('/checkout', $this->address());

        $order = Order::latest('id')->firstOrFail();

        // Somebody who counted upwards to the order number.
        $this->get("/order/{$order->number}")->assertForbidden();

        $signed = URL::temporarySignedRoute('order.confirmed', now()->addDay(), ['order' => $order->number]);

        $this->get($signed)->assertOk()->assertSee($order->number, false);
    }

    public function test_a_coupon_is_counted_once_and_recorded(): void
    {
        $product = Product::published()->where('price', '>', 5000)->firstOrFail();
        $this->bagWith($product);

        $bag = app(CartService::class);
        $this->assertNull($bag->applyCoupon('OJASVI10'), 'The seeded code should apply.');

        $used = \App\Models\Coupon::where('code', 'OJASVI10')->value('used');

        $this->post('/checkout', $this->address());

        $order = Order::latest('id')->firstOrFail();

        $this->assertSame('OJASVI10', $order->coupon_code);
        $this->assertGreaterThan(0, (float) $order->discount_total);
        $this->assertSame($used + 1, \App\Models\Coupon::where('code', 'OJASVI10')->value('used'));
        $this->assertDatabaseHas('coupon_redemptions', ['order_id' => $order->id]);
    }

    public function test_an_order_number_is_never_reused(): void
    {
        $product = $this->aSaree(6);

        $numbers = [];

        foreach (range(1, 3) as $i) {
            app(CartService::class)->forget();
            $this->bagWith($product);
            $this->post('/checkout', $this->address());
            $numbers[] = Order::latest('id')->value('number');
        }

        $this->assertCount(3, array_unique($numbers));
    }

    /* ------------------------------------------------------- the webhook */

    private function webhook(array $payload, ?string $secret = 'whsec-test'): \Illuminate\Testing\TestResponse
    {
        config(['services.razorpay.webhook_secret' => 'whsec-test']);

        $body = json_encode($payload);

        return $this->call(
            'POST',
            '/webhooks/razorpay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, $secret ?? 'wrong'),
            ],
            $body,
        );
    }

    private function anUnpaidOnlineOrder(): array
    {
        $product = $this->aSaree();
        $this->bagWith($product);

        $order = app(OrderService::class)->place(
            app(CartService::class)->current(),
            $this->address(['method' => 'razorpay']),
            'razorpay',
        );

        $payment = $order->payments()->create([
            'gateway' => 'razorpay',
            'gateway_order_id' => 'order_TESTONE',
            'amount' => $order->grand_total,
            'status' => 'created',
        ]);

        return [$order, $payment];
    }

    public function test_a_webhook_without_a_matching_signature_is_refused(): void
    {
        [$order] = $this->anUnpaidOnlineOrder();

        $this->webhook([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_X', 'order_id' => 'order_TESTONE']]],
        ], 'not-the-secret')->assertStatus(400);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_a_genuine_capture_marks_the_order_paid(): void
    {
        [$order, $payment] = $this->anUnpaidOnlineOrder();

        $this->webhook([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_TESTONE', 'order_id' => 'order_TESTONE', 'method' => 'upi',
            ]]],
        ])->assertOk();

        $order->refresh();

        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('captured', $payment->fresh()->status);
    }

    public function test_the_same_capture_twice_does_not_pay_the_order_twice(): void
    {
        [$order] = $this->anUnpaidOnlineOrder();

        $event = [
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_TESTONE', 'order_id' => 'order_TESTONE']]],
        ];

        $this->webhook($event)->assertOk();
        $paidAt = $order->fresh()->paid_at;

        $this->webhook($event)->assertOk();

        $this->assertEquals($paidAt, $order->fresh()->paid_at);
        $this->assertSame(1, $order->history()->where('to', 'confirmed')->count());
    }

    public function test_a_failed_payment_leaves_the_order_alone_so_they_can_try_again(): void
    {
        [$order, $payment] = $this->anUnpaidOnlineOrder();

        $this->webhook([
            'event' => 'payment.failed',
            'payload' => ['payment' => ['entity' => [
                'id' => 'pay_TESTONE', 'order_id' => 'order_TESTONE',
                'error_description' => 'The card was declined.',
            ]]],
        ])->assertOk();

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('pending', $order->fresh()->status, 'They may well try another card.');
    }

    public function test_a_webhook_for_an_order_we_do_not_know_is_answered_and_ignored(): void
    {
        // A 500 here would have Razorpay retrying for hours over nothing.
        $this->webhook([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_X', 'order_id' => 'order_NOTOURS']]],
        ])->assertOk();
    }

    public function test_a_refund_is_recorded_against_the_order(): void
    {
        [$order] = $this->anUnpaidOnlineOrder();

        $this->webhook([
            'event' => 'payment.captured',
            'payload' => ['payment' => ['entity' => ['id' => 'pay_TESTONE', 'order_id' => 'order_TESTONE']]],
        ])->assertOk();

        $this->webhook([
            'event' => 'refund.processed',
            'payload' => [
                'payment' => ['entity' => ['id' => 'pay_TESTONE', 'order_id' => 'order_TESTONE']],
                'refund' => ['entity' => ['id' => 'rfnd_X', 'amount' => (int) round($order->grand_total * 100)]],
            ],
        ])->assertOk();

        $order->refresh();

        $this->assertSame('refunded', $order->payment_status);
        $this->assertSame('refunded', $order->status);
        $this->assertEquals((float) $order->grand_total, (float) $order->refunded_total);
    }

    /* -------------------------------------------------- abandoned orders */

    public function test_an_order_never_paid_for_gives_its_stock_back(): void
    {
        $product = $this->aSaree();
        $before = $product->stock;

        [$order] = $this->anUnpaidOnlineOrder();

        $this->assertSame($before - 1, $product->fresh()->stock);

        $order->forceFill(['created_at' => now()->subHours(4)])->save();

        $this->artisan('ojasvi:release-unpaid')->assertSuccessful();

        $this->assertSame($before, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_a_cash_order_is_never_released(): void
    {
        $this->bagWith($this->aSaree());
        $this->post('/checkout', $this->address());

        $order = Order::latest('id')->firstOrFail();
        $order->forceFill(['created_at' => now()->subDays(3), 'status' => 'pending'])->save();

        $this->artisan('ojasvi:release-unpaid')->assertSuccessful();

        $this->assertSame('pending', $order->fresh()->status, 'Cash orders are unpaid by design.');
    }

    public function test_a_paid_order_is_never_released(): void
    {
        [$order] = $this->anUnpaidOnlineOrder();

        app(OrderService::class)->markPaid($order);

        $order->forceFill(['created_at' => now()->subDays(2)])->save();

        $this->artisan('ojasvi:release-unpaid')->assertSuccessful();

        $this->assertNotSame('cancelled', $order->fresh()->status);
    }
}
