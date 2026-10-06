<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The address a shopper has already given, kept for the next time.
 *
 * The checkout always knew how to offer a saved address; nothing ever saved
 * one, so for everybody but the seeder's own customers it was always empty.
 */
class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    private function aCustomer(): User
    {
        return User::create([
            'name' => 'Lakshmi', 'email' => 'lakshmi-'.uniqid().'@example.in',
            'password' => 'long-enough-for-this',
        ]);
    }

    private function order(User $customer, array $address = []): void
    {
        $saree = Product::published()->where('track_stock', false)->first()
            ?? Product::published()->firstOrFail();

        $this->actingAs($customer)->post('/bag/add', ['product_id' => $saree->id, 'quantity' => 1]);

        $this->actingAs($customer)->post('/checkout', array_merge([
            'name' => 'Lakshmi Prasad',
            'email' => $customer->email,
            'phone' => '9000000001',
            'line1' => '4-2-18 Sultan Bazaar',
            'city' => 'Hyderabad',
            'state' => 'Telangana',
            'pincode' => '500095',
            'method' => 'cod',
        ], $address));
    }

    public function test_an_order_leaves_its_address_behind_for_next_time(): void
    {
        $customer = $this->aCustomer();

        $this->assertSame(0, $customer->addresses()->count());

        $this->order($customer);

        $kept = $customer->addresses()->firstOrFail();

        $this->assertSame('4-2-18 Sultan Bazaar', $kept->line1);
        $this->assertSame('500095', $kept->pincode);
        $this->assertTrue($kept->is_default, 'the one just posted to is the one to offer next');
    }

    /** The same house typed twice is one house. */
    public function test_ordering_to_the_same_place_twice_keeps_one_address(): void
    {
        $customer = $this->aCustomer();

        $this->order($customer);
        $this->order($customer);

        $this->assertSame(1, $customer->addresses()->count());
    }

    public function test_a_new_address_becomes_the_one_the_checkout_offers(): void
    {
        $customer = $this->aCustomer();

        $this->order($customer);
        $this->order($customer, ['line1' => '12 Banjara Hills', 'pincode' => '500034']);

        $this->assertSame(2, $customer->addresses()->count());

        $this->assertSame('12 Banjara Hills', $customer->fresh()->defaultAddress->line1);

        // And the checkout offers it without being asked.
        $this->actingAs($customer)->post('/bag/add', ['product_id' => Product::published()->first()->id]);

        $this->actingAs($customer)->get('/checkout')
            ->assertOk()
            ->assertSee('12 Banjara Hills', false);
    }

    public function test_a_guest_leaves_nothing_behind(): void
    {
        $before = Address::count();

        $saree = Product::published()->firstOrFail();

        $this->post('/bag/add', ['product_id' => $saree->id]);
        $this->post('/checkout', [
            'name' => 'A guest', 'email' => 'guest@example.in', 'phone' => '9000000009',
            'line1' => '1 Nowhere', 'city' => 'Hyderabad', 'state' => 'Telangana',
            'pincode' => '500001', 'method' => 'cod',
        ]);

        $this->assertSame($before, Address::count(), 'a guest has nowhere to keep it');
    }

    public function test_she_can_choose_which_one_and_remove_the_rest(): void
    {
        $customer = $this->aCustomer();

        $this->order($customer);
        $this->order($customer, ['line1' => '12 Banjara Hills', 'pincode' => '500034']);

        $first = $customer->addresses()->where('line1', '4-2-18 Sultan Bazaar')->firstOrFail();

        $this->actingAs($customer)->post(route('account.address.use', $first))->assertRedirect();

        $this->assertTrue($first->fresh()->is_default);
        $this->assertSame(1, $customer->addresses()->where('is_default', true)->count());

        $this->actingAs($customer)->delete(route('account.address.forget', $first))->assertRedirect();

        $this->assertSame(1, $customer->addresses()->count());
    }

    /** Somebody else's address is not hers to move or delete. */
    public function test_one_customer_cannot_touch_another_s_address(): void
    {
        $hers = $this->aCustomer();
        $theirs = $this->aCustomer();

        $this->order($theirs);

        $address = $theirs->addresses()->firstOrFail();

        $this->actingAs($hers)->post(route('account.address.use', $address))->assertNotFound();
        $this->actingAs($hers)->delete(route('account.address.forget', $address))->assertNotFound();

        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_they_are_shown_on_her_account(): void
    {
        $customer = $this->aCustomer();

        $this->order($customer);

        $this->actingAs($customer)->get(route('account'))
            ->assertOk()
            ->assertSee('Where we send things')
            ->assertSee('4-2-18 Sultan Bazaar', false)
            ->assertSee('The next one goes here');
    }
}
