<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Google\GoogleStats;
use App\Services\Google\ServiceAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * What Google knows about the shop, brought back into the admin.
 *
 * Nothing here talks to Google: the point of these is what the shop sees when
 * Google is slow, or refuses, or was never set up — which is most of the time
 * for most shops, and the state a dashboard usually handles worst.
 */
class GoogleStatsTest extends TestCase
{
    use RefreshDatabase;

    /** A real RSA key, made here, so the signing is actually exercised. */
    private function credentials(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);

        return json_encode([
            'type'         => 'service_account',
            'client_email' => 'ojasvi@example.iam.gserviceaccount.com',
            'private_key'  => $pem,
        ]);
    }

    private function setUpGoogle(): void
    {
        Setting::put('google_service_account', $this->credentials(), 'text', 'analytics');
        Setting::put('google_ga4_property', '123456789', 'string', 'analytics');
        Setting::put('google_search_console_site', 'sc-domain:ojasvidrapes.in', 'string', 'analytics');
    }

    public function test_nothing_is_asked_of_google_until_the_shop_has_set_it_up(): void
    {
        Http::fake();

        $stats = new GoogleStats;

        $this->assertFalse($stats->configured());
        $this->assertNull($stats->visitors(30));
        $this->assertNull($stats->search(30));

        Http::assertNothingSent();
    }

    public function test_the_key_is_signed_and_exchanged_for_a_token(): void
    {
        $this->setUpGoogle();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token', 'expires_in' => 3600]),
        ]);

        $account = ServiceAccount::fromSettings();

        $this->assertSame('a-token', $account->token(ServiceAccount::ANALYTICS));

        Http::assertSent(function ($request) {
            // Three dots' worth of JWT, signed with the key above.
            $parts = explode('.', $request['assertion']);
            $claims = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            return count($parts) === 3
                && $claims['iss'] === 'ojasvi@example.iam.gserviceaccount.com'
                && $claims['aud'] === 'https://oauth2.googleapis.com/token'
                && str_contains($claims['scope'], 'analytics.readonly');
        });
    }

    public function test_visitors_are_read_from_analytics(): void
    {
        $this->setUpGoogle();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [
                    ['dimensionValues' => [['value' => 'Organic Search']], 'metricValues' => [['value' => '120'], ['value' => '140'], ['value' => '600']]],
                    ['dimensionValues' => [['value' => 'Direct']], 'metricValues' => [['value' => '30'], ['value' => '35'], ['value' => '90']]],
                ],
            ]),
        ]);

        $visitors = (new GoogleStats)->visitors(30);

        $this->assertSame(150, $visitors['people']);
        $this->assertSame(175, $visitors['visits']);
        $this->assertSame(690, $visitors['pages']);
        // Busiest first, whatever order Google sent them in.
        $this->assertSame('Organic Search', $visitors['from'][0]['name']);
    }

    public function test_what_people_searched_for_is_read_from_search_console(): void
    {
        $this->setUpGoogle();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'www.googleapis.com/webmasters/*' => Http::sequence()
                ->push(['rows' => [['clicks' => 42, 'impressions' => 1900, 'ctr' => 0.0221, 'position' => 14.6]]])
                ->push(['rows' => [
                    ['keys' => ['kanjivaram saree hyderabad'], 'clicks' => 20, 'impressions' => 400, 'position' => 6.2],
                ]]),
        ]);

        $search = (new GoogleStats)->search(30);

        $this->assertSame(42, $search['clicks']);
        $this->assertSame(1900, $search['impressions']);
        $this->assertEqualsWithDelta(2.21, $search['ctr'], 0.01);
        $this->assertSame('kanjivaram saree hyderabad', $search['queries'][0]['words']);
    }

    /** Google being slow must not read as nobody having visited. */
    public function test_a_refusal_is_not_reported_as_nobody_came(): void
    {
        $this->setUpGoogle();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'analyticsdata.googleapis.com/*' => Http::response(['error' => ['message' => 'permission denied']], 403),
            'www.googleapis.com/webmasters/*' => Http::response('', 500),
        ]);

        $stats = new GoogleStats;

        $this->assertNull($stats->visitors(30));
        $this->assertNull($stats->search(30));
    }

    public function test_the_analysis_screen_says_what_is_missing_rather_than_breaking(): void
    {
        Http::fake();

        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/insights')
            ->assertOk()
            ->assertSee('What Google knows')
            ->assertSee('Not set up yet');
    }

    /**
     * The populated state, which no browser here can reach.
     *
     * Without real credentials this panel can only ever be seen empty, and an
     * empty panel renders a different half of the template from a full one.
     */
    public function test_the_numbers_appear_on_the_analysis_screen(): void
    {
        $this->setUpGoogle();

        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'a-token']),
            'analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [
                    ['dimensionValues' => [['value' => 'Organic Search']], 'metricValues' => [['value' => '1240'], ['value' => '1500'], ['value' => '6200']]],
                ],
            ]),
            'www.googleapis.com/webmasters/*' => Http::sequence()
                ->push(['rows' => [['clicks' => 42, 'impressions' => 1900, 'ctr' => 0.0221, 'position' => 14.6]]])
                ->push(['rows' => [
                    ['keys' => ['kanjivaram saree hyderabad'], 'clicks' => 20, 'impressions' => 400, 'position' => 6.2],
                ]]),
        ]);

        $admin = User::create([
            'name' => 'OJASVI', 'email' => 'owner@example.test',
            'password' => 'long-enough-for-this', 'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin/insights')
            ->assertOk()
            ->assertSee('What Google knows')
            ->assertDontSee('Not set up yet')
            ->assertSee('1,240')                        // people
            ->assertSee('Organic Search')
            ->assertSee('kanjivaram saree hyderabad')
            ->assertSee('14.6');                        // average position
    }

    public function test_a_property_pasted_with_its_prefix_still_works(): void
    {
        $this->setUpGoogle();
        Setting::put('google_ga4_property', 'properties/123456789', 'string', 'analytics');

        $this->assertSame('123456789', (new GoogleStats)->propertyId());
    }
}
