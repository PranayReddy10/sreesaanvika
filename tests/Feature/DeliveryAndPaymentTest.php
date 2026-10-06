<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingZone;
use App\Models\User;
use App\Services\CartService;
use App\Services\Shipping\Delhivery;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * What it costs to deliver, who may pay how, and the courier.
 *
 * The figures the shop sets have to be the figures it charges — that is the
 * whole of it. Everything here is a way of checking a setting typed in the
 * admin reaches the till.
 */
class DeliveryAndPaymentTest extends TestCase
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

    private function bagWith(?Product $product = null, int $quantity = 1): Product
    {
        $product ??= Product::published()->where('stock', '>=', 6)->firstOrFail();

        app(CartService::class)->add($product, $quantity);

        return $product;
    }

    /* ------------------------------------------------- what delivery costs */

    public function test_free_delivery_begins_at_the_figure_the_shop_set(): void
    {
        $product = Product::published()->where('price', '<', 7000)->firstOrFail();
        $price = $product->priceFor();

        // Just above what this saree costs: delivery is charged.
        Setting::put('free_shipping_from', (string) ($price + 500), 'money', 'shipping');
        Setting::put('flat_rate', '99', 'money', 'shipping');
        ShippingZone::query()->delete();

        $this->bagWith($product);

        $this->assertEqualsWithDelta(99.0, app(CartService::class)->totals()->shippingTotal, 0.01);

        // Just below: it is free.
        Setting::put('free_shipping_from', (string) ($price - 1), 'money', 'shipping');

        $this->assertEqualsWithDelta(0.0, app(CartService::class)->totals()->shippingTotal, 0.01);
    }

    public function test_a_threshold_of_zero_makes_delivery_free_on_everything(): void
    {
        Setting::put('free_shipping_from', '0', 'money', 'shipping');
        ShippingZone::query()->delete();

        $this->bagWith();

        $this->assertEqualsWithDelta(0.0, app(CartService::class)->totals()->shippingTotal, 0.01);
    }

    public function test_a_delivery_area_wins_over_the_shop_wide_figures(): void
    {
        ShippingZone::query()->delete();

        ShippingZone::create([
            'name' => 'Far away', 'pincodes' => ['78'],
            'rate' => 149, 'free_from' => null, 'cod_allowed' => false, 'position' => 1,
        ]);
        ShippingZone::create([
            'name' => 'Everywhere else', 'pincodes' => [],
            'rate' => 49, 'free_from' => 99999, 'cod_allowed' => true, 'position' => 2,
        ]);

        $this->bagWith();

        $bag = app(CartService::class);

        $this->assertEqualsWithDelta(149.0, $bag->totals(null, '781001')->shippingTotal, 0.01);
        $this->assertEqualsWithDelta(49.0, $bag->totals(null, '500081')->shippingTotal, 0.01);
    }

    public function test_the_order_is_charged_the_delivery_its_pincode_earns(): void
    {
        ShippingZone::query()->delete();
        ShippingZone::create([
            'name' => 'Telangana', 'pincodes' => ['50'],
            'rate' => 49, 'free_from' => 999999, 'cod_allowed' => true, 'position' => 1,
        ]);
        Setting::put('cod_fee', '0', 'money', 'shipping');

        $this->bagWith();
        $this->post('/checkout', $this->address());

        $this->assertEqualsWithDelta(49.0, (float) Order::latest('id')->value('shipping_total'), 0.01);
    }

    /* ------------------------------------------------------- how they pay */

    public function test_cash_on_delivery_can_be_switched_off_from_the_admin(): void
    {
        Setting::put('cod_on', '0', 'bool', 'shipping');

        $this->bagWith();

        $this->get('/checkout')
            ->assertOk()
            ->assertSee('Cash on delivery is not available at the moment.');

        // And posting it anyway is refused: a form is a suggestion.
        $before = Order::count();

        $this->post('/checkout', $this->address())->assertSessionHasErrors('method');

        $this->assertSame($before, Order::count());
    }

    public function test_online_payment_can_be_switched_off_from_the_admin(): void
    {
        config(['services.razorpay.key' => 'rzp_test_x', 'services.razorpay.secret' => 's']);
        Setting::put('online_on', '0', 'bool', 'shipping');

        $this->bagWith();

        $this->get('/checkout')->assertOk()->assertDontSee('UPI, card, net banking');

        $before = Order::count();

        $this->post('/checkout', $this->address(['method' => 'razorpay']))->assertSessionHasErrors('method');

        $this->assertSame($before, Order::count());
    }

    public function test_online_payment_stays_off_without_keys_however_it_is_set(): void
    {
        config(['services.razorpay.key' => '', 'services.razorpay.secret' => '']);
        Setting::put('online_on', '1', 'bool', 'shipping');

        // Otherwise the shop offers a payment page that cannot open.
        $this->assertFalse(\App\Support\Shop::onlineOn());
    }

    public function test_cash_on_delivery_is_refused_above_the_limit_the_shop_set(): void
    {
        $dear = Product::published()->orderByDesc('price')->firstOrFail();

        Setting::put('cod_max', '5000', 'money', 'shipping');

        $this->bagWith($dear);

        $this->get('/checkout')->assertOk()->assertSee('only for orders up to');

        $before = Order::count();

        $this->post('/checkout', $this->address())->assertSessionHasErrors('method');

        $this->assertSame($before, Order::count());
    }

    public function test_a_cheap_bag_is_still_offered_cash_on_delivery(): void
    {
        $cheap = Product::published()->orderBy('price')->firstOrFail();

        Setting::put('cod_max', '99999', 'money', 'shipping');

        $this->bagWith($cheap);

        $this->get('/checkout')->assertOk()->assertSee('Cash on delivery');

        $this->post('/checkout', $this->address())->assertRedirectContains('/order/');
    }

    /* ---------------------------------------- the switches in the admin */

    public function test_the_settings_screen_shows_the_switches_as_the_shop_actually_behaves(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $this->actingAs($admin);

        \App\Models\Setting::whereIn('key', ['cod_on', 'online_on'])->delete();
        \Illuminate\Support\Facades\Cache::forget(\App\Models\Setting::CACHE_KEY);

        /*
         * Nothing stored means cash on delivery is on — so the toggle has to
         * show on. If it showed off, the first save would close the shop's
         * payments without anybody touching the switch.
         */
        \Livewire\Livewire::test(\App\Filament\Pages\ShopSettings::class)
            ->assertSet('data.cod_on', true)
            ->assertSet('data.online_on', true);
    }

    public function test_turning_a_switch_off_in_the_admin_closes_it_at_the_till(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm(['cod_on' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse(\App\Support\Shop::codOn());

        $this->bagWith();

        $this->get('/checkout')->assertOk()->assertSee('Cash on delivery is not available');
    }

    /* --------------------------------------------- who hears about an order */

    public function test_every_admin_is_told_when_an_order_comes_in(): void
    {
        Mail::fake();

        User::create(['name' => 'Amma', 'email' => 'amma@ojasvidrapes.in', 'password' => 'x-long-enough', 'is_admin' => true]);
        User::create(['name' => 'Accounts', 'email' => 'accounts@ojasvidrapes.in', 'password' => 'x-long-enough', 'is_admin' => true]);

        Setting::put('order_emails', 'warehouse@example.in', 'string', 'general');

        $this->bagWith();
        $this->post('/checkout', $this->address());

        foreach (['amma@ojasvidrapes.in', 'accounts@ojasvidrapes.in', 'warehouse@example.in'] as $who) {
            Mail::assertQueued(
                \App\Mail\NewOrderForShop::class,
                fn ($mail) => $mail->hasTo($who),
            );
        }
    }

    public function test_a_customer_is_never_told_about_somebody_elses_order(): void
    {
        Mail::fake();

        $customer = User::where('is_admin', false)->firstOrFail();

        $this->bagWith();
        $this->post('/checkout', $this->address());

        Mail::assertNotQueued(
            \App\Mail\NewOrderForShop::class,
            fn ($mail) => $mail->hasTo($customer->email),
        );
    }

    public function test_the_same_address_twice_over_is_emailed_once(): void
    {
        Mail::fake();

        // The shop's contact address is also an admin's, written differently.
        Setting::put('email', 'ADMIN@ojasvidrapes.in', 'string', 'general');
        Setting::put('order_emails', 'admin@ojasvidrapes.in,  admin@ojasvidrapes.in', 'string', 'general');

        User::create(['name' => 'Owner', 'email' => 'admin@ojasvidrapes.in', 'password' => 'x-long-enough', 'is_admin' => true]);

        $this->bagWith();
        $this->post('/checkout', $this->address());

        Mail::assertQueued(
            \App\Mail\NewOrderForShop::class,
            fn ($mail) => $mail->hasTo('admin@ojasvidrapes.in'),
        );

        Mail::assertQueuedCount(2); // the shopper's own, and one shop copy
    }

    /* ------------------------------------------------------------ the courier */

    private function withDelhivery(): void
    {
        config([
            'services.delhivery.token'  => 'test-token',
            'services.delhivery.base'   => 'https://track.delhivery.test',
            'services.delhivery.pickup' => 'OJASVI Hyderabad',
        ]);
    }

    private function anOrder(bool $cod = true): Order
    {
        $this->bagWith();
        $this->post('/checkout', $this->address(['method' => $cod ? 'cod' : 'cod']));

        return Order::latest('id')->with('items')->firstOrFail();
    }

    public function test_a_parcel_is_booked_and_its_number_written_down(): void
    {
        $this->withDelhivery();

        Http::fake(['*/api/cmu/create.json' => Http::response([
            'success'  => true,
            'packages' => [['waybill' => '1234567890123', 'status' => 'Success']],
        ])]);

        $order = $this->anOrder();

        $result = app(Delhivery::class)->book($order);

        $this->assertTrue($result['ok'], $result['message']);
        $this->assertSame('1234567890123', $order->fresh()->shipment->awb);
        $this->assertSame('manifested', $order->fresh()->shipment->status);
    }

    public function test_a_cash_order_is_booked_for_collection_and_a_paid_one_is_not(): void
    {
        $this->withDelhivery();

        Http::fake(['*' => Http::response(['packages' => [['waybill' => '999']]])]);

        $order = $this->anOrder();

        app(Delhivery::class)->book($order);

        Http::assertSent(function ($request) use ($order) {
            $shipment = json_decode($request['data'], true)['shipments'][0];

            return $shipment['payment_mode'] === 'COD'
                && (float) $shipment['cod_amount'] === round((float) $order->grand_total);
        });

        // A prepaid parcel must carry nothing to collect, or the customer is
        // asked to pay a second time at the door.
        $order->forceFill(['payment_method' => 'razorpay', 'payment_status' => 'paid'])->save();
        $order->shipment()->delete();

        app(Delhivery::class)->book(Order::with('items')->find($order->id));

        Http::assertSent(function ($request) {
            $shipment = json_decode($request['data'], true)['shipments'][0];

            return $shipment['payment_mode'] === 'Prepaid' && $shipment['cod_amount'] === '0';
        });
    }

    public function test_a_parcel_is_never_booked_twice(): void
    {
        $this->withDelhivery();

        Http::fake(['*' => Http::response(['packages' => [['waybill' => 'FIRST123']]])]);

        $order = $this->anOrder();

        app(Delhivery::class)->book($order);
        $second = app(Delhivery::class)->book($order);

        $this->assertTrue($second['ok']);
        $this->assertSame('FIRST123', $order->fresh()->shipment->awb);
        // Two labels, two pick-ups and a parcel that cannot be traced.
        Http::assertSentCount(1);
    }

    public function test_a_courier_that_refuses_says_why_and_breaks_nothing(): void
    {
        $this->withDelhivery();

        Http::fake(['*' => Http::response([
            'packages' => [['status' => 'Fail', 'remarks' => ['ClientWarehouse matching query does not exist']]],
        ])]);

        $order = $this->anOrder();

        $result = app(Delhivery::class)->book($order);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('ClientWarehouse', $result['message']);
        $this->assertNull($order->fresh()->shipment);
    }

    public function test_a_courier_that_is_down_does_not_stop_the_order(): void
    {
        $this->withDelhivery();

        Http::fake(['*' => Http::response('', 500)]);

        $order = $this->anOrder();

        $result = app(Delhivery::class)->book($order);

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('type the number in', $result['message']);

        // And the shop can still send it by hand.
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_tracking_moves_the_order_along_and_emails_when_it_leaves(): void
    {
        $this->withDelhivery();
        Mail::fake();

        Http::fake(['*/api/cmu/create.json' => Http::response(['packages' => [['waybill' => 'AWB1']]])]);

        $order = $this->anOrder();
        app(Delhivery::class)->book($order);

        Http::fake(['*/api/v1/packages/json*' => Http::response([
            'ShipmentData' => [['Shipment' => [
                'Status' => ['Status' => 'In Transit', 'StatusType' => 'IT', 'Instructions' => 'Shipment picked up'],
                'ExpectedDeliveryDate' => '2026-10-12 18:00:00',
                'Scans' => [['ScanDetail' => [
                    'ScanDateTime' => '2026-10-07T10:00:00',
                    'Instructions' => 'Shipment picked up',
                    'ScannedLocation' => 'Hyderabad',
                ]]],
            ]]],
        ])]);

        $this->artisan('ojasvi:track-parcels')->assertSuccessful();

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame('in_transit', $order->shipment->status);
        $this->assertNotEmpty($order->shipment->timeline);
        Mail::assertQueued(\App\Mail\OrderShipped::class, fn ($mail) => $mail->hasTo($order->email));
    }

    public function test_a_delivered_cash_parcel_counts_as_paid(): void
    {
        $this->withDelhivery();
        Mail::fake();

        Http::fake(['*/api/cmu/create.json' => Http::response(['packages' => [['waybill' => 'AWB2']]])]);

        $order = $this->anOrder();
        app(Delhivery::class)->book($order);

        Http::fake(['*/api/v1/packages/json*' => Http::response([
            'ShipmentData' => [['Shipment' => [
                'Status' => [
                    'Status' => 'Delivered', 'StatusType' => 'DL',
                    'Instructions' => 'Delivered', 'StatusDateTime' => '2026-10-10T12:00:00',
                ],
                'Scans' => [],
            ]]],
        ])]);

        $this->artisan('ojasvi:track-parcels')->assertSuccessful();

        $order->refresh();

        $this->assertSame('delivered', $order->status);
        $this->assertSame('paid', $order->payment_status, 'Cash is paid at the door.');
        $this->assertNotNull($order->paid_at);
    }

    public function test_a_parcel_coming_back_is_marked_returned(): void
    {
        $this->withDelhivery();

        Http::fake(['*/api/cmu/create.json' => Http::response(['packages' => [['waybill' => 'AWB3']]])]);

        $order = $this->anOrder();
        app(Delhivery::class)->book($order);

        Http::fake(['*/api/v1/packages/json*' => Http::response([
            'ShipmentData' => [['Shipment' => [
                'Status' => ['Status' => 'RTO', 'StatusType' => 'RT', 'Instructions' => 'Returning to origin'],
                'Scans' => [],
            ]]],
        ])]);

        $this->artisan('ojasvi:track-parcels')->assertSuccessful();

        $this->assertSame('returned', $order->fresh()->status);
    }

    public function test_nothing_is_asked_of_the_courier_until_it_is_set_up(): void
    {
        config(['services.delhivery.token' => '']);

        Http::fake();

        $this->artisan('ojasvi:track-parcels')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_a_delivered_parcel_is_not_asked_about_again(): void
    {
        $this->withDelhivery();

        // Clear the demonstration catalogue's parcels: one of them is legitimately
        // still on the road, and the command is right to ask about it.
        \App\Models\Shipment::query()->delete();

        $order = $this->anOrder();
        $order->shipment()->create([
            'courier' => 'delhivery', 'awb' => 'DONE1',
            'status' => 'delivered', 'delivered_at' => now()->subDay(),
        ]);

        Http::fake();

        $this->artisan('ojasvi:track-parcels')->assertSuccessful();

        Http::assertNothingSent();
    }
}
