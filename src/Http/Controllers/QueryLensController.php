<?php

namespace GladeHQ\QueryLens\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Services\AggregationService;
use GladeHQ\QueryLens\Services\DashboardService;
use GladeHQ\QueryLens\Services\ExplainService;
use GladeHQ\QueryLens\Services\AIQueryOptimizer;
use GladeHQ\QueryLens\Services\IndexAdvisor;
use GladeHQ\QueryLens\Services\QueryExportService;
use GladeHQ\QueryLens\Services\RegressionDetector;
use GladeHQ\QueryLens\Listeners\TransactionListener;

class QueryLensController extends Controller
{
    protected QueryAnalyzer $analyzer;
    protected QueryStorage $storage;
    protected ExplainService $explainService;
    protected QueryExportService $exportService;
    protected DashboardService $dashboardService;

    public function __construct(
        QueryAnalyzer $analyzer,
        QueryStorage $storage,
        ?ExplainService $explainService = null,
        ?QueryExportService $exportService = null,
        ?DashboardService $dashboardService = null,
    ) {
        $this->analyzer = $analyzer;
        $this->storage = $storage;
        $this->explainService = $explainService ?? new ExplainService();
        $this->exportService = $exportService ?? new QueryExportService();
        $this->dashboardService = $dashboardService ?? new DashboardService();
    }

    public function explain(Request $request): JsonResponse
    {
        $sql = $request->input('sql');
        $bindings = $request->input('bindings', []);
        $connection = $request->input('connection');

        if (!$sql || !str_starts_with(trim(strtoupper($sql)), 'SELECT')) {
            return response()->json(['error' => 'Only SELECT queries can be explained'], 400);
        }

        try {
            $result = $this->explainService->explain($sql, $bindings, $connection);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not explain query: ' . $e->getMessage()], 500);
        }

        return $this->noCacheResponse(response()->json($result));
    }

    public function dashboard(): \Illuminate\Http\Response
    {
        $response = response()->view('query-lens::dashboard', [
            'stats' => $this->analyzer->getStats(),
            'isEnabled' => config('query-lens.enabled', false),
        ]);

        return $this->noCacheResponse($response);
    }

    public function queries(Request $request): JsonResponse
    {
        $queries = $this->analyzer->getQueries();

        if ($requestId = $request->query('request_id')) {
            $queries = $queries->where('request_id', $requestId)->values();
        }

        $queries = $this->dashboardService->applyFilters($queries, $request);

        $sort = $request->query('sort', 'timestamp');
        $order = $request->query('order', 'desc');

        $queries = $queries->sortBy(function ($query) use ($sort) {
            if ($sort === 'time') return $query['time'];
            if ($sort === 'complexity') return $query['analysis']['complexity']['score'] ?? 0;
            return $query['timestamp'];
        }, SORT_REGULAR, $order === 'desc')->values();

        if ($request->has('limit')) {
            $queries = $queries->take((int) $request->limit);
        }

        return $this->noCacheResponse(response()->json([
            'queries' => $queries,
            'stats' => $this->analyzer->getStats(),
        ]));
    }

    public function requests(Request $request): JsonResponse
    {
        // Pre-load request metadata (method, path) from the storage layer.
        // The database driver stores these on the AnalyzedRequest model, but
        // toApiArray() on AnalyzedQuery does not include them -- so we look
        // them up once and index by request_id for O(1) enrichment below.
        $requestMeta = collect($this->storage->getRequests(1000))
            ->keyBy('request_id');

        $requests = $this->analyzer->getQueries()
            ->groupBy('request_id')
            ->map(function ($group) use ($request, $requestMeta) {
                $first = $group->first();
                $filtered = $this->dashboardService->applyFilters($group, $request);
                $requestId = $first['request_id'];

                // Resolve method and path: prefer the query-level keys (cache driver
                // stores these inline), then fall back to AnalyzedRequest metadata
                // (database driver), and finally to sensible defaults.
                $meta = $requestMeta->get($requestId, []);
                $method = $first['request_method']
                    ?? $meta['method']
                    ?? 'UNKNOWN';
                $path = $first['request_path']
                    ?? $meta['path']
                    ?? '/';
                $routeName = $first['route_name']
                    ?? $meta['route_name']
                    ?? null;

                return [
                    'request_id' => $requestId,
                    'method' => $method,
                    'path' => $path,
                    'route_name' => $routeName,
                    'timestamp' => $first['timestamp'],
                    'query_count' => $filtered->count(),
                    'slow_count' => $filtered->where('analysis.performance.is_slow', true)->count(),
                    'avg_time' => $filtered->average('time') ?? 0,
                ];
            })
            ->sortByDesc('timestamp')
            ->values();

        return $this->noCacheResponse(response()->json($requests));
    }

    public function query(Request $request, string $id): JsonResponse
    {
        $query = $this->analyzer->getQueries()->firstWhere('id', $id);

        if (!$query) {
            return response()->json(['error' => 'Query not found. ID: ' . $id], 404);
        }

        return $this->noCacheResponse(response()->json($query));
    }

    public function stats(): JsonResponse
    {
        return response()->json($this->analyzer->getStats());
    }

    public function reset(): JsonResponse
    {
        $this->analyzer->reset();

        return response()->json([
            'message' => 'Query collection has been reset',
            'stats' => $this->analyzer->getStats(),
        ]);
    }

    public function analyze(Request $request): JsonResponse
    {
        $sql = $request->input('sql');
        $bindings = $request->input('bindings', []);
        $time = $request->input('time', 0.0);

        if (!$sql) {
            return response()->json(['error' => 'SQL query is required'], 400);
        }

        $analysis = $this->analyzer->analyzeQuery($sql, $bindings, $time);

        return response()->json([
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'analysis' => $analysis,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $format = $request->input('format', 'json');
        $queries = $this->analyzer->getQueries();
        $stats = $this->analyzer->getStats();

        $result = $this->exportService->export($queries, $format, $stats);

        return response()->json($result);
    }

    // ==================== V2 API Endpoints ====================

    public function trends(Request $request): JsonResponse
    {
        $start = $request->query('start')
            ? Carbon::parse($request->query('start'))
            : now()->subDay();
        $end = $request->query('end')
            ? Carbon::parse($request->query('end'))
            : now();
        $granularity = $request->query('granularity', 'hour');

        $aggregationService = app(AggregationService::class);
        $aggregationService->setStorage($this->storage);
        $trends = $aggregationService->getPerformanceTrends($granularity, $start, $end);

        return $this->noCacheResponse(response()->json($trends));
    }

    public function topQueries(Request $request): JsonResponse
    {
        $type = $request->query('type', 'slowest');
        $period = $request->query('period', 'day');
        $limit = (int) $request->query('limit', 10);

        $topQueries = $this->storage->getTopQueries($type, $period, $limit);

        return $this->noCacheResponse(response()->json([
            'queries' => $topQueries,
            'type' => $type,
            'period' => $period,
        ]));
    }

    public function requestWaterfall(Request $request, string $id): JsonResponse
    {
        $queries = $this->storage->getByRequest($id);

        if (empty($queries)) {
            return response()->json(['error' => 'Request not found'], 404);
        }

        $waterfall = $this->dashboardService->buildWaterfallTimeline($queries);

        return $this->noCacheResponse(response()->json(array_merge(
            ['request_id' => $id, 'queries' => $queries],
            $waterfall,
        )));
    }

    public function overview(Request $request): JsonResponse
    {
        $aggregationService = app(AggregationService::class);
        $period = $request->query('period', '24h');
        $overview = $aggregationService->getOverviewStats($period);

        return $this->noCacheResponse(response()->json($overview));
    }

    public function poll(Request $request): JsonResponse
    {
        $since = (float) $request->query('since', 0);

        $newQueries = $this->storage->getQueriesSince($since, 50);
        $stats = $this->analyzer->getStats();

        $alerts = [];
        if ($this->storage->supportsPersistence()) {
            $alertService = app(\GladeHQ\QueryLens\Services\AlertService::class);
            $alerts = $alertService->getRecentAlerts(1, 10);
        }

        return $this->noCacheResponse(response()->json([
            'new_queries' => $newQueries,
            'stats' => $stats,
            'alerts' => $alerts,
            'timestamp' => microtime(true),
        ]));
    }

    public function requestsV2(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 100);
        $filters = [];

        if ($method = $request->query('method')) {
            $filters['method'] = $method;
        }

        if ($path = $request->query('path')) {
            $filters['path'] = $path;
        }

        if ($request->boolean('slow_only')) {
            $filters['slow_only'] = true;
        }

        $requests = $this->storage->getRequests($limit, $filters);

        return $this->noCacheResponse(response()->json([
            'requests' => $requests,
        ]));
    }

    public function search(Request $request): JsonResponse
    {
        $filters = array_filter([
            'sql_like' => $request->query('sql_like'),
            'table_name' => $request->query('table_name'),
            'time_from' => $request->query('time_from'),
            'time_to' => $request->query('time_to'),
            'min_duration' => $request->query('min_duration'),
            'max_duration' => $request->query('max_duration'),
            'type' => $request->query('type'),
            'is_slow' => $request->has('is_slow') ? $request->boolean('is_slow') : null,
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 15),
        ], fn($v) => $v !== null);

        $results = $this->storage->search($filters);

        return $this->noCacheResponse(response()->json($results));
    }

    public function storageInfo(): JsonResponse
    {
        $retentionService = app(\GladeHQ\QueryLens\Services\DataRetentionService::class);

        return response()->json([
            'driver' => config('query-lens.storage.driver', 'cache'),
            'supports_persistence' => $this->storage->supportsPersistence(),
            'stats' => $this->storage->supportsPersistence()
                ? $retentionService->getStorageStats()
                : null,
        ]);
    }

    public function aiOptimize(Request $request): JsonResponse
    {
        $optimizer = app(AIQueryOptimizer::class);

        $sql = $request->input('sql');

        if (!$sql) {
            return response()->json(['error' => 'SQL query is required'], 400);
        }

        $context = array_filter([
            'explain' => $request->input('explain'),
            'schema' => $request->input('schema'),
            'frequency' => $request->input('frequency'),
            'avg_duration' => $request->input('avg_duration'),
            'indexes' => $request->input('indexes'),
        ]);

        $result = $optimizer->optimize($sql, $context);

        return $this->noCacheResponse(response()->json($result));
    }

    public function regressions(Request $request): JsonResponse
    {
        $detector = app(RegressionDetector::class);

        $period = $request->query('period', 'daily');
        $threshold = (float) $request->query('threshold', config('query-lens.regression.threshold', 0.2));

        $result = $detector->detect($period, $threshold);

        return $this->noCacheResponse(response()->json($result));
    }

    public function indexSuggestions(Request $request): JsonResponse
    {
        $advisor = app(IndexAdvisor::class);

        if ($sql = $request->query('sql')) {
            $result = $advisor->analyzeQuery($sql);
            return $this->noCacheResponse(response()->json($result));
        }

        $days = (int) $request->query('days', 7);
        $suggestions = $advisor->analyzePatterns($days);

        return $this->noCacheResponse(response()->json([
            'suggestions' => $suggestions,
            'days_analyzed' => $days,
        ]));
    }

    public function transactions(Request $request): JsonResponse
    {
        if (!config('query-lens.track_transactions', false)) {
            return $this->noCacheResponse(response()->json([
                'error' => 'Transaction tracking is disabled',
                'hint' => 'Set QUERY_LENS_TRACK_TRANSACTIONS=true in your .env file',
            ], 400));
        }

        $transactionListener = app(TransactionListener::class);

        $completed = $transactionListener->getCompletedTransactions();
        $active = $transactionListener->getActiveTransactions();

        // Apply optional filters
        $status = $request->query('status');
        $connection = $request->query('connection');

        if ($status === 'active') {
            $results = $active;
        } elseif ($status) {
            $results = array_filter($completed, fn($t) => $t['status'] === $status);
        } else {
            $results = array_merge($active, $completed);
        }

        if ($connection) {
            $results = array_filter($results, fn($t) => $t['connection'] === $connection);
        }

        // Sort by started_at descending
        usort($results, fn($a, $b) => ($b['started_at'] ?? 0) <=> ($a['started_at'] ?? 0));

        $limit = (int) $request->query('limit', 100);
        $results = array_slice(array_values($results), 0, $limit);

        $summary = [
            'total_completed' => count($completed),
            'total_active' => count($active),
            'committed' => count(array_filter($completed, fn($t) => $t['status'] === 'committed')),
            'rolled_back' => count(array_filter($completed, fn($t) => $t['status'] === 'rolled_back')),
        ];

        return $this->noCacheResponse(response()->json([
            'transactions' => $results,
            'summary' => $summary,
        ]));
    }

    protected function noCacheResponse($response)
    {
        return $response->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
