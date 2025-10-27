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

    public function dashboard(): View
    {
        return view('query-analyzer::dashboard', [
            'stats' => $this->analyzer->getStats(),
            'isEnabled' => config('query-analyzer.enabled', false),
        ]);
    }

    public function queries(Request $request): JsonResponse
    {
        $queries = $this->analyzer->getQueries();

        if ($request->has('slow_only') && $request->boolean('slow_only')) {
            $slowThreshold = config('query-analyzer.performance_thresholds.slow', 1.0);
            $queries = $queries->where('time', '>', $slowThreshold);
        }

        if ($request->has('type') && $request->type !== 'all') {
            $queries = $queries->where('analysis.type', strtoupper($request->type));
        }

        if ($request->has('limit')) {
            $queries = $queries->take((int) $request->limit);
        }

        return response()->json([
            'queries' => $queries->values(),
            'stats' => $this->analyzer->getStats(),
        ]);
    }

    public function query(Request $request, int $index): JsonResponse
    {
        $queries = $this->analyzer->getQueries();

        if (!isset($queries[$index])) {
            return response()->json(['error' => 'Query not found'], 404);
        }

        return response()->json($queries[$index]);
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