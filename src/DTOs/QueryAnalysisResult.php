<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

readonly class QueryAnalysisResult
{
    public function __construct(
        public string $sql,
        public array $bindings,
        public float $time,
        public string $connection,
        public float $timestamp,
        public QueryType $type,
        public PerformanceRating $performance,
        public ComplexityAnalysis $complexity,
        public array $recommendations,
        public array $issues,
    ) {}

    public function toArray(): array
    {
        return [
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'time' => $this->time,
            'connection' => $this->connection,
            'timestamp' => $this->timestamp,
            'analysis' => [
                'type' => $this->type->value,
                'performance' => $this->performance->toArray(),
                'complexity' => $this->complexity->toArray(),
                'recommendations' => $this->recommendations,
                'issues' => $this->issues,
            ],
        ];
    }
}