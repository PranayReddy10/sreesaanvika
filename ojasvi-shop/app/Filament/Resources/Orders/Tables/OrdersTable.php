<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Filament\Resources\Orders\Actions\OrderActions;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * The order book.
 *
 * Opened many times a day, so it answers the only questions that matter at a
 * glance — who, how much, paid or not, and where it has got to — and lets the
 * status be moved without opening the order at all.
 */
class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->copyable()
                    ->description(fn (Order $record) => $record->created_at?->format('d M Y, g:ia')),

                TextColumn::make('shipping_address.name')
                    ->label('Customer')
                    ->searchable(query: fn (Builder $q, string $search) => $q
                        ->where('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->description(fn (Order $record) => $record->phone)
                    ->wrap(),

                TextColumn::make('items_count')
                    ->label('Pieces')
                    ->counts('items')
                    ->alignCenter(),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('INR')
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'paid'     => 'success',
                        'unpaid'   => 'warning',
                        'failed'   => 'danger',
                        'refunded' => 'gray',
                        default    => 'gray',
                    })
                    ->description(fn (Order $record) => $record->payment_method === 'cod' ? 'Cash on delivery' : null),

                TextColumn::make('status')
                    ->label('Stage')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'delivered'             => 'success',
                        'shipped', 'packed'     => 'info',
                        'confirmed'             => 'primary',
                        'pending'               => 'warning',
                        'cancelled', 'returned' => 'danger',
                        default                 => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state),

                TextColumn::make('shipment.awb')
                    ->label('Tracking')
                    ->placeholder('—')
                    ->copyable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(Order::STATUSES)->multiple(),

                SelectFilter::make('payment_status')->label('Payment')->options([
                    'unpaid' => 'Unpaid', 'paid' => 'Paid',
                    'failed' => 'Failed', 'refunded' => 'Refunded',
                ]),

                // What a shop opens first thing: paid, and not yet sent.
                Filter::make('to_pack')
                    ->label('Needs packing')
                    ->query(fn (Builder $q) => $q->where('payment_status', 'paid')
                        ->whereIn('status', ['confirmed', 'pending'])),

                Filter::make('cod')
                    ->label('Cash on delivery')
                    ->query(fn (Builder $q) => $q->where('payment_method', 'cod')),
            ])
            ->recordActions([
                ViewAction::make(),

                OrderActions::ship(),
                OrderActions::delivered(),

                OrderActions::cancel(),
                OrderActions::refund(),

                Action::make('advance')
                    ->label('Move on')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->schema([
                        Select::make('status')
                            ->label('To')
                            ->options(Order::STATUSES)
                            ->required()
                            ->native(false),
                        Textarea::make('note')->label('Note')->rows(2),
                    ])
                    ->action(function (Order $record, array $data): void {
                        $record->moveTo($data['status'], $data['note'] ?? null, auth()->id());

                        Notification::make()
                            ->title($record->number.' is now '.(Order::STATUSES[$data['status']] ?? $data['status']))
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_packed')
                        ->label('Mark packed')
                        ->icon('heroicon-o-archive-box')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each(
                            fn (Order $record) => $record->moveTo('packed', 'Marked in bulk', auth()->id())
                        ))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('No orders yet');
    }
}
