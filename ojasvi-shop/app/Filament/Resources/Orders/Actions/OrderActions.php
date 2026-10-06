<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Models\Order;
use App\Models\Shipment;
use App\Services\OrderMailer;
use App\Services\OrderService;
use App\Services\Payments\Razorpay;
use App\Services\Shipping\Delhivery;
use App\Support\Shop;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;

/**
 * The three things a shop actually does to an order after it is placed: send
 * it, call it off, and give the money back.
 *
 * Written once and used by both the list and the order's own page, so the two
 * cannot drift apart — the list is where a morning's packing is done and the
 * page is where a single awkward order is sorted out.
 */
class OrderActions
{
    /**
     * Book the parcel with Delhivery and let them give us the number.
     *
     * Shown only when Delhivery is set up; a shop that books by telephone just
     * uses "Mark as sent" and types the number in, exactly as before. Nothing
     * about the courier's API is allowed to stop an order being dealt with.
     */
    public static function book(): Action
    {
        return Action::make('book')
            ->label('Book with Delhivery')
            ->icon('heroicon-o-qr-code')
            ->color('gray')
            ->visible(fn (Order $record) => app(Delhivery::class)->configured()
                && ! $record->shipment?->awb
                && in_array($record->status, ['pending', 'confirmed', 'packed'], true))
            ->requiresConfirmation()
            ->modalDescription(fn (Order $record) => $record->isCod()
                ? 'Booked as cash on delivery, so the courier will collect ' . Shop::money($record->grand_total) . ' at the door.'
                : 'Booked as prepaid — the courier collects nothing.')
            ->action(function (Order $record): void {
                $result = app(Delhivery::class)->book($record);

                if (! $result['ok']) {
                    Notification::make()
                        ->title('Delhivery could not book it')
                        ->body($result['message'] . ' You can still use “Mark as sent” and type the number in.')
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $record->moveTo('packed', 'Booked with Delhivery — ' . $result['awb'], auth()->id());

                Notification::make()
                    ->title($result['message'])
                    ->body('Print the label in the Delhivery panel, then mark it sent when it is collected.')
                    ->success()
                    ->send();
            });
    }

    /** Put in a tracking number, and tell the customer. */
    public static function ship(): Action
    {
        return Action::make('ship')
            ->label('Mark as sent')
            ->icon('heroicon-o-truck')
            ->color('primary')
            ->visible(fn (Order $record) => in_array($record->status, ['pending', 'confirmed', 'packed'], true))
            ->schema([
                Select::make('courier')
                    ->label('Carried by')
                    ->default(fn (Order $record) => $record->shipment?->courier ?? 'delhivery')
                    ->options([
                        'delhivery'  => 'Delhivery',
                        'bluedart'   => 'Blue Dart',
                        'dtdc'       => 'DTDC',
                        'indiapost'  => 'India Post',
                        'xpressbees' => 'XpressBees',
                        'other'      => 'Somebody else',
                    ])
                    ->required()
                    ->native(false),

                TextInput::make('awb')
                    ->label('Tracking number')
                    ->required()
                    ->maxLength(60)
                    // Already there if it was booked through Delhivery, so the
                    // usual case is reading it rather than typing it.
                    ->default(fn (Order $record) => $record->shipment?->awb)
                    ->helperText('What the courier gave you. The customer gets this in an email.'),

                DatePicker::make('expected_on')
                    ->label('Should arrive')
                    ->default(now()->addDays(4))
                    ->native(false),

                Toggle::make('tell_them')
                    ->label('Email the customer now')
                    ->default(true),
            ])
            ->action(function (Order $record, array $data): void {
                $shipment = $record->shipment ?? new Shipment(['order_id' => $record->id]);

                $shipment->fill([
                    'order_id'     => $record->id,
                    'courier'      => $data['courier'],
                    'awb'          => $data['awb'],
                    'status'       => 'in_transit',
                    'status_label' => 'On its way',
                    'expected_on'  => $data['expected_on'] ?? null,
                    'shipped_at'   => now(),
                ])->save();

                $record->moveTo('shipped', "Sent by {$data['courier']}, {$data['awb']}", auth()->id());

                if ($data['tell_them'] ?? true) {
                    app(OrderMailer::class)->shipped($record->fresh('shipment'));
                }

                Notification::make()
                    ->title($record->number . ' marked as sent')
                    ->body(($data['tell_them'] ?? true) ? 'The customer has been emailed the tracking number.' : null)
                    ->success()
                    ->send();
            });
    }

    public static function delivered(): Action
    {
        return Action::make('delivered')
            ->label('It arrived')
            ->icon('heroicon-o-check-badge')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (Order $record) => $record->status === 'shipped')
            ->action(function (Order $record): void {
                $record->shipment?->update([
                    'status'       => 'delivered',
                    'status_label' => 'Delivered',
                    'delivered_at' => now(),
                ]);

                // Cash on delivery is paid at the door, so this is the moment
                // the money exists as far as the shop is concerned.
                if ($record->isCod() && $record->payment_status !== 'paid') {
                    $record->forceFill(['payment_status' => 'paid', 'paid_at' => now()])->save();
                }

                $record->moveTo('delivered', 'Delivered', auth()->id());

                Notification::make()->title($record->number . ' delivered')->success()->send();
            });
    }

    /**
     * Calling off an order puts its stock back. Forgetting that is how a shop
     * ends up with sarees it believes are sold.
     */
    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Call it off')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('The pieces go back into stock. If it was paid for, refund it separately.')
            ->visible(fn (Order $record) => ! in_array($record->status, ['cancelled', 'refunded', 'delivered'], true))
            ->schema([
                Textarea::make('why')->label('Why')->rows(2)->maxLength(300),
            ])
            ->action(function (Order $record, array $data): void {
                app(OrderService::class)->restock($record);
                $record->moveTo('cancelled', $data['why'] ?? 'Called off', auth()->id());

                Notification::make()
                    ->title($record->number . ' called off')
                    ->body('The pieces are back in stock.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Give the money back.
     *
     * For a Razorpay order this really refunds through Razorpay; for cash on
     * delivery there is nothing to call, so it only records what the shop did
     * by hand. Either way the figure on the order is the truth afterwards.
     */
    public static function refund(): Action
    {
        return Action::make('refund')
            ->label('Refund')
            ->icon('heroicon-o-banknotes')
            ->color('warning')
            ->visible(fn (Order $record) => $record->payment_status === 'paid'
                || $record->payment_status === 'partially_refunded')
            ->schema([
                TextInput::make('amount')
                    ->label('How much')
                    ->numeric()
                    ->prefix('₹')
                    ->required()
                    ->default(fn (Order $record) => round((float) $record->grand_total - (float) $record->refunded_total, 2))
                    ->helperText(fn (Order $record) => 'At most '
                        . Shop::money((float) $record->grand_total - (float) $record->refunded_total)
                        . ' is left to refund.')
                    ->rules([
                        fn (Order $record) => function (string $attribute, $value, $fail) use ($record) {
                            $left = round((float) $record->grand_total - (float) $record->refunded_total, 2);

                            if ((float) $value > $left) {
                                $fail("That is more than the {$left} still on this order.");
                            }
                        },
                    ]),

                Textarea::make('why')->label('Why')->rows(2)->maxLength(300),
            ])
            ->action(function (Order $record, array $data): void {
                $amount = (float) $data['amount'];
                $payment = $record->payments()->where('status', 'captured')->latest('id')->first();

                if ($payment?->gateway_payment_id) {
                    try {
                        app(Razorpay::class)->refund($payment, $amount);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Razorpay would not take the refund')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();

                        return;
                    }
                }

                $refunded = round((float) $record->refunded_total + $amount, 2);
                $full = $refunded >= (float) $record->grand_total;

                $record->forceFill([
                    'refunded_total' => $refunded,
                    'payment_status' => $full ? 'refunded' : 'partially_refunded',
                ])->save();

                $record->history()->create([
                    'from'    => $record->status,
                    'to'      => $record->status,
                    'note'    => 'Refunded ' . Shop::money($amount) . ($data['why'] ? ' — ' . $data['why'] : ''),
                    'user_id' => auth()->id(),
                ]);

                if ($full && $record->status !== 'refunded') {
                    $record->moveTo('refunded', 'Refunded in full', auth()->id());
                }

                Notification::make()
                    ->title(Shop::money($amount) . ' refunded')
                    ->body($payment?->gateway_payment_id
                        ? 'Razorpay has it; the bank takes a few days.'
                        : 'Recorded. Pay it back by hand — there was nothing to refund through a gateway.')
                    ->success()
                    ->send();
            });
    }
}
