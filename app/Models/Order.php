<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An order is a record, not a view.
 *
 * Every figure and every name on it was copied at the moment it was placed. A
 * saree renamed, repriced or deleted next month leaves this untouched, because
 * an invoice that changes after the fact is not an invoice.
 */
class Order extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'packed'    => 'Packed',
        'shipped'   => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned'  => 'Returned',
        'refunded'  => 'Refunded',
    ];

    protected function casts(): array
    {
        return [
            'shipping_address' => 'array',
            'billing_address'  => 'array',
            'items_total'      => 'decimal:2',
            'discount_total'   => 'decimal:2',
            'offer_total'      => 'decimal:2',
            'shipping_total'   => 'decimal:2',
            'tax_total'        => 'decimal:2',
            'grand_total'      => 'decimal:2',
            'refunded_total'   => 'decimal:2',
            'placed_at'        => 'datetime',
            'paid_at'          => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function history()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    /**
     * The next order number.
     *
     * Per year and never reused, so "OJ-2026-00041" means something to both
     * the shop and the courier. Taken inside a transaction by the caller.
     */
    public static function nextNumber(): string
    {
        $year   = now()->format('Y');
        $prefix = "OJ-{$year}-";

        $last = static::where('number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('number');

        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Move the order along, keeping a note of who moved it and why.
     */
    public function moveTo(string $status, ?string $note = null, ?int $userId = null): void
    {
        if ($status === $this->status) {
            return;
        }

        $from = $this->status;

        $this->forceFill([
            'status'       => $status,
            'cancelled_at' => $status === 'cancelled' ? now() : $this->cancelled_at,
        ])->save();

        $this->history()->create([
            'from'    => $from,
            'to'      => $status,
            'note'    => $note,
            'user_id' => $userId,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isCod(): bool
    {
        return $this->payment_method === 'cod';
    }

    /** Can the shopper still call it off themselves? */
    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }

    /**
     * WhatsApp for this customer, about this order.
     *
     * Most of a saree shop's after-sale talk happens here rather than by
     * email: "it is packed", "the courier tried you at four". The order number
     * goes in the message so nobody has to ask which one.
     *
     * Null when there is no number, or when what is there cannot be a
     * telephone number — a wa.me link built from rubbish opens WhatsApp on an
     * error, which looks like the shop's fault.
     */
    public function whatsappUrl(?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone) ?? '';

        // A ten-digit Indian number needs its country code; one that already
        // has it is left alone.
        $number = match (true) {
            strlen($digits) === 10 => '91'.$digits,
            strlen($digits) === 12 && str_starts_with($digits, '91') => $digits,
            strlen($digits) === 11 && str_starts_with($digits, '0') => '91'.substr($digits, 1),
            strlen($digits) >= 11 && strlen($digits) <= 15 => $digits,
            default => null,
        };

        if ($number === null) {
            return null;
        }

        $message ??= 'Hello, this is '.\App\Support\Shop::name().' about your order '.$this->number.'.';

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }
}
