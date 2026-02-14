<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use GladeHQ\QueryLens\Contracts\QueryStorage;

class IndexAdvisor
{
    protected ?QueryStorage $storage = null;

    public function setStorage(QueryStorage $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Analyze a single query and suggest indexes.
     *
     * @return array{suggestions: array, sql: string, analysis: array}
     */
    public function analyzeQuery(string $sql, ?array $explainResult = null): array
    {
        $cleanSql = str_replace('`', '', $sql);
        $candidates = $this->extractCandidateColumns($cleanSql);
        $tables = $this->extractTables($cleanSql);
        $suggestions = [];

        // Analyze EXPLAIN output when available
        $explainInsights = [];
        if ($explainResult) {
            $explainInsights = $this->analyzeExplainResult($explainResult);
        }

        // Build index suggestions from column candidates
        foreach ($tables as $table) {
            $tableCandidates = $this->getColumnsForTable($candidates, $table, $cleanSql);

            if (empty($tableCandidates)) {
                continue;
            }

            // WHERE + equality columns make the best composite index candidates
            $whereCols = array_filter($tableCandidates, fn($c) => $c['source'] === 'where');
            $joinCols = array_filter($tableCandidates, fn($c) => $c['source'] === 'join');
            $orderCols = array_filter($tableCandidates, fn($c) => $c['source'] === 'order_by');
            $groupCols = array_filter($tableCandidates, fn($c) => $c['source'] === 'group_by');

            // Composite index: WHERE columns + ORDER BY columns
            $compositeColumns = array_unique(array_merge(
                array_column($whereCols, 'column'),
                array_column($groupCols, 'column'),
                array_column($orderCols, 'column')
            ));

            if (count($compositeColumns) > 1) {
                $suggestions[] = $this->buildSuggestion(
                    $table,
                    $compositeColumns,
                    'composite',
                    'Composite index for WHERE/GROUP BY/ORDER BY columns',
                    $this->estimateImpact($explainInsights)
                );
            } elseif (count($compositeColumns) === 1) {
                $suggestions[] = $this->buildSuggestion(
                    $table,
                    $compositeColumns,
                    'single',
                    'Single column index for filter/sort',
                    $this->estimateImpact($explainInsights)
                );
            }

            // JOIN columns get their own index if not already covered
            foreach ($joinCols as $joinCol) {
                $col = $joinCol['column'];
                if (!in_array($col, $compositeColumns)) {
                    $suggestions[] = $this->buildSuggestion(
                        $table,
                        [$col],
                        'join',
                        "Index for JOIN condition on {$col}",
                        'medium'
                    );
                }
            }
        }

        // Add EXPLAIN-specific suggestions
        foreach ($explainInsights as $insight) {
            if (!empty($insight['suggestion'])) {
                $suggestions[] = $insight['suggestion'];
            }
        }

        // Deduplicate suggestions
        $suggestions = $this->deduplicateSuggestions($suggestions);

        return [
            'suggestions' => $suggestions,
            'sql' => $sql,
            'analysis' => [
                'tables' => $tables,
                'candidates' => $candidates,
                'explain_insights' => $explainInsights,
            ],
        ];
    }

    /**
     * Analyze historical query patterns to find the most impactful missing indexes.
     */
    public function analyzePatterns(int $days = 7): array
    {
        if (!$this->storage) {
            return [];
        }

        $topQueries = $this->storage->getTopQueries('slowest', 'week', 20);
        $frequentQueries = $this->storage->getTopQueries('most_frequent', 'week', 20);

        $allQueries = array_merge($topQueries, $frequentQueries);
        $suggestions = [];

        foreach ($allQueries as $queryGroup) {
            $sql = $queryGroup['sql_sample'] ?? '';
            if (empty($sql)) {
                continue;
            }

            $result = $this->analyzeQuery($sql);

            foreach ($result['suggestions'] as $suggestion) {
                $impact = ($queryGroup['count'] ?? 1) * ($queryGroup['avg_time'] ?? 0);
                $suggestion['estimated_impact_score'] = round($impact, 4);
                $suggestion['query_count'] = $queryGroup['count'] ?? 0;
                $suggestion['avg_time'] = $queryGroup['avg_time'] ?? 0;
                $suggestions[] = $suggestion;
            }
        }

        // Sort by impact score descending
        usort($suggestions, fn($a, $b) => ($b['estimated_impact_score'] ?? 0) <=> ($a['estimated_impact_score'] ?? 0));

        // Deduplicate by create_index_sql
        $seen = [];
        $unique = [];
        foreach ($suggestions as $s) {
            $key = $s['create_index_sql'] ?? '';
            if ($key && !isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $s;
            }
        }

        return $unique;
    }

    /**
     * Extract candidate columns from WHERE, JOIN, ORDER BY, GROUP BY clauses.
     */
    protected function extractCandidateColumns(string $sql): array
    {
        $candidates = [];
        $sqlUpper = strtoupper($sql);

        // WHERE clause columns (equality and range conditions)
        if (preg_match_all('/WHERE\s+(.+?)(?:\s+ORDER\s|\s+GROUP\s|\s+LIMIT\s|\s+HAVING\s|$)/is', $sql, $whereMatches)) {
            $whereClause = $whereMatches[1][0] ?? '';
            // Match column = ?, column > ?, column IN (?), column LIKE ?
            if (preg_match_all('/(\w+)\.?(\w+)?\s*(?:=|>|<|>=|<=|!=|<>|IN\s*\(|LIKE|BETWEEN)/i', $whereClause, $colMatches)) {
                foreach ($colMatches[0] as $i => $match) {
                    $col = $colMatches[2][$i] ?: $colMatches[1][$i];
                    $tableAlias = $colMatches[2][$i] ? $colMatches[1][$i] : null;
                    if (!$this->isSqlKeyword($col)) {
                        $candidates[] = [
                            'column' => $col,
                            'table_alias' => $tableAlias,
                            'source' => 'where',
                        ];
                    }
                }
            }
        }

        // JOIN conditions
        if (preg_match_all('/JOIN\s+(\w+)(?:\s+(?:AS\s+)?(\w+))?\s+ON\s+(\w+)\.(\w+)\s*=\s*(\w+)\.(\w+)/i', $sql, $joinMatches, PREG_SET_ORDER)) {
            foreach ($joinMatches as $match) {
                $candidates[] = [
                    'column' => $match[4],
                    'table_alias' => $match[3],
                    'source' => 'join',
                ];
                $candidates[] = [
                    'column' => $match[6],
                    'table_alias' => $match[5],
                    'source' => 'join',
                ];
            }
        }

        // ORDER BY columns
        if (preg_match('/ORDER\s+BY\s+(.+?)(?:\s+LIMIT\s|$)/is', $sql, $orderMatch)) {
            $orderClause = $orderMatch[1];
            if (preg_match_all('/(\w+)\.?(\w+)?(?:\s+(?:ASC|DESC))?/i', $orderClause, $orderCols)) {
                foreach ($orderCols[0] as $i => $match) {
                    $col = $orderCols[2][$i] ?: $orderCols[1][$i];
                    if (!$this->isSqlKeyword($col) && $col !== 'ASC' && $col !== 'DESC') {
                        $candidates[] = [
                            'column' => $col,
                            'table_alias' => $orderCols[2][$i] ? $orderCols[1][$i] : null,
                            'source' => 'order_by',
                        ];
                    }
                }
            }
        }

        // GROUP BY columns
        if (preg_match('/GROUP\s+BY\s+(.+?)(?:\s+HAVING\s|\s+ORDER\s|\s+LIMIT\s|$)/is', $sql, $groupMatch)) {
            $groupClause = $groupMatch[1];
            if (preg_match_all('/(\w+)\.?(\w+)?/i', $groupClause, $groupCols)) {
                foreach ($groupCols[0] as $i => $match) {
                    $col = $groupCols[2][$i] ?: $groupCols[1][$i];
                    if (!$this->isSqlKeyword($col)) {
                        $candidates[] = [
                            'column' => $col,
                            'table_alias' => $groupCols[2][$i] ? $groupCols[1][$i] : null,
                            'source' => 'group_by',
                        ];
                    }
                }
            }
        }

        return $candidates;
    }

    /**
     * Extract table names from FROM and JOIN clauses.
     */
    protected function extractTables(string $sql): array
    {
        $tables = [];

        // FROM table
        if (preg_match('/FROM\s+(\w+)/i', $sql, $match)) {
            $tables[] = $match[1];
        }

        // JOIN table
        if (preg_match_all('/JOIN\s+(\w+)/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }

        return array_unique($tables);
    }

    protected function getColumnsForTable(array $candidates, string $table, string $sql): array
    {
        // Simple heuristic: if a candidate has a table_alias, try to match it
        // Otherwise, associate with the primary table
        $tableCols = [];
        $aliases = $this->extractAliases($sql);

        foreach ($candidates as $candidate) {
            $alias = $candidate['table_alias'] ?? null;

            if ($alias === null) {
                // No alias, associate with primary table
                if ($table === ($this->extractTables($sql)[0] ?? '')) {
                    $tableCols[] = $candidate;
                }
            } elseif ($alias === $table || ($aliases[$alias] ?? null) === $table) {
                $tableCols[] = $candidate;
            }
        }

        return $tableCols;
    }

    protected function extractAliases(string $sql): array
    {
        $aliases = [];

        // FROM table alias or FROM table AS alias
        if (preg_match('/FROM\s+(\w+)(?:\s+(?:AS\s+)?(\w+))?/i', $sql, $match)) {
            if (!empty($match[2]) && !$this->isSqlKeyword($match[2])) {
                $aliases[$match[2]] = $match[1];
            }
        }

        // JOIN table alias
        if (preg_match_all('/JOIN\s+(\w+)(?:\s+(?:AS\s+)?(\w+))?\s+ON/i', $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (!empty($match[2]) && !$this->isSqlKeyword($match[2])) {
                    $aliases[$match[2]] = $match[1];
                }
            }
        }

        return $aliases;
    }

    /**
     * Analyze EXPLAIN output for optimization opportunities.
     */
    protected function analyzeExplainResult(array $explainResult): array
    {
        $insights = [];

        foreach ($explainResult as $row) {
            $type = $row['type'] ?? $row['select_type'] ?? '';
            $extra = $row['Extra'] ?? $row['extra'] ?? '';
            $key = $row['key'] ?? null;
            $rows = $row['rows'] ?? 0;
            $table = $row['table'] ?? 'unknown';

            // Full table scan detection
            if (strtoupper($type) === 'ALL') {
                $insights[] = [
                    'type' => 'full_table_scan',
                    'table' => $table,
                    'rows' => $rows,
                    'message' => "Full table scan on `{$table}` ({$rows} rows). Add an index on filter columns.",
                    'severity' => $rows > 1000 ? 'high' : 'medium',
                    'suggestion' => null,
                ];
            }

            // Using filesort detection
            if (str_contains($extra, 'Using filesort')) {
                $insights[] = [
                    'type' => 'filesort',
                    'table' => $table,
                    'message' => "Using filesort on `{$table}`. Consider adding an index that covers ORDER BY columns.",
                    'severity' => 'medium',
                    'suggestion' => null,
                ];
            }

            // Using temporary detection
            if (str_contains($extra, 'Using temporary')) {
                $insights[] = [
                    'type' => 'temporary_table',
                    'table' => $table,
                    'message' => "Using temporary table on `{$table}`. Consider a composite index for GROUP BY columns.",
                    'severity' => 'high',
                    'suggestion' => null,
                ];
            }

            // No index used
            if ($key === null && strtoupper($type) !== 'ALL') {
                if (in_array(strtoupper($type), ['INDEX', 'REF', 'RANGE', 'EQ_REF'])) {
                    // Has access type but no key - unusual
                    $insights[] = [
                        'type' => 'no_index',
                        'table' => $table,
                        'message' => "Access type `{$type}` on `{$table}` but no index key found.",
                        'severity' => 'low',
                        'suggestion' => null,
                    ];
                }
            }
        }

        return $insights;
    }

    protected function buildSuggestion(
        string $table,
        array $columns,
        string $type,
        string $reason,
        string $impact,
    ): array {
        $colList = implode(', ', $columns);
        $indexName = 'idx_' . $table . '_' . implode('_', array_map(fn($c) => substr($c, 0, 10), $columns));
        // Ensure index name is not too long
        $indexName = substr($indexName, 0, 64);

        return [
            'table' => $table,
            'columns' => $columns,
            'type' => $type,
            'reason' => $reason,
            'impact' => $impact,
            'create_index_sql' => "CREATE INDEX {$indexName} ON {$table} ({$colList})",
        ];
    }

    protected function estimateImpact(array $explainInsights): string
    {
        if (empty($explainInsights)) {
            return 'medium';
        }

        foreach ($explainInsights as $insight) {
            if (($insight['severity'] ?? '') === 'high') {
                return 'high';
            }
        }

        return 'medium';
    }

    protected function deduplicateSuggestions(array $suggestions): array
    {
        $seen = [];
        $unique = [];

        foreach ($suggestions as $suggestion) {
            $key = ($suggestion['table'] ?? '') . '|' . implode(',', $suggestion['columns'] ?? []);
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $suggestion;
            }
        }

        return $unique;
    }

    protected function isSqlKeyword(string $word): bool
    {
        $keywords = [
            'AND', 'OR', 'NOT', 'WHERE', 'JOIN', 'ON', 'LIMIT', 'ORDER', 'BY',
            'GROUP', 'HAVING', 'FROM', 'SELECT', 'INSERT', 'UPDATE', 'DELETE',
            'SET', 'INTO', 'VALUES', 'AS', 'IN', 'BETWEEN', 'LIKE', 'IS', 'NULL',
            'ASC', 'DESC', 'INNER', 'LEFT', 'RIGHT', 'OUTER', 'CROSS', 'FULL',
            'DISTINCT', 'UNION', 'ALL', 'EXISTS', 'CASE', 'WHEN', 'THEN', 'ELSE',
            'END', 'TRUE', 'FALSE', 'COUNT', 'SUM', 'AVG', 'MAX', 'MIN',
        ];

        return in_array(strtoupper($word), $keywords);
    }

    /**
     * Check if a suggested index is redundant (prefix of an existing index).
     */
    public function isRedundant(array $suggestion, array $existingIndexes): bool
    {
        $suggestedCols = $suggestion['columns'] ?? [];

        foreach ($existingIndexes as $existing) {
            $existingCols = $existing['columns'] ?? [];

            // Check if suggested columns are a prefix of an existing index
            if (count($suggestedCols) <= count($existingCols)) {
                $isPrefix = true;
                foreach ($suggestedCols as $i => $col) {
                    if (($existingCols[$i] ?? null) !== $col) {
                        $isPrefix = false;
                        break;
                    }
                }
                if ($isPrefix) {
                    return true;
                }
            }
        }

        return false;
    }
}
