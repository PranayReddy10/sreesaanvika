<?php

namespace App\Console\Commands;

use App\Models\Shipment;
use App\Services\OrderMailer;
use App\Services\Shipping\Delhivery;
use Illuminate\Console\Command;

/**
 * Ask the courier where every parcel has got to.
 *
 * Runs off the shop's one cron entry. Without it, a shop has to open the
 * courier's panel to answer "where is my saree?", which is exactly the
 * question a shop should never have to be asked.
 *
 * Deliberately gentle: parcels already delivered are left alone, and nothing
 * is asked about more than once an hour. A courier's API is somebody else's
 * server and hammering it is how a shop's token gets throttled.
 */
class TrackParcels extends Command
{
    protected $signature = 'ojasvi:track-parcels {--all : Ask about every parcel, however recently it was checked}';

    protected $description = 'Ask Delhivery where each parcel has got to, and tell the customer when it arrives';

    public function handle(Delhivery $delhivery, OrderMailer $mailer): int
    {
        if (! $delhivery->configured()) {
            $this->comment('Delhivery is not set up; nothing to ask.');

            return self::SUCCESS;
        }

        $parcels = Shipment::query()
            ->where('courier', 'delhivery')
            ->whereNotNull('awb')
            ->whereNotIn('status', ['delivered', 'rto'])
            ->when(! $this->option('all'), fn ($q) => $q->where(
                fn ($q) => $q->whereNull('polled_at')->orWhere('polled_at', '<', now()->subHour())
            ))
            ->with('order')
            ->get();

        if ($parcels->isEmpty()) {
            $this->info('Nothing on the road.');

            return self::SUCCESS;
        }

        $moved = 0;

        foreach ($parcels as $parcel) {
            $news = $delhivery->track($parcel->awb);

            // Record the attempt either way, so one unreachable parcel does
            // not get asked about on every single run.
            $parcel->polled_at = now();

            if (! $news) {
                $parcel->saveQuietly();
                $this->line("{$parcel->awb} — no answer");

                continue;
            }

            $was = $parcel->status;

            $parcel->fill([
                'status'       => $news['status'],
                'status_label' => $news['label'],
                'timeline'     => $news['timeline'] ?: $parcel->timeline,
                'expected_on'  => $news['expected_on'] ? substr($news['expected_on'], 0, 10) : $parcel->expected_on,
                'delivered_at' => $news['delivered_at'] ?? $parcel->delivered_at,
            ])->save();

            if ($was === $news['status']) {
                continue;
            }

            $moved++;
            $this->line("{$parcel->awb} — {$was} → {$news['status']}");

            $this->followOrder($parcel, $news['status'], $mailer);
        }

        $this->info("{$parcels->count()} asked about, {$moved} moved on.");

        return self::SUCCESS;
    }

    /**
     * Keep the order in step with its parcel.
     *
     * The courier is the authority on where a parcel is, so what it says moves
     * the order — including marking a cash order paid, because cash on
     * delivery is paid at the door and that is the moment it happened.
     */
    private function followOrder(Shipment $parcel, string $status, OrderMailer $mailer): void
    {
        $order = $parcel->order;

        if (! $order) {
            return;
        }

        if ($status === 'delivered' && $order->status !== 'delivered') {
            if ($order->isCod() && $order->payment_status !== 'paid') {
                $order->forceFill([
                    'payment_status' => 'paid',
                    'paid_at'        => $parcel->delivered_at ?? now(),
                ])->save();
            }

            $order->moveTo('delivered', 'Delhivery says it arrived');

            return;
        }

        if ($status === 'rto' && ! in_array($order->status, ['returned', 'cancelled'], true)) {
            $order->moveTo('returned', 'Coming back to us — ' . $parcel->status_label);

            return;
        }

        // The first scan after booking is the parcel actually leaving, which
        // is when the customer wants to hear from us.
        if (in_array($status, ['in_transit', 'out_for_delivery'], true)
            && ! in_array($order->status, ['shipped', 'delivered'], true)) {
            $order->moveTo('shipped', 'Picked up by Delhivery');
            $mailer->shipped($order->fresh('shipment'));
        }
    }
}
