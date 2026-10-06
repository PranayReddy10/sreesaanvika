<?php

namespace App\Services\Meta;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * What the shop is spending on Instagram and Facebook, and what came back.
 *
 * Most of a saree shop's advertising is a reel on Instagram, and the figure
 * that matters is not how many people saw it — it is what was spent against
 * what was bought. That one number lives in Meta's own dashboard, behind a
 * login nobody opens daily, which is why shops run advertising for months
 * without knowing whether it pays.
 *
 * A long-lived token pasted into Settings rather than a sign-in flow: this is
 * read by a cron and by whoever opens the admin, with nobody there to click
 * through a consent screen.
 */
class MetaAds
{
    /** Pinned, because a Graph version that drops out of support changes shape. */
    private const VERSION = 'v21.0';

    private const KEEP = 30;

    public function account(): string
    {
        $account = trim((string) Setting::get('meta_ad_account'));

        // act_123456 and 123456 are the same thing and a shop should not have
        // to know which one this wanted.
        return $account === '' ? '' : 'act_'.ltrim($account, 'act_');
    }

    public function token(): string
    {
        return trim((string) Setting::get('meta_access_token'));
    }

    public function configured(): bool
    {
        return $this->account() !== '' && $this->token() !== '';
    }

    /**
     * Spend and what it brought in, over the last so many days.
     *
     * @return array{spend: float, impressions: int, clicks: int, purchases: int, value: float, roas: ?float}|null
     */
    public function results(int $days): ?array
    {
        if (! $this->configured()) {
            return null;
        }

        return Cache::remember("meta:ads:{$this->account()}:{$days}", now()->addMinutes(self::KEEP), function () use ($days) {
            $response = Http::timeout(20)->get(
                'https://graph.facebook.com/'.self::VERSION.'/'.$this->account().'/insights',
                [
                    'fields'       => 'spend,impressions,clicks,actions,action_values',
                    'time_range'   => json_encode([
                        'since' => now()->subDays($days)->toDateString(),
                        'until' => now()->toDateString(),
                    ]),
                    'access_token' => $this->token(),
                ],
            );

            if (! $response->successful()) {
                return null;
            }

            $row = ($response->json('data') ?? [])[0] ?? null;

            if ($row === null) {
                // A real answer meaning nothing ran in this stretch, which is
                // not the same as not being able to ask.
                return ['spend' => 0.0, 'impressions' => 0, 'clicks' => 0, 'purchases' => 0, 'value' => 0.0, 'roas' => null];
            }

            $spend = (float) ($row['spend'] ?? 0);
            $value = $this->sum($row['action_values'] ?? []);

            return [
                'spend'       => $spend,
                'impressions' => (int) ($row['impressions'] ?? 0),
                'clicks'      => (int) ($row['clicks'] ?? 0),
                'purchases'   => (int) $this->sum($row['actions'] ?? []),
                'value'       => $value,
                'roas'        => $spend > 0 ? $value / $spend : null,
            ];
        });
    }

    /**
     * Purchases out of Meta's list of every kind of action.
     *
     * Both spellings are counted, and only one of them: "purchase" is what the
     * pixel on this shop sends, "omni_purchase" is Meta's own roll-up of the
     * same thing across the pixel and the shop on Facebook. A shop with only
     * the pixel has the first; adding them both would double every sale.
     */
    private function sum(array $actions): float
    {
        foreach (['purchase', 'omni_purchase'] as $wanted) {
            foreach ($actions as $action) {
                if (($action['action_type'] ?? '') === $wanted) {
                    return (float) ($action['value'] ?? 0);
                }
            }
        }

        return 0.0;
    }

    public function forget(): void
    {
        foreach ([7, 30, 90, 365] as $days) {
            Cache::forget("meta:ads:{$this->account()}:{$days}");
        }
    }
}
