<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer\Formatter;

use GladeHQ\QueryLens\ExplainAnalyzer\Analyzer\AnalysisResult;
use GladeHQ\QueryLens\ExplainAnalyzer\Nodes\OperationNode;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\Issue;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\IssueSeverity;

/**
 * Generates human-readable explanations from analysis results.
 */
class HumanExplainer
{
    private bool $useMarkdown = true;
    private bool $useEmoji = true;
    private bool $verbose = true;

    public function setUseMarkdown(bool $useMarkdown): self
    {
        $this->useMarkdown = $useMarkdown;
        return $this;
    }

    public function setUseEmoji(bool $useEmoji): self
    {
        $this->useEmoji = $useEmoji;
        return $this;
    }

    public function setVerbose(bool $verbose): self
    {
        $this->verbose = $verbose;
        return $this;
    }

    /**
     * Generate a complete human-readable explanation.
     */
    public function explain(AnalysisResult $result): string
    {
        $output = [];

        // Summary section
        $output[] = $this->generateSummary($result);

        // Query game plan
        $output[] = $this->generateGamePlan($result);

        // Step-by-step breakdown
        $output[] = $this->generateStepByStep($result);

        // Problems section
        if ($result->hasIssues()) {
            $output[] = $this->generateProblemsSection($result);
        }

        // Suggestions section
        if ($result->hasIssues()) {
            $output[] = $this->generateSuggestionsSection($result);
        }

        return implode("\n\n", array_filter($output));
    }

    /**
     * Generate a brief summary.
     */
    private function generateSummary(AnalysisResult $result): string
    {
        $time = $result->getTotalTime();
        $rows = $result->getTotalRowsExamined();
        $status = $result->getHealthStatus();
        $counts = $result->getIssueCounts();

        $statusEmoji = match ($status) {
            'critical' => $this->emoji('🚨'),
            'warning' => $this->emoji('⚠️'),
            'needs_attention' => $this->emoji('💡'),
            'good' => $this->emoji('✅'),
            default => '',
        };

        $statusText = match ($status) {
            'critical' => 'Critical issues found',
            'warning' => 'Performance warnings detected',
            'needs_attention' => 'Minor optimizations possible',
            'good' => 'Query looks healthy',
            default => 'Analysis complete',
        };

        $lines = [];

        if ($this->useMarkdown) {
            $lines[] = "# Summary";
        }

        $lines[] = sprintf('%s %s', $statusEmoji, $statusText);
        $lines[] = '';

        if ($time !== null) {
            $lines[] = sprintf(
                '%s **Total execution time:** %.2fms',
                $this->emoji('⏱️'),
                $time
            );
        }

        $lines[] = sprintf(
            '%s **Rows examined:** %s',
            $this->emoji('📊'),
            number_format($rows)
        );

        if ($counts['high'] > 0 || $counts['medium'] > 0) {
            $lines[] = sprintf(
                '%s **Issues found:** %d high, %d medium, %d low',
                $this->emoji('🔍'),
                $counts['high'],
                $counts['medium'],
                $counts['low']
            );
        }

        return implode("\n", $lines);
    }

    /**
     * Generate the query game plan explanation.
     */
    private function generateGamePlan(AnalysisResult $result): string
    {
        $nodes = $result->getNodes();

        if (empty($nodes)) {
            return '';
        }

        $lines = [];

        if ($this->useMarkdown) {
            $lines[] = "# Query Execution Plan";
        }

        // Analyze the query structure to describe what it's doing
        $description = $this->describeQueryPurpose($nodes);
        if ($description) {
            $lines[] = sprintf('**What it\'s doing:** %s', $description);
        }

        return implode("\n", $lines);
    }

    /**
     * Describe what the query is trying to accomplish.
     */
    private function describeQueryPurpose(array $nodes): string
    {
        $parts = [];
        $tables = [];
        $hasAggregate = false;
        $hasSort = false;
        $hasFilter = false;
        $hasLimit = false;

        $this->collectQueryInfo($nodes, $tables, $hasAggregate, $hasSort, $hasFilter, $hasLimit);

        $tables = array_unique($tables);

        if (!empty($tables)) {
            if (count($tables) === 1) {
                $parts[] = sprintf('Reading from table "%s"', $tables[0]);
            } else {
                $parts[] = sprintf('Joining tables: %s', implode(', ', $tables));
            }
        }

        if ($hasFilter) {
            $parts[] = 'filtering by conditions';
        }

        if ($hasAggregate) {
            $parts[] = 'aggregating results';
        }

        if ($hasSort) {
            $parts[] = 'sorting output';
        }

        if ($hasLimit) {
            $parts[] = 'limiting results';
        }

        return !empty($parts) ? ucfirst(implode(', ', $parts)) . '.' : '';
    }

    /**
     * Recursively collect query information from nodes.
     */
    private function collectQueryInfo(
        array $nodes,
        array &$tables,
        bool &$hasAggregate,
        bool &$hasSort,
        bool &$hasFilter,
        bool &$hasLimit
    ): void {
        foreach ($nodes as $node) {
            if ($node->getTableName()) {
                $tables[] = $node->getTableName();
            }

            $type = $node->getOperationType();

            if ($type === 'aggregate' || $type === 'group') {
                $hasAggregate = true;
            }
            if ($type === 'sort' || $type === 'filesort') {
                $hasSort = true;
            }
            if ($type === 'filter') {
                $hasFilter = true;
            }
            if ($type === 'limit') {
                $hasLimit = true;
            }

            if ($node->hasChildren()) {
                $this->collectQueryInfo(
                    $node->getChildren(),
                    $tables,
                    $hasAggregate,
                    $hasSort,
                    $hasFilter,
                    $hasLimit
                );
            }
        }
    }

    /**
     * Generate step-by-step breakdown.
     */
    private function generateStepByStep(AnalysisResult $result): string
    {
        $nodes = $result->getNodes();

        if (empty($nodes)) {
            return '';
        }

        $lines = [];

        if ($this->useMarkdown) {
            $lines[] = "# Step-by-Step Breakdown";
        }

        $stepNumber = 1;
        $this->explainNodes($nodes, $lines, $stepNumber, 0);

        return implode("\n\n", $lines);
    }

    /**
     * Recursively explain nodes.
     */
    private function explainNodes(array $nodes, array &$lines, int &$stepNumber, int $depth): void
    {
        // Process in reverse order (deepest first, as that's execution order)
        $reversedNodes = array_reverse($nodes);

        foreach ($reversedNodes as $node) {
            // First process children (they execute first)
            if ($node->hasChildren()) {
                $this->explainNodes($node->getChildren(), $lines, $stepNumber, $depth + 1);
            }

            // Then explain this node
            $explanation = $this->explainSingleNode($node, $stepNumber);
            if ($explanation) {
                $lines[] = $explanation;
                $stepNumber++;
            }
        }
    }

    /**
     * Explain a single node.
     */
    private function explainSingleNode(OperationNode $node, int $stepNumber): string
    {
        $lines = [];

        // Generate the human-readable explanation
        $explanation = $this->getOperationExplanation($node);

        if ($this->useMarkdown) {
            $lines[] = sprintf('**Step %d - %s**', $stepNumber, $this->getOperationTitle($node));
        } else {
            $lines[] = sprintf('Step %d - %s', $stepNumber, $this->getOperationTitle($node));
        }

        $lines[] = sprintf('> "%s"', $explanation);

        // Add metrics
        $metricsLines = $this->formatMetrics($node);
        if (!empty($metricsLines)) {
            $lines[] = '';
            $lines = array_merge($lines, $metricsLines);
        }

        return implode("\n", $lines);
    }

    /**
     * Get a title for the operation.
     */
    private function getOperationTitle(OperationNode $node): string
    {
        $type = $node->getOperationType();

        return match ($type) {
            'table_scan' => 'Table Scan',
            'index_lookup' => 'Index Lookup',
            'index_range' => 'Index Range Scan',
            'index_scan' => 'Index Scan',
            'filter' => 'Filter',
            'aggregate' => 'Aggregate',
            'sort', 'filesort' => 'Sort',
            'nested_loop' => 'Nested Loop Join',
            'hash_join' => 'Hash Join',
            'limit' => 'Limit',
            'group' => 'Group By',
            'temporary' => 'Temporary Table',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    /**
     * Get a human-readable explanation for an operation.
     */
    private function getOperationExplanation(OperationNode $node): string
    {
        $type = $node->getOperationType();
        $table = $node->getTableName();
        $index = $node->getIndexName();
        $keyCondition = $node->getKeyCondition();
        $filterCondition = $node->getFilterCondition();

        return match ($type) {
            'table_scan' => sprintf(
                'Scan every row in the "%s" table',
                $table ?? 'unknown'
            ),
            'index_lookup' => sprintf(
                'Look up rows in "%s" using the "%s" index%s',
                $table ?? 'unknown',
                $index ?? 'unknown',
                $keyCondition ? sprintf(' where %s', $keyCondition) : ''
            ),
            'index_range' => sprintf(
                'Scan a range of rows in "%s" using the "%s" index',
                $table ?? 'unknown',
                $index ?? 'unknown'
            ),
            'index_scan' => sprintf(
                'Scan the "%s" index on table "%s"',
                $index ?? 'unknown',
                $table ?? 'unknown'
            ),
            'filter' => sprintf(
                'Filter rows to keep only those where %s',
                $filterCondition ?? 'condition is met'
            ),
            'aggregate' => 'Calculate aggregate values (COUNT, SUM, AVG, etc.)',
            'sort', 'filesort' => 'Sort the result rows',
            'nested_loop' => 'Join tables using nested loop',
            'hash_join' => 'Join tables using hash join',
            'limit' => 'Limit the number of rows returned',
            'group' => 'Group rows by specified columns',
            'temporary' => 'Create a temporary table for intermediate results',
            default => $node->getOperation(),
        };
    }

    /**
     * Format metrics for display.
     */
    private function formatMetrics(OperationNode $node): array
    {
        $lines = [];

        $estimated = $node->getEstimatedRows();
        $actual = $node->getActualRows();
        $time = $node->getActualTimeLast();
        $loops = $node->getActualLoops();

        if ($estimated !== null && $actual !== null) {
            $accuracyEmoji = '';
            $accuracyNote = '';

            if ($estimated !== $actual) {
                $ratio = $actual > 0 ? $estimated / $actual : null;

                if ($ratio !== null) {
                    if ($ratio > 10 || $ratio < 0.1) {
                        $accuracyEmoji = $this->emoji(' 😬');
                        $accuracyNote = ' (MySQL\'s estimate was way off)';
                    } elseif ($ratio > 2 || $ratio < 0.5) {
                        $accuracyEmoji = $this->emoji(' ⚡');
                    } else {
                        $accuracyEmoji = $this->emoji(' ✓');
                    }
                }
            } else {
                $accuracyEmoji = $this->emoji(' ✓');
            }

            $lines[] = sprintf(
                '- **Estimated:** ~%s rows',
                number_format($estimated)
            );
            $lines[] = sprintf(
                '- **Actual:** %s rows%s%s',
                number_format($actual),
                $accuracyEmoji,
                $accuracyNote
            );
        } elseif ($actual !== null) {
            $lines[] = sprintf('- **Rows:** %s', number_format($actual));
        } elseif ($estimated !== null) {
            $lines[] = sprintf('- **Estimated rows:** ~%s', number_format($estimated));
        }

        if ($time !== null) {
            $lines[] = sprintf('- **Time:** %.2fms', $time);
        }

        if ($loops !== null && $loops > 1) {
            $lines[] = sprintf('- **Loops:** %s', number_format($loops));
        }

        return $lines;
    }

    /**
     * Generate problems section.
     */
    private function generateProblemsSection(AnalysisResult $result): string
    {
        $issues = $result->getIssuesBySeverity();

        if (empty($issues)) {
            return '';
        }

        $lines = [];

        if ($this->useMarkdown) {
            $lines[] = sprintf("# Problems Detected %s", $this->emoji('🚨'));
        }

        foreach ($issues as $issue) {
            $lines[] = $this->formatIssue($issue);
        }

        return implode("\n\n", $lines);
    }

    /**
     * Format a single issue.
     */
    private function formatIssue(Issue $issue): string
    {
        $lines = [];

        $emoji = $this->useEmoji ? $issue->getSeverityEmoji() . ' ' : '';
        $severity = strtoupper($issue->getSeverity()->value);

        if ($this->useMarkdown) {
            $lines[] = sprintf('## %s%s [%s]', $emoji, $issue->getTitle(), $severity);
        } else {
            $lines[] = sprintf('%s%s [%s]', $emoji, $issue->getTitle(), $severity);
        }

        $lines[] = $issue->getMessage();

        if ($issue->getNode()->getTableName()) {
            $lines[] = sprintf('*Table: %s*', $issue->getNode()->getTableName());
        }

        return implode("\n", $lines);
    }

    /**
     * Generate suggestions section.
     */
    private function generateSuggestionsSection(AnalysisResult $result): string
    {
        $issues = $result->getIssuesBySeverity();

        if (empty($issues)) {
            return '';
        }

        $lines = [];

        if ($this->useMarkdown) {
            $lines[] = sprintf("## Suggestions %s", $this->emoji('💡'));
        }

        $seen = [];
        foreach ($issues as $issue) {
            $suggestion = $issue->getSuggestion();

            // Avoid duplicate suggestions
            $hash = md5($suggestion);
            if (isset($seen[$hash])) {
                continue;
            }
            $seen[$hash] = true;

            if ($this->useMarkdown) {
                $lines[] = sprintf('## %s', $issue->getTitle());
            } else {
                $lines[] = sprintf('[%s]', $issue->getTitle());
            }

            $lines[] = $suggestion;
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Helper to conditionally include emoji.
     */
    private function emoji(string $emoji): string
    {
        return $this->useEmoji ? $emoji : '';
    }
}
