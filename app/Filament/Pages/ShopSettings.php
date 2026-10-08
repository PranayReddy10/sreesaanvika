<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Providers\ShopConfigProvider;
use App\Support\StorageCheck;
use Illuminate\Support\Facades\Storage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
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
        'online_on'          => ['shipping', 'bool'],
        'cod_max'            => ['shipping', 'money'],
        'order_emails'       => ['general', 'string'],
        'dispatch_days'      => ['shipping', 'int'],

        'bar_on'             => ['announcement', 'bool'],
        'bar_text'           => ['announcement', 'string'],
        'bar_url'            => ['announcement', 'string'],

        'instagram'          => ['social', 'string'],
        'facebook'           => ['social', 'string'],
        'youtube'            => ['social', 'string'],

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

        'google_service_account'      => ['analytics', 'text'],
        'google_ga4_property'         => ['analytics', 'string'],
        'google_search_console_site'  => ['analytics', 'string'],

        'meta_ad_account'             => ['analytics', 'string'],
        'meta_access_token'           => ['analytics', 'text'],

        'popup_on'      => ['popup', 'bool'],
        'popup_heading' => ['popup', 'string'],
        'popup_text'    => ['popup', 'text'],
        'popup_label'   => ['popup', 'string'],
        'popup_url'     => ['popup', 'string'],
        'popup_image'   => ['popup', 'string'],
        'popup_after'   => ['popup', 'string'],
        'popup_again'   => ['popup', 'string'],
        'popup_ask'     => ['popup', 'bool'],

        'storage_driver'  => ['storage', 'string'],
        'spaces_key'      => ['storage', 'string'],
        'spaces_secret'   => ['storage', 'secret'],
        'spaces_bucket'   => ['storage', 'string'],
        'spaces_region'   => ['storage', 'string'],
        'spaces_endpoint' => ['storage', 'string'],
        'spaces_url'      => ['storage', 'string'],

        'mail_host'       => ['email', 'string'],
        'mail_port'       => ['email', 'string'],
        'mail_encryption' => ['email', 'string'],
        'mail_username'   => ['email', 'string'],
        'mail_password'   => ['email', 'secret'],
        'mail_from'       => ['email', 'string'],
        'mail_from_name'  => ['email', 'string'],
    ];

    /**
     * The ones never sent back to the browser.
     *
     * A password that is filled into a form is a password in the page source,
     * in the browser's cache and in anything that records a screen. These are
     * saved and never shown again; left empty, the one already saved stays.
     */
    private const SECRETS = ['spaces_secret', 'mail_password'];

    /**
     * What a setting means before anybody has saved it.
     *
     * Only for the ones where "never written" is not the same as "off". Cash
     * on delivery is on until the shop says otherwise — and without this the
     * toggle would show off while the shop behaved as on, so the very first
     * save would quietly close the shop's payments.
     */
    private const UNSET_MEANS = [
        'cod_on'        => true,
        'online_on'     => true,
        'free_shipping_from' => null,   // null → fall back to the shop's own figure
        'flat_rate'     => null,
        'dispatch_days' => null,
    ];

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $values = [];

        foreach (array_keys(self::FIELDS) as $key) {
            if (in_array($key, self::SECRETS, true)) {
                // Blank, always. The helper text under the box says so.
                $values[$key] = '';

                continue;
            }

            $stored = Setting::get($key);

            if ($stored === null && array_key_exists($key, self::UNSET_MEANS)) {
                $stored = match ($key) {
                    'cod_on'             => true,
                    'online_on'          => true,
                    'free_shipping_from' => \App\Support\Shop::freeShippingFrom(),
                    'flat_rate'          => \App\Support\Shop::flatShipping(),
                    'dispatch_days'      => \App\Support\Shop::dispatchDays(),
                    default              => null,
                };
            }

            $values[$key] = $stored;
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

                            TextInput::make('order_emails')
                                ->label('Also tell these people about new orders')
                                ->maxLength(500)
                                ->placeholder('amma@example.in, accounts@example.in')
                                ->helperText('Separated by commas. Everybody with an admin account is told anyway — this is for anyone else.')
                                ->columnSpanFull(),
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
                        Section::make('What delivery costs')
                            ->description('These two figures are quoted all over the shop, so they have to be the ones checkout actually charges. Change them here and every page follows.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('free_shipping_from')
                                    ->label('Free delivery once the bag reaches')
                                    ->numeric()->prefix('₹')->minValue(0)
                                    ->helperText('Set it to 0 to give free delivery on everything.'),

                                TextInput::make('flat_rate')
                                    ->label('Otherwise delivery costs')
                                    ->numeric()->prefix('₹')->minValue(0),

                                TextInput::make('dispatch_days')
                                    ->label('Posted within')
                                    ->numeric()->suffix('working days')->minValue(0)->maxValue(30),

                                Placeholder::make('zones')
                                    ->label('Charging differently by area?')
                                    ->content('Delivery areas sets a rate and a free-delivery figure per group of pincodes, and those win over the two above.')
                                    ->helperText('Shop → Delivery areas.'),
                            ]),

                        Section::make('How people may pay')
                            ->description('Turn either off and it stops being offered at checkout at once. With both off, nothing can be ordered — which is the honest way to close the shop for a week.')
                            ->columns(2)
                            ->schema([
                                Toggle::make('online_on')
                                    ->label('Card, UPI and net banking')
                                    ->default(true)
                                    ->helperText('Needs the Razorpay keys in .env. Without them this stays off however it is set here.'),

                                Toggle::make('cod_on')
                                    ->label('Cash on delivery')
                                    ->default(true)
                                    ->live()
                                    ->helperText('Can also be refused for particular pincodes under Delivery areas.'),

                                TextInput::make('cod_fee')
                                    ->label('Extra charged for cash on delivery')
                                    ->numeric()->prefix('₹')->minValue(0)
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('cod_on'))
                                    ->helperText('Shown to the shopper before they place the order, never added afterwards.'),

                                TextInput::make('cod_max')
                                    ->label('And not offered above a bag of')
                                    ->numeric()->prefix('₹')->minValue(0)
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => (bool) $get('cod_on'))
                                    ->placeholder('No limit')
                                    ->helperText('A bridal silk sent out on trust is a large loss when it is refused at the door.'),
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

                    Tab::make('Email')->schema([
                        Section::make('How the shop sends email')
                            ->description('Order confirmations, the shipped notice, and anything written from the contact page. Leave the host empty and the shop uses whatever is in .env, which is how it worked before this screen existed.')
                            ->columns(2)
                            ->schema([
                                TextInput::make('mail_host')
                                    ->label('SMTP host')
                                    ->placeholder('smtp.hostinger.com')
                                    ->maxLength(190)
                                    ->helperText('From whoever provides the mailbox.'),

                                Select::make('mail_encryption')
                                    ->label('Security')
                                    ->options([
                                        'ssl'  => 'SSL — usually port 465',
                                        'tls'  => 'TLS — usually port 587',
                                        'none' => 'None',
                                    ])
                                    ->default('ssl')
                                    ->native(false),

                                TextInput::make('mail_port')
                                    ->label('Port')
                                    ->numeric()
                                    ->placeholder('465')
                                    ->helperText('Leave empty and the shop uses the usual one for the security above.'),

                                TextInput::make('mail_username')
                                    ->label('Username')
                                    ->maxLength(190)
                                    ->placeholder('care@ojasvidrapes.in')
                                    ->helperText('Usually the whole address.'),

                                TextInput::make('mail_password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(190)
                                    ->helperText('Kept encrypted. Leave empty to keep the one already saved.')
                                    ->columnSpanFull(),

                                TextInput::make('mail_from')
                                    ->label('Send from this address')
                                    ->email()
                                    ->maxLength(190)
                                    ->placeholder('care@ojasvidrapes.in')
                                    ->helperText('What a customer sees in her inbox. It normally has to be an address on the same mailbox as the username above, or the mail is refused as a forgery.'),

                                TextInput::make('mail_from_name')
                                    ->label('And under this name')
                                    ->maxLength(120)
                                    ->placeholder('OJASVI'),
                            ]),
                    ]),

                    Tab::make('Photograph storage')->schema([
                        Section::make('Where the photographs are kept')
                            ->description('On this server, or on DigitalOcean Spaces. The paths stored against each saree are the same either way, so moving is copying the files up and changing this — nothing in the database changes.')
                            ->schema([
                                Select::make('storage_driver')
                                    ->label('Keep them')
                                    ->options([
                                        ''       => 'On this server (as set in .env)',
                                        'spaces' => 'On DigitalOcean Spaces',
                                    ])
                                    ->default('')
                                    ->native(false)
                                    ->live(),

                                TextInput::make('spaces_bucket')
                                    ->label('Space name')
                                    ->maxLength(120)
                                    ->placeholder('ojasvi')
                                    ->visible(fn (Get $get) => $get('storage_driver') === 'spaces'),

                                TextInput::make('spaces_region')
                                    ->label('Region')
                                    ->maxLength(20)
                                    ->default('blr1')
                                    ->placeholder('blr1')
                                    ->helperText('blr1 is Bangalore. The shop sells in India; its photographs should not travel to Amsterdam and back.')
                                    ->visible(fn (Get $get) => $get('storage_driver') === 'spaces'),

                                TextInput::make('spaces_key')
                                    ->label('Access key')
                                    ->maxLength(190)
                                    ->visible(fn (Get $get) => $get('storage_driver') === 'spaces'),

                                TextInput::make('spaces_secret')
                                    ->label('Secret')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(190)
                                    ->helperText('Kept encrypted. Leave empty to keep the one already saved.')
                                    ->visible(fn (Get $get) => $get('storage_driver') === 'spaces'),

                                TextInput::make('spaces_url')
                                    ->label('CDN address')
                                    ->maxLength(300)
                                    ->placeholder('https://ojasvi.blr1.cdn.digitaloceanspaces.com')
                                    ->helperText('From the Space\'s settings, if its CDN is switched on. Leave empty and the files are served straight from the Space, which works and is slower.')
                                    ->visible(fn (Get $get) => $get('storage_driver') === 'spaces'),

                                TextInput::make('spaces_endpoint')
                                    ->label('Endpoint')
                                    ->maxLength(300)
                                    ->placeholder('https://blr1.digitaloceanspaces.com')
                                    ->helperText('Worked out from the region. Only fill this in for a Space somewhere unusual.')
                                    ->visible(fn (Get $get) => $get('storage_driver') === 'spaces'),
                            ]),
                    ]),

                    Tab::make('Popup')->schema([
                        Section::make()
                            ->description('One message, over the front of the shop. Used well — a sale, a new weave, the list — it works; used for nothing in particular it is the thing people close without reading. It stays shut for a month once somebody has closed it.')
                            ->columns(2)
                            ->schema([
                                Toggle::make('popup_on')
                                    ->label('Show it')
                                    ->helperText('Off, and nothing appears at all.')
                                    ->columnSpanFull(),

                                TextInput::make('popup_heading')
                                    ->label('Heading')
                                    ->maxLength(80)
                                    ->placeholder('Ten new weaves, this Friday'),

                                TextInput::make('popup_label')
                                    ->label('Button')
                                    ->maxLength(40)
                                    ->placeholder('See them first'),

                                Textarea::make('popup_text')
                                    ->label('And a line or two')
                                    ->rows(3)
                                    ->maxLength(300)
                                    ->columnSpanFull(),

                                TextInput::make('popup_url')
                                    ->label('Where the button goes')
                                    ->maxLength(300)
                                    ->placeholder('/sarees?sort=new')
                                    ->helperText('A path on this shop, or a whole address.'),

                                FileUpload::make('popup_image')
                                    ->label('A photograph beside it')
                                    ->image()
                                    ->disk('public')
                                    ->directory('popup')
                                    ->imageEditor()
                                    ->maxSize(2048),

                                TextInput::make('popup_after')
                                    ->label('Seconds before it appears')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(120)
                                    ->default(6)
                                    ->helperText('Long enough that she has seen the shop first.'),

                                TextInput::make('popup_again')
                                    ->label('Days before asking again')
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(365)
                                    ->default(30)
                                    ->helperText('After somebody closes it.'),

                                Toggle::make('popup_ask')
                                    ->label('Ask for an email address in it')
                                    ->helperText('Adds the mailing-list box. The address goes on the same list as the one in the footer.')
                                    ->columnSpanFull(),
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

                        Section::make('Reading the figures back')
                            ->description('The boxes above send figures to Google. These three bring them back, so Analysis shows visitors and what people searched for without leaving the shop. All three are needed, and none of them is needed for the shop to work.')
                            ->collapsed()
                            ->schema([
                                Textarea::make('google_service_account')
                                    ->label('The key file')
                                    ->rows(5)
                                    ->autosize(false)
                                    ->helperText(new HtmlString(
                                        'Paste the whole JSON file Google gave you. '
                                        . 'Cloud console → IAM → Service accounts → Keys → Add key → JSON. '
                                        . 'Then give that account’s address <strong>Viewer</strong> on the '
                                        . 'Analytics property, and add it as a user in Search Console. '
                                        . 'It is a password: anybody who can open this screen can read it.'
                                    ))
                                    ->rule(fn () => function (string $attribute, $value, $fail) {
                                        if (blank($value)) {
                                            return;
                                        }

                                        $key = json_decode((string) $value, true);

                                        if (! is_array($key) || blank($key['client_email'] ?? null) || blank($key['private_key'] ?? null)) {
                                            $fail('That is not the key file — it should be JSON with a client_email and a private_key in it.');
                                        }
                                    }),

                                TextInput::make('google_ga4_property')
                                    ->label('Analytics property number')
                                    ->placeholder('123456789')
                                    ->maxLength(40)
                                    ->helperText('Analytics → Admin → Property settings. A number, not the G- code above.'),

                                TextInput::make('google_search_console_site')
                                    ->label('Search Console property')
                                    ->placeholder('sc-domain:ojasvidrapes.in')
                                    ->maxLength(120)
                                    ->helperText(new HtmlString(
                                        'Exactly as Search Console spells it: <code>sc-domain:ojasvidrapes.in</code> '
                                        . 'for a domain property, or <code>https://ojasvidrapes.in/</code> for a URL one.'
                                    )),
                            ]),

                        Section::make('What the advertising costs')
                            ->description('Spend against sales, on the front page beside everything else. Google Ads comes through Analytics — link the two in Analytics → Admin → Google Ads links and nothing more is needed here. Instagram and Facebook need their own two.')
                            ->collapsed()
                            ->columns(2)
                            ->schema([
                                TextInput::make('meta_ad_account')
                                    ->label('Meta ad account')
                                    ->placeholder('act_1234567890')
                                    ->maxLength(60)
                                    ->helperText('Ads Manager, top left. With or without the act_ in front.'),

                                Textarea::make('meta_access_token')
                                    ->label('Meta access token')
                                    ->rows(3)
                                    ->helperText(new HtmlString(
                                        'A <strong>long-lived</strong> token with <code>ads_read</code>, from a system '
                                        . 'user in Business settings. A token from the Graph Explorer lasts an hour '
                                        . 'and this panel will be empty again by tomorrow. It is a password.'
                                    ))
                                    ->columnSpanFull(),
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
        $storageBefore = $this->storageFingerprint();

        foreach (self::FIELDS as $key => [$group, $type]) {
            $value = $state[$key] ?? '';

            // Leaving a password box empty means "keep the one you have", not
            // "forget it" — otherwise every save of any other setting would
            // quietly empty them.
            if (in_array($key, self::SECRETS, true) && trim((string) $value) === '') {
                continue;
            }

            Setting::put($key, $value, $type, $group);
        }

        Notification::make()
            ->title('Saved')
            ->body('The shop is using these straight away.')
            ->success()
            ->send();

        // Only when it is the photographs' home that has changed: a shop
        // editing its telephone number has no business waiting on DigitalOcean.
        if ($this->storageFingerprint() !== $storageBefore) {
            $this->checkTheStorage($state);
        }
    }

    /** Everything that decides where a photograph is written, as one string. */
    private function storageFingerprint(): string
    {
        $keys = array_filter(
            array_keys(self::FIELDS),
            fn (string $key) => $key === 'storage_driver' || str_starts_with($key, 'spaces_'),
        );

        return collect($keys)
            ->map(fn (string $key) => $key.'='.(string) Setting::get($key, ''))
            ->implode('|');
    }

    /**
     * Having just been told where the photographs go, try putting one there.
     *
     * Otherwise the first the shop hears of a mistyped key is a photograph
     * that will not upload, hours later, reported by the browser as "failed to
     * upload" and nothing else. One file is written, read back, fetched the way
     * a shopper fetches it, and deleted — and if any of that is refused, the
     * refusal is on screen while the keys are still in front of whoever typed
     * them.
     *
     * @param  array<string, mixed>  $state
     */
    private function checkTheStorage(array $state): void
    {
        if (($state['storage_driver'] ?? '') !== 'spaces') {
            return;
        }

        // The settings were saved a moment ago, so the disk built from the old
        // ones is no longer the one being tested.
        (new ShopConfigProvider(app()))->boot();
        Storage::forgetDisk('public');

        $check = StorageCheck::run();

        if ($check->ok) {
            Notification::make()
                ->title('DigitalOcean is working')
                ->body('A file was written to the Space, read back, and seen from outside. Photographs will upload.')
                ->success()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title($check->summary)
            ->body(trim(($check->advice ? $check->advice.' ' : '').($check->detail ?? '')) ?: null)
            ->danger()
            ->persistent()
            ->send();
    }
}
