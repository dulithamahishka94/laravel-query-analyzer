<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Models\QueryAggregate;
use GladeHQ\QueryLens\Models\TopQuery;

class AggregationService
{
    protected ?QueryStorage $storage = null;

    public function setStorage(QueryStorage $storage): self
    {
        $this->storage = $storage;
        return $this;
    }
    public function aggregateHourly(?Carbon $hour = null): void
    {
        $periodStart = ($hour ?? now())->startOfHour();
        $periodEnd = $periodStart->copy()->endOfHour();

        $this->computeAggregate('hour', $periodStart, $periodEnd);
        $this->computeTopQueries('hour', $periodStart, $periodEnd);
    }

    public function aggregateDaily(?Carbon $day = null): void
    {
        $periodStart = ($day ?? now())->startOfDay();
        $periodEnd = $periodStart->copy()->endOfDay();

        $this->computeAggregate('day', $periodStart, $periodEnd);
        $this->computeTopQueries('day', $periodStart, $periodEnd);
    }

    public function aggregateWeekly(?Carbon $week = null): void
    {
        $periodStart = ($week ?? now())->startOfWeek();
        $periodEnd = $periodStart->copy()->endOfWeek();

        $this->computeTopQueries('week', $periodStart, $periodEnd);
    }

    protected function computeAggregate(string $periodType, Carbon $start, Carbon $end): void
    {
        $queries = AnalyzedQuery::inPeriod($start, $end);
        $count = $queries->count();

        if ($count === 0) {
            return;
        }

        $times = (clone $queries)->pluck('time')->sort()->values();
        $slowCount = (clone $queries)->slow()->count();
        $nPlusOneCount = (clone $queries)->nPlusOne()->count();
        $issueCount = (clone $queries)->withIssues()->count();

        // Compute percentiles
        $p50 = $this->percentile($times, 50);
        $p95 = $this->percentile($times, 95);
        $p99 = $this->percentile($times, 99);

        // Type breakdown
        $typeBreakdown = (clone $queries)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Performance breakdown
        $performanceBreakdown = (clone $queries)
            ->selectRaw('performance_rating, COUNT(*) as count')
            ->groupBy('performance_rating')
            ->pluck('count', 'performance_rating')
            ->toArray();

        QueryAggregate::updateOrCreate(
            [
                'period_type' => $periodType,
                'period_start' => $start,
            ],
            [
                'total_queries' => $count,
                'slow_queries' => $slowCount,
                'avg_time' => $times->avg() ?? 0,
                'p50_time' => $p50,
                'p95_time' => $p95,
                'p99_time' => $p99,
                'max_time' => $times->max() ?? 0,
                'min_time' => $times->min() ?? 0,
                'total_time' => $times->sum(),
                'issue_count' => $issueCount,
                'n_plus_one_count' => $nPlusOneCount,
                'type_breakdown' => $typeBreakdown,
                'performance_breakdown' => $performanceBreakdown,
            ]
        );

        Log::debug("Query Analyzer: Aggregated {$periodType} stats for {$start->toDateTimeString()}", [
            'total' => $count,
            'slow' => $slowCount,
            'p95' => $p95,
        ]);
    }

    protected function computeTopQueries(string $period, Carbon $start, Carbon $end): void
    {
        $rankingTypes = ['slowest', 'most_frequent', 'most_issues'];

        foreach ($rankingTypes as $type) {
            $this->computeTopQueriesForType($type, $period, $start, $end);
        }
    }

    protected function computeTopQueriesForType(string $type, string $period, Carbon $start, Carbon $end): void
    {
        // Delete existing rankings for this period
        TopQuery::where('ranking_type', $type)
            ->where('period', $period)
            ->where('period_start', $start)
            ->delete();

        $orderBy = match ($type) {
            'slowest' => 'avg_time DESC',
            'most_frequent' => 'count DESC',
            'most_issues' => 'issue_count DESC',
            default => 'total_time DESC',
        };

        $connection = config('query-lens.storage.connection');
        $prefix = config('query-lens.storage.table_prefix', 'query_lens_');
        $table = $prefix . 'queries';

        // Build query based on database driver
        $driver = DB::connection($connection)->getDriverName();

        if ($driver === 'mysql') {
            $issueCountExpr = "SUM(CASE WHEN JSON_LENGTH(analysis, '$.issues') > 0 THEN 1 ELSE 0 END)";
        } elseif ($driver === 'pgsql') {
            $issueCountExpr = "SUM(CASE WHEN jsonb_array_length(analysis->'issues') > 0 THEN 1 ELSE 0 END)";
        } else {
            // SQLite fallback
            $issueCountExpr = "SUM(CASE WHEN analysis LIKE '%\"issues\":[%' AND analysis NOT LIKE '%\"issues\":[]%' THEN 1 ELSE 0 END)";
        }

        $results = DB::connection($connection)
            ->table($table)
            ->selectRaw("sql_hash, MIN(sql) as sql_sample, COUNT(*) as count,
                         AVG(time) as avg_time, MAX(time) as max_time, SUM(time) as total_time,
                         {$issueCountExpr} as issue_count")
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('sql_hash')
            ->orderByRaw($orderBy)
            ->limit(20)
            ->get();

        $rank = 0;
        foreach ($results as $row) {
            $rank++;
            TopQuery::create([
                'ranking_type' => $type,
                'period' => $period,
                'period_start' => $start,
                'sql_hash' => $row->sql_hash,
                'sql_sample' => $row->sql_sample,
                'count' => $row->count,
                'avg_time' => $row->avg_time,
                'max_time' => $row->max_time,
                'total_time' => $row->total_time,
                'issue_count' => $row->issue_count ?? 0,
                'rank' => $rank,
            ]);
        }
    }

    public function getPerformanceTrends(string $granularity, Carbon $start, Carbon $end): array
    {
        // Try to get from pre-computed aggregates first (database storage)
        // We only possess pre-computed data for 'hour' and 'day'
        if (config('query-lens.storage.driver') === 'database' && in_array($granularity, ['hour', 'day'])) {
            $periodType = $granularity === 'hour' ? 'hour' : 'day';

            $aggregates = QueryAggregate::where('period_type', $periodType)
                ->inRange($start, $end)
                ->orderBy('period_start')
                ->get();

            if ($aggregates->isNotEmpty()) {
                return $this->formatAggregatesForTrends($aggregates, $granularity);
            }
        }

        // Fallback: compute trends on-the-fly from raw query data
        return $this->computeTrendsOnTheFly($granularity, $start, $end);
    }

    protected function formatAggregatesForTrends($aggregates, string $granularity): array
    {
        $labels = [];
        $latency = [];
        $throughput = [];
        $p50 = [];
        $p95 = [];
        $p99 = [];

        foreach ($aggregates as $agg) {
            $labels[] = $agg->period_start->format(
                match ($granularity) {
                    'minute' => 'H:i',
                    'hour' => 'H:00',
                    default => 'M j'
                }
            );
            $latency[] = round($agg->avg_time * 1000, 2);
            $throughput[] = $agg->total_queries;
            $p50[] = round($agg->p50_time * 1000, 2);
            $p95[] = round($agg->p95_time * 1000, 2);
            $p99[] = round($agg->p99_time * 1000, 2);
        }

        return compact('labels', 'latency', 'throughput', 'p50', 'p95', 'p99');
    }

    protected function computeTrendsOnTheFly(string $granularity, Carbon $start, Carbon $end): array
    {
        // Get storage instance
        $storage = $this->storage ?? app(QueryStorage::class);

        // Get recent queries (limited for performance)
        $allQueries = $storage->get(500);

        if (empty($allQueries)) {
            return $this->emptyTrendsResponse();
        }

        // Group queries by time bucket efficiently
        $buckets = [];
        $bucketFormat = match ($granularity) {
            'minute' => 'Y-m-d H:i',
            'hour' => 'Y-m-d H',
            default => 'Y-m-d'
        };

        $labelFormat = match ($granularity) {
            'minute' => 'H:i',
            'hour' => 'H:00',
            default => 'M j'
        };
        $startTs = $start->timestamp;
        $endTs = $end->timestamp;

        foreach ($allQueries as $query) {
            $ts = $query['timestamp'] ?? 0;

            // Skip if outside date range
            if ($ts < $startTs || $ts > $endTs) {
                continue;
            }

            $bucketKey = date($bucketFormat, (int) $ts);

            if (!isset($buckets[$bucketKey])) {
                $buckets[$bucketKey] = [
                    'label' => date($labelFormat, (int) $ts),
                    'times' => [],
                ];
            }
            $buckets[$bucketKey]['times'][] = ($query['time'] ?? 0) * 1000;
        }

        if (empty($buckets)) {
            return $this->emptyTrendsResponse();
        }

        // Sort buckets chronologically
        ksort($buckets);

        // Compute stats for each bucket
        $labels = [];
        $latency = [];
        $throughput = [];
        $p50 = [];
        $p95 = [];
        $p99 = [];

        foreach ($buckets as $bucket) {
            $times = $bucket['times'];
            sort($times);
            $count = count($times);

            $labels[] = $bucket['label'];
            $throughput[] = $count;
            $latency[] = round(array_sum($times) / $count, 2);
            $p50[] = round($this->percentileArray($times, 50), 2);
            $p95[] = round($this->percentileArray($times, 95), 2);
            $p99[] = round($this->percentileArray($times, 99), 2);
        }

        return compact('labels', 'latency', 'throughput', 'p50', 'p95', 'p99');
    }

    protected function emptyTrendsResponse(): array
    {
        return [
            'labels' => [],
            'latency' => [],
            'throughput' => [],
            'p50' => [],
            'p95' => [],
            'p99' => [],
        ];
    }

    protected function percentileArray(array $sortedValues, int $percentile): float
    {
        $count = count($sortedValues);
        if ($count === 0) {
            return 0;
        }

        $index = (int) ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return (float) $sortedValues[$index];
    }

    public function getOverviewStats(string $period = '24h'): array
    {
        $now = now();
        $targetStart = $this->getPeriodStart($period);
        $previousStart = $targetStart->copy()->subSeconds($now->diffInSeconds($targetStart));

        // Current Period
        $today = $this->getPeriodStats($targetStart, $now);
        
        // Previous Period (Comparison)
        $yesterday = $this->getPeriodStats($previousStart, $targetStart);
        
        // Week (Legacy - kept for structure compatibility if needed, else we can optimize)
        $week = $this->getPeriodStats($now->copy()->subWeek(), $now);

        // Calculate comparisons
        $comparison = [
            'queries' => $this->calculateChange($today['total_queries'], $yesterday['total_queries']),
            'avg_time' => $this->calculateChange($today['avg_time'], $yesterday['avg_time']),
            'slow' => $this->calculateChange($today['slow_queries'], $yesterday['slow_queries']),
            'p95' => $this->calculateChange($today['p95_time'], $yesterday['p95_time']),
        ];

        return [
            'today' => $today,       // "Today" here represents the "Current Period" stats
            'yesterday' => $yesterday, // "Yesterday" represents "Previous Period" stats
            'week' => $week,
            'comparison' => $comparison,
        ];
    }

    protected function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            '1h' => now()->subHour(),
            '6h' => now()->subHours(6),
            '12h' => now()->subHours(12),
            '24h', 'day' => now()->subDay(),
            '7d', 'week' => now()->subWeek(),
            '30d', 'month' => now()->subDays(30),
            default => now()->subDay(),
        };
    }

    protected function getPeriodStats(Carbon $start, Carbon $end): array
    {
        $queries = AnalyzedQuery::inPeriod($start, $end);
        $count = $queries->count();

        if ($count === 0) {
            return [
                'total_queries' => 0,
                'slow_queries' => 0,
                'avg_time' => 0,
                'p95_time' => 0,
            ];
        }

        $times = (clone $queries)->pluck('time')->sort()->values();

        return [
            'total_queries' => $count,
            'slow_queries' => (clone $queries)->slow()->count(),
            'avg_time' => $times->avg() ?? 0,
            'p95_time' => $this->percentile($times, 95),
        ];
    }

    protected function percentile($values, int $percentile): float
    {
        if ($values->isEmpty()) {
            return 0;
        }

        $count = $values->count();
        $index = (int) ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return (float) $values->get($index, 0);
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
