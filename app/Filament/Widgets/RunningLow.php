<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * What to reorder. A handwoven saree takes weeks to replace, so "running low"
 * has to mean weeks of warning, not days — hence a per-saree warning level
 * rather than one number for the whole shop.
 */
class RunningLow extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Running low')
            ->description('Published sarees at or below their own warning level.')
            ->query(
                Product::query()
                    ->where('status', 'published')
                    ->where('track_stock', true)
                    ->whereColumn('stock', '<=', 'low_stock_at')
                    ->with('images')
                    ->orderBy('stock')
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                ImageColumn::make('photograph')
                    ->label('')
                    ->disk('public')
                    ->height(48)
                    ->width(36)
                    ->state(fn (Product $record) => $record->images->first()?->path),

                TextColumn::make('name')
                    ->label('Saree')
                    ->weight('medium')
                    ->wrap()
                    ->description(fn (Product $record) => $record->sku ? 'Design ' . $record->sku : null),

                TextColumn::make('stock')
                    ->label('Left')
                    ->badge()
                    ->color(fn (Product $record) => $record->stock <= 0 ? 'danger' : 'warning')
                    ->formatStateUsing(fn ($state) => $state <= 0 ? 'Sold out' : $state . ' left'),

                TextColumn::make('low_stock_at')
                    ->label('Warn at')
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Open')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->url(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record])),
            ])
            ->emptyStateHeading('Nothing is running out')
            ->emptyStateDescription('Every published saree is above its warning level.');
    }
}
