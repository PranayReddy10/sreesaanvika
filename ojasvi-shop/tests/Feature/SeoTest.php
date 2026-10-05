<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the shop tells search engines.
 *
 * Most of this is about keeping pages *out* of the index. A shop with its
 * checkout and a thousand filter combinations indexed is not a shop with more
 * presence in Google; it is one whose listing has to compete with itself.
 */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    public function test_the_sitemap_lists_the_pages_worth_arriving_on(): void
    {
        $response = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringContainsString(route('home'), $xml);
        $this->assertStringContainsString(route('shop'), $xml);
        $this->assertStringContainsString(route('product', Product::published()->first()->slug), $xml);
        $this->assertStringContainsString(route('page', 'returns'), $xml);

        // And not the ones it would be embarrassing to be found on.
        foreach (['/bag', '/checkout', '/account', '/admin'] as $private) {
            $this->assertStringNotContainsString(url($private), $xml);
        }

        $this->assertNotFalse(simplexml_load_string($xml), 'The sitemap has to be valid XML.');
    }

    public function test_a_draft_saree_is_not_in_the_sitemap(): void
    {
        $product = Product::published()->first();
        $product->update(['status' => 'draft']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee(route('product', $product->slug), false);
    }

    public function test_robots_keeps_crawlers_out_of_the_private_pages(): void
    {
        $body = $this->get('/robots.txt')->assertOk()->getContent();

        foreach (['/bag', '/checkout', '/account', '/admin', '/webhooks/'] as $path) {
            $this->assertStringContainsString("Disallow: {$path}", $body);
        }

        $this->assertStringContainsString('Sitemap: ' . url('/sitemap.xml'), $body);
    }

    public function test_a_shop_still_being_built_can_hide_itself_completely(): void
    {
        Setting::put('seo_hidden', '1', 'bool', 'seo');

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');

        // And says so on the page itself, because robots.txt is a request and
        // the meta tag is the one crawlers actually obey.
        $this->get('/')->assertOk()->assertSee('noindex, nofollow', false);

        // Nothing to feed a shopping service either.
        $this->get('/feed/google.xml')->assertNotFound();
    }

    public function test_a_narrowed_listing_is_followed_but_not_indexed(): void
    {
        $this->get('/sarees')
            ->assertOk()
            ->assertSee('index, follow', false)
            ->assertSee('rel="canonical" href="' . route('shop') . '"', false);

        foreach (['/sarees?q=silk', '/sarees?fabric=linen', '/sarees?on=offer', '/sarees?sort=price-low'] as $narrowed) {
            $this->get($narrowed)
                ->assertOk()
                ->assertSee('noindex, follow', false)
                // Pointing at the plain listing, so what authority it earns
                // goes there rather than being split a hundred ways.
                ->assertSee('rel="canonical" href="' . route('shop') . '"', false);
        }
    }

    public function test_a_saree_is_one_page_however_many_shades_it_has(): void
    {
        $product = Product::published()->has('colourways')->first();
        $shade = $product->colourways->first();

        $this->get(route('product', $product->slug) . '?shade=' . urlencode($shade->name))
            ->assertOk()
            ->assertSee('rel="canonical" href="' . route('product', $product->slug) . '"', false);
    }

    public function test_the_bag_and_the_checkout_are_never_indexed(): void
    {
        foreach (['/bag', '/account', '/track', '/sign-in', '/join'] as $private) {
            $this->get($private)->assertOk()->assertSee('noindex, nofollow', false);
        }
    }

    public function test_the_product_feed_has_one_entry_per_shade(): void
    {
        $xml = $this->get('/feed/google.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->getContent();

        $feed = simplexml_load_string($xml);

        $this->assertNotFalse($feed, 'Merchant Center rejects a feed it cannot parse.');

        $expected = Product::published()->withCount(['colourways' => fn ($q) => $q->where('is_visible', true)])
            ->get()
            ->sum(fn ($p) => max(1, $p->colourways_count));

        $this->assertCount($expected, $feed->channel->item);

        $first = $feed->channel->item[0]->children('g', true);

        $this->assertNotEmpty((string) $first->id);
        $this->assertStringContainsString('INR', (string) $first->price);
        $this->assertContains((string) $first->availability, ['in_stock', 'out_of_stock']);
    }

    public function test_the_shop_says_who_it_is_in_a_way_google_can_read(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.+?)</script>#s', $html, $blocks);

        $types = collect($blocks[1])
            ->map(fn ($json) => json_decode($json, true))
            ->filter()
            ->pluck('@type');

        $this->assertNotEmpty($blocks[1], 'There should be structured data on the home page.');
        $this->assertTrue($types->contains('Store'));
        $this->assertTrue($types->contains('WebSite'));

        foreach ($blocks[1] as $json) {
            $this->assertNotNull(json_decode($json), 'Every block has to be valid JSON: ' . substr($json, 0, 80));
        }
    }

    public function test_a_saree_page_carries_its_price_and_whether_it_is_in_stock(): void
    {
        $product = Product::published()->first();

        $html = $this->get(route('product', $product->slug))->assertOk()->getContent();

        preg_match_all('#<script type="application/ld\+json">(.+?)</script>#s', $html, $blocks);

        $product_ld = collect($blocks[1])
            ->map(fn ($json) => json_decode($json, true))
            ->firstWhere('@type', 'Product');

        $this->assertNotNull($product_ld, 'A saree page without Product data cannot show a price in search.');
        $this->assertSame($product->name, $product_ld['name']);
        $this->assertSame('INR', $product_ld['offers']['priceCurrency']);
        $this->assertNotEmpty($product_ld['offers']['availability']);
    }

    public function test_no_analytics_script_is_loaded_until_an_id_is_set(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
        $this->assertStringNotContainsString('connect.facebook.net', $html);

        Setting::put('analytics_ga4', 'G-ABCD123456', 'string', 'analytics');
        Setting::put('analytics_meta_pixel', '123456789012345', 'string', 'analytics');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=G-ABCD123456', $html);
        $this->assertStringContainsString('connect.facebook.net', $html);
    }

    public function test_a_nonsense_analytics_id_is_ignored_rather_than_printed(): void
    {
        // Pasted in with surrounding junk, as a tired shop owner would.
        Setting::put('analytics_ga4', '<script>alert(1)</script>', 'string', 'analytics');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertStringNotContainsString('googletagmanager', $html);
    }

    public function test_the_search_console_tag_is_accepted_whole_or_as_a_code(): void
    {
        Setting::put('seo_google_verification', '<meta name="google-site-verification" content="abc123" />', 'string', 'seo');

        $this->get('/')->assertOk()->assertSee('content="abc123"', false);

        Setting::put('seo_google_verification', 'xyz789', 'string', 'seo');

        $this->get('/')->assertOk()->assertSee('content="xyz789"', false);
    }

    public function test_every_policy_page_has_wording_even_before_the_shop_writes_its_own(): void
    {
        foreach (['returns', 'shipping', 'terms', 'privacy', 'story'] as $slug) {
            $response = $this->get("/page/{$slug}")->assertOk();

            $this->assertGreaterThan(
                800,
                strlen(strip_tags($response->getContent())),
                "The {$slug} page is too thin to satisfy a payment gateway.",
            );
        }
    }

    public function test_what_the_shop_writes_wins_over_the_standard_wording(): void
    {
        Setting::put('returns', 'We take nothing back, ever.', 'text', 'policy');

        $this->get('/page/returns')
            ->assertOk()
            ->assertSee('We take nothing back, ever.')
            ->assertDontSee('What we can take back');
    }

    public function test_policy_text_cannot_put_markup_on_the_page(): void
    {
        Setting::put('terms', 'Hello <script>alert(1)</script> there', 'text', 'policy');

        $this->get('/page/terms')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('alert(1)');
    }
}
