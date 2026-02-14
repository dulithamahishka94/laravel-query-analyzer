<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

readonly class ComplexityAnalysis
{
    public function __construct(
        public int $score,
        public ComplexityLevel $level,
        public int $joins,
        public int $subqueries,
        public int $conditions,
    ) {}

    public static function analyze(string $sql, array $weights = []): self
    {
        $upper = strtoupper($sql);

        $joinCount = substr_count($upper, 'JOIN');
        $subqueryCount = max(0, substr_count($upper, 'SELECT') - 1);
        $conditionCount = substr_count($upper, 'WHERE') + substr_count($upper, 'HAVING');
        $orderByCount = substr_count($upper, 'ORDER BY');
        $groupByCount = substr_count($upper, 'GROUP BY');

        $defaultWeights = [
            'joins' => 2,
            'subqueries' => 3,
            'conditions' => 1,
            'order_by' => 1,
            'group_by' => 1,
        ];

        $weights = array_merge($defaultWeights, $weights);

        $score = ($joinCount * $weights['joins'])
            + ($subqueryCount * $weights['subqueries'])
            + ($conditionCount * $weights['conditions'])
            + ($orderByCount * $weights['order_by'])
            + ($groupByCount * $weights['group_by']);

        $level = ComplexityLevel::fromScore($score);

        return new self(
            score: $score,
            level: $level,
            joins: $joinCount,
            subqueries: $subqueryCount,
            conditions: $conditionCount,
        );
    }

    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'level' => $this->level->value,
            'joins' => $this->joins,
            'subqueries' => $this->subqueries,
            'conditions' => $this->conditions,
        ];
    }
}