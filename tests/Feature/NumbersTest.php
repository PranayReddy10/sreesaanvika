<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\Support\Shop;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The figures the shop keeps by hand.
 *
 * A saree shop sells across a counter as well as on a website, so the number
 * worth showing a shopper — how many pieces have gone out, how many people
 * have written in — is not one this software can count. It is typed in, and
 * everything here is about it being the shop's figure: shown when it is set,
 * absent when it is not, and never quietly replaced by something smaller that
 * happens to be countable.
 */
class NumbersTest extends TestCase
{
    use RefreshDatabase;

    public function test_nothing_is_shown_until_the_shop_sets_something(): void
    {
        $this->assertSame([], Shop::numbers());
    }

    public function test_a_figure_that_is_set_comes_back_with_its_label(): void
    {
        Setting::put('sold_count', 4120, 'int', 'numbers');

        $this->assertSame(
            [['value' => '4,120', 'label' => 'Sarees sold']],
            Shop::numbers(),
        );
    }

    /** Lakhs, not thousands: this shop sells in India. */
    public function test_a_large_figure_is_grouped_the_Indian_way(): void
    {
        Setting::put('sold_count', 124500, 'int', 'numbers');

        $this->assertSame('1,24,500', Shop::numbers()[0]['value']);
        $this->assertSame('1,24,500', Shop::grouped(124500));
        $this->assertSame('999', Shop::grouped(999));
    }

    /** And the money it has always written stays written the same way. */
    public function test_grouping_money_is_unchanged(): void
    {
        $this->assertSame('₹1,24,500', Shop::money(124500));
        $this->assertSame('₹999', Shop::money(999));
        $this->assertSame('-₹1,250.50', Shop::money(-1250.5, true));
    }

    public function test_the_shop_can_call_them_what_it_likes(): void
    {
        Setting::put('sold_count', 300, 'int', 'numbers');
        Setting::put('sold_label', 'Sarees in homes', 'string', 'numbers');
        Setting::put('reviews_count', 86, 'int', 'numbers');
        Setting::put('reviews_label', 'Kind words', 'string', 'numbers');

        $this->assertSame([
            ['value' => '300', 'label' => 'Sarees in homes'],
            ['value' => '86', 'label' => 'Kind words'],
        ], Shop::numbers());
    }

    /** One set and one not is one figure, not one figure and a zero. */
    public function test_a_figure_left_empty_is_left_out(): void
    {
        Setting::put('reviews_count', 86, 'int', 'numbers');

        $this->assertSame([['value' => '86', 'label' => 'Reviews']], Shop::numbers());
    }

    /* ------------------------------------------------- and on the shop */

    public function test_the_front_page_row_shows_them(): void
    {
        $this->seed(DemoSeeder::class);

        Setting::put('sold_count', 4120, 'int', 'numbers');
        Setting::put('reviews_count', 86, 'int', 'numbers');

        $this->theNumbersRow();

        $this->get('/')
            ->assertOk()
            ->assertSee('4,120', false)
            ->assertSee('Sarees sold', false)
            ->assertSee('86', false)
            ->assertSee('Reviews', false);
    }

    /**
     * And says nothing when there is nothing to say.
     *
     * A row left on the front page while the figures are empty must not draw
     * a heading over a blank space — "0 sarees sold" is worse than silence.
     */
    public function test_the_row_draws_nothing_when_the_figures_are_empty(): void
    {
        $this->seed(DemoSeeder::class);

        // The demo catalogue comes with figures; a real shop starts without
        // them, and this is that shop.
        Setting::put('sold_count', 0, 'int', 'numbers');
        Setting::put('reviews_count', 0, 'int', 'numbers');

        $this->theNumbersRow('What we have sold');

        $this->get('/')->assertOk()->assertDontSee('What we have sold', false);
    }

    public function test_the_shop_can_set_them_in_the_admin(): void
    {
        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\ShopSettings::class)
            ->fillForm([
                'shop_name' => 'OJASVI',
                'sold_count' => 4120,
                'reviews_count' => 86,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(4120, Setting::get('sold_count'));
        $this->assertSame([
            ['value' => '4,120', 'label' => 'Sarees sold'],
            ['value' => '86', 'label' => 'Reviews'],
        ], Shop::numbers());
    }

    private function theNumbersRow(?string $heading = null): Section
    {
        return Section::create([
            'key' => 'numbers',
            'heading' => $heading,
            'position' => 99,
            'is_visible' => true,
        ]);
    }
}
