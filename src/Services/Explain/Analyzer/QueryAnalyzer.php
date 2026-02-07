<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer\Analyzer;

use GladeHQ\QueryLens\ExplainAnalyzer\Nodes\OperationNode;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\Issue;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\IssueType;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\IssueSeverity;

/**
 * Analyzes parsed execution plan nodes to identify performance issues.
 */
class QueryAnalyzer
{
    /** @var Issue[] */
    private array $issues = [];

    /** @var OperationNode[] */
    private array $nodes = [];

    /**
     * Analyze the execution plan nodes.
     *
     * @param OperationNode[] $nodes
     * @return AnalysisResult
     */
    public function analyze(array $nodes): AnalysisResult
    {
        $this->issues = [];
        $this->nodes = $nodes;

        $this->analyzeNodes($nodes);

        return new AnalysisResult(
            nodes: $nodes,
            issues: $this->issues,
            totalTime: $this->calculateTotalTime($nodes),
            totalRowsExamined: $this->calculateTotalRowsExamined($nodes)
        );
    }

    /**
     * Recursively analyze nodes for issues.
     *
     * @param OperationNode[] $nodes
     */
    private function analyzeNodes(array $nodes): void
    {
        foreach ($nodes as $node) {
            $this->analyzeNode($node);

            if ($node->hasChildren()) {
                $this->analyzeNodes($node->getChildren());
            }
        }
    }

    /**
     * Analyze a single node for issues.
     */
    private function analyzeNode(OperationNode $node): void
    {
        $this->checkTableScan($node);
        $this->checkRowEstimation($node);
        $this->checkFilterAfterFetch($node);
        $this->checkFilesort($node);
        $this->checkTemporaryTable($node);
        $this->checkFunctionOnColumn($node);
        $this->checkHighLoopCount($node);
        $this->checkSlowOperation($node);
    }

    /**
     * Check for full table scans.
     */
    private function checkTableScan(OperationNode $node): void
    {
        if ($node->getAccessType() !== 'table_scan') {
            return;
        }

        $rowsExamined = $node->getActualRows() ?? $node->getEstimatedRows() ?? 0;

        if ($rowsExamined > 1000) {
            $this->issues[] = new Issue(
                type: IssueType::FULL_TABLE_SCAN,
                severity: $rowsExamined > 10000 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                node: $node,
                message: sprintf(
                    'Full table scan on "%s" examining %s rows',
                    $node->getTableName() ?? 'unknown',
                    number_format($rowsExamined)
                ),
                suggestion: sprintf(
                    'Consider adding an index on the columns used in WHERE/JOIN conditions for table "%s".',
                    $node->getTableName() ?? 'unknown'
                )
            );
        }
    }

    /**
     * Check for row estimation issues.
     */
    private function checkRowEstimation(OperationNode $node): void
    {
        $estimated = $node->getEstimatedRows();
        $actual = $node->getActualRows();

        if ($estimated === null || $actual === null) {
            return;
        }

        $ratio = $actual > 0 ? $estimated / $actual : null;

        if ($ratio === null) {
            return;
        }

        // Overestimate by 10x or more
        if ($ratio > 10) {
            $this->issues[] = new Issue(
                type: IssueType::ROW_ESTIMATION_ERROR,
                severity: $ratio > 100 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                node: $node,
                message: sprintf(
                    'Row estimation was %.1fx too high (estimated %s, actual %s)',
                    $ratio,
                    number_format($estimated),
                    number_format($actual)
                ),
                suggestion: 'Consider running ANALYZE TABLE to update statistics, or check if there are outdated/missing histograms.'
            );
        }

        // Underestimate by 10x or more
        if ($ratio < 0.1) {
            $this->issues[] = new Issue(
                type: IssueType::ROW_ESTIMATION_ERROR,
                severity: $ratio < 0.01 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                node: $node,
                message: sprintf(
                    'Row estimation was %.1fx too low (estimated %s, actual %s)',
                    1 / $ratio,
                    number_format($estimated),
                    number_format($actual)
                ),
                suggestion: 'Consider running ANALYZE TABLE to update statistics. This underestimation might cause suboptimal join ordering.'
            );
        }
    }

    /**
     * Check for filter operations that discard many rows after fetching.
     */
    private function checkFilterAfterFetch(OperationNode $node): void
    {
        if ($node->getOperationType() !== 'filter') {
            return;
        }

        // Look at child nodes to see how many rows were fetched
        foreach ($node->getChildren() as $child) {
            $fetchedRows = $child->getActualRows();
            $filteredRows = $node->getActualRows();

            if ($fetchedRows === null || $filteredRows === null) {
                continue;
            }

            // If we filtered out more than 90% of rows
            if ($fetchedRows > 0 && $filteredRows / $fetchedRows < 0.1) {
                $discardedPercentage = (1 - $filteredRows / $fetchedRows) * 100;

                $this->issues[] = new Issue(
                    type: IssueType::INEFFICIENT_FILTER,
                    severity: $discardedPercentage > 99 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                    node: $node,
                    message: sprintf(
                        'Filter discarded %.1f%% of rows (fetched %s, kept %s)',
                        $discardedPercentage,
                        number_format($fetchedRows),
                        number_format($filteredRows)
                    ),
                    suggestion: $this->generateFilterSuggestion($node, $child)
                );
            }
        }
    }

    /**
     * Generate a suggestion for inefficient filter.
     */
    private function generateFilterSuggestion(OperationNode $filterNode, OperationNode $childNode): string
    {
        $filterCondition = $filterNode->getFilterCondition();
        $tableName = $childNode->getTableName();
        $indexName = $childNode->getIndexName();

        if ($filterCondition && str_contains($filterCondition, 'cast(')) {
            return sprintf(
                'The filter uses CAST() which prevents index usage. Consider using a range query instead of casting, or store data in the format you query it. Current filter: %s',
                $filterCondition
            );
        }

        if ($filterCondition && preg_match('/(\w+)\s*=/', $filterCondition, $matches)) {
            $column = $matches[1];
            if ($indexName) {
                return sprintf(
                    'Consider creating a composite index on ("%s", %s) to push the filter condition into the index lookup.',
                    $indexName,
                    $column
                );
            }
            return sprintf(
                'Consider adding an index that includes the column "%s" used in the filter condition.',
                $column
            );
        }

        return 'Consider adding an index that covers the filter condition to reduce rows fetched.';
    }

    /**
     * Check for filesort operations.
     */
    private function checkFilesort(OperationNode $node): void
    {
        $operation = strtolower($node->getOperation());

        if (!str_contains($operation, 'filesort') && !str_contains($operation, 'sort')) {
            return;
        }

        $rowsSorted = $node->getActualRows() ?? $node->getEstimatedRows() ?? 0;

        if ($rowsSorted > 1000) {
            $this->issues[] = new Issue(
                type: IssueType::FILESORT,
                severity: $rowsSorted > 100000 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                node: $node,
                message: sprintf('Filesort operation on %s rows', number_format($rowsSorted)),
                suggestion: 'Consider adding an index that matches the ORDER BY clause to avoid sorting. If the sort is on the result of a computation, consider storing the computed value.'
            );
        }
    }

    /**
     * Check for temporary table usage.
     */
    private function checkTemporaryTable(OperationNode $node): void
    {
        $operation = strtolower($node->getOperation());

        if (!str_contains($operation, 'temporary') && !str_contains($operation, 'materialize')) {
            return;
        }

        $this->issues[] = new Issue(
            type: IssueType::TEMPORARY_TABLE,
            severity: IssueSeverity::MEDIUM,
            node: $node,
            message: 'Query uses a temporary table',
            suggestion: 'Temporary tables are created for GROUP BY, DISTINCT, or complex subqueries. Consider restructuring the query or adding appropriate indexes.'
        );
    }

    /**
     * Check for functions applied to columns (preventing index usage).
     */
    private function checkFunctionOnColumn(OperationNode $node): void
    {
        $filter = $node->getFilterCondition();

        if ($filter === null) {
            return;
        }

        // Common functions that prevent index usage
        $problematicPatterns = [
            '/cast\s*\(\s*(\w+)\b/' => 'CAST()',
            '/date\s*\(\s*(\w+)\b/' => 'DATE()',
            '/year\s*\(\s*(\w+)\b/' => 'YEAR()',
            '/month\s*\(\s*(\w+)\b/' => 'MONTH()',
            '/lower\s*\(\s*(\w+)\b/' => 'LOWER()',
            '/upper\s*\(\s*(\w+)\b/' => 'UPPER()',
            '/substring\s*\(\s*(\w+)\b/' => 'SUBSTRING()',
            '/concat\s*\(/' => 'CONCAT()',
            '/ifnull\s*\(\s*(\w+)\b/' => 'IFNULL()',
            '/coalesce\s*\(\s*(\w+)\b/' => 'COALESCE()',
        ];

        foreach ($problematicPatterns as $pattern => $functionName) {
            if (preg_match($pattern, strtolower($filter), $matches)) {
                $columnName = $matches[1] ?? 'column';

                $this->issues[] = new Issue(
                    type: IssueType::FUNCTION_ON_COLUMN,
                    severity: IssueSeverity::HIGH,
                    node: $node,
                    message: sprintf(
                        '%s function applied to column "%s" in filter condition',
                        $functionName,
                        $columnName
                    ),
                    suggestion: $this->generateFunctionSuggestion($functionName, $columnName, $filter)
                );
                break; // One issue per node is enough
            }
        }
    }

    /**
     * Generate suggestion for function-on-column issue.
     */
    private function generateFunctionSuggestion(string $functionName, string $columnName, string $filter): string
    {
        return match ($functionName) {
            'CAST()' => sprintf(
                'Instead of CAST(%s), use a range query. For example, if comparing a datetime to a date, use: %s >= \'2024-01-01\' AND %s < \'2024-01-02\'',
                $columnName,
                $columnName,
                $columnName
            ),
            'DATE()' => sprintf(
                'Instead of DATE(%s) = \'2024-01-01\', use: %s >= \'2024-01-01\' AND %s < \'2024-01-02\'',
                $columnName,
                $columnName,
                $columnName
            ),
            'YEAR()', 'MONTH()' => sprintf(
                'Instead of %s(%s), use a range query: %s >= \'2024-01-01\' AND %s < \'2025-01-01\'',
                $functionName,
                $columnName,
                $columnName,
                $columnName
            ),
            'LOWER()', 'UPPER()' => sprintf(
                'Instead of %s(%s), ensure consistent casing in your data or use a case-insensitive collation on the column.',
                $functionName,
                $columnName
            ),
            default => sprintf(
                'Avoid applying functions to columns in WHERE clauses. Either transform the comparison value instead, or consider adding a generated/computed column.',
            ),
        };
    }

    /**
     * Check for high loop counts (nested loop joins with many iterations).
     */
    private function checkHighLoopCount(OperationNode $node): void
    {
        $loops = $node->getActualLoops();

        if ($loops === null || $loops < 100) {
            return;
        }

        $rowsPerLoop = $node->getActualRows();
        $totalRows = $rowsPerLoop !== null ? $rowsPerLoop * $loops : $loops;

        if ($totalRows > 10000) {
            $this->issues[] = new Issue(
                type: IssueType::HIGH_LOOP_COUNT,
                severity: $loops > 1000 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                node: $node,
                message: sprintf(
                    'Operation executed %s times (loops=%s, rows per loop=%s)',
                    number_format($loops),
                    number_format($loops),
                    $rowsPerLoop !== null ? number_format($rowsPerLoop) : 'unknown'
                ),
                suggestion: 'High loop counts often indicate a nested loop join iterating over many rows. Consider adding indexes or restructuring the query to reduce the outer result set.'
            );
        }
    }

    /**
     * Check for slow operations.
     */
    private function checkSlowOperation(OperationNode $node): void
    {
        $time = $node->getActualTimeLast();

        if ($time === null) {
            return;
        }

        // Flag operations taking more than 100ms
        if ($time > 100) {
            $this->issues[] = new Issue(
                type: IssueType::SLOW_OPERATION,
                severity: $time > 1000 ? IssueSeverity::HIGH : IssueSeverity::MEDIUM,
                node: $node,
                message: sprintf(
                    'Slow operation: %.2fms for "%s"',
                    $time,
                    $this->truncateOperation($node->getOperation())
                ),
                suggestion: 'This operation is taking significant time. Review the child operations and ensure proper indexes are in place.'
            );
        }
    }

    /**
     * Truncate long operation strings.
     */
    private function truncateOperation(string $operation, int $maxLength = 50): string
    {
        if (strlen($operation) <= $maxLength) {
            return $operation;
        }

        return substr($operation, 0, $maxLength - 3) . '...';
    }

    /**
     * Calculate total execution time from nodes.
     *
     * @param OperationNode[] $nodes
     */
    private function calculateTotalTime(array $nodes): ?float
    {
        if (empty($nodes)) {
            return null;
        }

        // The root node usually contains the total time
        $rootNode = $nodes[0];
        return $rootNode->getActualTimeLast();
    }

    /**
     * Calculate total rows examined.
     *
     * @param OperationNode[] $nodes
     */
    private function calculateTotalRowsExamined(array $nodes): int
    {
        $total = 0;

        foreach ($nodes as $node) {
            $rows = $node->getActualRows() ?? 0;
            $loops = $node->getActualLoops() ?? 1;
            $total += $rows * $loops;

            if ($node->hasChildren()) {
                $total += $this->calculateTotalRowsExamined($node->getChildren());
            }
        }

        return $total;
    }
}
