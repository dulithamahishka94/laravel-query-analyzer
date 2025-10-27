<?php

namespace Laravel\QueryAnalyzer;

use Illuminate\Support\Collection;

class QueryAnalyzer
{
    protected array $config;
    protected Collection $queries;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->queries = collect();
    }

    public function recordQuery(string $sql, array $bindings = [], float $time = 0.0, string $connection = 'default'): void
    {
        $minTime = $this->config['analysis']['min_execution_time'] ?? 0.001;
        if ($time < $minTime) {
            return;
        }

        $this->queries->push([
            'sql' => $sql,
            'bindings' => $bindings,
            'time' => $time,
            'connection' => $connection,
            'timestamp' => microtime(true),
            'analysis' => $this->analyzeQuery($sql, $bindings, $time),
        ]);

        $maxQueries = $this->config['analysis']['max_queries'] ?? 1000;
        if ($this->queries->count() > $maxQueries) {
            $this->queries = $this->queries->slice(-$maxQueries);
        }
    }

    public function analyzeQuery(string $sql, array $bindings = [], float $time = 0.0): array
    {
        return [
            'type' => $this->getQueryType($sql),
            'performance' => $this->analyzePerformance($sql, $time),
            'complexity' => $this->analyzeComplexity($sql),
            'recommendations' => $this->getRecommendations($sql, $time),
            'issues' => $this->detectIssues($sql, $bindings),
        ];
    }

    protected function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));

        if (str_starts_with($sql, 'SELECT')) return 'SELECT';
        if (str_starts_with($sql, 'INSERT')) return 'INSERT';
        if (str_starts_with($sql, 'UPDATE')) return 'UPDATE';
        if (str_starts_with($sql, 'DELETE')) return 'DELETE';
        if (str_starts_with($sql, 'CREATE')) return 'CREATE';
        if (str_starts_with($sql, 'ALTER')) return 'ALTER';
        if (str_starts_with($sql, 'DROP')) return 'DROP';

        return 'OTHER';
    }

    protected function analyzePerformance(string $sql, float $time): array
    {
        $thresholds = $this->config['performance_thresholds'] ?? [
            'fast' => 0.1,
            'moderate' => 0.5,
            'slow' => 1.0,
        ];

        $rating = 'very_slow';
        if ($time <= $thresholds['fast']) {
            $rating = 'fast';
        } elseif ($time <= $thresholds['moderate']) {
            $rating = 'moderate';
        } elseif ($time <= $thresholds['slow']) {
            $rating = 'slow';
        }

        return [
            'execution_time' => $time,
            'rating' => $rating,
            'is_slow' => $time > $thresholds['slow'],
        ];
    }

    protected function analyzeComplexity(string $sql): array
    {
        $sql = strtoupper($sql);

        $joinCount = substr_count($sql, 'JOIN');
        $subqueryCount = substr_count($sql, 'SELECT') - 1; // Subtract main SELECT
        $conditionCount = substr_count($sql, 'WHERE') + substr_count($sql, 'HAVING');
        $orderByCount = substr_count($sql, 'ORDER BY');
        $groupByCount = substr_count($sql, 'GROUP BY');

        $complexityScore = $joinCount * 2 + $subqueryCount * 3 + $conditionCount + $orderByCount + $groupByCount;

        $complexity = 'low';
        if ($complexityScore > 10) {
            $complexity = 'high';
        } elseif ($complexityScore > 5) {
            $complexity = 'medium';
        }

        return [
            'score' => $complexityScore,
            'level' => $complexity,
            'joins' => $joinCount,
            'subqueries' => $subqueryCount,
            'conditions' => $conditionCount,
        ];
    }

    protected function getRecommendations(string $sql, float $time): array
    {
        $recommendations = [];
        $sql = strtoupper($sql);

        if ($time > ($this->config['performance_thresholds']['slow'] ?? 1.0)) {
            $recommendations[] = 'Consider optimizing this query as it exceeds the slow threshold';
        }

        if (str_contains($sql, 'SELECT *')) {
            $recommendations[] = 'Avoid SELECT * - specify only needed columns';
        }

        if (str_contains($sql, 'ORDER BY') && !str_contains($sql, 'LIMIT')) {
            $recommendations[] = 'Consider adding LIMIT when using ORDER BY';
        }

        if (substr_count($sql, 'JOIN') > 3) {
            $recommendations[] = 'Query has many JOINs - consider breaking into smaller queries';
        }

        if (str_contains($sql, 'LIKE') && !str_contains($sql, 'INDEX')) {
            $recommendations[] = 'LIKE queries can be slow - ensure proper indexing';
        }

        return $recommendations;
    }

    protected function detectIssues(string $sql, array $bindings): array
    {
        $issues = [];
        $sql = strtoupper($sql);

        if (str_contains($sql, 'OR')) {
            $issues[] = ['type' => 'performance', 'message' => 'OR conditions can prevent index usage'];
        }

        if (str_contains($sql, 'FUNCTION(')) {
            $issues[] = ['type' => 'performance', 'message' => 'Functions in WHERE clause prevent index usage'];
        }

        if (str_contains($sql, 'SELECT *') && str_contains($sql, 'JOIN')) {
            $issues[] = ['type' => 'efficiency', 'message' => 'SELECT * with JOINs can return unnecessary data'];
        }

        if (empty($bindings) && preg_match('/[\'"][^\'"]*(DELETE|UPDATE|INSERT)[^\']*[\'"]/i', $sql)) {
            $issues[] = ['type' => 'security', 'message' => 'Potential SQL injection vulnerability - use parameter binding'];
        }

        return $issues;
    }

    public function getQueries(): Collection
    {
        return $this->queries;
    }

    public function getStats(): array
    {
        if ($this->queries->isEmpty()) {
            return [
                'total_queries' => 0,
                'total_time' => 0,
                'average_time' => 0,
                'slow_queries' => 0,
                'query_types' => [],
            ];
        }

        $totalTime = $this->queries->sum('time');
        $slowThreshold = $this->config['performance_thresholds']['slow'] ?? 1.0;

        return [
            'total_queries' => $this->queries->count(),
            'total_time' => $totalTime,
            'average_time' => $totalTime / $this->queries->count(),
            'slow_queries' => $this->queries->where('time', '>', $slowThreshold)->count(),
            'query_types' => $this->queries->groupBy('analysis.type')->map->count()->toArray(),
        ];
    }

    public function reset(): void
    {
        $this->queries = collect();
    }
}