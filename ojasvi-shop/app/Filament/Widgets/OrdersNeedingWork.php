<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * What the shop has to act on today — paid and waiting to be packed, oldest
 * first, because the one that has been sitting longest is the one a customer
 * is about to ask about.
 */
class OrdersNeedingWork extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Waiting on you')
            ->description('Orders that are paid or placed and not yet packed.')
            ->query(
                Order::query()
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->with('items')
                    ->oldest('created_at')
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('number')
                    ->label('Order')
                    ->weight('medium')
                    ->description(fn (Order $record) => $record->created_at?->diffForHumans()),

                TextColumn::make('email')
                    ->label('Customer')
                    ->description(fn (Order $record) => $record->phone)
                    ->wrap(),

                TextColumn::make('items_count')
                    ->label('Pieces')
                    ->state(fn (Order $record) => $record->items->sum('quantity')),

                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('INR'),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->color(fn (string $state) => match ($state) {
                        'paid' => 'success',
                        'failed' => 'danger',
                        default => 'warning',
                    })
                    ->description(fn (Order $record) => $record->payment_method === 'cod' ? 'Cash on delivery' : null),
            ])
            ->recordActions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Order $record) => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading('Nothing waiting')
            ->emptyStateDescription('Every order has been packed.');
    }
}
