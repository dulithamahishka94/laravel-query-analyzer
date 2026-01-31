<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\ExplainAnalyzer\Issues;

use Coderflex\QueryLens\ExplainAnalyzer\Nodes\OperationNode;

/**
 * Represents a detected issue in the execution plan.
 */
class Issue
{
    public function __construct(
        private readonly IssueType $type,
        private readonly IssueSeverity $severity,
        private readonly OperationNode $node,
        private readonly string $message,
        private readonly string $suggestion
    ) {}

    public function getType(): IssueType
    {
        return $this->type;
    }

    public function getSeverity(): IssueSeverity
    {
        return $this->severity;
    }

    public function getNode(): OperationNode
    {
        return $this->node;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSuggestion(): string
    {
        return $this->suggestion;
    }

    /**
     * Get a human-readable title for the issue type.
     */
    public function getTitle(): string
    {
        return match ($this->type) {
            IssueType::FULL_TABLE_SCAN => 'Full Table Scan',
            IssueType::ROW_ESTIMATION_ERROR => 'Row Estimation Mismatch',
            IssueType::INEFFICIENT_FILTER => 'Inefficient Filter',
            IssueType::FILESORT => 'Filesort Required',
            IssueType::TEMPORARY_TABLE => 'Temporary Table Used',
            IssueType::FUNCTION_ON_COLUMN => 'Function on Column',
            IssueType::HIGH_LOOP_COUNT => 'High Loop Count',
            IssueType::SLOW_OPERATION => 'Slow Operation',
            IssueType::MISSING_INDEX => 'Missing Index',
            IssueType::IMPLICIT_CONVERSION => 'Implicit Type Conversion',
        };
    }

    /**
     * Get emoji indicator for severity.
     */
    public function getSeverityEmoji(): string
    {
        return match ($this->severity) {
            IssueSeverity::HIGH => '🚨',
            IssueSeverity::MEDIUM => '⚠️',
            IssueSeverity::LOW => '💡',
            IssueSeverity::INFO => 'ℹ️',
        };
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'severity' => $this->severity->value,
            'title' => $this->getTitle(),
            'message' => $this->message,
            'suggestion' => $this->suggestion,
            'operation' => $this->node->getOperation(),
            'table' => $this->node->getTableName(),
        ];
    }
}
