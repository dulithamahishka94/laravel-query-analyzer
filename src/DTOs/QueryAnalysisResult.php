<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

class QueryAnalysisResult
{
    public function __construct(
        public readonly QueryType $type,
        public readonly PerformanceRating $performance,
        public readonly float $executionTime,
        public readonly ComplexityAnalysis $complexity,
        public readonly array $recommendations,
        public readonly array $issues,
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