<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Widgets;

use GladeHQ\QueryLens\Filament\Concerns\BaseChartWidgetResolver;
use GladeHQ\QueryLens\Filament\QueryLensDataService;

/**
 * Bar chart widget showing query volume over time.
 *
 * Displays total query count per time bucket.
 * When Filament is installed, extends ChartWidget for native rendering.
 */
class QueryVolumeChart extends BaseChartWidgetResolver
{
    protected static ?string $heading = 'Query Volume';
    protected static int $sort = 21;
    protected static string $pollingInterval = '30s';
    protected string $period = '24h';

    protected function getData(): array
    {
        $dataService = $this->resolveDataService();
        $granularity = $this->resolveGranularity();
        $range = $this->resolveTimeRange();

        $trends = $dataService->getTrendsData($granularity, $range['start'], $range['end']);

        return [
            'datasets' => [
                [
                    'label' => 'Query Count',
                    'data' => $trends['throughput'] ?? [],
                    'backgroundColor' => '#6366F1',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $trends['labels'] ?? [],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function setPeriod(string $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getPeriod(): string
    {
        return $this->period;
    }

    protected function resolveGranularity(): string
    {
        return match ($this->period) {
            '7d', '30d' => 'day',
            default => 'hour',
        };
    }

    protected function resolveTimeRange(): array
    {
        $end = now();
        $start = match ($this->period) {
            '7d' => $end->copy()->subDays(7),
            '30d' => $end->copy()->subDays(30),
            default => $end->copy()->subDay(),
        };

        return ['start' => $start, 'end' => $end];
    }

    protected function resolveDataService(): QueryLensDataService
    {
        return app(QueryLensDataService::class);
    }
}
