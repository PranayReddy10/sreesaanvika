<?php

namespace App\Filament\Resources\Attributes\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Fabric, weave, occasion, shade — the words a saree is described and found by.
 *
 * These are not categories. A saree is one design; these are the facts about
 * it, which is also what makes the shop's filters possible without pretending
 * the shop sells several kinds of thing.
 */
class AttributeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('The heading')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(80)
                            ->placeholder('Fabric')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        TextInput::make('position')
                            ->label('Order')
                            ->numeric()->default(0)->required(),

                        Toggle::make('is_filterable')
                            ->label('Offer it as a filter on the shop')
                            ->default(true)
                            ->helperText('Only tick this for things a shopper would narrow by — fabric and shade, not thread count.'),
                    ]),

                Section::make('The choices')
                    ->schema([
                        Repeater::make('values')
                            ->label('')
                            ->relationship()
                            ->orderColumn('position')
                            ->addActionLabel('Add a choice')
                            ->columns(2)
                            ->itemLabel(fn (array $state) => $state['value'] ?? null)
                            ->collapsed()
                            ->schema([
                                TextInput::make('value')
                                    ->required()
                                    ->maxLength(80)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug((string) $state))),

                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(100),
                            ]),
                    ]),
            ]);
    }
}
