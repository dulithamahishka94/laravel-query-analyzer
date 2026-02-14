<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Contracts;

interface AIProvider
{
    /**
     * Analyze a SQL query and return optimization suggestions.
     *
     * @param string $sql The SQL query to analyze
     * @param array $context Additional context (EXPLAIN output, schema, frequency, duration, index info)
     * @return array{suggestions: array, raw_response: ?string, provider: string}
     */
    public function analyze(string $sql, array $context = []): array;

    /**
     * Get the provider name identifier.
     */
    public function getName(): string;

    /**
     * Check if the provider is properly configured and ready.
     */
    public function isAvailable(): bool;
}
