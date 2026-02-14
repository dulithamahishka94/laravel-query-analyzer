<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Widgets;

use GladeHQ\QueryLens\Filament\Concerns\BaseChartWidgetResolver;
use GladeHQ\QueryLens\Filament\QueryLensDataService;

/**
 * Line chart widget showing query performance trends over time.
 *
 * Displays three datasets: average latency, P95, and P99.
 * When Filament is installed, extends ChartWidget for native Chart.js rendering
 * with automatic polling. When absent, provides the same data via getChartData().
 */
class QueryPerformanceChart extends BaseChartWidgetResolver
{
    protected static ?string $heading = 'Query Performance';
    protected static int $sort = 20;
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
                    'label' => 'Avg Latency (ms)',
                    'data' => array_map(fn ($v) => round($v * 1000, 2), $trends['latency'] ?? []),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
                [
                    'label' => 'P95 (ms)',
                    'data' => array_map(fn ($v) => round($v * 1000, 2), $trends['p95'] ?? []),
                    'borderColor' => '#F59E0B',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [5, 5],
                ],
                [
                    'label' => 'P99 (ms)',
                    'data' => array_map(fn ($v) => round($v * 1000, 2), $trends['p99'] ?? []),
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'transparent',
                    'borderDash' => [2, 2],
                ],
            ],
            'labels' => $trends['labels'] ?? [],
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
