<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\ExplainAnalyzer\Nodes;

/**
 * Represents a single operation/step in the MySQL execution plan.
 */
class OperationNode
{
    private string $operation = '';
    private int $depth = 0;

    // Estimated metrics
    private ?float $estimatedCost = null;
    private ?int $estimatedRows = null;

    // Actual metrics
    private ?float $actualTimeFirst = null;
    private ?float $actualTimeLast = null;
    private ?int $actualRows = null;
    private ?int $actualLoops = null;
    private bool $hasActualMetrics = false;

    // Table/Index information
    private ?string $tableName = null;
    private ?string $indexName = null;
    private ?string $accessType = null;
    private ?string $keyCondition = null;
    private ?string $filterCondition = null;

    /** @var OperationNode[] */
    private array $children = [];

    // Getters and Setters

    public function getOperation(): string
    {
        return $this->operation;
    }

    public function setOperation(string $operation): self
    {
        $this->operation = $operation;
        return $this;
    }

    public function getDepth(): int
    {
        return $this->depth;
    }

    public function setDepth(int $depth): self
    {
        $this->depth = $depth;
        return $this;
    }

    public function getEstimatedCost(): ?float
    {
        return $this->estimatedCost;
    }

    public function setEstimatedCost(?float $estimatedCost): self
    {
        $this->estimatedCost = $estimatedCost;
        return $this;
    }

    public function getEstimatedRows(): ?int
    {
        return $this->estimatedRows;
    }

    public function setEstimatedRows(?int $estimatedRows): self
    {
        $this->estimatedRows = $estimatedRows;
        return $this;
    }

    public function getActualTimeFirst(): ?float
    {
        return $this->actualTimeFirst;
    }

    public function setActualTimeFirst(?float $actualTimeFirst): self
    {
        $this->actualTimeFirst = $actualTimeFirst;
        return $this;
    }

    public function getActualTimeLast(): ?float
    {
        return $this->actualTimeLast;
    }

    public function setActualTimeLast(?float $actualTimeLast): self
    {
        $this->actualTimeLast = $actualTimeLast;
        return $this;
    }

    public function getActualRows(): ?int
    {
        return $this->actualRows;
    }

    public function setActualRows(?int $actualRows): self
    {
        $this->actualRows = $actualRows;
        return $this;
    }

    public function getActualLoops(): ?int
    {
        return $this->actualLoops;
    }

    public function setActualLoops(?int $actualLoops): self
    {
        $this->actualLoops = $actualLoops;
        return $this;
    }

    public function hasActualMetrics(): bool
    {
        return $this->hasActualMetrics;
    }

    public function setHasActualMetrics(bool $hasActualMetrics): self
    {
        $this->hasActualMetrics = $hasActualMetrics;
        return $this;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    public function setTableName(?string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function setIndexName(?string $indexName): self
    {
        $this->indexName = $indexName;
        return $this;
    }

    public function getAccessType(): ?string
    {
        return $this->accessType;
    }

    public function setAccessType(?string $accessType): self
    {
        $this->accessType = $accessType;
        return $this;
    }

    public function getKeyCondition(): ?string
    {
        return $this->keyCondition;
    }

    public function setKeyCondition(?string $keyCondition): self
    {
        $this->keyCondition = $keyCondition;
        return $this;
    }

    public function getFilterCondition(): ?string
    {
        return $this->filterCondition;
    }

    public function setFilterCondition(?string $filterCondition): self
    {
        $this->filterCondition = $filterCondition;
        return $this;
    }

    /**
     * @return OperationNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param OperationNode[] $children
     */
    public function setChildren(array $children): self
    {
        $this->children = $children;
        return $this;
    }

    public function addChild(OperationNode $child): self
    {
        $this->children[] = $child;
        return $this;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    // Calculated metrics

    /**
     * Get the row estimation accuracy ratio.
     * Returns null if we don't have both estimated and actual rows.
     */
    public function getRowEstimationAccuracy(): ?float
    {
        if ($this->estimatedRows === null || $this->actualRows === null) {
            return null;
        }

        if ($this->actualRows === 0) {
            return $this->estimatedRows === 0 ? 1.0 : null;
        }

        return $this->estimatedRows / $this->actualRows;
    }

    /**
     * Check if the row estimation was significantly off.
     */
    public function hasRowEstimationIssue(): bool
    {
        $accuracy = $this->getRowEstimationAccuracy();

        if ($accuracy === null) {
            return false;
        }

        // More than 10x overestimate or underestimate
        return $accuracy > 10 || $accuracy < 0.1;
    }

    /**
     * Get total time for this operation.
     */
    public function getTotalTime(): ?float
    {
        if ($this->actualTimeLast === null) {
            return null;
        }

        return $this->actualTimeLast * ($this->actualLoops ?? 1);
    }

    /**
     * Get the operation type category.
     */
    public function getOperationType(): string
    {
        $operation = strtolower($this->operation);

        if (str_contains($operation, 'table scan')) {
            return 'table_scan';
        }
        if (str_contains($operation, 'index lookup')) {
            return 'index_lookup';
        }
        if (str_contains($operation, 'index range scan')) {
            return 'index_range';
        }
        if (str_contains($operation, 'index scan')) {
            return 'index_scan';
        }
        if (str_contains($operation, 'filter')) {
            return 'filter';
        }
        if (str_contains($operation, 'aggregate')) {
            return 'aggregate';
        }
        if (str_contains($operation, 'sort')) {
            return 'sort';
        }
        if (str_contains($operation, 'nested loop')) {
            return 'nested_loop';
        }
        if (str_contains($operation, 'hash join')) {
            return 'hash_join';
        }
        if (str_contains($operation, 'limit')) {
            return 'limit';
        }
        if (str_contains($operation, 'group')) {
            return 'group';
        }
        if (str_contains($operation, 'temporary')) {
            return 'temporary';
        }
        if (str_contains($operation, 'filesort')) {
            return 'filesort';
        }

        return 'other';
    }

    /**
     * Check if this operation is potentially problematic.
     */
    public function isPotentiallyProblematic(): bool
    {
        $type = $this->getOperationType();

        // Table scans are usually problematic on large tables
        if ($type === 'table_scan' && ($this->actualRows ?? 0) > 1000) {
            return true;
        }

        // Filesorts can be expensive
        if ($type === 'filesort') {
            return true;
        }

        // Temporary tables can be expensive
        if ($type === 'temporary') {
            return true;
        }

        // Large row estimation errors
        if ($this->hasRowEstimationIssue()) {
            return true;
        }

        return false;
    }

    /**
     * Convert node to array for debugging/serialization.
     */
    public function toArray(): array
    {
        $data = [
            'operation' => $this->operation,
            'depth' => $this->depth,
            'estimated_cost' => $this->estimatedCost,
            'estimated_rows' => $this->estimatedRows,
            'actual_time_first' => $this->actualTimeFirst,
            'actual_time_last' => $this->actualTimeLast,
            'actual_rows' => $this->actualRows,
            'actual_loops' => $this->actualLoops,
            'table_name' => $this->tableName,
            'index_name' => $this->indexName,
            'access_type' => $this->accessType,
            'key_condition' => $this->keyCondition,
            'filter_condition' => $this->filterCondition,
        ];

        if (!empty($this->children)) {
            $data['children'] = array_map(fn($child) => $child->toArray(), $this->children);
        }

        return array_filter($data, fn($v) => $v !== null);
    }
}
