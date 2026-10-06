<?php

namespace App\Services\Shipping;

use App\Models\Order;
use App\Models\Shipment;
use App\Support\Shop;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Delhivery.
 *
 * Two jobs: book a parcel and get a tracking number back, and ask afterwards
 * where it has got to. Everything else the courier offers, a shop this size
 * does by telephone.
 *
 * Nothing here is allowed to be the reason an order cannot be dealt with. The
 * courier's API is somebody else's server, and it is down sometimes; when it
 * is, the shop types the tracking number in by hand exactly as before and the
 * order moves on. Every method says plainly whether it worked rather than
 * throwing into a Filament action.
 */
class Delhivery
{
    public function configured(): bool
    {
        return trim((string) config('services.delhivery.token')) !== '';
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.delhivery.base'), '/'))
            ->withHeaders([
                'Authorization' => 'Token ' . trim((string) config('services.delhivery.token')),
                'Accept'        => 'application/json',
            ])
            ->timeout(20)
            // Their API has bad minutes. Twice more, then give up and let the
            // shop type the number in by hand.
            ->retry(2, 400, throw: false);
    }

    /* ------------------------------------------------------- can we get there */

    /**
     * Does Delhivery serve this pincode, and will they collect cash there?
     *
     * @return array{serviceable: bool, cod: bool, prepaid: bool, city: ?string, state: ?string}|null
     *         null when the question could not be asked at all
     */
    public function serviceability(string $pincode): ?array
    {
        if (! $this->configured() || ! preg_match('/^\d{6}$/', $pincode)) {
            return null;
        }

        try {
            $response = $this->http()->get('/c/api/pin-codes/json/', ['filter_codes' => $pincode]);

            if (! $response->successful()) {
                return null;
            }

            $code = $response->json('delivery_codes.0.postal_code');

            if (! $code) {
                return ['serviceable' => false, 'cod' => false, 'prepaid' => false, 'city' => null, 'state' => null];
            }

            return [
                'serviceable' => true,
                // Their flags are the strings "Y" and "N", not booleans.
                'cod'     => ($code['cod'] ?? 'N') === 'Y',
                'prepaid' => ($code['pre_paid'] ?? 'N') === 'Y',
                'city'    => $code['city'] ?? null,
                'state'   => $code['state_code'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Delhivery would not answer about a pincode', [
                'pincode' => $pincode,
                'error'   => $e->getMessage(),
            ]);

            return null;
        }
    }

    /* ------------------------------------------------------------- booking it */

    /**
     * Book the parcel and write the tracking number onto the order.
     *
     * @return array{ok: bool, awb: ?string, message: string}
     */
    public function book(Order $order): array
    {
        if (! $this->configured()) {
            return ['ok' => false, 'awb' => null, 'message' => 'Delhivery is not set up. Add the token in .env.'];
        }

        $pickup = trim((string) config('services.delhivery.pickup'));

        if ($pickup === '') {
            return [
                'ok' => false, 'awb' => null,
                'message' => 'No pickup location is set. Add DELHIVERY_PICKUP_NAME, exactly as it is written in the Delhivery panel.',
            ];
        }

        // Asked of the database rather than of the loaded relation: a record
        // handed in by a Filament action may well have cached a null from
        // before this booking, and booking twice means two labels, two
        // pick-ups and a parcel that cannot be traced.
        $existing = $order->shipment()->first();

        if ($existing?->awb) {
            // Booking twice is how a shop ends up with two labels, two
            // pick-ups and a parcel that cannot be traced.
            return ['ok' => true, 'awb' => $existing->awb, 'message' => 'Already booked — ' . $existing->awb];
        }

        $address = $order->shipping_address ?? [];

        try {
            $response = $this->http()->asForm()->post('/api/cmu/create.json', [
                'format' => 'json',
                'data'   => json_encode([
                    'pickup_location' => ['name' => $pickup],
                    'shipments' => [$this->shipmentFor($order, $address)],
                ]),
            ]);

            $body = $response->json();
            $package = $body['packages'][0] ?? null;
            $awb = $package['waybill'] ?? null;

            if (! $response->successful() || ! $awb) {
                $why = $package['remarks'][0]
                    ?? $body['rmk']
                    ?? $body['error']
                    ?? 'Delhivery did not answer with a tracking number.';

                Log::warning('Delhivery refused a booking', ['order' => $order->number, 'response' => $body]);

                return [
                    'ok' => false, 'awb' => null,
                    // Always with the way out, because this is read by somebody
                    // who has a parcel in front of them and a day to get on with.
                    'message' => (is_string($why) ? $why : 'Delhivery refused the booking.')
                        . ' You can book it in their panel and type the number in.',
                ];
            }

            $shipment = $existing ?? new Shipment(['order_id' => $order->id]);

            $shipment->fill([
                'order_id'     => $order->id,
                'courier'      => 'delhivery',
                'awb'          => $awb,
                'status'       => 'manifested',
                'status_label' => 'Booked, waiting for pick-up',
                'timeline'     => [[
                    'at'   => now()->toAtomString(),
                    'what' => 'Booked with Delhivery',
                ]],
            ])->save();

            return ['ok' => true, 'awb' => $awb, 'message' => "Booked. Tracking number {$awb}."];
        } catch (\Throwable $e) {
            Log::error('Delhivery booking failed', ['order' => $order->number, 'error' => $e->getMessage()]);

            return [
                'ok' => false, 'awb' => null,
                'message' => 'Could not reach Delhivery. Try again, or book it in their panel and type the number in.',
            ];
        }
    }

    /** @return array<string, mixed> */
    private function shipmentFor(Order $order, array $address): array
    {
        $pieces = $order->items->sum('quantity');

        return [
            'name'            => $address['name'] ?? '',
            'add'             => trim(implode(', ', array_filter([
                $address['line1'] ?? null,
                $address['line2'] ?? null,
                $address['landmark'] ?? null,
            ]))),
            'city'            => $address['city'] ?? '',
            'state'           => $address['state'] ?? '',
            'country'         => 'India',
            'pin'             => $address['pincode'] ?? '',
            'phone'           => $address['phone'] ?? $order->phone,
            'order'           => $order->number,
            'payment_mode'    => $order->isCod() ? 'COD' : 'Prepaid',
            // Only a cash order has anything to collect. Sending the total on
            // a prepaid order is how a customer gets asked to pay twice.
            'cod_amount'      => $order->isCod() ? (string) round((float) $order->grand_total) : '0',
            'total_amount'    => (string) round((float) $order->grand_total),
            'quantity'        => (string) $pieces,
            'weight'          => (string) max(300, $order->items->sum(
                fn ($item) => ($item->product?->weight_g ?? 700) * $item->quantity
            )),
            'seller_name'     => Shop::name(),
            'products_desc'   => $order->items->pluck('name')->implode(', '),
            'shipment_width'  => '28',
            'shipment_height' => '8',
            'waybill'         => '',
        ];
    }

    /* ------------------------------------------------------------- following it */

    /**
     * Where has it got to?
     *
     * @return array{status: string, label: string, delivered_at: ?string, expected_on: ?string, timeline: array}|null
     */
    public function track(string $awb): ?array
    {
        if (! $this->configured() || trim($awb) === '') {
            return null;
        }

        try {
            $response = $this->http()->get('/api/v1/packages/json/', ['waybill' => $awb]);

            if (! $response->successful()) {
                return null;
            }

            $package = $response->json('ShipmentData.0.Shipment');

            if (! $package) {
                return null;
            }

            $status = $package['Status'] ?? [];

            return [
                'status'       => $this->ourWordFor($status['Status'] ?? '', $status['StatusType'] ?? ''),
                'label'        => $status['Instructions'] ?: ($status['Status'] ?? 'On its way'),
                'delivered_at' => ($status['Status'] ?? '') === 'Delivered' ? ($status['StatusDateTime'] ?? null) : null,
                'expected_on'  => $package['ExpectedDeliveryDate'] ?? null,
                'timeline'     => collect($package['Scans'] ?? [])
                    ->map(fn ($scan) => [
                        'at'    => $scan['ScanDetail']['ScanDateTime'] ?? null,
                        'what'  => $scan['ScanDetail']['Instructions'] ?? ($scan['ScanDetail']['Scan'] ?? ''),
                        'where' => $scan['ScanDetail']['ScannedLocation'] ?? null,
                    ])
                    ->filter(fn ($row) => $row['what'] !== '')
                    ->values()
                    ->all(),
            ];
        } catch (\Throwable $e) {
            Log::warning('Delhivery tracking failed', ['awb' => $awb, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Their vocabulary, in ours.
     *
     * Delhivery says "UD" and "RT"; the shop says "out for delivery" and
     * "coming back to us", and the customer reads the second one.
     */
    private function ourWordFor(string $status, string $type): string
    {
        return match (true) {
            $status === 'Delivered'                 => 'delivered',
            $status === 'RTO'   , $type === 'RT'    => 'rto',
            $status === 'Dispatched', $type === 'UD' => 'out_for_delivery',
            $status === 'Pending'                   => 'undelivered',
            $status === 'In Transit', $type === 'IT' => 'in_transit',
            $status === 'Manifested', $type === 'PP' => 'manifested',
            default                                  => 'in_transit',
        };
    }
}
