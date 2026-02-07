<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer\Analyzer;

use GladeHQ\QueryLens\ExplainAnalyzer\Nodes\OperationNode;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\Issue;
use GladeHQ\QueryLens\ExplainAnalyzer\Issues\IssueSeverity;

/**
 * Contains the results of analyzing an execution plan.
 */
class AnalysisResult
{
    /**
     * @param OperationNode[] $nodes
     * @param Issue[] $issues
     */
    public function __construct(
        private readonly array $nodes,
        private readonly array $issues,
        private readonly ?float $totalTime,
        private readonly int $totalRowsExamined
    ) {}

    /**
     * @return OperationNode[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return Issue[]
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * Get issues sorted by severity (high to low).
     *
     * @return Issue[]
     */
    public function getIssuesBySeverity(): array
    {
        $issues = $this->issues;

        usort($issues, function (Issue $a, Issue $b) {
            $severityOrder = [
                IssueSeverity::HIGH->value => 0,
                IssueSeverity::MEDIUM->value => 1,
                IssueSeverity::LOW->value => 2,
                IssueSeverity::INFO->value => 3,
            ];

            return ($severityOrder[$a->getSeverity()->value] ?? 99)
                <=> ($severityOrder[$b->getSeverity()->value] ?? 99);
        });

        return $issues;
    }

    public function getTotalTime(): ?float
    {
        return $this->totalTime;
    }

    public function getTotalRowsExamined(): int
    {
        return $this->totalRowsExamined;
    }

    public function hasIssues(): bool
    {
        return !empty($this->issues);
    }

    public function hasHighSeverityIssues(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->getSeverity() === IssueSeverity::HIGH) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get count of issues by severity.
     *
     * @return array<string, int>
     */
    public function getIssueCounts(): array
    {
        $counts = [
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0,
        ];

        foreach ($this->issues as $issue) {
            $counts[$issue->getSeverity()->value]++;
        }

        return $counts;
    }

    /**
     * Get overall health status.
     */
    public function getHealthStatus(): string
    {
        $counts = $this->getIssueCounts();

        if ($counts['high'] > 0) {
            return 'critical';
        }

        if ($counts['medium'] > 2) {
            return 'warning';
        }

        if ($counts['medium'] > 0 || $counts['low'] > 3) {
            return 'needs_attention';
        }

        return 'good';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'total_time_ms' => $this->totalTime,
            'total_rows_examined' => $this->totalRowsExamined,
            'health_status' => $this->getHealthStatus(),
            'issue_counts' => $this->getIssueCounts(),
            'issues' => array_map(fn(Issue $i) => $i->toArray(), $this->issues),
            'nodes' => array_map(fn(OperationNode $n) => $n->toArray(), $this->nodes),
        ];
    }
}
