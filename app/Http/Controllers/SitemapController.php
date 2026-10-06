<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\Seo;
use Illuminate\Http\Response;

/**
 * The map Google is given, and the rules it is given with it.
 *
 * Built on request rather than written to a file: the catalogue is small
 * enough that the query costs nothing, and a file would need a job to keep it
 * honest — one more thing to go quietly stale on a shared host.
 *
 * Only pages the shop would want somebody to arrive on are in here. A sitemap
 * listing a checkout is a sitemap that teaches Google the shop does not know
 * what its own pages are for.
 */
class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [];

        $urls[] = ['loc' => route('home'), 'priority' => '1.0', 'freq' => 'daily'];
        $urls[] = ['loc' => route('shop'), 'priority' => '0.9', 'freq' => 'daily'];

        $newest = Product::published()->max('updated_at');

        if ($newest) {
            $urls[0]['lastmod'] = $urls[1]['lastmod'] = $this->stamp($newest);
        }

        foreach (Product::published()->orderBy('id')->get(['slug', 'updated_at', 'is_featured']) as $product) {
            $urls[] = [
                'loc'      => route('product', $product->slug),
                'lastmod'  => $this->stamp($product->updated_at),
                'freq'     => 'weekly',
                // A piece the shop has put forward is worth more of Google's
                // attention than one it has not.
                'priority' => $product->is_featured ? '0.8' : '0.7',
            ];
        }

        foreach (['story', 'contact', 'shipping', 'returns', 'terms', 'privacy'] as $slug) {
            $urls[] = ['loc' => route('page', $slug), 'priority' => '0.3', 'freq' => 'monthly'];
        }

        return $this->xml(view('seo.sitemap', ['urls' => $urls])->render());
    }

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        if (Seo::hiddenFromSearch()) {
            // The shop has asked to be invisible. Say so plainly and say
            // nothing else — no sitemap, no map of what exists.
            $lines[] = 'Disallow: /';

            return $this->text(implode("\n", $lines) . "\n");
        }

        foreach ([
            '/bag', '/checkout', '/order/', '/account', '/sign-in', '/join',
            '/track', '/admin', '/webhooks/',
        ] as $path) {
            $lines[] = "Disallow: {$path}";
        }

        // The listing itself is wanted; the same listing under every
        // combination of filters is thousands of pages saying the same thing.
        $lines[] = 'Disallow: /sarees?*';
        $lines[] = 'Allow: /sarees$';

        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');

        return $this->text(implode("\n", $lines) . "\n");
    }

    /** max() hands back a string while a model hands back a date; take either. */
    private function stamp($date): string
    {
        if (! $date) {
            return now()->toAtomString();
        }

        return ($date instanceof \DateTimeInterface ? \Illuminate\Support\Carbon::instance($date) : \Illuminate\Support\Carbon::parse($date))
            ->toAtomString();
    }

    private function xml(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function text(string $body): Response
    {
        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
