<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Accounts are optional here — a shopper can buy without one — so what matters
 * is that having one never costs them anything they were carrying.
 */
class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    public function test_somebody_can_make_an_account_and_sign_in(): void
    {
        $this->post('/join', [
            'name' => 'Padma',
            'email' => 'padma@example.in',
            'password' => 'a-long-enough-one',
            'password_confirmation' => 'a-long-enough-one',
        ])->assertRedirect('/account');

        $this->assertAuthenticated();

        $this->post('/sign-out')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/sign-in', ['email' => 'padma@example.in', 'password' => 'a-long-enough-one'])
            ->assertRedirect('/account');

        $this->assertAuthenticated();
    }

    public function test_a_new_account_is_never_an_admin(): void
    {
        $this->post('/join', [
            'name' => 'Padma',
            'email' => 'padma@example.in',
            'password' => 'a-long-enough-one',
            'password_confirmation' => 'a-long-enough-one',
            // Posted by somebody who read the database schema.
            'is_admin' => 1,
        ]);

        $this->assertFalse(User::firstWhere('email', 'padma@example.in')->is_admin);
        $this->get('/admin')->assertForbidden();
    }

    public function test_the_wrong_password_is_refused_and_then_throttled(): void
    {
        $user = User::where('is_admin', false)->firstOrFail();

        foreach (range(1, 5) as $try) {
            $this->post('/sign-in', ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
        }

        $this->assertGuest();

        // The sixth is refused for being the sixth, not for being wrong — so
        // the form cannot be used to work through a password list.
        $this->post('/sign-in', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_guest_bag_follows_them_in_when_they_sign_in(): void
    {
        // One with stock to spare, so the clamp cannot stand in for the bug
        // this test is about.
        $product = Product::published()->where('stock', '>=', 6)->firstOrFail();
        $user = User::where('is_admin', false)->firstOrFail();

        // Fill a bag as a guest, and keep the cookie the browser would keep.
        $this->post('/bag/add', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect('/bag');

        $token = \App\Models\Cart::whereNull('user_id')->value('token');
        $this->assertNotNull($token, 'A guest bag should exist.');

        // withCookie, not withUnencryptedCookie: the shop's cookies go through
        // EncryptCookies like a browser's, and a raw one is simply dropped.
        $this->withCookie(CartService::COOKIE, $token)
            ->post('/sign-in', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/account');

        // And it is there on the next page they open, not only once they add
        // something else.
        $this->actingAs($user->fresh())
            ->get('/bag')
            ->assertOk()
            ->assertSee($product->name, false);

        $this->assertSame(2, (int) $user->cart()->first()?->items()->sum('quantity'));
    }

    public function test_a_saree_can_be_saved_and_unsaved(): void
    {
        $user = User::where('is_admin', false)->firstOrFail();
        $product = Product::published()->firstOrFail();

        $this->actingAs($user);

        $this->from("/saree/{$product->slug}")->post("/account/saved/{$product->slug}")->assertRedirect();
        $this->assertDatabaseHas('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);

        $this->get('/account/saved')->assertOk()->assertSee($product->name, false);

        $this->from("/saree/{$product->slug}")->post("/account/saved/{$product->slug}")->assertRedirect();
        $this->assertDatabaseMissing('wishlist_items', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_saving_needs_an_account(): void
    {
        $product = Product::published()->firstOrFail();

        $this->post("/account/saved/{$product->slug}")->assertRedirect('/sign-in');
    }

    public function test_an_account_shows_only_its_own_orders(): void
    {
        $user = User::whereHas('orders')->firstOrFail();
        $other = \App\Models\Order::where('user_id', '!=', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->get('/account')
            ->assertOk()
            ->assertSee($user->orders()->first()->number, false)
            ->assertDontSee($other->number, false);
    }
}
