<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Setting;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private function cart(): CartService
    {
        return app(CartService::class);
    }

    private function saree(string $name, float $price, ?int $stock = null): Product
    {
        return Product::create([
            'name'        => $name,
            'slug'        => str($name)->slug()->value(),
            'price'       => $price,
            'status'      => 'published',
            'track_stock' => $stock !== null,
            'stock'       => $stock ?? 0,
        ]);
    }

    public function test_adding_the_same_piece_twice_adds_up_rather_than_duplicating(): void
    {
        $a = $this->saree('Kanchipuram', 1000);
        $service = $this->cart();

        $service->add($a, 1);
        $service->add($a, 2);

        $cart = $service->current();
        $this->assertCount(1, $cart->items);
        $this->assertSame(3, $cart->items->first()->quantity);
    }

    public function test_asking_for_more_than_there_is_takes_everything_there_is_and_says_so(): void
    {
        $a = $this->saree('Rare', 1000, stock: 2);

        $result = $this->cart()->add($a, 5);

        $this->assertTrue($result['ok']);
        $this->assertNotNull($result['message']);
        $this->assertSame(2, $this->cart()->current()->items->first()->quantity);
    }

    public function test_a_piece_with_no_stock_cannot_go_in(): void
    {
        $a = $this->saree('Sold out', 1000, stock: 0);

        $this->assertFalse($this->cart()->add($a, 1)['ok']);
    }

    public function test_an_unpublished_piece_cannot_go_in(): void
    {
        $a = $this->saree('Draft', 1000);
        $a->update(['status' => 'draft']);

        $this->assertFalse($this->cart()->add($a, 1)['ok']);
    }

    public function test_setting_the_quantity_to_zero_takes_the_line_out(): void
    {
        $a = $this->saree('A', 1000);
        $service = $this->cart();
        $service->add($a, 2);

        $item   = $service->current()->items->first();
        $result = $service->setQuantity($item, 0);

        $this->assertTrue($result['removed']);
        $this->assertCount(0, $service->current(false)->items()->get());
    }

    public function test_the_total_follows_the_product_and_is_never_stored(): void
    {
        // The bag records what was chosen; the price is read fresh each time.
        $a = $this->saree('A', 1000);
        $service = $this->cart();
        $service->add($a, 2);

        $this->assertSame(2000.0, $service->totals()->subtotal);

        $a->update(['price' => 1500]);

        $this->assertSame(3000.0, $service->totals()->subtotal);
    }

    public function test_delivery_is_charged_below_the_threshold_and_free_above_it(): void
    {
        Setting::put('free_shipping_from', 2999, 'money');
        Setting::put('shipping_flat', 99, 'money');

        $a = $this->saree('A', 1000);
        $service = $this->cart();
        $service->add($a, 1);

        $this->assertSame(99.0, $service->totals()->shippingTotal);

        $service->setQuantity($service->current()->items->first(), 3);

        $this->assertSame(0.0, $service->totals()->shippingTotal);
    }

    public function test_the_shopper_is_told_how_much_more_earns_free_delivery(): void
    {
        Setting::put('free_shipping_from', 2999, 'money');
        $a = $this->saree('A', 699);
        $service = $this->cart();
        $service->add($a, 1);

        $this->assertSame(2300.0, $service->totals()->awayFromFreeShipping(2999));
    }

    public function test_a_percentage_coupon_comes_off_the_goods(): void
    {
        Setting::put('free_shipping_from', 2999, 'money');
        $a = $this->saree('A', 1000);
        Coupon::create(['code' => 'OJASVI10', 'type' => 'percent', 'value' => 10, 'is_active' => true]);

        $service = $this->cart();
        $service->add($a, 2);
        $cart = $service->current();
        $cart->update(['coupon_code' => 'OJASVI10']);

        $totals = $service->totals($cart->fresh('items'));

        $this->assertSame(200.0, $totals->couponTotal);
    }

    public function test_a_coupon_below_its_minimum_spend_does_nothing(): void
    {
        $a = $this->saree('A', 500);
        Coupon::create([
            'code' => 'BIG', 'type' => 'fixed', 'value' => 100,
            'min_spend' => 5000, 'is_active' => true,
        ]);

        $service = $this->cart();
        $service->add($a, 1);
        $cart = $service->current();
        $cart->update(['coupon_code' => 'BIG']);

        $this->assertSame(0.0, $service->totals($cart->fresh('items'))->couponTotal);
    }

    public function test_a_free_shipping_coupon_removes_the_delivery_charge(): void
    {
        Setting::put('free_shipping_from', 999999, 'money');
        Setting::put('shipping_flat', 99, 'money');

        $a = $this->saree('A', 500);
        Coupon::create(['code' => 'SHIPFREE', 'type' => 'free_shipping', 'is_active' => true]);

        $service = $this->cart();
        $service->add($a, 1);
        $cart = $service->current();
        $cart->update(['coupon_code' => 'SHIPFREE']);

        $this->assertSame(0.0, $service->totals($cart->fresh('items'))->shippingTotal);
    }

    public function test_a_coupon_can_never_discount_more_than_the_bag_holds(): void
    {
        $a = $this->saree('A', 200);
        Coupon::create(['code' => 'HUGE', 'type' => 'fixed', 'value' => 5000, 'is_active' => true]);

        $service = $this->cart();
        $service->add($a, 1);
        $cart = $service->current();
        $cart->update(['coupon_code' => 'HUGE']);

        $totals = $service->totals($cart->fresh('items'));

        $this->assertSame(200.0, $totals->couponTotal);
        $this->assertGreaterThanOrEqual(0.0, $totals->grandTotal);
    }

    public function test_an_empty_bag_totals_nothing(): void
    {
        $totals = $this->cart()->totals();

        $this->assertTrue($totals->isEmpty());
        $this->assertSame(0.0, $totals->grandTotal);
    }
}
