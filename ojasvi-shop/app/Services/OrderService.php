<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Support\Shop;
use Illuminate\Support\Facades\DB;

/**
 * Turning a bag into an order.
 *
 * Three rules this exists to enforce.
 *
 * Every figure is worked out again here, from the products, at the moment the
 * order is written. Nothing the browser sent about price, discount or delivery
 * is believed — a form is a suggestion, not an invoice.
 *
 * Stock is taken when the order is written, not when the payment lands. Two
 * people must not both be sold the last Patola because one of them was slower
 * through Razorpay. Orders that are never paid give their stock back; see
 * ReleaseUnpaidOrders.
 *
 * Every line keeps its own copy of the name, the design code and the price,
 * so a saree renamed or repriced next month cannot rewrite what somebody
 * bought today.
 */
class OrderService
{
    public function __construct(private CartService $bag, private OfferEngine $offers)
    {
    }

    /**
     * @param  array{name:string,phone:string,email:string,line1:string,line2?:?string,landmark?:?string,city:string,state:string,pincode:string,country?:string}  $address
     * @param  'razorpay'|'cod'  $method
     *
     * @throws \RuntimeException when the bag can no longer be sold as it stands
     */
    public function place(Cart $cart, array $address, string $method, ?string $note = null): Order
    {
        return DB::transaction(function () use ($cart, $address, $method, $note) {
            // Locked for the length of the transaction, so two checkouts for
            // the last piece cannot both read "1 in stock".
            $cart->load(['items.product', 'items.colourway']);

            $items = $cart->items->filter(fn (CartItem $i) => $i->product !== null);

            if ($items->isEmpty()) {
                throw new \RuntimeException('Your bag is empty.');
            }

            $productIds = $items->pluck('product_id')->unique()->all();
            $locked = Product::whereIn('id', $productIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($items as $item) {
                $product = $locked[$item->product_id] ?? null;

                if (! $product || $product->status !== 'published') {
                    throw new \RuntimeException("{$item->product->name} is no longer for sale.");
                }

                if (! $product->canOrder((int) $item->quantity, $item->colourway)) {
                    $left = $product->stockFor($item->colourway);

                    throw new \RuntimeException($left > 0
                        ? "Only {$left} left of {$product->name}. Please change the quantity."
                        : "{$product->name} has just sold out.");
                }
            }

            $totals = $this->bag->totals($cart, $address['pincode'] ?? null);

            if ($method === 'cod' && ! Shop::codOn()) {
                throw new \RuntimeException('Cash on delivery is not available at the moment.');
            }

            $codFee = $method === 'cod' ? Shop::codFee() : 0.0;

            $order = Order::create([
                'number'          => Order::nextNumber(),
                'user_id'         => $cart->user_id,
                'status'          => 'pending',
                'payment_status'  => 'unpaid',
                'payment_method'  => $method,
                'email'           => $address['email'],
                'phone'           => $address['phone'],
                'items_total'     => $totals->subtotal,
                'offer_total'     => $totals->offerTotal,
                'discount_total'  => $totals->couponTotal,
                'shipping_total'  => $totals->shippingTotal + $codFee,
                'grand_total'     => round($totals->grandTotal + $codFee, 2),
                'currency'        => config('shop.currency', 'INR'),
                'coupon_code'     => $totals->couponCode,
                'shipping_address' => $this->addressFor($address),
                'note'            => $note,
                'placed_at'       => now(),
            ]);

            $this->writeLines($order, $items, $totals, $locked);
            $this->takeStock($items, $locked);

            if ($totals->couponCode) {
                $this->redeem($order, $totals);
            }

            $order->history()->create(['to' => 'pending', 'note' => 'Placed']);

            return $order;
        });
    }

    /** @return array<string, string|null> */
    private function addressFor(array $address): array
    {
        return [
            'name'     => $address['name'],
            'phone'    => $address['phone'],
            'line1'    => $address['line1'],
            'line2'    => $address['line2'] ?? null,
            'landmark' => $address['landmark'] ?? null,
            'city'     => $address['city'],
            'state'    => $address['state'],
            'pincode'  => $address['pincode'],
            'country'  => $address['country'] ?? 'IN',
        ];
    }

    private function writeLines(Order $order, $items, CartTotals $totals, $locked): void
    {
        foreach ($items as $item) {
            $product = $locked[$item->product_id];
            $key = (string) $item->id;
            $unit = $product->priceFor($item->colourway);
            $saved = $totals->offers?->savedOn($key) ?? 0.0;

            $order->items()->create([
                'product_id'     => $product->id,
                'colourway_id'   => $item->colourway_id,
                'name'           => $product->name,
                'sku'            => $item->colourway?->sku ?: $product->sku,
                'colourway_name' => $item->colourway?->name,
                'image'          => $product->firstImage($item->colourway)?->path,
                'unit_price'     => $unit,
                // Spread over the line rather than attached to one unit: the
                // invoice has to add up either way round.
                'unit_discount'  => $item->quantity > 0 ? round($saved / $item->quantity, 2) : 0,
                'quantity'       => $item->quantity,
                'line_total'     => round(($unit * $item->quantity) - $saved, 2),
                'offer_id'       => $totals->offers?->offerIdByKey[$key] ?? null,
                'offer_name'     => $totals->offers?->offerNameByKey[$key] ?? null,
                'free_units'     => $totals->offers?->freeUnits($key) ?? 0,
            ]);
        }
    }

    private function takeStock($items, $locked): void
    {
        foreach ($items as $item) {
            $product = $locked[$item->product_id];

            if (! $product->track_stock) {
                continue;
            }

            // A shade with its own count is the one that falls; the design's
            // total falls either way, because that is what the shop has.
            if ($item->colourway && $item->colourway->stock !== null) {
                $item->colourway->decrement('stock', $item->quantity);
            }

            $product->decrement('stock', $item->quantity);
        }
    }

    private function redeem(Order $order, CartTotals $totals): void
    {
        $coupon = Coupon::where('code', $totals->couponCode)->lockForUpdate()->first();

        if (! $coupon) {
            return;
        }

        $coupon->increment('used');

        $coupon->redemptions()->create([
            'order_id' => $order->id,
            'user_id'  => $order->user_id,
            'amount'   => $totals->couponTotal,
        ]);
    }

    /**
     * Give the stock back.
     *
     * Called when an order is cancelled or when an unpaid one is let go, so
     * the shop is not left holding pieces nobody bought.
     */
    public function restock(Order $order): void
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $line) {
                $product = $line->product_id
                    ? Product::lockForUpdate()->find($line->product_id)
                    : null;

                if (! $product || ! $product->track_stock) {
                    continue;
                }

                if ($line->colourway_id) {
                    \App\Models\Colourway::where('id', $line->colourway_id)
                        ->whereNotNull('stock')
                        ->increment('stock', $line->quantity);
                }

                $product->increment('stock', $line->quantity);
            }
        });
    }

    /** Once the money is in: the order is confirmed and the bag is cleared. */
    public function markPaid(Order $order, ?string $note = null): void
    {
        if ($order->payment_status === 'paid') {
            return;
        }

        $order->forceFill([
            'payment_status' => 'paid',
            'paid_at'        => now(),
        ])->save();

        $order->moveTo('confirmed', $note ?? 'Payment received');
    }
}
