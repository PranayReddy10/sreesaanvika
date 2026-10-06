<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Models\Page;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('title')
                    ->label('Page')
                    ->searchable()
                    ->weight('medium')
                    ->description(fn (Page $record) => '/page/'.$record->slug),

                TextColumn::make('blurb')
                    ->label('Says')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—'),

                IconColumn::make('in_footer')
                    ->label('In the footer')
                    ->boolean(),

                IconColumn::make('is_visible')
                    ->label('Published')
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label('Changed')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                // The six the shop came with stay. The footer and the checkout
                // link to them by name, and a payment gateway will not approve
                // a shop whose returns page can be taken down.
                DeleteAction::make()->hidden(fn (Page $record) => $record->is_fixed),
            ])
            /*
             * No bulk delete, on purpose. Filament's own one empties the rows
             * with a single query when everything is selected, and a query
             * delete goes round the model entirely — including the rule that
             * the six pages the shop came with cannot be removed. There are
             * never enough pages for deleting them in batches to be worth that.
             */
            ->emptyStateHeading('No pages');
    }
}
