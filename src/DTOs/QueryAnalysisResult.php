<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

readonly class QueryAnalysisResult
{
    public function __construct(
        public QueryType $type,
        public PerformanceRating $performance,
        public float $executionTime,
        public ComplexityAnalysis $complexity,
        public array $recommendations,
        public array $issues,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'performance' => $this->performance->toArray($this->executionTime),
            'complexity' => $this->complexity->toArray(),
            'recommendations' => $this->recommendations,
            'issues' => $this->issues,
        ];
    }
}