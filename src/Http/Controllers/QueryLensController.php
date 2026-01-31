<?php

namespace Coderflex\QueryLens\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Coderflex\QueryLens\Contracts\QueryStorage;
use Coderflex\QueryLens\QueryAnalyzer;
use Coderflex\QueryLens\Services\AggregationService;

class QueryLensController extends Controller
{
    protected QueryAnalyzer $analyzer;
    protected QueryStorage $storage;

    public function __construct(QueryAnalyzer $analyzer, QueryStorage $storage)
    {
        $this->analyzer = $analyzer;
        $this->storage = $storage;
    }

    public function explain(Request $request): JsonResponse
    {
        $response = $this->explainLogic($request);
        return $response->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    protected function explainLogic(Request $request): JsonResponse
    {
        $sql = $request->input('sql');
        $bindings = $request->input('bindings', []);
        $connection = $request->input('connection');

        if (!$sql || !str_starts_with(trim(strtoupper($sql)), 'SELECT')) {
            return response()->json(['error' => 'Only SELECT queries can be explained'], 400);
        }

        $standardResult = [];
        $analyzeResult = [];
        $supportsAnalyze = false;

        try {
            // 1. Always get Standard EXPLAIN for the table view
            $standardResult = \Illuminate\Support\Facades\DB::connection($connection)->select('EXPLAIN ' . $sql, $bindings);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not explain query: ' . $e->getMessage()], 500);
        }

        try {
            // 2. Try EXPLAIN ANALYZE for the profiling data
            $analyzeResult = \Illuminate\Support\Facades\DB::connection($connection)->select('EXPLAIN ANALYZE ' . $sql, $bindings);
            $supportsAnalyze = true;
        } catch (\Exception $e) {
            // Silently fail if ANALYZE is not supported
        }

        // Initialize default humanization from standard result
        $humanized = $this->humanizeExplain((array) $standardResult, (array) $analyzeResult);

        // If EXPLAIN ANALYZE is supported, use the Deep Analyzer for superior insights
        if ($supportsAnalyze && !empty($analyzeResult)) {
            try {
                $rawAnalyze = (string) (reset($analyzeResult[0]) ?: '');
                $deepAnalyzer = new \Coderflex\QueryLens\ExplainAnalyzer\ExplainAnalyzer();
                $analysisResult = $deepAnalyzer->analyze($rawAnalyze);
                
                // 1. Get the full human-readable explanation
                $fullExplanation = $deepAnalyzer->getExplainer()->explain($analysisResult);
                
                // Replace the raw output with the human-readable one for the 'Profiling Tree' section
                // We keep the first key to maintain structure compatibility with the frontend
                $firstKey = array_key_first((array) $analyzeResult[0]);
                $analyzeResult = [[$firstKey => $fullExplanation]];

                // 2. Enhance Summary and Insights using the deep analysis
                // We overwrite the basic summary with the one from our analyzer
                $summaryGenerator = new \Coderflex\QueryLens\ExplainAnalyzer\Formatter\CompactFormatter();
                // Extract just the summary line from compact formatter (it's the first line)
                $compactStats = explode("\n", $summaryGenerator->format($analysisResult))[0] ?? '';
                
                // Combine health status with time/rows
                $healthStatus = $analysisResult->getHealthStatus();
                $healthMsg = match($healthStatus) {
                    'critical' => 'Critical issues detected.',
                    'warning' => 'Performance warnings found.',
                    'needs_attention' => 'Some optimization opportunities identified.',
                    'good' => 'Query appears healthy.',
                    default => 'Analysis complete.'
                };
                
                $humanized['summary'] = "{$healthMsg} {$compactStats}";

                // 3. Add Deep Analysis Issues to Insights
                $deepIssues = [];
                foreach ($analysisResult->getIssuesBySeverity() as $issue) {
                    $severity = strtoupper($issue->getSeverity()->value);
                    $deepIssues[] = "{$issue->getSeverityEmoji()} **{$issue->getTitle()}** ({$severity}): {$issue->getMessage()}";
                }
                
                if (!empty($deepIssues)) {
                    // Prepend deep issues to existing standard insights
                    $humanized['insights'] = array_merge($deepIssues, $humanized['insights']);
                }

            } catch (\Exception $e) {
                // Fallback to raw output on error, log it
                \Illuminate\Support\Facades\Log::warning('Deep Explain Analyzer failed: ' . $e->getMessage());
            }
        }

        // Detect if the result is a tree (for backward compatibility)
        $isStandardTree = count((array) ($standardResult[0] ?? [])) === 1;

        return response()->json([
            'standard' => array_values((array) $standardResult),
            'analyze' => array_values((array) $analyzeResult),
            'raw_analyze' => $rawAnalyze ?? null,
            'supports_analyze' => $supportsAnalyze,
            'summary' => $humanized['summary'] ?? 'No summary available.',
            'insights' => array_values($humanized['insights'] ?? []),
            // BACKWARD COMPATIBILITY: Force old JS to render trees correctly
            'result' => !empty($analyzeResult) ? $analyzeResult : $standardResult,
            'type' => (!empty($analyzeResult) || $isStandardTree) ? 'analyze' : 'standard',
        ]);
    }

    protected function humanizeExplain(array $standard, array $analyze): array
    {
        $insights = [];
        $summaryParts = [];
        
        if (empty($standard)) {
            return ['summary' => 'No execution plan data was returned from the database.', 'insights' => []];
        }

        foreach ($standard as $row) {
            $row = (array) $row;
            if (empty($row)) continue;

            // Handle tree format (Standard EXPLAIN in some MySQL 8 configs)
            if (count($row) === 1 && !isset($row['type'])) {
                $treePlan = (string) (reset($row) ?: '');
                if (str_contains($treePlan, 'Table scan') || str_contains($treePlan, 'Full scan')) {
                    $summaryParts[] = "The database is performing a **Full Table Scan**.";
                    $insights[] = "❌ **Full Table Scan**: Database is checking every single row because no suitable index was found.";
                }
                if (str_contains($treePlan, 'Index lookup') || str_contains($treePlan, 'Index scan')) {
                    $summaryParts[] = "The database is using an **Index** to look up data.";
                    $insights[] = "✅ **Index Used**: The query is using an index for filtering.";
                }
                continue;
            }

            $type = $row['type'] ?? '';
            $extra = $row['Extra'] ?? '';
            $key = $row['key'] ?? '';
            $rows = $row['rows'] ?? 'unknown number of';
            $table = $row['table'] ?? 'your table';

            // 1. Access Method
            if ($type === 'ALL') {
                $summaryParts[] = "The database is performing a **Full Table Scan** on `$table`.";
                $insights[] = "❌ **Full Table Scan**: Database is checking every single row because no suitable index was found.";
            } elseif ($key) {
                $summaryParts[] = "The database is using the **`$key` index** to look up data in `$table`.";
                $insights[] = "✅ **Index Used**: The query is efficiently filtered using the `$key` index.";
            }

            // 2. Volume
            if ($type === 'ALL' || (is_numeric($rows) && $rows > 1000)) {
                 $summaryParts[] = "It expects to scan approximately **$rows rows** to resolve this part of the query.";
            }

            // 3. Overheads
            if (str_contains($extra, 'Using filesort')) {
                $summaryParts[] = "It is also performing a **Filesort**, meaning results are being sorted in memory or on disk.";
                $insights[] = "🐌 **Filesort**: Consider adding an index on your `ORDER BY` columns to avoid expensive memory/disk sorting.";
            }

            if (str_contains($extra, 'Using temporary')) {
                $summaryParts[] = "An **Internal Temporary Table** is being created to resolve this query.";
                $insights[] = "🛠️ **Temporary Table**: This is often caused by complex GROUP BY or DISTINCT operations. Efficiency could be improved.";
            }
        }

        // Only add generic analyze insights if we haven't already processed them in the main logic
        // (This function is still useful for the standard explain fallback)
        if (!empty($analyze)) {
            $firstRow = (array) ($analyze[0] ?? []);
            $plan = (string) (reset($firstRow) ?: '');
            if ($plan && str_contains($plan, 'disk')) {
                $insights[] = "🔥 **Disk I/O**: The profiler detected that temporary data was written to disk, which is a major performance killer.";
            }
        }

        $summary = !empty($summaryParts) 
            ? implode(' ', array_unique($summaryParts)) . "."
            : "The database is resolving this query using standard index lookups. No major performance red flags were detected during optimization.";

        return [
            'summary' => $summary,
            'insights' => array_unique($insights)
        ];
    }

    public function dashboard(): \Illuminate\Http\Response
    {
        $response = response()->view('query-analyzer::dashboard', [
            'stats' => $this->analyzer->getStats(),
            'isEnabled' => config('query-lens.enabled', false),
        ]);
        
        return $this->noCacheResponse($response); // Force browser to reload JS
    }

    public function queries(Request $request): JsonResponse
    {
        $queries = $this->analyzer->getQueries();

        // Filter by Request ID if provided
        if ($requestId = $request->query('request_id')) {
            $queries = $queries->where('request_id', $requestId)->values();
        }

        // Apply shared filters
        $queries = $this->applyFilters($queries, $request);

        // Apply sorting
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
        // Aggregate/Group queries by Request ID efficiently
        // We do NOT filter before grouping so that we preserve the request list
        $requests = $this->analyzer->getQueries()
            ->groupBy('request_id')
            ->map(function ($group) use ($request) {
                $first = $group->first();
                
                // Analyze the relevant queries for this request based on filters
                $filtered = $this->applyFilters($group, $request);
                
                return [
                    'request_id' => $first['request_id'],
                    'method' => $first['request_method'] ?? 'UNKNOWN',
                    'path' => $first['request_path'] ?? 'terminal',
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

    protected function applyFilters($queries, Request $request)
    {
        // Apply filters
        if ($type = $request->query('type')) {
            $queries = $queries->filter(fn($q) => strtolower($q['analysis']['type']) === $type)->values();
        }

        if ($rating = $request->query('rating')) {
            $queries = $queries->filter(fn($q) => ($q['analysis']['performance']['rating'] ?? 'unknown') === $rating)->values();
        }

        // Filter by issue type
        if ($issueType = $request->query('issue_type')) {
            $queries = $queries->filter(function ($q) use ($issueType) {
                $issues = $q['analysis']['issues'] ?? [];
                if (empty($issues)) return false;
                
                // Check if any issue matches the requested type
                foreach ($issues as $issue) {
                    if (strtolower($issue['type']) === strtolower($issueType)) {
                        return true;
                    }
                }
                return false;
            })->values();
        }

        // Legacy slow_only filter
        if ($request->has('slow_only') && $request->boolean('slow_only')) {
            $slowThreshold = config('query-lens.performance_thresholds.slow', 1.0);
            $queries = $queries->where('time', '>', $slowThreshold);
        }

        return $queries;
    }

    public function query(Request $request, string $id): JsonResponse
    {
        return $this->noCacheResponse($this->queryLogic($request, $id));
    }

    protected function queryLogic(Request $request, string $id): JsonResponse
    {
        $rawQueries = $this->analyzer->getQueries();
        
        $query = $rawQueries->firstWhere('id', $id);

        if (!$query) {
             return response()->json(['error' => 'Query not found. ID: ' . $id], 404);
        }

        return response()->json($query);
    }

    protected function noCacheResponse($response)
    {
        return $response->withHeaders([
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
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

        if ($format === 'csv') {
            $csv = "Index,Type,Time,Performance,Complexity,Issues,SQL\n";
            foreach ($queries as $index => $query) {
                $analysis = $query['analysis'];
                $csv .= sprintf(
                    "%d,%s,%.3f,%s,%s,%d,\"%s\"\n",
                    $index + 1,
                    $analysis['type'],
                    $query['time'],
                    $analysis['performance']['rating'],
                    $analysis['complexity']['level'],
                    count($analysis['issues']),
                    str_replace('"', '""', $query['sql'])
                );
            }

            return response()->json(['data' => $csv, 'filename' => 'query-analysis-' . date('Y-m-d-H-i-s') . '.csv']);
        }

        return response()->json([
            'data' => $queries->toArray(),
            'stats' => $this->analyzer->getStats(),
            'filename' => 'query-analysis-' . date('Y-m-d-H-i-s') . '.json'
        ]);
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

        // Build timeline data
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

        return $this->noCacheResponse(response()->json([
            'request_id' => $id,
            'queries' => $queries,
            'timeline_data' => $timelineData,
            'total_queries' => count($queries),
            'total_time' => array_sum(array_column($queries, 'time')),
        ]));
    }

    public function overview(): JsonResponse
    {
        $aggregationService = app(AggregationService::class);
        $overview = $aggregationService->getOverviewStats();

        return $this->noCacheResponse(response()->json($overview));
    }

    public function poll(Request $request): JsonResponse
    {
        $since = (float) $request->query('since', 0);

        $newQueries = $this->storage->getQueriesSince($since, 50);
        $stats = $this->analyzer->getStats();

        // Get recent alerts if using database storage
        $alerts = [];
        if ($this->storage->supportsPersistence()) {
            $alertService = app(\Coderflex\QueryLens\Services\AlertService::class);
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

    public function storageInfo(): JsonResponse
    {
        $retentionService = app(\Coderflex\QueryLens\Services\DataRetentionService::class);

        return response()->json([
            'driver' => config('query-lens.storage.driver', 'cache'),
            'supports_persistence' => $this->storage->supportsPersistence(),
            'stats' => $this->storage->supportsPersistence()
                ? $retentionService->getStorageStats()
                : null,
        ]);
    }
}