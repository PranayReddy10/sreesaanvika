<?php

namespace App\Filament\Resources\Sections\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * The pictures in a hero row. Edited inside the row they belong to, because a
 * slide on its own means nothing.
 */
class SlidesRelationManager extends RelationManager
{
    protected static string $relationship = 'allSlides';

    protected static ?string $title = 'Slides';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                FileUpload::make('image')
                    ->label('Picture')
                    ->image()
                    ->disk('public')
                    ->directory('slides')
                    ->imageEditor()
                    ->maxSize(6144)
                    ->helperText('Landscape, at least 1600px wide.')
                    ->columnSpanFull(),

                TextInput::make('eyebrow')->label('Small line above')->maxLength(80),
                TextInput::make('heading')->label('Heading')->maxLength(140),

                Textarea::make('text')->label('A line underneath')->rows(2)->maxLength(300)->columnSpanFull(),

                TextInput::make('button_label')->label('Button says')->maxLength(60)->placeholder('Shop the silks'),
                TextInput::make('button_url')->label('Button goes to')->maxLength(300)->placeholder('/sarees'),

                Select::make('align')
                    ->label('Words sit')
                    ->options(['left' => 'Left', 'centre' => 'Centre', 'right' => 'Right'])
                    ->default('left')
                    ->native(false),

                TextInput::make('position')->label('Order')->numeric()->default(0)->required(),

                Toggle::make('is_visible')->label('Show this slide')->default(true)->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->reorderable('position')
            ->columns([
                ImageColumn::make('image')
                    ->label('')
                    ->disk('public')
                    ->height(44)
                    ->width(78),

                TextColumn::make('heading')
                    ->wrap()
                    ->weight('medium')
                    ->placeholder('(no heading)')
                    ->description(fn ($record) => $record->eyebrow),

                TextColumn::make('button_label')
                    ->label('Button')
                    ->placeholder('—')
                    ->description(fn ($record) => $record->button_url),

                IconColumn::make('is_visible')->label('Showing')->boolean(),
            ])
            ->headerActions([
                CreateAction::make()->label('Add a slide'),
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
            ->emptyStateHeading('No slides in this row yet');
    }
}
