<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Support\Icons\Heroicon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * One order, read-only.
 *
 * Written for the moment a customer is on the phone: what they bought, what
 * they paid, where it is going and where it has got to — in that order, on one
 * screen, without a click.
 */
class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')->columns(4)->schema([
                TextEntry::make('number')->label('Number')->copyable()->weight('bold'),
                TextEntry::make('created_at')->label('Placed')->dateTime('d M Y, g:ia'),
                TextEntry::make('status')
                    ->label('Stage')->badge()
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'delivered' => 'success',
                        'shipped', 'packed' => 'info',
                        'cancelled', 'returned' => 'danger',
                        default => 'warning',
                    }),
                TextEntry::make('grand_total')->label('Total')->money('INR')->weight('bold'),
            ]),

            Section::make('Who')->columns(4)->schema([
                TextEntry::make('shipping_address.name')->label('Name'),
                TextEntry::make('phone')->label('Phone')->copyable(),
                TextEntry::make('email')->label('Email')->copyable(),

                /*
                 * Most of a saree shop's after-sale talk happens on WhatsApp
                 * rather than by email — "it is packed", "the courier tried
                 * you at four" — and the number is right there to be typed out
                 * by hand otherwise. The order number is already in the
                 * message, so nobody has to ask which one.
                 */
                TextEntry::make('whatsapp')
                    ->label('WhatsApp')
                    ->state(fn (Order $record) => $record->whatsappUrl() ? 'Message them' : null)
                    ->placeholder('No usable number')
                    ->badge()
                    ->color('success')
                    ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                    ->url(fn (Order $record) => $record->whatsappUrl())
                    ->openUrlInNewTab(),
            ]),

            Section::make('Where it is going')->columns(2)->schema([
                TextEntry::make('address')
                    ->label('Address')
                    ->html()
                    // Built from the record rather than the state: Filament
                    // treats an array state as many values and would format
                    // each line on its own.
                    ->state(function (Order $record) {
                        $a = $record->shipping_address ?? [];

                        $lines = collect([
                            $a['name'] ?? null,
                            $a['line1'] ?? null,
                            $a['line2'] ?? null,
                            $a['landmark'] ?? null,
                            trim(($a['city'] ?? '') . ' ' . ($a['pincode'] ?? '')),
                            $a['state'] ?? null,
                            $a['phone'] ?? null,
                        ])->filter()->implode("\n");

                        // Escaped before the line breaks go in — this is
                        // whatever the shopper typed, printed as HTML.
                        return $lines === '' ? '—' : nl2br(e($lines));
                    }),

                TextEntry::make('note')->label('What they asked for')->placeholder('Nothing'),
            ]),

            Section::make('Pieces')->schema([
                RepeatableEntry::make('items')
                    ->hiddenLabel()
                    ->schema([
                        Grid::make(5)->schema([
                            TextEntry::make('name')->label('Saree')->columnSpan(2)
                                ->helperText(fn ($record) => $record->colourway_name),
                            TextEntry::make('sku')->label('Design')->placeholder('—'),
                            TextEntry::make('quantity')->label('Qty'),
                            TextEntry::make('line_total')->label('Total')->money('INR'),
                        ]),
                        TextEntry::make('offer_name')
                            ->label('Offer applied')
                            ->placeholder('')
                            ->visible(fn ($record) => (bool) $record->offer_name)
                            ->badge()->color('success'),
                    ]),
            ]),

            Section::make('Money')->columns(4)->schema([
                TextEntry::make('items_total')->label('Goods')->money('INR'),
                TextEntry::make('offer_total')->label('Offers')->money('INR')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '− ₹'.number_format((float) $state, 2) : '—'),
                TextEntry::make('discount_total')->label('Coupon')->money('INR')
                    ->formatStateUsing(fn ($state) => $state > 0 ? '− ₹'.number_format((float) $state, 2) : '—'),
                TextEntry::make('shipping_total')->label('Delivery')->money('INR'),
                TextEntry::make('payment_method')->label('Paid by')
                    ->formatStateUsing(fn (?string $state) => $state === 'cod' ? 'Cash on delivery' : ucfirst((string) $state)),
                TextEntry::make('payment_status')->label('Payment')->badge()
                    ->color(fn (string $state) => $state === 'paid' ? 'success' : ($state === 'failed' ? 'danger' : 'warning')),
                TextEntry::make('paid_at')->label('Paid at')->dateTime('d M Y, g:ia')->placeholder('—'),
                TextEntry::make('grand_total')->label('Charged')->money('INR')->weight('bold'),
            ]),

            Section::make('Delivery')->columns(4)->collapsible()->schema([
                TextEntry::make('shipment.courier')->label('Courier')->placeholder('Not booked'),
                /*
                 * The number, and a way to follow it.
                 *
                 * Still copyable, because a courier whose address pattern is
                 * not known here leaves nothing but the number — and a link
                 * that goes to a courier's home page having lost the number is
                 * worse than no link.
                 */
                TextEntry::make('shipment.awb')
                    ->label('Tracking number')
                    ->copyable()
                    ->placeholder('—')
                    ->url(fn (Order $record) => $record->shipment?->trackingUrl())
                    ->openUrlInNewTab()
                    ->color(fn (Order $record) => $record->shipment?->trackingUrl() ? 'primary' : null)
                    ->icon(fn (Order $record) => $record->shipment?->trackingUrl()
                        ? Heroicon::OutlinedArrowTopRightOnSquare
                        : null)
                    ->iconPosition(\Filament\Support\Enums\IconPosition::After)
                    ->helperText(fn (Order $record) => $record->shipment?->trackingUrl()
                        ? 'Opens '.$record->shipment->courier
                        : null),
                TextEntry::make('shipment.status')->label('Where it is')
                    ->formatStateUsing(fn (?string $state) => $state ? (\App\Models\Shipment::STATUSES[$state] ?? $state) : '—'),
                TextEntry::make('shipment.expected_on')->label('Expected')->date('d M Y')->placeholder('—'),
            ]),

            Section::make('History')->collapsed()->schema([
                RepeatableEntry::make('history')
                    ->hiddenLabel()
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('to')->label('Moved to')
                                ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state),
                            TextEntry::make('created_at')->label('When')->dateTime('d M Y, g:ia'),
                            TextEntry::make('note')->label('Note')->placeholder('—'),
                        ]),
                    ]),
            ]),
        ]);
    }
}
