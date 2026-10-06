<?php

namespace App\Filament\Resources\Attributes\Tables;

use App\Models\Attribute;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AttributesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('medium'),

                TextColumn::make('values')
                    ->label('Choices')
                    ->state(fn (Attribute $record) => $record->values->pluck('value')->take(8)->implode(', ')
                        . ($record->values->count() > 8 ? ' …' : ''))
                    ->placeholder('None yet')
                    ->wrap(),

                TextColumn::make('values_count')
                    ->label('')
                    ->counts('values')
                    ->badge(),

                IconColumn::make('is_filterable')
                    ->label('A shop filter')
                    ->boolean(),
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
            ->emptyStateHeading('Nothing to describe sarees by yet')
            ->emptyStateDescription('Fabric, weave, occasion and shade are the usual four.');
    }
}
