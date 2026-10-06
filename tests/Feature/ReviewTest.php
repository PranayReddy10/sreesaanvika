<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a shopper thought of a saree.
 *
 * The shop could already show reviews and moderate them; there was simply no
 * way for anybody to write one.
 */
class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    /** Always the same one: published() has no order of its own. */
    private function aSaree(): Product
    {
        return Product::published()->orderBy('id')->firstOrFail();
    }

    private function words(array $extra = []): array
    {
        return array_merge([
            'rating' => 5,
            'title'  => 'Wore it to my sister’s wedding',
            'body'   => 'The colour is exactly as it looks here, and it falls beautifully.',
        ], $extra);
    }

    /**
     * Somebody with an account, at a given address or at one nobody else has.
     *
     * The seeder already made customers, and some of these tests need the
     * address an order was placed with — so this takes over an existing
     * account rather than failing on a duplicate.
     */
    private function aCustomer(?string $email = null): User
    {
        $customer = User::firstOrNew(['email' => $email ?: 'lakshmi-'.uniqid().'@example.in']);

        $customer->fill(['name' => 'Lakshmi', 'password' => 'long-enough-for-this'])->save();

        return $customer;
    }

    public function test_a_shopper_can_write_one(): void
    {
        $saree = $this->aSaree();

        $this->actingAs($this->aCustomer())
            ->post(route('review.store', $saree->slug), $this->words())
            ->assertRedirect()
            ->assertSessionHas('review');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $saree->id,
            'name'       => 'Lakshmi',
            'rating'     => 5,
        ]);
    }

    /** Nothing goes up until somebody at the shop has read it. */
    public function test_it_does_not_appear_on_the_shop_until_it_is_approved(): void
    {
        $saree = $this->aSaree();

        $this->actingAs($this->aCustomer())->post(route('review.store', $saree->slug), $this->words([
            'title' => 'A thing nobody has read yet',
        ]));

        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertDontSee('A thing nobody has read yet');

        Review::where('title', 'A thing nobody has read yet')->update(['is_approved' => true]);

        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertSee('A thing nobody has read yet');
    }

    /**
     * The verified badge is earned rather than claimed.
     *
     * Matched on the address the order was placed with, because most people
     * buy as a guest and come back weeks later to say what they thought.
     */
    public function test_someone_who_actually_bought_it_is_marked_as_such(): void
    {
        // An order that actually carries an address: that is the only thing
        // the badge can be matched on.
        $order = Order::where('payment_status', 'paid')
            ->whereNotNull('email')
            ->whereHas('items')
            ->orderBy('id')
            ->firstOrFail();
        $bought = $order->items()->orderBy('id')->firstOrFail();
        $saree = Product::findOrFail($bought->product_id);

        $customer = $this->aCustomer($order->email);

        // The seeder may already have her writing about this one, which is the
        // rule about writing twice rather than the thing being tested here.
        Review::where('user_id', $customer->id)->where('product_id', $saree->id)->delete();

        $this->actingAs($customer)->post(route('review.store', $saree->slug), $this->words());

        $written = Review::where('user_id', $customer->id)->where('product_id', $saree->id)->firstOrFail();

        $this->assertTrue($written->is_verified);

        // Tied to an order of hers holding this saree — not necessarily the
        // one picked above, since a customer may have bought it more than
        // once and the newest is the one that counts.
        $this->assertNotNull($written->order_id);

        $linked = Order::findOrFail($written->order_id);

        $this->assertSame($order->email, $linked->email);
        $this->assertTrue($linked->items()->where('product_id', $saree->id)->exists());
    }

    public function test_a_stranger_is_not_marked_as_having_bought_it(): void
    {
        $saree = $this->aSaree();

        $this->actingAs($this->aCustomer('nobody@example.in'))
            ->post(route('review.store', $saree->slug), $this->words());

        $this->assertDatabaseHas('reviews', [
            'product_id'  => $saree->id,
            'is_verified' => false,
            'order_id'    => null,
        ]);
    }

    public function test_the_same_person_cannot_write_about_the_same_saree_twice(): void
    {
        $saree = $this->aSaree();
        $customer = $this->aCustomer();

        $this->actingAs($customer)
            ->post(route('review.store', $saree->slug), $this->words())
            ->assertSessionHas('review');

        $this->actingAs($customer)
            ->post(route('review.store', $saree->slug), $this->words(['title' => 'Again']))
            ->assertSessionHas('review_error');

        $this->assertSame(1, Review::where('product_id', $saree->id)->where('user_id', $customer->id)->count());
    }

    public function test_a_review_needs_a_rating_and_something_to_say(): void
    {
        $saree = $this->aSaree();

        $this->actingAs($this->aCustomer())
            ->post(route('review.store', $saree->slug), $this->words(['rating' => null, 'body' => 'Nice']))
            ->assertSessionHasErrors(['rating', 'body']);

        $this->assertSame(0, Review::where('product_id', $saree->id)->where('name', 'Lakshmi')->count());
    }

    /** The field no human sees, which is the whole point of it. */
    public function test_something_filling_in_every_box_is_turned_away(): void
    {
        $saree = $this->aSaree();

        $this->actingAs($this->aCustomer())
            ->post(route('review.store', $saree->slug), $this->words(['website' => 'http://buy-handbags.example']))
            ->assertSessionHasErrors('website');
    }

    public function test_the_form_is_on_the_saree_page_for_somebody_signed_in(): void
    {
        $saree = $this->aSaree();

        // The heading rather than the button, whose wording depends on whether
        // anybody has written about this one yet.
        $this->actingAs($this->aCustomer())
            ->get(route('product', $saree->slug))
            ->assertOk()
            ->assertSee('What did you think of it?')
            ->assertSee(route('review.store', $saree->slug), false);
    }

    public function test_a_saree_nobody_has_written_about_asks_to_be_the_first(): void
    {
        $quiet = Product::published()->whereDoesntHave('approvedReviews')->orderBy('id')->firstOrFail();

        $this->actingAs($this->aCustomer())
            ->get(route('product', $quiet->slug))
            ->assertOk()
            ->assertSee('Be the first to say something');
    }

    /**
     * A guest is asked to sign in, and brought back to where she was.
     *
     * Reviews come from people with an account — the cheapest thing that keeps
     * a shop of twelve sarees from waking up to forty reviews of somebody
     * else's handbags.
     */
    public function test_a_guest_is_asked_to_sign_in_first(): void
    {
        $saree = $this->aSaree();

        $this->get(route('product', $saree->slug))
            ->assertOk()
            ->assertSee('Sign in to write a review')
            ->assertDontSee('What did you think of it?');

        $this->post(route('review.store', $saree->slug), $this->words())
            ->assertRedirect(route('sign-in'));

        $this->assertSame(0, Review::where('product_id', $saree->id)->where('name', 'Lakshmi')->count());
    }

    public function test_signing_in_brings_her_back_to_the_reviews(): void
    {
        $saree = $this->aSaree();
        $back = route('product', $saree->slug).'#reviews';

        $customer = $this->aCustomer('meera@example.in');

        $this->post('/sign-in', [
            'email' => 'meera@example.in',
            'password' => 'long-enough-for-this',
            'next' => $back,
        ])->assertRedirect($back);
    }

    /** A sign-in form that will send people anywhere is a phishing kit. */
    public function test_it_will_not_send_her_to_somebody_else_s_website(): void
    {
        $this->aCustomer('meera@example.in');

        $this->post('/sign-in', [
            'email' => 'meera@example.in',
            'password' => 'long-enough-for-this',
            'next' => 'https://not-ojasvi.example/collect',
        ])->assertRedirect(route('account'));
    }
}
