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
                    ->label('Played by')
                    ->badge()
                    ->state(fn (Video $record) => match (true) {
                        (bool) $record->path   => 'The shop',
                        $record->isEmbed()     => 'Instagram',
                        default                => 'Elsewhere',
                    })
                    ->color(fn ($state) => $state === 'The shop' ? 'primary' : 'gray')
                    ->description(fn (Video $record) => $record->isEmbed() ? 'Needs one tap' : 'Plays on its own')
                    ->tooltip(fn (Video $record) => $record->path ?: $record->url),

                // A film that has stopped working looks exactly like one that
                // has not, both here and on the page — a black rectangle is
                // all a shopper gets. This is the only place it is ever said.
                TextColumn::make('plays')
                    ->label('Plays')
                    ->badge()
                    ->state(fn (Video $record) => $record->problem() ?? 'Yes')
                    ->color(fn ($state) => $state === 'Yes' ? 'success' : 'danger')
                    ->wrap(),

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
