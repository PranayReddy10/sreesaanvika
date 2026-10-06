<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every admin screen, opened.
 *
 * Filament resolves a closure's arguments by name at render time, so a form or
 * table that is wrong in that way is perfectly valid PHP and fails only when
 * somebody opens the page. These tests open every page, against seeded rows,
 * which is the only way that class of mistake is caught before the shop finds
 * it.
 */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);

        $this->admin = User::create([
            'name' => 'OJASVI',
            'email' => 'admin@example.test',
            'password' => 'secret-for-tests',
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($this->admin);
    }

    public static function listPages(): array
    {
        return [
            'dashboard'      => ['/admin'],
            'sarees'         => ['/admin/products'],
            'collections'    => ['/admin/categories'],
            'descriptions'   => ['/admin/attributes'],
            'orders'         => ['/admin/orders'],
            'offers'         => ['/admin/offers'],
            'coupons'        => ['/admin/coupons'],
            'front page'     => ['/admin/sections'],
            'reviews'        => ['/admin/reviews'],
            'customers'      => ['/admin/users'],
            'delivery areas' => ['/admin/shipping-zones'],
            'settings'       => ['/admin/shop-settings'],
        ];
    }

    #[DataProvider('listPages')]
    public function test_every_list_page_opens(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public static function createPages(): array
    {
        return [
            'saree'         => ['/admin/products/create'],
            'collection'    => ['/admin/categories/create'],
            'description'   => ['/admin/attributes/create'],
            'offer'         => ['/admin/offers/create'],
            'coupon'        => ['/admin/coupons/create'],
            'front page row' => ['/admin/sections/create'],
            'review'        => ['/admin/reviews/create'],
            'customer'      => ['/admin/users/create'],
            'delivery area' => ['/admin/shipping-zones/create'],
        ];
    }

    #[DataProvider('createPages')]
    public function test_every_create_page_opens(string $path): void
    {
        $this->get($path)->assertOk();
    }

    public static function editPages(): array
    {
        return [
            'saree'         => ['products'],
            'collection'    => ['categories'],
            'description'   => ['attributes'],
            'offer'         => ['offers'],
            'coupon'        => ['coupons'],
            'front page row' => ['sections'],
            'review'        => ['reviews'],
            'customer'      => ['users'],
            'delivery area' => ['shipping-zones'],
        ];
    }

    #[DataProvider('editPages')]
    public function test_every_edit_page_opens(string $resource): void
    {
        $this->get("/admin/{$resource}/1/edit")->assertOk();
    }

    /**
     * Every link the admin writes to a record, followed.
     *
     * Opening /admin/products/1/edit by hand proves nothing about the button
     * the shop actually presses, and that was the gap. Filament builds a
     * record's address from the model's own route key — the slug for a saree,
     * the number for an order, because that is what the shop's public
     * addresses are made of — while a resource that sets $recordRouteKeyName
     * looks the record up by something else. Nothing reconciles the two, so
     * every Edit and View link on those resources pointed where nothing could
     * be found and answered 404, while a test typing the id straight in
     * passed.
     *
     * Swept over the whole panel rather than listed, so a resource added later
     * cannot bring it back quietly.
     */
    public function test_every_link_the_admin_writes_to_a_record_opens(): void
    {
        $followed = 0;

        foreach (Filament::getPanel('admin')->getResources() as $resource) {
            $record = $resource::getModel()::query()->first();

            if (! $record) {
                continue;
            }

            foreach (['view', 'edit'] as $page) {
                if (! $resource::hasPage($page)) {
                    continue;
                }

                $url = $resource::getUrl($page, ['record' => $record]);

                $this->assertSame(200, $this->get($url)->getStatusCode(), $url);

                $followed++;
            }
        }

        $this->assertGreaterThan(5, $followed, 'no links were followed, so this proves nothing');
    }

    public function test_an_order_can_be_read(): void
    {
        // Every order the seeder makes, because each status renders different
        // panels — a shipped one has a courier, a refunded one has no shipment.
        foreach (\App\Models\Order::pluck('id') as $id) {
            $this->get("/admin/orders/{$id}")->assertOk();
        }
    }

    public function test_an_order_can_be_moved_on_from_the_list(): void
    {
        $order = \App\Models\Order::where('status', 'confirmed')->firstOrFail();

        \Livewire\Livewire::test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->callTableAction('advance', $order, ['status' => 'packed', 'note' => 'Boxed'])
            ->assertHasNoActionErrors();

        $this->assertSame('packed', $order->fresh()->status);

        // The move is recorded, not just applied: an order that changed hands
        // must be able to say when, and on whose say-so.
        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->getKey(),
            'from' => 'confirmed',
            'to' => 'packed',
            'user_id' => $this->admin->getKey(),
        ]);
    }

    public function test_an_order_can_be_sent_which_emails_the_tracking_number(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $order = \App\Models\Order::where('status', 'confirmed')->firstOrFail();

        \Livewire\Livewire::test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->callTableAction('ship', $order, [
                'courier' => 'delhivery',
                'awb' => '12345678901',
                'expected_on' => now()->addDays(4)->toDateString(),
                'tell_them' => true,
            ])
            ->assertHasNoActionErrors();

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame('12345678901', $order->shipment->awb);

        \Illuminate\Support\Facades\Mail::assertQueued(
            \App\Mail\OrderShipped::class,
            fn ($mail) => $mail->hasTo($order->email),
        );
    }

    public function test_calling_off_an_order_puts_the_stock_back(): void
    {
        $order = \App\Models\Order::where('status', 'confirmed')->with('items')->firstOrFail();
        $line = $order->items->first();
        $before = \App\Models\Product::find($line->product_id)->stock;

        \Livewire\Livewire::test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->callTableAction('cancel', $order, ['why' => 'They changed their mind'])
            ->assertHasNoActionErrors();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(
            $before + $line->quantity,
            \App\Models\Product::find($line->product_id)->stock,
            'A cancelled order must not leave the shop believing pieces are sold.',
        );
    }

    public function test_a_cash_order_counts_as_paid_once_it_arrives(): void
    {
        $order = \App\Models\Order::where('payment_method', 'cod')
            ->where('payment_status', '!=', 'paid')
            ->firstOrFail();

        $order->forceFill(['status' => 'shipped'])->save();
        $order->shipment()->create(['courier' => 'delhivery', 'awb' => '999', 'status' => 'in_transit']);

        \Livewire\Livewire::test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->callTableAction('delivered', $order)
            ->assertHasNoActionErrors();

        $order->refresh();

        $this->assertSame('delivered', $order->status);
        $this->assertSame('paid', $order->payment_status, 'Cash is paid at the door.');
    }

    public function test_a_refund_cannot_exceed_what_was_charged(): void
    {
        $order = \App\Models\Order::where('payment_status', 'paid')->firstOrFail();

        \Livewire\Livewire::test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->callTableAction('refund', $order, ['amount' => (float) $order->grand_total + 1000])
            ->assertHasActionErrors(['amount']);

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_a_refund_without_a_gateway_payment_is_recorded_anyway(): void
    {
        // A cash order that was paid at the door: there is nothing to call, so
        // the shop pays it back by hand and this records that it did.
        $order = \App\Models\Order::where('payment_method', 'cod')->firstOrFail();
        $order->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();

        \Livewire\Livewire::test(\App\Filament\Resources\Orders\Pages\ListOrders::class)
            ->callTableAction('refund', $order, ['amount' => (float) $order->grand_total, 'why' => 'Returned'])
            ->assertHasNoActionErrors();

        $order->refresh();

        $this->assertSame('refunded', $order->payment_status);
        $this->assertEquals((float) $order->grand_total, (float) $order->refunded_total);
    }

    public function test_settings_can_be_saved(): void
    {
        \Livewire\Livewire::test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm(['shop_name' => 'OJASVI Drapes', 'free_shipping_from' => '3499'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('OJASVI Drapes', \App\Models\Setting::get('shop_name'));
        $this->assertSame(3499.0, \App\Models\Setting::get('free_shipping_from'));
    }

    public function test_the_seeder_refuses_to_make_an_account_nobody_can_sign_in_to(): void
    {
        \App\Models\User::query()->forceDelete();

        /*
         * A blank ADMIN_PASSWORD in .env is an empty string, not the fallback,
         * because env() only falls back when the key is absent. Seeding it
         * hashed an empty string and made an account that could never be
         * signed in to, since the login form will not submit one.
         */
        config(['shop.admin.password' => '']);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertSame(0, \App\Models\User::count(), 'Better no admin than an unusable one.');
    }

    public function test_an_admin_password_can_be_set_from_the_command_line(): void
    {
        \App\Models\User::query()->forceDelete();

        $this->artisan('ojasvi:admin', [
            '--email' => 'owner@example.test',
            '--password' => 'a-real-password',
            '--name' => 'Owner',
        ])->assertSuccessful();

        $admin = \App\Models\User::firstWhere('email', 'owner@example.test');

        $this->assertNotNull($admin);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a-real-password', $admin->password));

        // And it is the way back in for an account that already exists.
        $this->artisan('ojasvi:admin', [
            '--email' => 'owner@example.test',
            '--password' => 'a-different-password',
        ])->assertSuccessful();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('a-different-password', $admin->fresh()->password));
        $this->assertSame(1, \App\Models\User::where('email', 'owner@example.test')->count());
    }

    public function test_a_password_too_short_to_be_safe_is_refused(): void
    {
        \App\Models\User::query()->forceDelete();

        $this->artisan('ojasvi:admin', ['--email' => 'owner@example.test', '--password' => 'short'])
            ->assertFailed();

        $this->assertSame(0, \App\Models\User::count());
    }

    /**
     * Nothing in the admin asks the server for anything by itself.
     *
     * Filament polls a chart every 5 seconds and the notification bell every
     * 30 unless both are turned off. On shared hosting that is how an admin
     * tab left open on the dashboard spends the account's request allowance
     * and gets the whole site answered 429 — shoppers included. Written down
     * as a test because the cost is invisible: the page looks identical
     * either way, and a widget added later brings the 5 seconds back with it.
     *
     * The widgets are rendered rather than asked, because the polling is only
     * ever visible in the markup — and only after the lazy placeholder has
     * given way to the real thing, which is what the browser ends up holding.
     */
    public function test_no_admin_page_polls_the_server(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertFalse(
            $panel->hasDatabaseNotifications(),
            'the notification bell asks the server every 30 seconds whether it is still empty',
        );

        $widgets = $panel->getWidgets();

        $this->assertNotEmpty($widgets, 'no widgets found, so this test is proving nothing');

        Livewire::withoutLazyLoading();

        foreach ($widgets as $widget) {
            $html = Livewire::test($widget)->html();

            $this->assertStringNotContainsString('wire:poll', $html, $widget.' polls the server');
        }
    }

    public function test_a_customer_cannot_open_the_admin(): void
    {
        $customer = User::firstWhere('is_admin', false);

        $this->actingAs($customer)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_a_stranger_is_sent_to_the_login_page(): void
    {
        auth()->logout();

        $this->get('/admin/orders')->assertRedirect('/admin/login');
    }
}
