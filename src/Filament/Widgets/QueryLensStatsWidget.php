<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Widgets;

use GladeHQ\QueryLens\Filament\Concerns\BaseStatsWidgetResolver;
use GladeHQ\QueryLens\Filament\QueryLensDataService;

/**
 * Stats overview widget for the Filament dashboard.
 *
 * When Filament is installed, this extends StatsOverviewWidget and
 * returns Filament Stat objects via getStats().
 * When Filament is absent, it provides structured stat data arrays.
 */
class QueryLensStatsWidget extends BaseStatsWidgetResolver
{
    protected static int $sort = 10;
    protected static string $pollingInterval = '30s';

    /**
     * Get stats for the widget.
     *
     * When Filament is installed, this returns Filament Stat objects.
     * Without Filament, it returns structured arrays.
     */
    public function getStats(): array
    {
        $dataService = $this->resolveDataService();
        $stats = $dataService->getStatsForWidget();

        if (class_exists(\Filament\Widgets\StatsOverviewWidget\Stat::class)) {
            return $this->buildFilamentStats($stats);
        }

        return $this->buildArrayStats($stats);
    }

    /**
     * Build Filament Stat objects when Filament is available.
     */
    protected function buildFilamentStats(array $stats): array
    {
        $statClass = \Filament\Widgets\StatsOverviewWidget\Stat::class;

        return [
            $statClass::make('Total Queries (24h)', number_format($stats['total_queries']))
                ->description($this->formatChange($stats['query_change']))
                ->descriptionIcon($this->getDirectionIcon($stats['query_change']))
                ->color($this->getChangeColor($stats['query_change'])),

            $statClass::make('Slow Queries', number_format($stats['slow_queries']))
                ->description($this->formatChange($stats['slow_change']))
                ->descriptionIcon($this->getDirectionIcon($stats['slow_change']))
                ->color($this->getSlowColor($stats['slow_change'])),

            $statClass::make('Avg Response Time', $stats['avg_time'] . 'ms')
                ->description($this->formatChange($stats['avg_time_change']))
                ->descriptionIcon($this->getDirectionIcon($stats['avg_time_change']))
                ->color($this->getTimeColor($stats['avg_time_change'])),

            $statClass::make('P95 Latency', $stats['p95_time'] . 'ms')
                ->description('95th percentile')
                ->color('gray'),
        ];
    }

    /**
     * Build structured array stats for non-Filament usage.
     */
    public function buildArrayStats(array $stats): array
    {
        return [
            [
                'label' => 'Total Queries (24h)',
                'value' => number_format($stats['total_queries']),
                'change' => $this->formatChange($stats['query_change']),
                'change_direction' => $stats['query_change']['direction'] ?? 'neutral',
                'color' => $this->getChangeColor($stats['query_change']),
            ],
            [
                'label' => 'Slow Queries',
                'value' => number_format($stats['slow_queries']),
                'change' => $this->formatChange($stats['slow_change']),
                'change_direction' => $stats['slow_change']['direction'] ?? 'neutral',
                'color' => $this->getSlowColor($stats['slow_change']),
            ],
            [
                'label' => 'Avg Response Time',
                'value' => $stats['avg_time'] . 'ms',
                'change' => $this->formatChange($stats['avg_time_change']),
                'change_direction' => $stats['avg_time_change']['direction'] ?? 'neutral',
                'color' => $this->getTimeColor($stats['avg_time_change']),
            ],
            [
                'label' => 'P95 Latency',
                'value' => $stats['p95_time'] . 'ms',
                'change' => '95th percentile',
                'change_direction' => 'neutral',
                'color' => 'gray',
            ],
        ];
    }

    public function formatChange(array $change): string
    {
        $value = $change['value'] ?? 0;
        $direction = $change['direction'] ?? 'neutral';

        if ($direction === 'neutral' || $value == 0) {
            return 'No change';
        }

        $arrow = $direction === 'up' ? '+' : '-';

        return "{$arrow}{$value}% vs previous period";
    }

    public function getChangeColor(array $change): string
    {
        return match ($change['direction'] ?? 'neutral') {
            'up' => 'success',
            'down' => 'danger',
            default => 'gray',
        };
    }

    public function getSlowColor(array $change): string
    {
        return match ($change['direction'] ?? 'neutral') {
            'up' => 'danger',
            'down' => 'success',
            default => 'gray',
        };
    }

    public function getTimeColor(array $change): string
    {
        return match ($change['direction'] ?? 'neutral') {
            'up' => 'danger',
            'down' => 'success',
            default => 'gray',
        };
    }

    protected function getDirectionIcon(array $change): string
    {
        return match ($change['direction'] ?? 'neutral') {
            'up' => 'heroicon-m-arrow-trending-up',
            'down' => 'heroicon-m-arrow-trending-down',
            default => 'heroicon-m-minus',
        };
    }

    protected function resolveDataService(): QueryLensDataService
    {
        return app(QueryLensDataService::class);
    }
}
