<?php

namespace App\Filament\Resources\Videos\Tables;

use App\Models\Video;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class VideosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('poster')
                    ->label('')
                    ->disk('public')
                    ->height(56)
                    ->width(42)
                    // The saree's own photograph where no frame was chosen, so
                    // the list is never a column of empty squares.
                    ->state(fn (Video $record) => $record->poster
                        ?: $record->product?->firstImage()?->path),

                TextColumn::make('title')
                    ->label('Film')
                    ->searchable()
                    ->weight('medium')
                    ->placeholder('(untitled)')
                    ->description(fn (Video $record) => $record->caption),

                TextColumn::make('product.name')
                    ->label('Of')
                    ->placeholder('Nothing in particular')
                    ->wrap()
                    ->searchable(),

                TextColumn::make('where')
                    ->label('Kept')
                    ->badge()
                    ->state(fn (Video $record) => $record->path ? 'Here' : 'Elsewhere')
                    ->color(fn ($state) => $state === 'Here' ? 'primary' : 'gray')
                    ->tooltip(fn (Video $record) => $record->path ?: $record->url),

                IconColumn::make('is_visible')
                    ->label('Showing')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_visible')->label('Showing'),
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
            ->emptyStateHeading('No films yet')
            ->emptyStateDescription('Add one here, then put a Films row on the front page under Storefront → Front page.');
    }
}
