<?php

namespace Laravel\QueryAnalyzer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Laravel\QueryAnalyzer\QueryAnalyzer;

class QueryAnalyzerController extends Controller
{
    protected QueryAnalyzer $analyzer;

    public function __construct(QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
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

        $humanized = $this->humanizeExplain((array) $standardResult, (array) $analyzeResult);

        // Detect if the result is a tree (for backward compatibility)
        $isStandardTree = count((array) ($standardResult[0] ?? [])) === 1;

        return response()->json([
            'standard' => array_values((array) $standardResult),
            'analyze' => array_values((array) $analyzeResult),
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

        // Analyze specific keywords for MySQL 8+ Tree
        if (!empty($analyze)) {
            $firstRow = (array) ($analyze[0] ?? []);
            $plan = (string) (reset($firstRow) ?: '');
            if ($plan && str_contains($plan, 'disk')) {
                $insights[] = "🔥 **Disk I/O**: The profiler detected that temporary data was written to disk, which is a major performance killer.";
            }
            if ($plan && (str_contains($plan, 'Table scan') || str_contains($plan, 'Full scan')) && empty($summaryParts)) {
                 $insights[] = "❌ **Full Table Scan**: The profiler confirmed a full scan is occurring.";
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
            'isEnabled' => config('query-analyzer.enabled', false),
        ]);
        
        return $this->noCacheResponse($response); // Force browser to reload JS
    }

    public function queries(Request $request): JsonResponse
    {
        return $this->noCacheResponse($this->queriesLogic($request));
    }

    protected function queriesLogic(Request $request): JsonResponse
    {
        $queries = $this->analyzer->getQueries();

        // Filtering by type
        if ($request->has('type') && $request->type !== 'all') {
            $queries = $queries->where('analysis.type', strtoupper($request->type));
        }

        // Filtering by performance rating
        if ($request->has('rating') && $request->rating !== 'all') {
            $queries = $queries->where('analysis.performance.rating', $request->rating);
        }

        // Legacy slow_only filter (kept for backward compatibility)
        if ($request->has('slow_only') && $request->boolean('slow_only')) {
            $slowThreshold = config('query-analyzer.performance_thresholds.slow', 1.0);
            $queries = $queries->where('time', '>', $slowThreshold);
        }

        // Sorting
        $sort = $request->input('sort', 'timestamp');
        $order = $request->input('order', 'desc');

        if ($sort === 'time') {
            $queries = $order === 'asc' ? $queries->sortBy('time') : $queries->sortByDesc('time');
        } elseif ($sort === 'complexity') {
            $queries = $order === 'asc' ? $queries->sortBy('analysis.complexity.score') : $queries->sortByDesc('analysis.complexity.score');
        } else {
            $queries = $order === 'asc' ? $queries->sortBy('timestamp') : $queries->sortByDesc('timestamp');
        }

        if ($request->has('limit')) {
            $queries = $queries->take((int) $request->limit);
        }

        return response()->json([
            'queries' => $queries->values(),
            'stats' => $this->analyzer->getStats(),
        ]);
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
}