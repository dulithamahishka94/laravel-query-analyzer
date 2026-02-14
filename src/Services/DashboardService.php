<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Apply query filters from the request to a collection of queries.
     */
    public function applyFilters(Collection $queries, Request $request): Collection
    {
        if ($type = $request->query('type')) {
            $queries = $queries->filter(fn($q) => strtolower($q['analysis']['type']) === $type)->values();
        }

        if ($rating = $request->query('rating')) {
            $queries = $queries->filter(fn($q) => ($q['analysis']['performance']['rating'] ?? 'unknown') === $rating)->values();
        }

        if ($issueType = $request->query('issue_type')) {
            $queries = $queries->filter(function ($q) use ($issueType) {
                $issues = $q['analysis']['issues'] ?? [];
                if (empty($issues)) {
                    return false;
                }

                foreach ($issues as $issue) {
                    if (strtolower($issue['type']) === strtolower($issueType)) {
                        return true;
                    }
                }

                return false;
            })->values();
        }

        if ($request->has('slow_only') && $request->boolean('slow_only')) {
            $slowThreshold = config('query-lens.performance_thresholds.slow', 1.0);
            $queries = $queries->where('time', '>', $slowThreshold);
        }

        return $queries;
    }

    /**
     * Build a waterfall timeline from an array of queries.
     *
     * @return array{timeline_data: array, total_queries: int, total_time: float}
     */
    public function buildWaterfallTimeline(array $queries): array
    {
        $minTimestamp = min(array_column($queries, 'timestamp'));
        $timelineData = [];

        foreach ($queries as $index => $query) {
            $startMs = (($query['timestamp'] ?? 0) - ($query['time'] ?? 0) - $minTimestamp) * 1000;
            $endMs = (($query['timestamp'] ?? 0) - $minTimestamp) * 1000;

            $timelineData[] = [
                'index' => $index + 1,
                'type' => $query['analysis']['type'] ?? 'OTHER',
                'start_ms' => max(0, $startMs),
                'end_ms' => $endMs,
                'duration_ms' => ($query['time'] ?? 0) * 1000,
                'sql_preview' => substr($query['sql'] ?? '', 0, 100),
                'is_slow' => $query['analysis']['performance']['is_slow'] ?? false,
            ];
        }

        return [
            'timeline_data' => $timelineData,
            'total_queries' => count($queries),
            'total_time' => array_sum(array_column($queries, 'time')),
        ];
    }
}
