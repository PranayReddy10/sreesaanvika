<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * The settings the shop changes itself.
 *
 * Every field here is one row in the settings table, read through a cache that
 * is dropped on save. Nothing on this page is code, a template or a redirect —
 * the lesson of the WordPress build is that a shop owner should be able to
 * change their own phone number without anyone editing a file.
 */
class ShopSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.shop-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static \UnitEnum|string|null $navigationGroup = 'Shop';

    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Shop settings';

    protected static ?int $navigationSort = 9;

    /**
     * key => [group, type]. The one place a setting is declared; the form, the
     * loading and the saving all read from it, so adding a setting is one line.
     */
    public const FIELDS = [
        'shop_name'          => ['general', 'string'],
        'tagline'            => ['general', 'string'],
        'email'              => ['general', 'string'],
        'phone'              => ['general', 'string'],
        'whatsapp'           => ['general', 'string'],
        'address'            => ['general', 'text'],
        'logo'               => ['general', 'image'],

        'free_shipping_from' => ['shipping', 'money'],
        'flat_rate'          => ['shipping', 'money'],
        'cod_fee'            => ['shipping', 'money'],
        'cod_on'             => ['shipping', 'bool'],
        'dispatch_days'      => ['shipping', 'int'],

        'bar_on'             => ['announcement', 'bool'],
        'bar_text'           => ['announcement', 'string'],
        'bar_url'            => ['announcement', 'string'],

        'instagram'          => ['social', 'string'],
        'facebook'           => ['social', 'string'],
        'youtube'            => ['social', 'string'],

        'returns'            => ['policy', 'text'],
        'shipping_policy'    => ['policy', 'text'],
        'terms'              => ['policy', 'text'],
        'privacy'            => ['policy', 'text'],
        'story'              => ['policy', 'text'],

        'seo_home_title'          => ['seo', 'string'],
        'seo_home_description'    => ['seo', 'text'],
        'seo_title_suffix'        => ['seo', 'string'],
        'seo_google_verification' => ['seo', 'string'],
        'seo_bing_verification'   => ['seo', 'string'],
        'seo_hidden'              => ['seo', 'bool'],

        'analytics_ga4'              => ['analytics', 'string'],
        'analytics_meta_pixel'       => ['analytics', 'string'],
        'analytics_google_ads'       => ['analytics', 'string'],
        'analytics_google_ads_label' => ['analytics', 'string'],
    ];

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $values = [];

        foreach (array_keys(self::FIELDS) as $key) {
            $values[$key] = Setting::get($key);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()->tabs([
                    Tab::make('The shop')->schema([
                        Section::make()->columns(2)->schema([
                            TextInput::make('shop_name')->label('Name')->required()->maxLength(120),
                            TextInput::make('tagline')->label('One line about it')->maxLength(200),
                            TextInput::make('email')->label('Email customers write to')->email()->maxLength(190),
                            TextInput::make('phone')->label('Telephone')->tel()->maxLength(30),
                            TextInput::make('whatsapp')
                                ->label('WhatsApp number')
                                ->maxLength(20)
                                ->placeholder('919000000000')
                                ->helperText('With the country code and no spaces, so the chat button works.'),
                            Textarea::make('address')->label('Postal address')->rows(3)->columnSpanFull(),
                            FileUpload::make('logo')
                                ->label('Logo')
                                ->image()
                                ->disk('public')
                                ->directory('brand')
                                ->maxSize(2048)
                                ->helperText('Leave empty to use the OJASVI logo the site ships with.')
                                ->columnSpanFull(),
                        ]),
                    ]),

                    Tab::make('Delivery & payment')->schema([
                        Section::make()->columns(2)->schema([
                            TextInput::make('free_shipping_from')
                                ->label('Free delivery once the bag reaches')
                                ->numeric()->prefix('₹')
                                ->helperText('This is the figure the shop promises on every page, so it must match what checkout charges.'),
                            TextInput::make('flat_rate')->label('Otherwise delivery costs')->numeric()->prefix('₹'),
                            TextInput::make('cod_fee')->label('Extra for cash on delivery')->numeric()->prefix('₹'),
                            TextInput::make('dispatch_days')
                                ->label('Posted within')
                                ->numeric()->suffix('working days')->minValue(0)->maxValue(30),
                            Toggle::make('cod_on')->label('Offer cash on delivery')->columnSpanFull(),
                        ]),
                    ]),

                    Tab::make('The bar at the top')->schema([
                        Section::make()->columns(2)->schema([
                            Toggle::make('bar_on')->label('Show it')->columnSpanFull(),
                            TextInput::make('bar_text')->label('It says')->maxLength(160)->columnSpanFull(),
                            TextInput::make('bar_url')->label('And links to')->maxLength(300)->placeholder('/sarees'),
                        ]),
                    ]),

                    Tab::make('Elsewhere')->schema([
                        Section::make()->columns(2)->schema([
                            TextInput::make('instagram')->label('Instagram')->url()->maxLength(300),
                            TextInput::make('facebook')->label('Facebook')->url()->maxLength(300),
                            TextInput::make('youtube')->label('YouTube')->url()->maxLength(300),
                        ]),
                    ]),

                    Tab::make('What you promise')->schema([
                        Section::make()
                            ->description('Leave any of these empty and the shop uses sensible wording of its own. Razorpay will not approve a shop without returns, delivery, terms and privacy, so these are not optional in practice.')
                            ->schema([
                                Textarea::make('story')->label('Our story')->rows(5),
                                Textarea::make('returns')->label('Returns')->rows(5),
                                Textarea::make('shipping_policy')->label('Delivery')->rows(5),
                                Textarea::make('terms')->label('Terms')->rows(8),
                                Textarea::make('privacy')->label('Privacy')->rows(8),
                            ]),
                    ]),

                    Tab::make('Found on Google')->schema([
                        Section::make('How the shop reads in search results')
                            ->schema([
                                TextInput::make('seo_home_title')
                                    ->label('Title of the home page')
                                    ->maxLength(70)
                                    ->placeholder(\App\Support\Seo::homeTitle())
                                    ->helperText('About sixty characters. Google cuts off what is longer. The most valuable line on the whole site — say what you sell and where.'),

                                Textarea::make('seo_home_description')
                                    ->label('The two lines underneath')
                                    ->rows(3)
                                    ->maxLength(300)
                                    ->placeholder(\App\Support\Seo::homeDescription())
                                    ->helperText('About a hundred and fifty characters. This is an advertisement, not a summary — it is what makes somebody click you rather than the shop above you.'),

                                TextInput::make('seo_title_suffix')
                                    ->label('Put after every other page title')
                                    ->maxLength(40)
                                    ->placeholder(\App\Support\Shop::name()),
                            ]),

                        Section::make('Proving the shop is yours')
                            ->description('Search Console is how you see what people searched for before they found you. Add the site at search.google.com/search-console, choose the HTML tag method, and paste what it gives you here.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('seo_google_verification')
                                    ->label('Google Search Console')
                                    ->maxLength(300)
                                    ->placeholder('google-site-verification=…')
                                    ->helperText('Paste the whole tag or just the code — either works.'),

                                TextInput::make('seo_bing_verification')
                                    ->label('Bing Webmaster Tools')
                                    ->maxLength(300),
                            ]),

                        Section::make('Where to point Google')
                            ->schema([
                                Placeholder::make('sitemap')
                                    ->label('Your sitemap')
                                    ->content(fn () => url('/sitemap.xml'))
                                    ->helperText('Give this address to Search Console once. It keeps itself up to date as you add sarees.'),

                                Placeholder::make('feed')
                                    ->label('Your product feed')
                                    ->content(fn () => url('/feed/google.xml'))
                                    ->helperText('For Google Merchant Center and Meta commerce — free listings on Google Shopping, and tagging on Instagram.'),

                                Toggle::make('seo_hidden')
                                    ->label('Hide the whole shop from search engines')
                                    ->helperText('For while you are still setting up. Turn it off on the day you open, or nobody will ever find you.'),
                            ]),
                    ]),

                    Tab::make('Analytics')->schema([
                        Section::make()
                            ->description('Leave any of these empty and nothing is loaded — a shop with no Google Analytics should not be paying for the script that does nothing.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('analytics_ga4')
                                    ->label('Google Analytics')
                                    ->placeholder('G-XXXXXXXXXX')
                                    ->maxLength(30)
                                    ->helperText('analytics.google.com → Admin → Data streams.'),

                                TextInput::make('analytics_meta_pixel')
                                    ->label('Meta (Facebook) pixel')
                                    ->placeholder('123456789012345')
                                    ->maxLength(30)
                                    ->helperText('Needed for Instagram and Facebook advertising.'),

                                TextInput::make('analytics_google_ads')
                                    ->label('Google Ads')
                                    ->placeholder('AW-XXXXXXXXX')
                                    ->maxLength(30),

                                TextInput::make('analytics_google_ads_label')
                                    ->label('Google Ads purchase label')
                                    ->placeholder('abcDEFghIJ')
                                    ->maxLength(60)
                                    ->helperText('From the conversion action you made for a purchase.'),
                            ]),
                    ]),
                ])->persistTabInQueryString(),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (self::FIELDS as $key => [$group, $type]) {
            Setting::put($key, $state[$key] ?? '', $type, $group);
        }

        Notification::make()
            ->title('Saved')
            ->body('The shop is using these straight away.')
            ->success()
            ->send();
    }
}
