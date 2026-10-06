<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

/**
 * Takings by day, for the last stretch the shop chooses. Paid orders only —
 * see the note on ShopOverview.
 */
class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    /*
     * Filament polls a chart every 5 seconds unless told not to. On a shared
     * host that is 12 requests a minute, each one booting the whole framework,
     * for a number that changes when an order arrives — a few times a day. Left
     * on, an admin tab forgotten on the dashboard spends the account's request
     * allowance all by itself, and the host answers 429 to whoever asks next,
     * shoppers included. Reload the page to see today's takings.
     */
    protected ?string $pollingInterval = null;

    protected ?string $heading = 'Taken by day';

    protected int|string|array $columnSpan = 'full';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return [
            '7'  => 'Last 7 days',
            '30' => 'Last 30 days',
            '90' => 'Last 3 months',
        ];
    }

    protected function getData(): array
    {
        $days = (int) ($this->filter ?: 30);
        $since = now()->subDays($days - 1)->startOfDay();

        $rows = Order::query()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, SUM(grand_total) as total, COUNT(*) as orders')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $money = [];
        $counts = [];

        for ($day = $since->copy(); $day <= now(); $day->addDay()) {
            $key = $day->toDateString();
            // Every day appears, including the quiet ones — a chart that skips
            // the zeroes makes a bad week look like a good one.
            $labels[] = $days > 31 ? $day->format('j M') : $day->format('D j');
            $money[] = round((float) ($rows[$key]->total ?? 0));
            $counts[] = (int) ($rows[$key]->orders ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Taken (₹)',
                    'data' => $money,
                    'borderColor' => '#a8781f',
                    'backgroundColor' => 'rgba(168, 120, 31, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Orders',
                    'data' => $counts,
                    'borderColor' => '#6b7280',
                    'backgroundColor' => 'rgba(107, 114, 128, 0.08)',
                    'fill' => false,
                    'tension' => 0.3,
                    'yAxisID' => 'orders',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'orders' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => ['drawOnChartArea' => false],
                    'ticks' => ['precision' => 0],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
