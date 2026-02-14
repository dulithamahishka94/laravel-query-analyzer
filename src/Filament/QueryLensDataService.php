<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament;

use Carbon\Carbon;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Services\AggregationService;

/**
 * Centralizes data retrieval for all Filament pages and widgets.
 * Keeps Filament components thin by extracting query/storage logic here.
 */
class QueryLensDataService
{
    protected QueryStorage $storage;
    protected AggregationService $aggregationService;

    public function __construct(QueryStorage $storage, AggregationService $aggregationService)
    {
        $this->storage = $storage;
        $this->aggregationService = $aggregationService;
    }

    public function getStatsForWidget(): array
    {
        $now = Carbon::now();
        $dayAgo = $now->copy()->subDay();
        $twoDaysAgo = $now->copy()->subDays(2);

        $current = $this->storage->getStats($dayAgo, $now);
        $previous = $this->storage->getStats($twoDaysAgo, $dayAgo);

        $currentAvg = $current['avg_time'] ?? 0;
        $previousAvg = $previous['avg_time'] ?? 0;

        return [
            'total_queries' => $current['total_queries'] ?? 0,
            'slow_queries' => $current['slow_queries'] ?? 0,
            'avg_time' => round(($current['avg_time'] ?? 0) * 1000, 2),
            'p95_time' => round(($current['p95_time'] ?? $current['max_time'] ?? 0) * 1000, 2),
            'query_change' => $this->calculateChange(
                (float) ($current['total_queries'] ?? 0),
                (float) ($previous['total_queries'] ?? 0)
            ),
            'slow_change' => $this->calculateChange(
                (float) ($current['slow_queries'] ?? 0),
                (float) ($previous['slow_queries'] ?? 0)
            ),
            'avg_time_change' => $this->calculateChange($currentAvg, $previousAvg),
        ];
    }

    /**
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function getRecentQueries(array $filters = []): array
    {
        return $this->storage->search($filters);
    }

    public function getTrendsData(string $granularity = 'hour', ?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = $start ?? now()->subDay();
        $end = $end ?? now();

        $this->aggregationService->setStorage($this->storage);

        return $this->aggregationService->getPerformanceTrends($granularity, $start, $end);
    }

    public function getTopQueries(string $type = 'slowest', string $period = 'day', int $limit = 10): array
    {
        return $this->storage->getTopQueries($type, $period, $limit);
    }

    protected function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return ['value' => 0, 'direction' => 'neutral'];
        }

        $change = (($current - $previous) / $previous) * 100;

        return [
            'value' => round(abs($change), 1),
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
        ];
    }
}
