<?php

namespace App\Filament\Resources\Offers\Tables;

use App\Models\Offer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium')
                    ->wrap()
                    // What the shopper will actually read, under what the shop
                    // calls it, so a confusing headline shows up here.
                    ->description(fn (Offer $record) => $record->headlineText()),

                TextColumn::make('kind')
                    ->label('Shape')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'tiers' ? 'Quantity breaks' : 'Buy & get')
                    ->color(fn ($state) => $state === 'tiers' ? 'info' : 'primary'),

                TextColumn::make('terms')
                    ->label('Terms')
                    ->state(function (Offer $record) {
                        if ($record->kind === 'tiers') {
                            $tiers = collect($record->tierList())
                                ->map(fn ($t) => "{$t['qty']}+ → " . (int) $t['percent'] . '%')
                                ->implode(', ');

                            return $tiers !== '' ? $tiers : 'No breaks set';
                        }

                        $line = "Buy {$record->buy}, get {$record->get}";
                        $line .= (int) $record->percent === 100 ? ' free' : ' at ' . (int) $record->percent . '% off';

                        return $line . ($record->repeats ? ', repeating' : ', once');
                    })
                    ->wrap(),

                TextColumn::make('covers')
                    ->label('Covers')
                    ->state(function (Offer $record) {
                        if ($record->applies_to_all) {
                            return 'Everything';
                        }

                        $parts = [];
                        $products = $record->products_count ?? $record->products()->count();
                        $categories = $record->categories_count ?? $record->categories()->count();

                        if ($products) {
                            $parts[] = $products . ' ' . ($products === 1 ? 'saree' : 'sarees');
                        }
                        if ($categories) {
                            $parts[] = $categories . ' ' . ($categories === 1 ? 'collection' : 'collections');
                        }

                        return $parts === [] ? 'Nothing yet' : implode(' + ', $parts);
                    })
                    ->color(fn ($state) => $state === 'Nothing yet' ? 'danger' : null),

                TextColumn::make('window')
                    ->label('When')
                    ->state(function (Offer $record) {
                        $from = $record->starts_at?->format('j M Y') ?? 'now';
                        $to = $record->ends_at?->format('j M Y') ?? 'no end';

                        return "{$from} → {$to}";
                    })
                    ->toggleable(),

                IconColumn::make('live')
                    ->label('Live')
                    ->boolean()
                    // Running and inside its dates — the only state that
                    // matters, and the one the shop asks about.
                    ->state(fn (Offer $record) => $record->is_active
                        && (! $record->starts_at || $record->starts_at->isPast())
                        && (! $record->ends_at || $record->ends_at->isFuture()))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-pause-circle')
                    ->tooltip(fn (Offer $record) => ! $record->is_active
                        ? 'Switched off'
                        : ($record->starts_at && $record->starts_at->isFuture()
                            ? 'Starts ' . $record->starts_at->diffForHumans()
                            : ($record->ends_at && $record->ends_at->isPast() ? 'Ended' : 'Running now'))),
            ])
            ->filters([
                SelectFilter::make('kind')
                    ->label('Shape')
                    ->options(['bogo' => 'Buy & get', 'tiers' => 'Quantity breaks']),

                TernaryFilter::make('is_active')->label('Switched on'),
            ])
            ->recordActions([
                EditAction::make(),
                ReplicateAction::make()
                    ->label('Copy')
                    ->excludeAttributes(['created_at', 'updated_at'])
                    ->beforeReplicaSaved(function (Offer $replica) {
                        $replica->name = $replica->name . ' (copy)';
                        $replica->is_active = false;
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No offers yet')
            ->emptyStateDescription('An offer applies itself — the shopper types nothing at checkout.');
    }
}
