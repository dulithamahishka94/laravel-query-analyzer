<?php

namespace GladeHQ\QueryLens;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\DTOs\ComplexityAnalysis;
use GladeHQ\QueryLens\DTOs\PerformanceRating;
use GladeHQ\QueryLens\DTOs\QueryAnalysisResult;
use GladeHQ\QueryLens\DTOs\QueryType;
use GladeHQ\QueryLens\Support\SqlNormalizer;

class QueryAnalyzer
{
    protected array $config;
    protected QueryStorage $storage;
    protected ?string $requestId = null;
    protected ?string $currentTransactionId = null;
    protected array $queryStructures = [];
    protected bool $enabled = true;
    protected ?bool $sampledForRequest = null;

    public function __construct(array $config, QueryStorage $storage)
    {
        $this->config = $config;
        $this->storage = $storage;
    }

    public function disableRecording(): void
    {
        $this->enabled = false;
    }

    public function enableRecording(): void
    {
        $this->enabled = true;
    }

    public function isRecording(): bool
    {
        return $this->enabled;
    }

    public function setRequestId(string $id): void
    {
        $this->requestId = $id;
        $this->queryStructures = []; // Clear structure tracking for new request
        $this->sampledForRequest = $this->decideSampling();
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function setCurrentTransactionId(?string $transactionId): void
    {
        $this->currentTransactionId = $transactionId;
    }

    public function getCurrentTransactionId(): ?string
    {
        return $this->currentTransactionId;
    }

    public function isSampled(): bool
    {
        // Lazy-evaluate for CLI or contexts where setRequestId was never called
        if ($this->sampledForRequest === null) {
            $this->sampledForRequest = $this->decideSampling();
        }

        return $this->sampledForRequest;
    }

    protected function decideSampling(): bool
    {
        $rate = (float) ($this->config['sampling_rate'] ?? 1.0);

        if ($rate <= 0.0) {
            return false;
        }

        if ($rate >= 1.0) {
            return true;
        }

        return mt_rand(1, 10000) / 10000 <= $rate;
    }

    public function recordQuery(string $sql, array $bindings = [], float $time = 0.0, string $connection = 'default'): void
    {
        if (!$this->enabled || !$this->isSampled()) {
            return;
        }

        // 1. Ignore ALL queries targeting Query Lens's own tables (database driver)
        // and cache keys (cache driver). This is the primary defense against
        // self-observation -- the package must never record its own internal queries.
        $tablePrefix = $this->config['storage']['table_prefix'] ?? 'query_lens_';
        if (str_contains($sql, $tablePrefix)) {
            return;
        }

        // Also check the cache driver's key in both SQL and bindings, as
        // database-backed cache drivers (e.g. Redis via DB) use parameter binding.
        if (str_contains($sql, 'laravel_query_lens_')) {
            return;
        }

        // 1.1 Strictly ignore EXPLAIN queries to prevent recursion loops or noise
        if (str_starts_with(strtoupper(trim($sql)), 'EXPLAIN')) {
            return;
        }

        // Also check bindings for internal cache/table references
        foreach ($bindings as $binding) {
            if (is_string($binding) && (str_contains($binding, $tablePrefix) || str_contains($binding, 'laravel_query_lens_'))) {
                return;
            }
        }

        // 1.2 Check configurable excluded patterns
        if ($this->matchesExcludedPattern($sql)) {
            return;
        }

        // 2. Ignore ALL queries when we are currently on a Query Lens route.
        // Recording is disabled at the container-resolution level (see
        // QueryLensServiceProvider) and again by AnalyzeQueryMiddleware, but
        // this serves as a final safety net in case state was corrupted (e.g.
        // by a third-party package re-enabling recording during the request).
        try {
            $path = request()->getPathInfo();
            if (str_contains($path, '/query-lens')) {
                return;
            }
        } catch (\Throwable) {
            // request() may throw outside HTTP context; ignore gracefully.
        }

        $minTime = $this->config['analysis']['min_execution_time'] ?? 0.001;
        if ($time < $minTime) {
            return;
        }

        // Track query structure for N+1 detection
        $structureHash = $this->getStructureHash($sql);
        $this->queryStructures[$structureHash] = ($this->queryStructures[$structureHash] ?? 0) + 1;
        $isPotentialNPlusOne = $this->queryStructures[$structureHash] > 1;

        $currentRequest = request();
        $route = $currentRequest->route();

        $query = [
            'id' => (string) Str::orderedUuid(),
            'request_id' => $this->requestId ?? 'cli-' . getmypid(),
            'transaction_id' => $this->currentTransactionId,
            'request_path' => $currentRequest->path(),
            'request_method' => $currentRequest->method(),
            'route_name' => $route?->getName(),
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'connection' => $connection,
            'timestamp' => microtime(true),
            'analysis' => $this->analyzeWithNPlusOne($sql, $bindings, $time, $isPotentialNPlusOne),
            'origin' => $this->findOrigin(),
        ];

        $this->storage->store($query);
    }

    public function recordCacheInteraction(string $type, string $key, array $tags = [], mixed $value = null): void
    {
        if (!$this->enabled || !$this->isSampled()) {
            return;
        }

        // Ignore analyzer's own cache keys (covers all internal cache operations)
        if (str_contains($key, 'laravel_query_lens_')) {
            return;
        }

        $currentRequest = request();
        $route = $currentRequest->route();

        $event = [
            'id' => (string) Str::orderedUuid(),
            'request_id' => $this->requestId ?? 'cli-' . getmypid(),
            'request_path' => $currentRequest->path(),
            'request_method' => $currentRequest->method(),
            'route_name' => $route?->getName(),
            'sql' => "CACHE " . strtoupper($type) . ": " . $key, // Pseudo-SQL for display
            'bindings' => ['key' => $key, 'tags' => $tags],
            'time' => 0.0001, // Minimal time for visualization
            'connection' => 'cache',
            'timestamp' => microtime(true),
            'analysis' => [
                'type' => 'CACHE',
                'performance' => ['rating' => 'fast', 'execution_time' => 0.0001],
                'complexity' => ['score' => 0, 'level' => 'low'],
                'recommendations' => [],
                'issues' => [],
                'cache_info' => [
                    'type' => $type, // 'hit' or 'miss'
                    'key' => $key,
                    'tags' => $tags,
                    'size' => $value ? strlen(serialize($value)) : 0,
                ]
            ],
            'origin' => $this->findOrigin(),
        ];

        $this->storage->store($event);
    }

    protected function matchesExcludedPattern(string $sql): bool
    {
        $patterns = $this->config['excluded_patterns'] ?? [];

        foreach ($patterns as $pattern) {
            if (stripos($sql, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function analyzeWithNPlusOne(string $sql, array $bindings, float $time, bool $isNPlusOne): array
    {
        $analysis = $this->analyzeQuery($sql, $bindings, $time);
        
        if ($isNPlusOne) {
            $analysis['issues'][] = [
                'type' => 'n+1',
                'message' => 'Potential N+1 Query detected. This query structure has been executed ' . $this->queryStructures[$this->getStructureHash($sql)] . ' times in this request.'
            ];
            $analysis['recommendations'][] = 'Ensure you are using Eager Loading (with()) to avoid N+1 issues.';
        }
        
        return $analysis;
    }

    protected function getStructureHash(string $sql): string
    {
        return SqlNormalizer::hash($sql);
    }

    protected function findOrigin(): array
    {
        if (!($this->config['trace_origins'] ?? true)) {
            return ['file' => 'unknown', 'line' => 0, 'is_vendor' => false];
        }

        $limit = (int) ($this->config['backtrace_limit'] ?? 30);
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit);
        $packageRoot = realpath(__DIR__ . '/..');

        // Find the first frame that is not part of the framework or this package
        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            $file = realpath($frame['file']);

            // Skip the package itself
            if ($file && str_starts_with($file, $packageRoot)) {
                continue;
            }

            // Skip Laravel framework and common internal paths
            if (str_contains($file, '/vendor/laravel/') ||
                str_contains($file, '/vendor/illuminate/') ||
                str_contains($file, '/storage/framework/')) {
                continue;
            }

            return [
                'file' => $frame['file'],
                'line' => $frame['line'] ?? 0,
                'is_vendor' => str_contains(realpath($frame['file']), '/vendor/'),
            ];
        }

        return ['file' => 'unknown', 'line' => 0, 'is_vendor' => false];
    }

    public function analyzeQuery(string $sql, array $bindings = [], float $time = 0.0): array
    {
        return $this->buildAnalysisResult($sql, $bindings, $time)->toArray();
    }

    protected function buildAnalysisResult(string $sql, array $bindings, float $time): QueryAnalysisResult
    {
        $thresholds = $this->config['performance_thresholds'] ?? [
            'fast' => 0.1,
            'moderate' => 0.5,
            'slow' => 1.0,
        ];

        $weights = $this->config['complexity_weights'] ?? [];

        return new QueryAnalysisResult(
            type: QueryType::fromSql($sql),
            performance: PerformanceRating::fromTime($time, $thresholds),
            executionTime: $time,
            complexity: ComplexityAnalysis::analyze($sql, $weights),
            recommendations: $this->getRecommendations($sql, $time),
            issues: $this->detectIssues($sql, $bindings),
        );
    }

    protected function getRecommendations(string $sql, float $time): array
    {
        $recommendations = [];
        $sqlUpper = strtoupper($sql);

        // Performance Threshold Check
        if ($time > ($this->config['performance_thresholds']['slow'] ?? 1.0)) {
            $recommendations[] = 'Query is SLOW (' . $time . 's). Consider optimization.';
            
            // Suggest Indexes for slow queries
            $indexSuggestions = $this->suggestIndexes($sql);
            foreach ($indexSuggestions as $suggestion) {
                $recommendations[] = $suggestion;
            }
        }

        // 1. SELECT *
        if (str_contains($sqlUpper, 'SELECT *')) {
            $recommendations[] = 'Avoid "SELECT *" - select only necessary columns to reduce data transfer.';
        }

        // 2. ORDER BY RAND() - Critical
        if (str_contains($sqlUpper, 'ORDER BY RAND()')) {
            $recommendations[] = 'CRITICAL: "ORDER BY RAND()" kills performance on large tables. Use application-level shuffling or key-based random selection.';
        }

        // 3. Leading Wildcard LIKE
        if (preg_match('/LIKE\s*[\'"]%/', $sqlUpper)) {
            $recommendations[] = 'Leading wildcard in LIKE ("%value") prevents index usage. Consider full-text search.';
        }

        // 4. Large OFFSET (Deep Pagination)
        if (preg_match('/OFFSET\s+(\d+)/i', $sqlUpper, $matches)) {
            $offset = (int)$matches[1];
            if ($offset > 1000) {
                $recommendations[] = "Deep pagination (OFFSET $offset) is slow. Consider keyset pagination (cursor-based) or caching.";
            }
        }

        // 5. Negative Operators
        if (preg_match('/\s(!=|<>|NOT\s+IN)\s/', $sqlUpper)) {
            $recommendations[] = 'Negative operators (!=, <>, NOT IN) typically cannot use indices. rewrite with positive conditions if possible.';
        }

        // 6. Large IN() lists
        if (preg_match_all('/,\s*/', $sqlUpper, $matches) > 50 && str_contains($sqlUpper, 'IN (')) {
             // Heuristic: if many commas and IN clause
            $recommendations[] = 'Large IN() lists can be slow to parse and execute. Consider using a temporary table or JOIN.';
        }

        // 7. Missing LIMIT with ORDER BY
        if (str_contains($sqlUpper, 'ORDER BY') && !str_contains($sqlUpper, 'LIMIT')) {
            $recommendations[] = 'Sorting (ORDER BY) without LIMIT can be resource intensive.';
        }

        // 8. Too many JOINs
        if (substr_count($sqlUpper, 'JOIN') > 3) {
            $recommendations[] = 'Complex query with > 3 JOINs. Monitor performance closely; consider breaking it down.';
        }

        return $recommendations;
    }

    protected function detectIssues(string $sql, array $bindings): array
    {
        $issues = [];
        $sqlUpper = strtoupper($sql);

        // 1. OR Usage
        if (str_contains($sqlUpper, ' OR ')) {
             // Improve heuristic: warn generally about OR
            $issues[] = ['type' => 'performance', 'message' => 'OR conditions may invalidate index usage. Check EXPLAIN plan.'];
        }

        // 2. Functions in WHERE (Non-sargable)
        if (preg_match('/WHERE\s+.*\b(UPPER|LOWER|DATE|YEAR|MONTH|DAY|SUBSTRING|TRIM|LENGTH|CAST)\s*\(/i', $sqlUpper)) {
            $issues[] = ['type' => 'performance', 'message' => 'Functions in WHERE clause (e.g. DATE(col)) prevent index usage and force full table scans.'];
        }

        // 3. HAVING without Aggregates
        // Heuristic: HAVING used without standard aggregates (SUM, COUNT, AVG, MAX, MIN) usually belongs in WHERE
        if (str_contains($sqlUpper, 'HAVING') && !preg_match('/\b(SUM|COUNT|AVG|MAX|MIN)\s*\(/', $sqlUpper)) {
            $issues[] = ['type' => 'efficiency', 'message' => 'HAVING clause without aggregates found. This usually should be a WHERE clause for better performance.'];
        }

        // 4. Eager Loading N+1 Check (Heuristic)
        // This is hard to detect in a single query analysis, but we can look for "SELECT ... WHERE id IN (...)" with small lists which might indicate lazy loading loops if repeated. 
        // We'll skip this single-query distinct check for now as it needs context.

        // 5. SELECT * with JOIN
        if (str_contains($sqlUpper, 'SELECT *') && str_contains($sqlUpper, 'JOIN')) {
            $issues[] = ['type' => 'efficiency', 'message' => 'SELECT * with JOINs returns duplicate/unused usage data from joined tables.'];
        }

        // 6. SQL Injection Detection
        if (empty($bindings) && preg_match('/[\'"][^\'"]*(DELETE|UPDATE|INSERT)[^\']*[\'"]/i', $sqlUpper)) {
             // Only flag if it looks like raw values are embedded
             if (preg_match('/=\s*[\'"]\w+[\'"]/', $sql)) {
                 $issues[] = ['type' => 'security', 'message' => 'Potential Raw SQL value injection. Use parameter binding (?) instead of embedding strings.'];
             }
        }

        return $issues;
    }

    protected function suggestIndexes(string $sql): array 
    {
        $suggestions = [];
        // regex to extract table and potentially column from WHERE clause
        // Basic match for "FROM table" or "JOIN table"
        // This is a naive parser. 
        
        $sql = str_replace('`', '', $sql); // Remove backticks for easier parsing

        // Find probable table name
        preg_match('/FROM\s+(\w+)/i', $sql, $tableMatch);
        $table = $tableMatch[1] ?? 'unknown_table';

        // Extract columns in WHERE simple equality or IN
        // matches "col =" or "col IN" or "col >"
        preg_match_all('/(\w+)\s*(=|>|<|IN|LIKE)/i', $sql, $whereMatches);

        if (!empty($whereMatches[1])) {
             $columns = array_unique($whereMatches[1]);
             // Filter out keywords just in case
             $columns = array_filter($columns, fn($c) => !in_array(strtoupper($c), ['AND', 'OR', 'NOT', 'WHERE', 'JOIN', 'ON', 'LIMIT']));
             
             if (!empty($columns)) {
                 $colList = implode(', ', array_slice($columns, 0, 3)); // suggest first 3 columns
                 $suggestions[] = "Consider adding an INDEX on table `$table` columns: ($colList).";
             }
        }

        // Extract ORDER BY column
        if (preg_match('/ORDER\s+BY\s+(\w+)/i', $sql, $orderMatch)) {
            $sortCol = $orderMatch[1];
             $suggestions[] = "Consider adding an INDEX on `$table` column: ($sortCol) for sorting.";
        }
        
        return $suggestions;
    }

    public function getQueries(): Collection
    {
        return collect($this->storage->get(10000));
    }

    public function getStats(): array
    {
        $queries = $this->getQueries();
        
        if ($queries->isEmpty()) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'slow_queries' => 0,
                'query_types' => [],
            ];
        }

        $totalTime = $queries->sum('time');
        $slowThreshold = $this->config['performance_thresholds']['slow'] ?? 1.0;

        return [
            'total_queries' => $queries->count(),
            'total_time' => $totalTime,
            'average_time' => $totalTime / $queries->count(),
            'slow_queries' => $queries->where('time', '>', $slowThreshold)->count(),
            'query_types' => $queries->groupBy('analysis.type')->map->count()->toArray(),
        ];
    }

    public function reset(): void
    {
        $this->storage->clear();
    }
}