<?php

namespace GladeHQ\QueryLens\Storage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Support\SqlNormalizer;

class CacheQueryStorage implements QueryStorage
{
    protected string $cacheKey = 'laravel_query_lens_queries_v3';
    protected string $requestsCacheKey = 'laravel_query_lens_requests_v1';
    protected int $ttl = 3600; // 1 hour
    protected ?string $store = null;
    protected ?\GladeHQ\QueryLens\Services\AlertService $alertService = null;
    protected ?\GladeHQ\QueryLens\QueryAnalyzer $analyzer = null;

    public function __construct(?string $store = null)
    {
        $this->store = $store;
    }

    public function setAlertService(\GladeHQ\QueryLens\Services\AlertService $alertService): void
    {
        $this->alertService = $alertService;
    }

    public function setAnalyzer(\GladeHQ\QueryLens\QueryAnalyzer $analyzer): void
    {
        $this->analyzer = $analyzer;
    }

    /**
     * Execute a callback with query recording temporarily disabled.
     *
     * This prevents the cache storage driver's own operations from being
     * captured by the QueryListener. When the cache is backed by a database
     * driver, Cache::store()->get/put fire QueryExecuted events; when using
     * any driver, CacheHit/CacheMiss events fire. Both must be silenced
     * during internal operations.
     */
    protected function withoutRecording(callable $callback): mixed
    {
        if (!$this->analyzer) {
            return $callback();
        }

        $wasRecording = $this->analyzer->isRecording();
        $this->analyzer->disableRecording();

        try {
            return $callback();
        } finally {
            if ($wasRecording) {
                $this->analyzer->enableRecording();
            }
        }
    }

    public function store(array $query): void
    {
        $this->withoutRecording(function () use ($query) {
            $queries = $this->getRaw(10000);

            // Add new query to the beginning
            array_unshift($queries, $query);

            // Limit to max items (e.g. 10000) to prevent cache explosion
            $queries = array_slice($queries, 0, 10000);

            Cache::store($this->store)->put($this->cacheKey, $queries, $this->ttl);

            // Check alerts
            if ($this->alertService) {
                $this->checkAlerts($query);
            }
        });
    }

    protected function checkAlerts(array $query): void
    {
        // Hydrate a temporary model for alert checking
        $analyzedQuery = new \GladeHQ\QueryLens\Models\AnalyzedQuery($query);
        
        // Populate specific fields that might be missing or nested
        $analyzedQuery->time = $query['time'] ?? 0;
        $analyzedQuery->n_plus_one_count = $this->calculateNPlusOneCount($query);
        $analyzedQuery->is_n_plus_one = $query['analysis']['is_n_plus_one'] ?? false; // Assuming this logic exists or re-derive it
        
        // Re-derive is_n_plus_one if not present (it's in analysis issues usually)
        $issues = $query['analysis']['issues'] ?? [];
        $analyzedQuery->is_n_plus_one = collect($issues)->contains(fn($i) => ($i['type'] ?? '') === 'n+1');
        
        $this->alertService->checkAlerts($analyzedQuery);
    }

    protected function calculateNPlusOneCount(array $query): int
    {
        // For cache, we need to count from the cached array
        if (!$query['request_id']) return 0;
        
        $sqlHash = SqlNormalizer::hash($query['sql'] ?? '');

        $queries = $this->getByRequest($query['request_id']);

        return collect($queries)
            ->filter(fn($q) => SqlNormalizer::hash($q['sql'] ?? '') === $sqlHash)
            ->count();
    }

    /**
     * Internal cache read -- no recording guard (caller is responsible).
     */
    protected function getRaw(int $limit = 10000): array
    {
        $queries = Cache::store($this->store)->get($this->cacheKey, []);

        return array_slice($queries, 0, $limit);
    }

    public function get(int $limit = 100): array
    {
        return $this->withoutRecording(function () use ($limit) {
            return $this->getRaw($limit);
        });
    }

    public function clear(): void
    {
        $this->withoutRecording(function () {
            Cache::store($this->store)->forget($this->cacheKey);
            Cache::store($this->store)->forget($this->requestsCacheKey);
        });
    }

    public function getByRequest(string $requestId): array
    {
        return $this->withoutRecording(function () use ($requestId) {
            $queries = $this->getRaw(10000);

            return array_filter($queries, fn($q) => ($q['request_id'] ?? '') === $requestId);
        });
    }

    public function getStats(Carbon $start, Carbon $end): array
    {
        return $this->withoutRecording(function () use ($start, $end) {
            $queries = collect($this->getRaw(10000))
                ->filter(fn($q) => ($q['timestamp'] ?? 0) >= $start->timestamp && ($q['timestamp'] ?? 0) <= $end->timestamp);

            $total = $queries->count();
            $slowCount = $queries->where('analysis.performance.is_slow', true)->count();

            return [
                'total_queries' => $total,
                'slow_queries' => $slowCount,
                'avg_time' => $queries->avg('time') ?? 0,
                'max_time' => $queries->max('time') ?? 0,
                'total_time' => $queries->sum('time'),
            ];
        });
    }

    public function getTopQueries(string $type, string $period, int $limit = 10): array
    {
        return $this->withoutRecording(function () use ($type, $period, $limit) {
            $queries = collect($this->getRaw(10000));
            $cutoff = $this->getPeriodCutoff($period);

            $queries = $queries->filter(fn($q) => ($q['timestamp'] ?? 0) >= $cutoff->timestamp);

            // Group by normalized SQL hash
            $grouped = $queries->groupBy(function ($q) {
                return SqlNormalizer::hash($q['sql'] ?? '');
            });

            $ranked = $grouped->map(function ($group, $hash) {
                $sample = $group->first();
                return [
                    'sql_hash' => $hash,
                    'sql_sample' => $sample['sql'] ?? '',
                    'count' => $group->count(),
                    'avg_time' => $group->avg('time') ?? 0,
                    'max_time' => $group->max('time') ?? 0,
                    'total_time' => $group->sum('time'),
                    'issue_count' => $group->sum(fn($q) => count($q['analysis']['issues'] ?? [])),
                ];
            });

            // Sort based on type
            $sorted = match ($type) {
                'slowest' => $ranked->sortByDesc('avg_time'),
                'most_frequent' => $ranked->sortByDesc('count'),
                'most_issues' => $ranked->sortByDesc('issue_count'),
                default => $ranked->sortByDesc('total_time'),
            };

            return $sorted->take($limit)->values()->toArray();
        });
    }

    public function getAggregates(string $periodType, Carbon $start, Carbon $end): array
    {
        return $this->withoutRecording(function () use ($periodType, $start, $end) {
            // Cache storage computes aggregates on-the-fly from raw data
            $queries = collect($this->getRaw(10000))
                ->filter(fn($q) => ($q['timestamp'] ?? 0) >= $start->timestamp && ($q['timestamp'] ?? 0) <= $end->timestamp);

            if ($queries->isEmpty()) {
                return [];
            }

            // Group by period
            $grouped = $queries->groupBy(function ($q) use ($periodType) {
                $timestamp = $q['timestamp'] ?? time();
                $carbon = Carbon::createFromTimestamp($timestamp);
                return $periodType === 'hour'
                    ? $carbon->startOfHour()->toIso8601String()
                    : $carbon->startOfDay()->toIso8601String();
            });

            return $grouped->map(function ($group, $periodStart) use ($periodType) {
                $times = $group->pluck('time')->sort()->values();

                return [
                    'period_type' => $periodType,
                    'period_start' => $periodStart,
                    'total_queries' => $group->count(),
                    'slow_queries' => $group->where('analysis.performance.is_slow', true)->count(),
                    'avg_time' => $times->avg() ?? 0,
                    'p50_time' => $this->percentile($times, 50),
                    'p95_time' => $this->percentile($times, 95),
                    'p99_time' => $this->percentile($times, 99),
                    'max_time' => $times->max() ?? 0,
                    'min_time' => $times->min() ?? 0,
                ];
            })->values()->toArray();
        });
    }

    public function storeRequest(string $requestId, array $data): void
    {
        $this->withoutRecording(function () use ($requestId, $data) {
            $requests = Cache::store($this->store)->get($this->requestsCacheKey, []);
            $requests[$requestId] = array_merge($requests[$requestId] ?? [], $data, ['updated_at' => time()]);

            // Limit to 1000 requests
            if (count($requests) > 1000) {
                $requests = array_slice($requests, -1000, 1000, true);
            }

            Cache::store($this->store)->put($this->requestsCacheKey, $requests, $this->ttl);
        });
    }

    public function getRequests(int $limit = 100, array $filters = []): array
    {
        return $this->withoutRecording(function () use ($limit, $filters) {
            $requests = Cache::store($this->store)->get($this->requestsCacheKey, []);

            // Sort by updated_at descending
            uasort($requests, fn($a, $b) => ($b['updated_at'] ?? 0) - ($a['updated_at'] ?? 0));

            return array_slice($requests, 0, $limit, true);
        });
    }

    public function getQueriesSince(float $since, int $limit = 100): array
    {
        return $this->withoutRecording(function () use ($since, $limit) {
            $queries = $this->getRaw(10000);

            return array_slice(
                array_filter($queries, fn($q) => ($q['timestamp'] ?? 0) > $since),
                0,
                $limit
            );
        });
    }

    public function finalizeRequest(string $requestId): void
    {
        // Cache storage computes aggregates on-the-fly; no finalization needed
    }

    public function search(array $filters = []): array
    {
        // Cache storage does not support persistent search -- return empty result set
        return [
            'data' => [],
            'total' => 0,
            'page' => max(1, (int) ($filters['page'] ?? 1)),
            'per_page' => max(1, min(100, (int) ($filters['per_page'] ?? 15))),
        ];
    }

    public function supportsPersistence(): bool
    {
        return false;
    }

    protected function getPeriodCutoff(string $period): Carbon
    {
        return match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            default => now()->subDay(),
        };
    }

    protected function percentile(\Illuminate\Support\Collection $values, int $percentile): float
    {
        if ($values->isEmpty()) {
            return 0;
        }

        $count = $values->count();
        $index = (int) ceil(($percentile / 100) * $count) - 1;
        $index = max(0, min($index, $count - 1));

        return (float) $values->values()->get($index, 0);
    }
}
