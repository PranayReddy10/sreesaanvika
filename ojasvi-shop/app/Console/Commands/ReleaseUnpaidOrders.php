<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

/**
 * Give back the stock behind orders that were never paid for.
 *
 * Stock is taken the moment an order is written, so two shoppers cannot both
 * be sold the last Patola. The price of that is orders that are started and
 * abandoned, holding stock nobody bought. This lets them go.
 *
 * Deliberately generous: a shopper can take twenty minutes over a bank's
 * one-time password, and cancelling an order out from under somebody who is
 * mid-payment is far worse than holding one saree for an hour.
 */
class ReleaseUnpaidOrders extends Command
{
    protected $signature = 'ojasvi:release-unpaid {--minutes=90} {--dry}';

    protected $description = 'Cancel online orders that were never paid for, and put their stock back';

    public function handle(OrderService $orders): int
    {
        $minutes = max(30, (int) $this->option('minutes'));
        $cutoff = now()->subMinutes($minutes);

        $stale = Order::query()
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            // Never cash on delivery: those are unpaid by design, and
            // cancelling them would cancel the shop's actual orders.
            ->where('payment_method', '!=', 'cod')
            ->where('created_at', '<', $cutoff)
            ->with('items')
            ->get();

        if ($stale->isEmpty()) {
            $this->info('Nothing to release.');

            return self::SUCCESS;
        }

        foreach ($stale as $order) {
            $this->line("{$order->number} — placed {$order->created_at->diffForHumans()}");

            if ($this->option('dry')) {
                continue;
            }

            $orders->restock($order);
            $order->moveTo('cancelled', "Never paid for; released after {$minutes} minutes");
        }

        $this->info($this->option('dry')
            ? $stale->count() . ' would be released.'
            : $stale->count() . ' released.');

        return self::SUCCESS;
    }
}
