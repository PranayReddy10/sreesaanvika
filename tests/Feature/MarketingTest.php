<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Google\GoogleStats;
use App\Services\Meta\MetaAds;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * What the advertising cost, and what came back.
 *
 * Spend against sales is the only figure that settles whether advertising is
 * worth doing, and it normally lives in two dashboards nobody opens daily.
 */
class MarketingTest extends TestCase
{
    use RefreshDatabase;

    private function googleCredentials(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);

        Setting::put('google_service_account', json_encode([
            'client_email' => 'ojasvi@example.iam.gserviceaccount.com',
            'private_key'  => $pem,
        ]), 'text', 'analytics');
        Setting::put('google_ga4_property', '123456789', 'string', 'analytics');
    }

    private function metaCredentials(): void
    {
        Setting::put('meta_ad_account', '1234567890', 'string', 'analytics');
        Setting::put('meta_access_token', 'a-long-lived-token', 'text', 'analytics');
    }

    /* ------------------------------------------------------------- Meta */

    public function test_nothing_is_asked_of_meta_until_the_shop_has_set_it_up(): void
    {
        Http::fake();

        $this->assertFalse((new MetaAds)->configured());
        $this->assertNull((new MetaAds)->results(30));

        Http::assertNothingSent();
    }

    public function test_an_ad_account_is_accepted_with_or_without_its_prefix(): void
    {
        Setting::put('meta_ad_account', 'act_1234567890', 'string', 'analytics');
        $this->assertSame('act_1234567890', (new MetaAds)->account());

        Setting::put('meta_ad_account', '1234567890', 'string', 'analytics');
        $this->assertSame('act_1234567890', (new MetaAds)->account());
    }

    public function test_meta_spend_and_sales_are_read(): void
    {
        $this->metaCredentials();

        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [[
            'spend'         => '4200.50',
            'impressions'   => '91000',
            'clicks'        => '1320',
            'actions'       => [
                ['action_type' => 'link_click', 'value' => '1320'],
                ['action_type' => 'purchase', 'value' => '14'],
            ],
            'action_values' => [
                ['action_type' => 'purchase', 'value' => '168000'],
            ],
        ]]])]);

        $results = (new MetaAds)->results(30);

        $this->assertEqualsWithDelta(4200.50, $results['spend'], 0.01);
        $this->assertSame(14, $results['purchases']);
        $this->assertEqualsWithDelta(168000.0, $results['value'], 0.01);
        $this->assertEqualsWithDelta(39.99, $results['roas'], 0.01);
    }

    /**
     * Meta rolls the same sale up under a second name.
     *
     * A shop with only the pixel reports "purchase"; one that also sells on
     * Facebook gets "omni_purchase" as well, for the same sales. Counting
     * both would double every one of them.
     */
    public function test_a_sale_is_not_counted_twice_under_metas_two_names(): void
    {
        $this->metaCredentials();

        Http::fake(['graph.facebook.com/*' => Http::response(['data' => [[
            'spend'   => '1000',
            'actions' => [
                ['action_type' => 'purchase', 'value' => '10'],
                ['action_type' => 'omni_purchase', 'value' => '10'],
            ],
            'action_values' => [
                ['action_type' => 'purchase', 'value' => '50000'],
                ['action_type' => 'omni_purchase', 'value' => '50000'],
            ],
        ]]])]);

        $results = (new MetaAds)->results(30);

        $this->assertSame(10, $results['purchases']);
        $this->assertEqualsWithDelta(50000.0, $results['value'], 0.01);
    }

    public function test_a_stretch_with_no_advertising_is_not_the_same_as_no_answer(): void
    {
        $this->metaCredentials();

        Http::fake(['graph.facebook.com/*' => Http::response(['data' => []])]);

        $results = (new MetaAds)->results(30);

        $this->assertNotNull($results, 'an empty answer is still an answer');
        $this->assertSame(0.0, $results['spend']);
        $this->assertNull($results['roas']);
    }

    public function test_an_expired_token_is_reported_as_no_answer(): void
    {
        $this->metaCredentials();

        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'expired']], 400)]);

        $this->assertNull((new MetaAds)->results(30));
    }

    /* ----------------------------------------------------------- Google */

    public function test_google_ad_cost_comes_through_analytics(): void
    {
        $this->googleCredentials();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [['metricValues' => [
                    ['value' => '8200'], ['value' => '940'], ['value' => '120000'], ['value' => '6.4'],
                ]]],
            ]),
        ]);

        $ads = (new GoogleStats)->ads(30);

        $this->assertEqualsWithDelta(8200.0, $ads['cost'], 0.01);
        $this->assertSame(940, $ads['clicks']);
        $this->assertSame(120000, $ads['impressions']);
        $this->assertEqualsWithDelta(6.4, $ads['roas'], 0.01);
    }

    /** Analytics refuses the whole report when Ads is not linked to it. */
    public function test_google_ads_not_being_linked_reads_as_nothing_rather_than_zero(): void
    {
        $this->googleCredentials();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'analyticsdata.googleapis.com/*' => Http::response(
                ['error' => ['message' => 'Field advertiserAdCost is not compatible']],
                400,
            ),
        ]);

        $this->assertNull((new GoogleStats)->ads(30));
    }

    /* ------------------------------------------------------- the screen */

    public function test_the_front_page_says_what_is_missing_rather_than_breaking(): void
    {
        Http::fake();

        $this->actingAs($this->anAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('What the advertising cost')
            ->assertSee('Needs an ad account and a long-lived token');
    }

    public function test_the_front_page_shows_both_when_both_are_set_up(): void
    {
        $this->googleCredentials();
        $this->metaCredentials();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [['metricValues' => [
                    ['value' => '8200'], ['value' => '940'], ['value' => '120000'], ['value' => '6.4'],
                ]]],
            ]),
            'graph.facebook.com/*' => Http::response(['data' => [[
                'spend' => '4200', 'impressions' => '91000', 'clicks' => '1320',
                'actions' => [['action_type' => 'purchase', 'value' => '14']],
                'action_values' => [['action_type' => 'purchase', 'value' => '168000']],
            ]]]),
        ]);

        $this->actingAs($this->anAdmin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('What the advertising cost')
            ->assertDontSee('Needs an ad account')
            ->assertSee('₹8,200')     // what Google cost
            ->assertSee('₹4,200')     // what Meta cost
            ->assertSee('₹6.40');     // back for every rupee, from Google
    }

    /** The old Dashboard and Analysis are one screen now. */
    public function test_there_is_one_front_page_and_it_has_both_halves_on_it(): void
    {
        Http::fake();

        $page = $this->actingAs($this->anAdmin())->get('/admin')->assertOk();

        // The four panels that were the Dashboard. By class rather than by
        // their headings: Filament mounts a widget lazily, so the first
        // response carries the component and not yet a word of its contents.
        foreach (['ShopOverview', 'SalesChart', 'OrdersNeedingWork', 'RunningLow'] as $widget) {
            $page->assertSee('Widgets\\'.$widget, false);
        }

        // And what was only ever on Analysis.
        $page->assertSee('Who is about')
            ->assertSee('What Google knows')
            ->assertSee('What the advertising cost');

        $this->actingAs($this->anAdmin())->get('/admin/insights')->assertNotFound();
    }

    private function anAdmin(): User
    {
        return User::firstOrCreate(
            ['email' => 'owner@example.test'],
            ['name' => 'OJASVI', 'password' => 'long-enough-for-this', 'is_admin' => true],
        );
    }
}
