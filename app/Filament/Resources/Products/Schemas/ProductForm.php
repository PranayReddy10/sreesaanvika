<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use App\Models\Product;
use App\Support\Uploads;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Adding a saree.
 *
 * Laid out in the order the shop actually works: what it is, what it looks
 * like, what it costs, and only then the housekeeping. Colourways sit with the
 * photographs rather than under pricing, because a shade is something you see
 * before it is something you charge for.
 */
class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make()->columnSpanFull()->tabs([

                Tab::make('The piece')->icon('heroicon-o-sparkles')->schema([
                    Section::make()->columns(2)->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            // Only fills the address while it is still new, so
                            // renaming a published saree never breaks a link
                            // somebody has already shared.
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug($state));
                                }
                            }),

                        TextInput::make('slug')
                            ->label('Web address')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('ojasvidrapes.in/product/<this>. Changing it on a live piece breaks shared links.'),

                        TextInput::make('sku')
                            ->label('Design code')
                            ->unique(ignoreRecord: true)
                            ->helperText('Shown to shoppers as "Design OD-1200", and how you will look it up.'),

                        Select::make('status')
                            ->options([
                                'draft'     => 'Draft — nobody can see it',
                                'published' => 'Published — on sale',
                                'archived'  => 'Archived — hidden, history kept',
                            ])
                            ->default('draft')
                            ->required()
                            ->native(false),
                    ]),

                    Section::make('Description')->schema([
                        Textarea::make('short_description')
                            ->label('One or two lines')
                            ->rows(2)
                            ->maxLength(500)
                            ->helperText('Shown under the name on the product page and in the featured rows.'),

                        Textarea::make('description')
                            ->label('The full story')
                            ->rows(8)
                            ->helperText('Weave, zari, border, how it was made. Basic HTML is allowed.'),
                    ]),

                    Section::make('Grouping')->columns(2)->schema([
                        Select::make('categories')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->preload()
                            ->helperText('Optional. A shop selling one kind of thing needs none.'),

                        Select::make('attributeValues')
                            ->label('Fabric, weave, occasion')
                            ->relationship('attributeValues', 'value')
                            ->multiple()
                            ->preload()
                            ->helperText('What the specification table shows, and what the shop can filter by.'),
                    ]),
                ]),

                Tab::make('Photographs')->icon('heroicon-o-photo')->schema([
                    Section::make('The saree worn')
                        ->description('One picture of this saree on somebody. It opens the saree\'s page, and it is what a shopper sees when she picks a shade nobody has photographed separately — which is most shades of most sarees, since nobody photographs a model in every colour they weave.')
                        ->schema([
                            FileUpload::make('model_image')
                                ->label('Worn by a model')
                                ->image()
                                ->disk('public')
                                ->directory('products')
                                ->imageEditor()
                                ->maxSize(Uploads::ceiling())
                                /*
                                 * Asked for on a new saree, not demanded of
                                 * the ones already in the shop: making it
                                 * required everywhere would lock the shop out
                                 * of editing a catalogue it photographed
                                 * before this existed.
                                 */
                                ->required(fn (?Product $record) => $record === null)
                                ->helperText('The saree\'s own page opens on this, and then shows the photographs of whichever shade is chosen.')
                                ->columnSpanFull(),
                        ]),

                    Repeater::make('images')
                        ->relationship('baseImages')
                        ->label('Pictures of this saree')
                        ->helperText('Drag to reorder. The first one is what appears on the cards.')
                        ->orderColumn('position')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['alt'] ?? null)
                        ->schema([
                            FileUpload::make('path')
                                ->label('Image')
                                ->image()
                                ->disk('public')
                                ->directory('products')
                                ->imageEditor()
                                ->required()
                                /*
                                 * The whole photograph, as it came off the
                                 * camera. It is shrunk on the way in — long
                                 * edge to 2400, which is more than any screen
                                 * shows — so there is nothing to be gained by
                                 * the shop doing it first, and a good deal to
                                 * be lost if it does it badly.
                                 */
                                ->maxSize(Uploads::ceiling())
                                ->helperText('Straight off the camera is fine — up to about '
                                    .Uploads::ceilingInMegabytes().' MB. Large photographs are '
                                    .'resized here, so what the shop sends a customer is a '
                                    .'tenth of the size and looks the same.')
                                ->columnSpanFull(),

                            TextInput::make('alt')
                                ->label('Describe it')
                                ->helperText('For readers who cannot see it, and for search engines.')
                                ->maxLength(255),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Add a photograph'),
                ]),

                Tab::make('Colourways')->icon('heroicon-o-swatch')->schema([
                    Repeater::make('colourways')
                        ->relationship()
                        ->label('The same design, woven in other shades')
                        ->helperText('Leave empty if this design comes in one colour. A shade with its own photographs swaps the whole gallery when a shopper picks it.')
                        ->orderColumn('position')
                        ->reorderable()
                        ->collapsed()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Shade')
                        ->schema([
                            Section::make()->columns(3)->schema([
                                TextInput::make('name')->label('Shade')->required()->maxLength(80),
                                ColorPicker::make('hex')->label('Swatch dot'),
                                TextInput::make('sku')->label('Design code')->maxLength(80),
                            ]),

                            Section::make()->columns(3)->schema([
                                TextInput::make('price')
                                    ->label('Price, if different')
                                    ->numeric()->prefix('₹')
                                    ->helperText('Leave empty to use the design price.'),
                                TextInput::make('sale_price')->label('Offer price')->numeric()->prefix('₹'),
                                TextInput::make('stock')
                                    ->label('In stock')
                                    ->numeric()
                                    ->helperText('Leave empty to share the design stock.'),
                            ]),

                            Repeater::make('images')
                                ->relationship()
                                ->label('Photographs of this shade')
                                ->orderColumn('position')
                                ->reorderable()
                                ->schema([
                                    FileUpload::make('path')
                                        ->image()->disk('public')->directory('products')
                                        ->imageEditor()->required()
                                        ->maxSize(Uploads::ceiling())
                                        ->columnSpanFull(),
                                    TextInput::make('alt')->label('Describe it')->maxLength(255),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Add a photograph')
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $livewire): array {
                                    // A shade's photograph belongs to the design too, or it
                                    // would be orphaned the moment the gallery is read.
                                    $data['product_id'] = $livewire->record?->getKey();

                                    return $data;
                                }),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Add a shade'),
                ]),

                Tab::make('Price & stock')->icon('heroicon-o-banknotes')->schema([
                    Section::make('Price')->columns(3)->schema([
                        TextInput::make('price')->label('Price')->required()->numeric()->prefix('₹'),
                        TextInput::make('sale_price')
                            ->label('Offer price')->numeric()->prefix('₹')
                            ->helperText('Leave empty when it is not on offer.')
                            // Caught here rather than in the bag, where it would
                            // quietly show a discount of zero per cent.
                            ->lt('price'),
                        TextInput::make('cost_price')
                            ->label('What it cost you')->numeric()->prefix('₹')
                            ->helperText('Never shown to shoppers. Used for margin.'),
                    ]),

                    Section::make('When the offer runs')->columns(2)->schema([
                        DateTimePicker::make('sale_starts_at')->label('From')->seconds(false),
                        DateTimePicker::make('sale_ends_at')->label('Until')->seconds(false)
                            ->helperText('Leave both empty and the offer price runs until you remove it.'),
                    ]),

                    Section::make('Stock')->columns(3)->schema([
                        Toggle::make('track_stock')->label('Count stock')->default(true)->live(),
                        TextInput::make('stock')->label('In stock')->numeric()->default(0)
                            ->visible(fn ($get) => (bool) $get('track_stock')),
                        TextInput::make('low_stock_at')->label('Warn me at')->numeric()->default(3)
                            ->visible(fn ($get) => (bool) $get('track_stock')),
                        Toggle::make('backorder')
                            ->label('Allow orders when it runs out')
                            ->visible(fn ($get) => (bool) $get('track_stock')),
                    ]),

                    Section::make('Parcel')->columns(4)->collapsed()->schema([
                        TextInput::make('weight_g')->label('Weight (g)')->numeric(),
                        TextInput::make('length_cm')->label('Length (cm)')->numeric(),
                        TextInput::make('width_cm')->label('Width (cm)')->numeric(),
                        TextInput::make('height_cm')->label('Height (cm)')->numeric(),
                    ])->description('What the courier charges by. Only needed once you book pickups.'),
                ]),

                Tab::make('Goes with')->icon('heroicon-o-squares-plus')->schema([
                    Select::make('matches')
                        ->label('Complete the look')
                        ->relationship('matches', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->helperText('Shown under this saree and in the bag, so a shopper can take the pair.'),
                ]),

                Tab::make('Listing & search')->icon('heroicon-o-magnifying-glass')->schema([
                    Section::make()->columns(2)->schema([
                        Toggle::make('is_featured')
                            ->label('Feature it')
                            ->helperText('Gives it a full editorial row on the homepage.'),
                        DateTimePicker::make('published_at')
                            ->label('Publish at')->seconds(false)
                            ->helperText('Leave empty to publish as soon as the status says so.'),
                    ]),

                    Section::make('How it reads on Google')->schema([
                        TextInput::make('meta_title')->label('Title')->maxLength(255)
                            ->helperText('Leave empty to use the name.'),
                        Textarea::make('meta_description')->label('Description')->rows(2)->maxLength(500)
                            ->helperText('Leave empty to use the short description.'),
                    ]),
                ]),
            ]),
        ]);
    }
}
