<?php

namespace App\Filament\Resources\Sections\Tables;

use App\Filament\Resources\Sections\Schemas\SectionForm;
use App\Models\Section;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SectionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                TextColumn::make('position')
                    ->label('#')
                    ->sortable()
                    ->width('1%'),

                TextColumn::make('heading')
                    ->label('Row')
                    ->weight('medium')
                    ->wrap()
                    ->placeholder('(no heading)')
                    ->description(fn (Section $record) => $record->eyebrow),

                TextColumn::make('key')
                    ->label('Kind')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str(SectionForm::KINDS[$state] ?? $state)
                        ->before(' —')->toString()),

                TextColumn::make('all_slides_count')
                    ->label('Slides')
                    ->counts('allSlides')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray'),

                IconColumn::make('is_visible')
                    ->label('Showing')
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
            ->emptyStateHeading('The front page has no rows yet')
            ->emptyStateDescription('Add a hero, then a row of sarees. Drag them to change the order.');
    }
}
