<?php

namespace App\Services\Google;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * What Google knows about the shop: visitors, and how it is found.
 *
 * Two quite separate things, which people run together. Analytics counts who
 * arrived and what they did once they were here. Search Console says what was
 * typed into Google, how often the shop was shown for it, and how often that
 * was clicked — which is the only place the shop can learn that it comes up
 * for "banarasi saree hyderabad" and nobody clicks.
 *
 * Both fail quietly. A dashboard that throws because Google was slow is worse
 * than one that says it could not ask.
 */
class GoogleStats
{
    /** Asked at most twice an hour: these numbers move slowly and quotas do not. */
    private const KEEP = 30;

    public function __construct(private ?ServiceAccount $account = null)
    {
        $this->account ??= ServiceAccount::fromSettings();
    }

    public function configured(): bool
    {
        return $this->account !== null && ($this->propertyId() !== '' || $this->site() !== '');
    }

    public function propertyId(): string
    {
        // A shop that pastes "properties/123456" has done nothing wrong.
        return trim(str_replace('properties/', '', (string) Setting::get('google_ga4_property')));
    }

    public function site(): string
    {
        return trim((string) Setting::get('google_search_console_site'));
    }

    /**
     * Visitors, from the Analytics Data API.
     *
     * @return array{people: int, visits: int, pages: int, from: array<int, array{name: string, visits: int}>}|null
     */
    public function visitors(int $days): ?array
    {
        if ($this->account === null || $this->propertyId() === '') {
            return null;
        }

        return Cache::remember("google:ga4:{$this->propertyId()}:{$days}", now()->addMinutes(self::KEEP), function () use ($days) {
            $token = $this->account->token(ServiceAccount::ANALYTICS);

            if ($token === null) {
                return null;
            }

            $response = Http::withToken($token)
                ->timeout(20)
                ->post("https://analyticsdata.googleapis.com/v1beta/properties/{$this->propertyId()}:runReport", [
                    'dateRanges' => [['startDate' => "{$days}daysAgo", 'endDate' => 'today']],
                    'metrics'    => [
                        ['name' => 'totalUsers'],
                        ['name' => 'sessions'],
                        ['name' => 'screenPageViews'],
                    ],
                    'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
                    'limit'      => 10,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $rows = $response->json('rows') ?? [];

            $from = [];
            $people = $visits = $pages = 0;

            foreach ($rows as $row) {
                $name = $row['dimensionValues'][0]['value'] ?? 'Unknown';
                $these = (int) ($row['metricValues'][0]['value'] ?? 0);
                $sessions = (int) ($row['metricValues'][1]['value'] ?? 0);

                $people += $these;
                $visits += $sessions;
                $pages += (int) ($row['metricValues'][2]['value'] ?? 0);

                $from[] = ['name' => $name, 'visits' => $sessions];
            }

            usort($from, fn ($a, $b) => $b['visits'] <=> $a['visits']);

            return ['people' => $people, 'visits' => $visits, 'pages' => $pages, 'from' => $from];
        });
    }

    /**
     * How the shop is found, from Search Console.
     *
     * @return array{clicks: int, impressions: int, ctr: float, position: float, queries: array<int, array<string, mixed>>}|null
     */
    public function search(int $days): ?array
    {
        if ($this->account === null || $this->site() === '') {
            return null;
        }

        return Cache::remember("google:gsc:{$this->site()}:{$days}", now()->addMinutes(self::KEEP), function () use ($days) {
            $token = $this->account->token(ServiceAccount::SEARCH_CONSOLE);

            if ($token === null) {
                return null;
            }

            /*
             * Google's own figures run three days behind, so asking up to
             * today returns a tail of zeroes that makes the average position
             * look better than it is.
             */
            $to = now()->subDays(3);
            $from = $to->copy()->subDays($days);

            $url = 'https://www.googleapis.com/webmasters/v3/sites/'
                .rawurlencode($this->site()).'/searchAnalytics/query';

            $totals = Http::withToken($token)->timeout(20)->post($url, [
                'startDate' => $from->toDateString(),
                'endDate'   => $to->toDateString(),
            ]);

            if (! $totals->successful()) {
                return null;
            }

            $row = ($totals->json('rows') ?? [[]])[0] ?? [];

            $queries = Http::withToken($token)->timeout(20)->post($url, [
                'startDate'  => $from->toDateString(),
                'endDate'    => $to->toDateString(),
                'dimensions' => ['query'],
                'rowLimit'   => 12,
            ]);

            return [
                'clicks'      => (int) ($row['clicks'] ?? 0),
                'impressions' => (int) ($row['impressions'] ?? 0),
                'ctr'         => (float) ($row['ctr'] ?? 0) * 100,
                'position'    => (float) ($row['position'] ?? 0),
                'queries'     => collect($queries->successful() ? ($queries->json('rows') ?? []) : [])
                    ->map(fn (array $r) => [
                        'words'       => $r['keys'][0] ?? '',
                        'clicks'      => (int) ($r['clicks'] ?? 0),
                        'impressions' => (int) ($r['impressions'] ?? 0),
                        'position'    => round((float) ($r['position'] ?? 0), 1),
                    ])
                    ->all(),
            ];
        });
    }

    /** So a shop that has just pasted its key does not wait half an hour to see. */
    public function forget(): void
    {
        foreach ([7, 30, 90, 365] as $days) {
            Cache::forget("google:ga4:{$this->propertyId()}:{$days}");
            Cache::forget("google:gsc:{$this->site()}:{$days}");
        }
    }
}
