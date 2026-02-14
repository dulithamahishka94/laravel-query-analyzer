<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Carbon\Carbon;
use GladeHQ\QueryLens\Contracts\QueryStorage;

class RegressionDetector
{
    protected QueryStorage $storage;

    public function __construct(QueryStorage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Compare current period metrics against the previous period.
     *
     * @return array{regressions: array, summary: array}
     */
    public function detect(string $period = 'daily', float $threshold = 0.2): array
    {
        $now = Carbon::now();
        [$currentStart, $currentEnd, $previousStart, $previousEnd] = $this->getPeriodBounds($period, $now);

        $currentStats = $this->computePeriodMetrics($currentStart, $currentEnd);
        $previousStats = $this->computePeriodMetrics($previousStart, $previousEnd);

        $regressions = $this->compareMetrics($currentStats, $previousStats, $threshold);

        $hasCritical = collect($regressions)->contains(fn($r) => $r['severity'] === 'critical');
        $hasWarning = collect($regressions)->contains(fn($r) => $r['severity'] === 'warning');

        return [
            'regressions' => $regressions,
            'summary' => [
                'period' => $period,
                'threshold' => $threshold,
                'current_period' => [
                    'start' => $currentStart->toIso8601String(),
                    'end' => $currentEnd->toIso8601String(),
                ],
                'previous_period' => [
                    'start' => $previousStart->toIso8601String(),
                    'end' => $previousEnd->toIso8601String(),
                ],
                'current_stats' => $currentStats,
                'previous_stats' => $previousStats,
                'has_critical' => $hasCritical,
                'has_warning' => $hasWarning,
                'regression_count' => count($regressions),
            ],
        ];
    }

    /**
     * Get period boundaries for current and previous comparison windows.
     *
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    protected function getPeriodBounds(string $period, Carbon $now): array
    {
        return match ($period) {
            'hourly' => [
                $now->copy()->subHour(),
                $now->copy(),
                $now->copy()->subHours(2),
                $now->copy()->subHour(),
            ],
            default => [ // daily
                $now->copy()->subDay(),
                $now->copy(),
                $now->copy()->subDays(2),
                $now->copy()->subDay(),
            ],
        };
    }

    /**
     * Compute percentile and aggregate metrics for a time period.
     */
    protected function computePeriodMetrics(Carbon $start, Carbon $end): array
    {
        $stats = $this->storage->getStats($start, $end);

        $totalQueries = $stats['total_queries'] ?? 0;
        $slowQueries = $stats['slow_queries'] ?? 0;

        // Get time-series data for percentile computation
        $aggregates = $this->storage->getAggregates('hour', $start, $end);

        $allTimes = [];
        foreach ($aggregates as $agg) {
            if (isset($agg['p50_time'])) {
                $allTimes[] = $agg['p50_time'];
            }
        }

        $p50 = $this->computePercentileFromAggregates($aggregates, 'p50_time');
        $p95 = $this->computePercentileFromAggregates($aggregates, 'p95_time');
        $p99 = $this->computePercentileFromAggregates($aggregates, 'p99_time');

        $slowRatio = $totalQueries > 0 ? $slowQueries / $totalQueries : 0;

        return [
            'total_queries' => $totalQueries,
            'slow_queries' => $slowQueries,
            'slow_ratio' => round($slowRatio, 4),
            'avg_time' => $stats['avg_time'] ?? 0,
            'max_time' => $stats['max_time'] ?? 0,
            'p50' => $p50,
            'p95' => $p95,
            'p99' => $p99,
        ];
    }

    protected function computePercentileFromAggregates(array $aggregates, string $field): float
    {
        $values = array_filter(array_column($aggregates, $field), fn($v) => $v > 0);

        if (empty($values)) {
            return 0;
        }

        sort($values);

        // Use the max of the per-bucket values as the period-level estimate
        return (float) end($values);
    }

    /**
     * Compare two periods and identify regressions.
     */
    protected function compareMetrics(array $current, array $previous, float $threshold): array
    {
        $regressions = [];

        $metricsToCompare = [
            'p50' => 'P50 Latency',
            'p95' => 'P95 Latency',
            'p99' => 'P99 Latency',
            'avg_time' => 'Average Query Time',
            'slow_ratio' => 'Slow Query Ratio',
            'total_queries' => 'Query Count',
        ];

        foreach ($metricsToCompare as $key => $label) {
            $currentValue = (float) ($current[$key] ?? 0);
            $previousValue = (float) ($previous[$key] ?? 0);

            if ($previousValue == 0) {
                continue;
            }

            $changePct = ($currentValue - $previousValue) / $previousValue;

            // Only flag increases as regressions (higher latency/ratio is bad)
            if ($changePct > $threshold) {
                $regressions[] = [
                    'metric' => $key,
                    'label' => $label,
                    'previous' => round($previousValue, 6),
                    'current' => round($currentValue, 6),
                    'change_pct' => round($changePct, 4),
                    'severity' => $this->classifySeverity($changePct),
                ];
            }
        }

        return $regressions;
    }

    protected function classifySeverity(float $changePct): string
    {
        if ($changePct > 0.5) {
            return 'critical';
        }

        if ($changePct > 0.2) {
            return 'warning';
        }

        return 'info';
    }
}
