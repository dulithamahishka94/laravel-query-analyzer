<?php

namespace GladeHQ\QueryLens\Tests\Fakes;

use Carbon\Carbon;
use GladeHQ\QueryLens\Contracts\QueryStorage;

class InMemoryQueryStorage implements QueryStorage
{
    protected array $queries = [];
    protected array $requests = [];

    public function store(array $query): void
    {
        array_unshift($this->queries, $query);
    }

    public function get(int $limit = 100): array
    {
        return array_slice($this->queries, 0, $limit);
    }

    public function clear(): void
    {
        $this->queries = [];
        $this->requests = [];
    }

    public function getByRequest(string $requestId): array
    {
        return array_filter($this->queries, fn($q) => ($q['request_id'] ?? '') === $requestId);
    }

    public function getStats(Carbon $start, Carbon $end): array
    {
        $filtered = array_filter($this->queries, function ($q) use ($start, $end) {
            $ts = $q['timestamp'] ?? 0;
            return $ts >= $start->timestamp && $ts <= $end->timestamp;
        });

        $times = array_column($filtered, 'time');
        $slowCount = count(array_filter($filtered, fn($q) => ($q['analysis']['performance']['is_slow'] ?? false)));

        return [
            'total_queries' => count($filtered),
            'slow_queries' => $slowCount,
            'avg_time' => count($times) > 0 ? array_sum($times) / count($times) : 0,
            'max_time' => count($times) > 0 ? max($times) : 0,
            'total_time' => array_sum($times),
        ];
    }

    public function getTopQueries(string $type, string $period, int $limit = 10): array
    {
        $cutoff = match ($period) {
            'hour' => time() - 3600,
            'day' => time() - 86400,
            'week' => time() - 604800,
            default => time() - 86400,
        };

        $filtered = array_filter($this->queries, fn($q) => ($q['timestamp'] ?? 0) >= $cutoff);

        // Group by SQL hash
        $grouped = [];
        foreach ($filtered as $q) {
            $hash = md5($q['sql'] ?? '');
            if (!isset($grouped[$hash])) {
                $grouped[$hash] = [
                    'sql_hash' => $hash,
                    'sql_sample' => $q['sql'] ?? '',
                    'count' => 0,
                    'total_time' => 0,
                    'max_time' => 0,
                    'times' => [],
                    'issue_count' => 0,
                ];
            }
            $grouped[$hash]['count']++;
            $grouped[$hash]['total_time'] += $q['time'] ?? 0;
            $grouped[$hash]['times'][] = $q['time'] ?? 0;
            $grouped[$hash]['max_time'] = max($grouped[$hash]['max_time'], $q['time'] ?? 0);
            $grouped[$hash]['issue_count'] += count($q['analysis']['issues'] ?? []);
        }

        // Calculate averages
        foreach ($grouped as &$g) {
            $g['avg_time'] = $g['count'] > 0 ? $g['total_time'] / $g['count'] : 0;
            unset($g['times']);
        }

        // Sort
        $sorted = match ($type) {
            'slowest' => $this->sortByDesc($grouped, 'avg_time'),
            'most_frequent' => $this->sortByDesc($grouped, 'count'),
            'most_issues' => $this->sortByDesc($grouped, 'issue_count'),
            default => $this->sortByDesc($grouped, 'total_time'),
        };

        return array_slice(array_values($sorted), 0, $limit);
    }

    public function getAggregates(string $periodType, Carbon $start, Carbon $end): array
    {
        $filtered = array_filter($this->queries, function ($q) use ($start, $end) {
            $ts = $q['timestamp'] ?? 0;
            return $ts >= $start->timestamp && $ts <= $end->timestamp;
        });

        if (empty($filtered)) {
            return [];
        }

        // Group by period
        $grouped = [];
        foreach ($filtered as $q) {
            $ts = $q['timestamp'] ?? time();
            $periodStart = $periodType === 'hour'
                ? Carbon::createFromTimestamp($ts)->startOfHour()->toIso8601String()
                : Carbon::createFromTimestamp($ts)->startOfDay()->toIso8601String();

            if (!isset($grouped[$periodStart])) {
                $grouped[$periodStart] = [];
            }
            $grouped[$periodStart][] = $q;
        }

        $result = [];
        foreach ($grouped as $periodStart => $queries) {
            $times = array_column($queries, 'time');
            sort($times);
            $count = count($times);

            $result[] = [
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'total_queries' => $count,
                'slow_queries' => count(array_filter($queries, fn($q) => ($q['analysis']['performance']['is_slow'] ?? false))),
                'avg_time' => $count > 0 ? array_sum($times) / $count : 0,
                'p50_time' => $this->percentile($times, 50),
                'p95_time' => $this->percentile($times, 95),
                'p99_time' => $this->percentile($times, 99),
                'max_time' => $count > 0 ? max($times) : 0,
                'min_time' => $count > 0 ? min($times) : 0,
            ];
        }

        return $result;
    }

    public function storeRequest(string $requestId, array $data): void
    {
        $this->requests[$requestId] = array_merge($this->requests[$requestId] ?? [], $data, ['updated_at' => time()]);
    }

    public function getRequests(int $limit = 100, array $filters = []): array
    {
        $requests = $this->requests;

        // Apply filters
        if (!empty($filters['method'])) {
            $requests = array_filter($requests, fn($r) => ($r['method'] ?? '') === $filters['method']);
        }

        if (!empty($filters['path'])) {
            $requests = array_filter($requests, fn($r) => str_contains($r['path'] ?? '', $filters['path']));
        }

        // Sort by updated_at
        uasort($requests, fn($a, $b) => ($b['updated_at'] ?? 0) - ($a['updated_at'] ?? 0));

        return array_slice($requests, 0, $limit, true);
    }

    public function getQueriesSince(float $since, int $limit = 100): array
    {
        $filtered = array_filter($this->queries, fn($q) => ($q['timestamp'] ?? 0) > $since);
        return array_slice($filtered, 0, $limit);
    }

    public function supportsPersistence(): bool
    {
        return false;
    }

    protected function sortByDesc(array $arr, string $key): array
    {
        usort($arr, fn($a, $b) => ($b[$key] ?? 0) <=> ($a[$key] ?? 0));
        return $arr;
    }

    protected function percentile(array $values, int $percentile): float
    {
        if (empty($values)) {
            return 0;
        }

        $count = count($values);
        $index = (int) ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return (float) ($values[$index] ?? 0);
    }

    // Helper methods for testing
    public function getAllQueries(): array
    {
        return $this->queries;
    }

    public function getAllRequests(): array
    {
        return $this->requests;
    }
}
