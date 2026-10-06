<?php

namespace App\Filament\Resources\ShippingZones\Tables;

use App\Models\ShippingZone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ShippingZonesTable
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
                    ->description(fn (ShippingZone $record) => ($record->pincodes ?? []) === []
                        ? 'Everywhere else'
                        : 'Pincodes starting ' . implode(', ', $record->pincodes)),

                TextColumn::make('rate')
                    ->label('Charge')
                    ->money('INR')
                    ->description(fn (ShippingZone $record) => $record->free_from
                        ? 'Free over ₹' . number_format((float) $record->free_from)
                        : 'Never free'),

                TextColumn::make('days')
                    ->label('Arrives in')
                    ->state(function (ShippingZone $record) {
                        if (! $record->days_min && ! $record->days_max) {
                            return '—';
                        }

                        return $record->days_min && $record->days_max && $record->days_min !== $record->days_max
                            ? "{$record->days_min}–{$record->days_max} days"
                            : (($record->days_max ?? $record->days_min) . ' days');
                    }),

                IconColumn::make('cod_allowed')
                    ->label('Cash on delivery')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Delivering')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Delivering'),
                TernaryFilter::make('cod_allowed')->label('Cash on delivery'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No delivery areas set')
            ->emptyStateDescription('Add one zone with no pincodes to charge the same everywhere.');
    }
}
