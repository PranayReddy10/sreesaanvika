<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Filament\Shared\VideoFields;
use App\Models\Video;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Films of this saree, added from the saree itself — which is where whoever
 * has just photographed it is already standing.
 */
class VideosRelationManager extends RelationManager
{
    protected static string $relationship = 'allVideos';

    protected static ?string $title = 'Films';

    protected static ?string $modelLabel = 'film';

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components(VideoFields::make());
    }

    public function table(Table $table): Table
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
                    ->state(fn (Video $record) => $record->poster
                        ?: $record->product?->firstImage()?->path),

                TextColumn::make('title')
                    ->label('Film')
                    ->placeholder('(untitled)')
                    ->weight('medium')
                    ->description(fn (Video $record) => $record->caption),

                TextColumn::make('where')
                    ->label('Played by')
                    ->badge()
                    ->state(fn (Video $record) => match (true) {
                        (bool) $record->path => 'The shop',
                        $record->isEmbed()   => 'Instagram',
                        default              => 'Elsewhere',
                    })
                    ->color(fn ($state) => $state === 'The shop' ? 'primary' : 'gray'),

                IconColumn::make('on_home')->label('On the front page')->boolean(),
                IconColumn::make('is_visible')->label('Showing')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add a film'),
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
            ->emptyStateHeading('No film of this saree yet')
            ->emptyStateDescription('Fifteen seconds of it being worn, held portrait, does more than another photograph.');
    }
}
