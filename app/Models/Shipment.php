<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'pending'          => 'Not dispatched',
        'manifested'       => 'Manifested',
        'picked'           => 'Picked up',
        'in_transit'       => 'In transit',
        'out_for_delivery' => 'Out for delivery',
        'delivered'        => 'Delivered',
        'undelivered'      => 'Attempted, not delivered',
        'rto'              => 'Returning to origin',
    ];

    protected function casts(): array
    {
        return [
            'timeline'     => 'array',
            'expected_on'  => 'date',
            'shipped_at'   => 'datetime',
            'delivered_at' => 'datetime',
            'polled_at'    => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Where this parcel can be followed, on the courier's own site.
     *
     * The shop stores a courier and a number; the address is worked out from
     * the two, because storing a URL as well would be one more thing to get
     * wrong and a second place for the number to live.
     *
     * Null for a courier whose pattern is not known, in which case the number
     * stays copyable and somebody can paste it wherever they like. A link that
     * goes to a courier's home page and loses the number is worse than no
     * link at all.
     */
    public function trackingUrl(): ?string
    {
        $awb = trim((string) $this->awb);

        if ($awb === '') {
            return null;
        }

        $courier = \Illuminate\Support\Str::of((string) $this->courier)->lower()->replace([' ', '-', '_'], '')->toString();

        return match (true) {
            str_contains($courier, 'delhivery')  => "https://www.delhivery.com/track/package/{$awb}",
            str_contains($courier, 'bluedart')   => "https://www.bluedart.com/web/guest/trackdartresult?trackFor=0&trackNo={$awb}",
            str_contains($courier, 'dtdc')       => "https://www.dtdc.in/tracking/shipment-tracking.asp?strCnno={$awb}",
            str_contains($courier, 'xpressbees') => "https://www.xpressbees.com/shipment/tracking?awbNo={$awb}",
            str_contains($courier, 'ekart')      => "https://ekartlogistics.com/shipmenttrack/{$awb}",
            str_contains($courier, 'shadowfax')  => "https://tracker.shadowfax.in/#/tracking/{$awb}",
            str_contains($courier, 'indiapost'), str_contains($courier, 'speedpost')
                => 'https://www.indiapost.gov.in/_layouts/15/DOP.Portal.Tracking/TrackConsignment.aspx',
            default => null,
        };
    }

    public function label(): string
    {
        return $this->status_label ?: (self::STATUSES[$this->status] ?? $this->status);
    }
}
