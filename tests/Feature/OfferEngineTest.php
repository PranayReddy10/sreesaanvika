<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\Product;
use App\Services\OfferEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferEngineTest extends TestCase
{
    use RefreshDatabase;

    private function saree(string $name, float $price, ?float $sale = null): Product
    {
        return Product::create([
            'name'        => $name,
            'slug'        => str($name)->slug()->value(),
            'price'       => $price,
            'sale_price'  => $sale,
            'status'      => 'published',
            'track_stock' => false,
        ]);
    }

    /** @param array<int, array{0: Product, 1: int}> $rows */
    private function bag(array $rows)
    {
        return collect($rows)->map(fn ($row, $i) => [
            'key'       => 'line'.$i,
            'product'   => $row[0],
            'colourway' => null,
            'quantity'  => $row[1],
        ]);
    }

    private function bogo(array $products, int $buy = 2, int $get = 1, int $percent = 100, bool $repeats = true): Offer
    {
        $offer = Offer::create([
            'name' => 'Buy '.$buy.' Get '.$get, 'kind' => 'bogo',
            'buy' => $buy, 'get' => $get, 'percent' => $percent,
            'repeats' => $repeats, 'is_active' => true,
        ]);

        $offer->products()->sync(collect($products)->pluck('id'));

        return $offer;
    }

    public function test_a_bag_with_no_offer_is_simply_its_subtotal(): void
    {
        $a = $this->saree('Kanchipuram', 1000);

        $result = (new OfferEngine())->apply($this->bag([[$a, 2]]));

        $this->assertSame(2000.0, $result->subtotal);
        $this->assertSame(0.0, $result->discount);
        $this->assertSame(2000.0, $result->total);
    }

    public function test_the_cheapest_of_three_goes_free(): void
    {
        $a = $this->saree('Dear', 3000);
        $b = $this->saree('Middle', 2000);
        $c = $this->saree('Cheap', 1000);
        $this->bogo([$a, $b, $c]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 1], [$b, 1], [$c, 1]]));

        // The promise on the banner is the cheapest, not the dearest.
        $this->assertSame(1000.0, $result->discount);
        $this->assertSame(5000.0, $result->total);
    }

    public function test_two_pieces_earn_nothing_and_are_told_how_many_more(): void
    {
        $a = $this->saree('One', 1000);
        $this->bogo([$a]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 2]]));

        $this->assertSame(0.0, $result->discount);
        $this->assertSame(1, $result->progress[0]['need']);
    }

    public function test_three_of_the_same_saree_count_as_three_units(): void
    {
        // One line, quantity three — an offer counts pieces, not rows.
        $a = $this->saree('Same', 900);
        $this->bogo([$a]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 3]]));

        $this->assertSame(900.0, $result->discount);
        $this->assertSame(1, $result->freeUnits('line0'));
    }

    public function test_six_pieces_earn_two_free_when_the_offer_repeats(): void
    {
        $a = $this->saree('A', 1000);
        $this->bogo([$a]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 6]]));

        $this->assertSame(2000.0, $result->discount);
    }

    public function test_an_offer_that_does_not_repeat_gives_one_free_however_large_the_bag(): void
    {
        $a = $this->saree('A', 1000);
        $this->bogo([$a], repeats: false);

        $result = (new OfferEngine())->apply($this->bag([[$a, 9]]));

        $this->assertSame(1000.0, $result->discount);
    }

    public function test_a_percentage_offer_discounts_rather_than_frees(): void
    {
        $a = $this->saree('A', 1000);
        $this->bogo([$a], percent: 50);

        $result = (new OfferEngine())->apply($this->bag([[$a, 3]]));

        $this->assertSame(500.0, $result->discount);
    }

    public function test_pieces_outside_the_offer_are_not_counted_towards_it(): void
    {
        $in  = $this->saree('Covered', 1000);
        $out = $this->saree('Not covered', 500);
        $this->bogo([$in]);

        // Two covered plus one that is not is not a qualifying three.
        $result = (new OfferEngine())->apply($this->bag([[$in, 2], [$out, 1]]));

        $this->assertSame(0.0, $result->discount);
        $this->assertSame(2500.0, $result->total);
    }

    public function test_a_free_unit_is_credited_to_the_line_it_came_from(): void
    {
        $a = $this->saree('Dear', 2000);
        $b = $this->saree('Cheap', 800);
        $this->bogo([$a, $b]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 2], [$b, 1]]));

        $this->assertSame(800.0, $result->savedOn('line1'));
        $this->assertSame(0.0, $result->savedOn('line0'));
    }

    public function test_the_sale_price_is_what_an_offer_works_from(): void
    {
        // A saree already reduced must go free at its reduced price, or the
        // shop gives away more than it meant to.
        $a = $this->saree('On sale', 2000, 1000);
        $this->bogo([$a]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 3]]));

        $this->assertSame(3000.0, $result->subtotal);
        $this->assertSame(1000.0, $result->discount);
    }

    public function test_running_it_twice_gives_the_same_answer(): void
    {
        // The engine changes nothing, so a second pass cannot compound.
        $a = $this->saree('A', 1200);
        $this->bogo([$a]);
        $bag = $this->bag([[$a, 3]]);

        $engine = new OfferEngine();
        $first  = $engine->apply($bag);
        $second = $engine->apply($bag);

        $this->assertSame($first->discount, $second->discount);
        $this->assertSame($first->total, $second->total);
    }

    public function test_an_expired_offer_does_nothing(): void
    {
        $a = $this->saree('A', 1000);
        $offer = $this->bogo([$a]);
        $offer->update(['ends_at' => now()->subDay()]);

        $this->assertSame(0.0, (new OfferEngine())->apply($this->bag([[$a, 3]]))->discount);
    }

    public function test_an_offer_that_has_not_started_does_nothing(): void
    {
        $a = $this->saree('A', 1000);
        $offer = $this->bogo([$a]);
        $offer->update(['starts_at' => now()->addDay()]);

        $this->assertSame(0.0, (new OfferEngine())->apply($this->bag([[$a, 3]]))->discount);
    }

    public function test_quantity_breaks_take_the_richest_the_bag_qualifies_for(): void
    {
        $a = $this->saree('A', 1000);
        $offer = Offer::create([
            'name' => 'Bulk', 'kind' => 'tiers', 'is_active' => true,
            'tiers' => [['qty' => 2, 'percent' => 5], ['qty' => 4, 'percent' => 15]],
        ]);
        $offer->products()->sync([$a->id]);

        // Four pieces pass both breaks; only the better one applies.
        $result = (new OfferEngine())->apply($this->bag([[$a, 4]]));

        $this->assertSame(600.0, $result->discount);
    }

    public function test_a_bag_below_every_break_pays_full_price(): void
    {
        $a = $this->saree('A', 1000);
        $offer = Offer::create([
            'name' => 'Bulk', 'kind' => 'tiers', 'is_active' => true,
            'tiers' => [['qty' => 3, 'percent' => 10]],
        ]);
        $offer->products()->sync([$a->id]);

        $result = (new OfferEngine())->apply($this->bag([[$a, 2]]));

        $this->assertSame(0.0, $result->discount);
        $this->assertSame(1, $result->progress[0]['need']);
    }

    public function test_an_offer_can_cover_the_whole_shop(): void
    {
        $a = $this->saree('A', 1000);
        Offer::create([
            'name' => 'Everything', 'kind' => 'bogo', 'buy' => 2, 'get' => 1,
            'percent' => 100, 'repeats' => true, 'is_active' => true, 'applies_to_all' => true,
        ]);

        $this->assertSame(1000.0, (new OfferEngine())->apply($this->bag([[$a, 3]]))->discount);
    }

    public function test_an_empty_bag_is_free_and_does_not_fall_over(): void
    {
        $result = (new OfferEngine())->apply(collect());

        $this->assertSame(0.0, $result->subtotal);
        $this->assertSame(0.0, $result->total);
    }
}
