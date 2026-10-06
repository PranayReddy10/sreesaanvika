<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * The stock list.
 *
 * A shop owner opens this to answer three questions — what is live, what is
 * running out, and what is this piece called again — so the picture, the
 * design code and the stock come first, and the twenty columns nobody scans
 * are not here at all.
 */
class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('first_image')
                    ->label('')
                    ->disk('public')
                    ->height(56)
                    ->width(42)
                    ->state(fn ($record) => $record->images->first()?->path),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->sku ? 'Design '.$record->sku : null)
                    ->wrap(),

                TextColumn::make('price')
                    ->label('Price')
                    ->sortable()
                    ->money('INR')
                    // The struck-through figure, so a mispriced offer is
                    // visible from the list rather than from the shop floor.
                    ->description(fn ($record) => $record->sale_price
                        ? 'Offer ₹'.number_format((float) $record->sale_price, 2)
                        : null),

                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->badge()
                    ->state(fn ($record) => $record->track_stock ? $record->stock : '∞')
                    ->color(fn ($record) => match (true) {
                        ! $record->track_stock          => 'gray',
                        $record->stock <= 0             => 'danger',
                        $record->stock <= $record->low_stock_at => 'warning',
                        default                         => 'success',
                    }),

                TextColumn::make('colourways_count')
                    ->label('Shades')
                    ->counts('colourways')
                    ->alignCenter()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->sortable()
                    ->color(fn (string $state) => match ($state) {
                        'published' => 'success',
                        'draft'     => 'warning',
                        default     => 'gray',
                    }),

                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Changed')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived',
                ]),

                Filter::make('low_stock')
                    ->label('Running out')
                    ->query(fn (Builder $q) => $q->where('track_stock', true)
                        ->whereColumn('stock', '<=', 'low_stock_at')),

                Filter::make('on_offer')
                    ->label('On offer')
                    ->query(fn (Builder $q) => $q->whereNotNull('sale_price')),

                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                // Photographing a saree is the slow part; a near-identical
                // shade is far quicker to copy than to enter again.
                ReplicateAction::make()
                    ->label('Duplicate')
                    ->excludeAttributes(['slug', 'sku', 'views'])
                    ->beforeReplicaSaved(function ($replica): void {
                        $replica->name = $replica->name.' (copy)';
                        $replica->slug = $replica->slug.'-copy-'.uniqid();
                        $replica->status = 'draft';
                    })
                    ->successRedirectUrl(fn ($replica) => route('filament.admin.resources.products.edit', $replica)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No sarees yet')
            ->emptyStateDescription('Add the first one and it will appear on the shop.');
    }
}
