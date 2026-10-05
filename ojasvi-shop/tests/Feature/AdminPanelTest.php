<?php

namespace Tests\Feature;

use App\Models\User;
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

    public function test_settings_can_be_saved(): void
    {
        \Livewire\Livewire::test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm(['shop_name' => 'OJASVI Drapes', 'free_shipping_from' => '3499'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('OJASVI Drapes', \App\Models\Setting::get('shop_name'));
        $this->assertSame(3499.0, \App\Models\Setting::get('free_shipping_from'));
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
