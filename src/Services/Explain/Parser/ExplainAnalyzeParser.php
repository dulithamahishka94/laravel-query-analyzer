<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer\Parser;

use GladeHQ\QueryLens\ExplainAnalyzer\Nodes\OperationNode;

/**
 * Parses MySQL EXPLAIN ANALYZE output into a structured tree of operation nodes.
 */
class ExplainAnalyzeParser
{
    private const INDENT_SIZE = 4;

    /**
     * Parse EXPLAIN ANALYZE output text into operation nodes.
     *
     * @param string $explainOutput Raw EXPLAIN ANALYZE output
     * @return OperationNode[] Array of root-level operation nodes
     */
    public function parse(string $explainOutput): array
    {
        $lines = $this->normalizeLines($explainOutput);
        $nodes = [];
        $nodeStack = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $indentLevel = $this->getIndentLevel($line);
            $cleanLine = trim(preg_replace('/^[\s\-\>]+/', '', $line));

            if (empty($cleanLine)) {
                continue;
            }

            $node = $this->parseLine($cleanLine);
            $node->setDepth($indentLevel);

            // Find parent based on indent level
            while (!empty($nodeStack) && end($nodeStack)->getDepth() >= $indentLevel) {
                array_pop($nodeStack);
            }

            if (!empty($nodeStack)) {
                end($nodeStack)->addChild($node);
            } else {
                $nodes[] = $node;
            }

            $nodeStack[] = $node;
        }

        return $this->mergeEstimatedAndActual($nodes);
    }

    /**
     * Normalize lines from the input.
     */
    private function normalizeLines(string $output): array
    {
        // Split by newlines and handle both Unix and Windows line endings
        return preg_split('/\r?\n/', $output);
    }

    /**
     * Get the indentation level of a line.
     */
    private function getIndentLevel(string $line): int
    {
        preg_match('/^(\s*)/', $line, $matches);
        $spaces = strlen($matches[1] ?? '');

        // Count arrows as well
        preg_match('/^[\s]*(\->\s*)*/', $line, $arrowMatches);
        $arrowCount = substr_count($arrowMatches[0] ?? '', '->');

        return (int) floor($spaces / self::INDENT_SIZE) + $arrowCount;
    }

    /**
     * Parse a single line into an OperationNode.
     */
    private function parseLine(string $line): OperationNode
    {
        $node = new OperationNode();

        // Extract operation type (everything before the first parenthesis or end)
        if (preg_match('/^([^(]+)/', $line, $matches)) {
            $node->setOperation(trim($matches[1]));
        }

        // Extract estimated metrics (cost=X rows=Y)
        if (preg_match('/\(cost=([0-9.]+)\s+rows=([0-9]+)\)/', $line, $matches)) {
            $node->setEstimatedCost((float) $matches[1]);
            $node->setEstimatedRows((int) $matches[2]);
        }

        // Extract actual metrics (actual time=X..Y rows=Z loops=W)
        if (preg_match('/\(actual time=([0-9.]+)\.\.([0-9.]+)\s+rows=([0-9]+)\s+loops=([0-9]+)\)/', $line, $matches)) {
            $node->setActualTimeFirst((float) $matches[1]);
            $node->setActualTimeLast((float) $matches[2]);
            $node->setActualRows((int) $matches[3]);
            $node->setActualLoops((int) $matches[4]);
            $node->setHasActualMetrics(true);
        }

        // Extract table and index information
        $this->extractTableInfo($line, $node);

        // Extract filter conditions
        $this->extractFilterConditions($line, $node);

        return $node;
    }

    /**
     * Extract table and index information from the line.
     */
    private function extractTableInfo(string $line, OperationNode $node): void
    {
        $utf8Identifier = '(?:`[^`]+`|[^\s()]+)';

        // Index lookup: "Index lookup on TABLE using INDEX"
        if (preg_match('/Index lookup on (' . $utf8Identifier . ') using (' . $utf8Identifier . ')/', $line, $matches)) {
            $node->setTableName($matches[1]);
            $node->setIndexName($matches[2]);
            $node->setAccessType('index_lookup');
        }

        // Index range scan: "Index range scan on TABLE using INDEX"
        if (preg_match('/Index range scan on (' . $utf8Identifier . ') using (' . $utf8Identifier . ')/', $line, $matches)) {
            $node->setTableName($matches[1]);
            $node->setIndexName($matches[2]);
            $node->setAccessType('index_range');
        }

        // Table scan: "Table scan on TABLE"
        if (preg_match('/Table scan on (' . $utf8Identifier . ')/', $line, $matches)) {
            $node->setTableName($matches[1]);
            $node->setAccessType('table_scan');
        }

        // Index scan: "Index scan on TABLE using INDEX"
        if (preg_match('/Index scan on (' . $utf8Identifier . ') using (' . $utf8Identifier . ')/', $line, $matches)) {
            $node->setTableName($matches[1]);
            $node->setIndexName($matches[2]);
            $node->setAccessType('index_scan');
        }

        // Extract key conditions (column = value)
        if (preg_match('/\(([^)]+=[^)]+)\)/', $line, $matches)) {
            // Only if it looks like a key condition, not metrics
            if (!preg_match('/cost=|rows=|time=/', $matches[1])) {
                $node->setKeyCondition($matches[1]);
            }
        }
    }

    /**
     * Extract filter conditions from the line.
     */
    private function extractFilterConditions(string $line, OperationNode $node): void
    {
        // Filter: "Filter: (condition)"
        if (preg_match('/Filter:\s*\((.+)\)(?:\s*\(|$)/U', $line, $matches)) {
            $node->setFilterCondition($matches[1]);
        }

        // Alternative filter pattern
        if (preg_match('/Filter:\s*(.+?)(?:\s*\(cost=|\s*\(actual|$)/', $line, $matches)) {
            $condition = trim($matches[1]);
            if (!empty($condition) && !$node->getFilterCondition()) {
                $node->setFilterCondition($condition);
            }
        }
    }

    /**
     * Merge estimated-only nodes with their actual counterparts.
     * MySQL EXPLAIN ANALYZE can output estimated and actual on separate lines.
     */
    private function mergeEstimatedAndActual(array $nodes): array
    {
        $merged = [];
        $i = 0;

        while ($i < count($nodes)) {
            $current = $nodes[$i];

            // Check if next node is the actual metrics for this estimated node
            if (isset($nodes[$i + 1])) {
                $next = $nodes[$i + 1];

                if ($this->shouldMergeNodes($current, $next)) {
                    $this->mergeNodeMetrics($current, $next);
                    $i += 2;
                    $merged[] = $current;
                    continue;
                }
            }

            // Recursively merge children
            if ($current->hasChildren()) {
                $current->setChildren($this->mergeEstimatedAndActual($current->getChildren()));
            }

            $merged[] = $current;
            $i++;
        }

        return $merged;
    }

    /**
     * Check if two nodes should be merged (estimated + actual).
     */
    private function shouldMergeNodes(OperationNode $estimated, OperationNode $actual): bool
    {
        // Same operation type and depth
        if ($estimated->getOperation() !== $actual->getOperation()) {
            return false;
        }

        if ($estimated->getDepth() !== $actual->getDepth()) {
            return false;
        }

        // One has estimated, one has actual
        return !$estimated->hasActualMetrics() && $actual->hasActualMetrics();
    }

    /**
     * Merge actual metrics from one node into another.
     */
    private function mergeNodeMetrics(OperationNode $target, OperationNode $source): void
    {
        $target->setActualTimeFirst($source->getActualTimeFirst());
        $target->setActualTimeLast($source->getActualTimeLast());
        $target->setActualRows($source->getActualRows());
        $target->setActualLoops($source->getActualLoops());
        $target->setHasActualMetrics(true);

        // Merge children
        if ($source->hasChildren()) {
            foreach ($source->getChildren() as $child) {
                $target->addChild($child);
            }
        }
    }
}
