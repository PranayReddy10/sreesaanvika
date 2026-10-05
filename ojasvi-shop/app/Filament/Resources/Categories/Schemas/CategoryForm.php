<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The collection')
                    ->description('A name for a group of sarees. The shop has no category menu — this is how several designs are pointed at in one go.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            // Only while it is new: a slug changed after launch
                            // breaks every link anyone saved.
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(140)
                            ->unique(ignoreRecord: true)
                            ->helperText('Appears in the web address. Changing it breaks saved links.'),

                        Select::make('parent_id')
                            ->label('Sits inside')
                            ->relationship(
                                name: 'parent',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query, ?Category $record) => $record
                                    ? $query->whereKeyNot($record->getKey())
                                    : $query,
                            )
                            ->searchable()
                            ->preload()
                            ->placeholder('Nothing — this is a top-level collection'),

                        TextInput::make('position')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Lower numbers come first.'),

                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Where it shows')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image')
                            ->label('Picture')
                            ->image()
                            ->disk('public')
                            ->directory('collections')
                            ->imageEditor()
                            ->maxSize(4096)
                            ->helperText('Only used if this collection is shown on the shop.'),

                        Toggle::make('is_visible')
                            ->label('Show on the shop')
                            ->default(false)
                            ->helperText('Off means the collection is only used behind the scenes, for offers and reports.'),
                    ]),
            ]);
    }
}
