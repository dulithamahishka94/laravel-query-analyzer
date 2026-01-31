<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\ExplainAnalyzer\Formatter;

use Coderflex\QueryLens\ExplainAnalyzer\Analyzer\AnalysisResult;
use Coderflex\QueryLens\ExplainAnalyzer\Nodes\OperationNode;
use Coderflex\QueryLens\ExplainAnalyzer\Issues\Issue;

/**
 * Compact text formatter for terminal/log output.
 */
class CompactFormatter
{
    /**
     * Format the analysis result as compact text.
     */
    public function format(AnalysisResult $result): string
    {
        $lines = [];

        // One-line summary
        $lines[] = $this->formatSummaryLine($result);
        $lines[] = str_repeat('-', 60);

        // Execution flow
        $lines[] = 'Execution Flow:';
        $this->formatNodesCompact($result->getNodes(), $lines, 1);

        // Issues
        if ($result->hasIssues()) {
            $lines[] = '';
            $lines[] = 'Issues:';
            foreach ($result->getIssuesBySeverity() as $issue) {
                $lines[] = $this->formatIssueCompact($issue);
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Format the summary line.
     */
    private function formatSummaryLine(AnalysisResult $result): string
    {
        $time = $result->getTotalTime();
        $rows = $result->getTotalRowsExamined();
        $counts = $result->getIssueCounts();

        $parts = [];

        if ($time !== null) {
            $parts[] = sprintf('Time: %.2fms', $time);
        }

        $parts[] = sprintf('Rows: %s', number_format($rows));

        $issueTotal = $counts['high'] + $counts['medium'] + $counts['low'];
        if ($issueTotal > 0) {
            $parts[] = sprintf('Issues: %d (%dH/%dM/%dL)',
                $issueTotal,
                $counts['high'],
                $counts['medium'],
                $counts['low']
            );
        } else {
            $parts[] = 'No issues';
        }

        return implode(' | ', $parts);
    }

    /**
     * Format nodes compactly.
     */
    private function formatNodesCompact(array $nodes, array &$lines, int $depth): void
    {
        foreach ($nodes as $node) {
            $indent = str_repeat('  ', $depth);

            $line = sprintf(
                '%s-> %s',
                $indent,
                $this->getCompactOperation($node)
            );

            // Add metrics inline
            $metrics = [];
            if ($node->getActualRows() !== null) {
                $metrics[] = sprintf('rows=%s', number_format($node->getActualRows()));
            }
            if ($node->getActualTimeLast() !== null) {
                $metrics[] = sprintf('time=%.2fms', $node->getActualTimeLast());
            }

            if (!empty($metrics)) {
                $line .= ' [' . implode(', ', $metrics) . ']';
            }

            // Mark problematic nodes
            if ($node->isPotentiallyProblematic()) {
                $line .= ' ⚠️';
            }

            $lines[] = $line;

            if ($node->hasChildren()) {
                $this->formatNodesCompact($node->getChildren(), $lines, $depth + 1);
            }
        }
    }

    /**
     * Get compact operation description.
     */
    private function getCompactOperation(OperationNode $node): string
    {
        $type = $node->getOperationType();
        $table = $node->getTableName();
        $index = $node->getIndexName();

        return match ($type) {
            'table_scan' => sprintf('TABLE_SCAN(%s)', $table ?? '?'),
            'index_lookup' => sprintf('INDEX_LOOKUP(%s.%s)', $table ?? '?', $index ?? '?'),
            'index_range' => sprintf('INDEX_RANGE(%s.%s)', $table ?? '?', $index ?? '?'),
            'index_scan' => sprintf('INDEX_SCAN(%s.%s)', $table ?? '?', $index ?? '?'),
            'filter' => 'FILTER',
            'aggregate' => 'AGGREGATE',
            'sort', 'filesort' => 'SORT',
            'nested_loop' => 'NESTED_LOOP',
            'hash_join' => 'HASH_JOIN',
            'limit' => 'LIMIT',
            'group' => 'GROUP',
            'temporary' => 'TEMP_TABLE',
            default => strtoupper($type),
        };
    }

    /**
     * Format an issue compactly.
     */
    private function formatIssueCompact(Issue $issue): string
    {
        $severity = strtoupper(substr($issue->getSeverity()->value, 0, 1));
        return sprintf(
            '  [%s] %s: %s',
            $severity,
            $issue->getTitle(),
            $this->truncate($issue->getMessage(), 60)
        );
    }

    /**
     * Truncate a string.
     */
    private function truncate(string $text, int $length): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }

        return substr($text, 0, $length - 3) . '...';
    }
}
