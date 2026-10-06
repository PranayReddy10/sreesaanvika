<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Models\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Heading')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            // Only while the address is still being chosen. A
                            // page that is already published keeps its address
                            // when it is renamed, because changing it breaks
                            // every link to it that exists in the world.
                            ->afterStateUpdated(function (string $operation, $state, $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Its address')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true)
                            ->rule('alpha_dash')
                            ->disabled(fn (?Page $record) => (bool) $record?->is_fixed)
                            ->dehydrated()
                            ->prefix(rtrim(config('app.url'), '/').'/page/')
                            ->helperText(fn (?Page $record) => $record?->is_fixed
                                ? 'Fixed, because the footer and the checkout link to this page by name.'
                                : 'Letters, numbers and hyphens. Changing it breaks any link already out there.'),

                        Textarea::make('blurb')
                            ->label('The line under the heading')
                            ->maxLength(200)
                            ->rows(2)
                            ->helperText('Also what a search result shows underneath the title.')
                            ->columnSpanFull(),

                        Textarea::make('body')
                            ->label('The page')
                            ->rows(18)
                            ->columnSpanFull()
                            ->helperText(new HtmlString(
                                'Leave a blank line between paragraphs. A line on its own wrapped in '
                                . '<code>**two stars**</code> becomes a heading. Nothing else is read as '
                                . 'markup — anything you paste appears as the words you pasted, which is '
                                . 'deliberate: a page nobody can accidentally put a script on.'
                            )),
                    ]),

                Section::make('Where it shows')
                    ->columns(3)
                    ->schema([
                        Toggle::make('is_visible')
                            ->label('Published')
                            ->default(true)
                            ->disabled(fn (?Page $record) => (bool) $record?->is_fixed)
                            ->helperText(fn (?Page $record) => $record?->is_fixed
                                ? 'This one cannot be taken down.'
                                : 'Off while you write it.'),

                        Toggle::make('in_footer')
                            ->label('Link to it in the footer')
                            ->helperText('Under Help, on every page.'),

                        TextInput::make('position')
                            ->label('Order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Lower numbers come first.'),
                    ]),
            ]);
    }
}
