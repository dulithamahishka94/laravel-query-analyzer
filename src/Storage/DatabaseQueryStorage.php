<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Storage;

use Carbon\Carbon;
use Illuminate\Support\Str;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Models\AlertLog;
use GladeHQ\QueryLens\Models\AnalyzedRequest;
use GladeHQ\QueryLens\Models\QueryAggregate;
use GladeHQ\QueryLens\Models\TopQuery;
use GladeHQ\QueryLens\Services\AlertService;

class DatabaseQueryStorage implements QueryStorage
{
    protected ?AlertService $alertService = null;
    protected ?\GladeHQ\QueryLens\QueryAnalyzer $analyzer = null;

    public function setAlertService(AlertService $alertService): void
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
     * This prevents the database storage driver's own Eloquent operations
     * (INSERT into query_lens_queries, SELECT from query_lens_requests, etc.)
     * from being captured by the QueryListener. This is the secondary defense
     * layer -- the primary layer is the table-prefix check in QueryAnalyzer::recordQuery().
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
            $requestId = $query['request_id'] ?? null;

            // Ensure request exists
            if ($requestId) {
                $this->ensureRequestExists($requestId, $query);
            }

            // Normalize and hash SQL
            $sqlHash = AnalyzedQuery::hashSql($query['sql'] ?? '');
            $sqlNormalized = AnalyzedQuery::normalizeSql($query['sql'] ?? '');

            // Extract analysis data
            $analysis = $query['analysis'] ?? [];
            $issues = $analysis['issues'] ?? [];
            $isNPlusOne = collect($issues)->contains(fn($i) => ($i['type'] ?? '') === 'n+1');

            // Create query record
            $analyzedQuery = AnalyzedQuery::create([
                'id' => $query['id'] ?? Str::orderedUuid()->toString(),
                'request_id' => $requestId,
                'sql_hash' => $sqlHash,
                'sql' => $query['sql'],
                'sql_normalized' => $sqlNormalized,
                'bindings' => $query['bindings'] ?? [],
                'time' => $query['time'] ?? 0,
                'connection' => $query['connection'] ?? 'default',
                'type' => strtoupper($analysis['type'] ?? 'OTHER'),
                'performance_rating' => $analysis['performance']['rating'] ?? 'fast',
                'is_slow' => $analysis['performance']['is_slow'] ?? false,
                'complexity_score' => $analysis['complexity']['score'] ?? 0,
                'complexity_level' => $analysis['complexity']['level'] ?? 'low',
                'analysis' => [
                    'recommendations' => $analysis['recommendations'] ?? [],
                    'issues' => $issues,
                ],
                'origin' => $query['origin'] ?? ['file' => 'unknown', 'line' => 0, 'is_vendor' => false],
                'is_n_plus_one' => $isNPlusOne,
                'n_plus_one_count' => $isNPlusOne ? ($this->countSimilarQueries($requestId, $sqlHash) + 1) : 0,
                'created_at' => now(),
            ]);

            // Check alerts
            if ($this->alertService) {
                $this->alertService->checkAlerts($analyzedQuery);
            }
        });
    }

    public function get(int $limit = 100): array
    {
        return $this->withoutRecording(fn() => AnalyzedQuery::orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($q) => $q->toApiArray())
            ->toArray());
    }

    public function clear(): void
    {
        $this->withoutRecording(function () {
            AnalyzedQuery::truncate();
            AnalyzedRequest::truncate();
            QueryAggregate::truncate();
            TopQuery::truncate();
            AlertLog::truncate();
        });
    }

    public function getByRequest(string $requestId): array
    {
        return $this->withoutRecording(fn() => AnalyzedQuery::where('request_id', $requestId)
            ->orderBy('created_at')
            ->get()
            ->map(fn($q) => $q->toApiArray())
            ->toArray());
    }

    public function getStats(Carbon $start, Carbon $end): array
    {
        return $this->withoutRecording(function () use ($start, $end) {
            $queries = AnalyzedQuery::inPeriod($start, $end);

            $total = $queries->count();
            $slowCount = (clone $queries)->slow()->count();
            $times = (clone $queries)->pluck('time');

            return [
                'total_queries' => $total,
                'slow_queries' => $slowCount,
                'avg_time' => $times->avg() ?? 0,
                'max_time' => $times->max() ?? 0,
                'total_time' => $times->sum(),
                'n_plus_one_count' => (clone $queries)->nPlusOne()->count(),
                'issue_count' => (clone $queries)->withIssues()->count(),
            ];
        });
    }

    public function getTopQueries(string $type, string $period, int $limit = 10): array
    {
        return $this->withoutRecording(function () use ($type, $period, $limit) {
            // Try to get pre-computed rankings first
            $periodStart = TopQuery::getPeriodStart($period);
            $precomputed = TopQuery::byRankingType($type)
                ->byPeriod($period)
                ->forPeriodStart($periodStart)
                ->topN($limit)
                ->get();

            if ($precomputed->isNotEmpty()) {
                return $precomputed->toArray();
            }

            // Fall back to computing on-the-fly
            return $this->computeTopQueries($type, $period, $limit);
        });
    }

    public function getAggregates(string $periodType, Carbon $start, Carbon $end): array
    {
        return $this->withoutRecording(fn() => QueryAggregate::where('period_type', $periodType)
            ->inRange($start, $end)
            ->orderBy('period_start')
            ->get()
            ->toArray());
    }

    public function storeRequest(string $requestId, array $data): void
    {
        $this->withoutRecording(function () use ($requestId, $data) {
            AnalyzedRequest::updateOrCreate(
                ['id' => $requestId],
                array_merge($data, ['created_at' => now()])
            );
        });
    }

    public function getRequests(int $limit = 100, array $filters = []): array
    {
        return $this->withoutRecording(function () use ($limit, $filters) {
            $query = AnalyzedRequest::query()->orderByDesc('created_at');

            if (!empty($filters['method'])) {
                $query->byMethod($filters['method']);
            }

            if (!empty($filters['path'])) {
                $query->byPath($filters['path']);
            }

            if (!empty($filters['slow_only'])) {
                $query->withSlowQueries();
            }

            return $query->limit($limit)
                ->get()
                ->map(function ($request) {
                    return [
                        'request_id' => $request->id,
                        'method' => $request->method,
                        'path' => $request->path,
                        'route_name' => $request->route_name,
                        'timestamp' => $request->created_at?->timestamp ?? time(),
                        'query_count' => $request->query_count,
                        'slow_count' => $request->slow_count,
                        'avg_time' => $request->avg_time,
                    ];
                })
                ->toArray();
        });
    }

    public function getQueriesSince(float $since, int $limit = 100): array
    {
        return $this->withoutRecording(fn() => AnalyzedQuery::where('created_at', '>', Carbon::createFromTimestamp($since))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn($q) => $q->toApiArray())
            ->toArray());
    }

    public function search(array $filters = []): array
    {
        return $this->withoutRecording(function () use ($filters) {
            $page = max(1, (int) ($filters['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($filters['per_page'] ?? 15)));

            $query = AnalyzedQuery::query()->orderByDesc('created_at');

            if (!empty($filters['sql_like'])) {
                $query->where('sql_normalized', 'like', '%' . $filters['sql_like'] . '%');
            }

            if (!empty($filters['table_name'])) {
                $query->where('sql', 'like', '%' . $filters['table_name'] . '%');
            }

            if (!empty($filters['time_from'])) {
                $query->where('created_at', '>=', Carbon::parse($filters['time_from']));
            }

            if (!empty($filters['time_to'])) {
                $query->where('created_at', '<=', Carbon::parse($filters['time_to']));
            }

            if (isset($filters['min_duration'])) {
                $query->where('time', '>=', (float) $filters['min_duration']);
            }

            if (isset($filters['max_duration'])) {
                $query->where('time', '<=', (float) $filters['max_duration']);
            }

            if (!empty($filters['type'])) {
                $query->where('type', strtoupper($filters['type']));
            }

            if (isset($filters['is_slow'])) {
                $query->where('is_slow', (bool) $filters['is_slow']);
            }

            $total = $query->count();

            $data = $query
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn($q) => $q->toApiArray())
                ->toArray();

            return [
                'data' => $data,
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
            ];
        });
    }

    public function supportsPersistence(): bool
    {
        return true;
    }

    public function finalizeRequest(string $requestId): void
    {
        $this->withoutRecording(fn() => $this->updateRequestAggregates($requestId));
    }

    protected function ensureRequestExists(string $requestId, array $query): void
    {
        $existing = AnalyzedRequest::find($requestId);

        if (!$existing) {
            AnalyzedRequest::create([
                'id' => $requestId,
                'method' => $query['request_method'] ?? 'GET',
                'path' => $query['request_path'] ?? '/',
                'route_name' => $query['route_name'] ?? null,
                'created_at' => now(),
            ]);
        } elseif (!$existing->route_name && !empty($query['route_name'])) {
            // The first query in a request may fire before route resolution
            // completes (e.g., during middleware). Update the route_name once
            // it becomes available from a later query in the same request.
            $existing->update(['route_name' => $query['route_name']]);
        }
    }

    protected function updateRequestAggregates(string $requestId): void
    {
        $request = AnalyzedRequest::find($requestId);
        if ($request) {
            $request->updateAggregates();
        }
    }

    protected function countSimilarQueries(string $requestId, string $sqlHash): int
    {
        return AnalyzedQuery::where('request_id', $requestId)
            ->where('sql_hash', $sqlHash)
            ->count();
    }

    protected function computeTopQueries(string $type, string $period, int $limit): array
    {
        $cutoff = match ($period) {
            'hour' => now()->subHour(),
            'day' => now()->subDay(),
            'week' => now()->subWeek(),
            default => now()->subDay(),
        };

        $connection = config('query-lens.storage.connection');
        $driver = \Illuminate\Support\Facades\DB::connection($connection)->getDriverName();

        // Build driver-aware issue count expression
        if ($driver === 'mysql') {
            $issueCountExpr = "SUM(CASE WHEN JSON_LENGTH(`analysis`, '$.issues') > 0 THEN 1 ELSE 0 END)";
        } elseif ($driver === 'pgsql') {
            $issueCountExpr = "SUM(CASE WHEN jsonb_array_length(\"analysis\"->'issues') > 0 THEN 1 ELSE 0 END)";
        } else {
            // SQLite fallback
            $issueCountExpr = "SUM(CASE WHEN \"analysis\" LIKE '%\"issues\":[%' AND \"analysis\" NOT LIKE '%\"issues\":[]%' THEN 1 ELSE 0 END)";
        }

        // Quote reserved-word column names to avoid MySQL syntax errors.
        // 'sql', 'time', 'count', and 'analysis' are reserved words in MySQL.
        $q = $driver === 'mysql' ? '`' : '"';

        $query = AnalyzedQuery::where('created_at', '>=', $cutoff)
            ->selectRaw("{$q}sql_hash{$q}, MIN({$q}sql{$q}) as sql_sample, COUNT(*) as query_count,
                         AVG({$q}time{$q}) as avg_time, MAX({$q}time{$q}) as max_time, SUM({$q}time{$q}) as total_time,
                         {$issueCountExpr} as issue_count")
            ->groupBy('sql_hash');

        $orderBy = match ($type) {
            'slowest' => 'avg_time DESC',
            'most_frequent' => 'query_count DESC',
            'most_issues' => 'issue_count DESC',
            default => 'total_time DESC',
        };

        return $query->orderByRaw($orderBy)
            ->limit($limit)
            ->get()
            ->map(function ($row, $index) {
                return [
                    'sql_hash' => $row->sql_hash,
                    'sql_sample' => $row->sql_sample,
                    'count' => (int) $row->query_count,
                    'avg_time' => (float) $row->avg_time,
                    'max_time' => (float) $row->max_time,
                    'total_time' => (float) $row->total_time,
                    'issue_count' => (int) $row->issue_count,
                    'rank' => $index + 1,
                ];
            })
            ->toArray();
    }
}
