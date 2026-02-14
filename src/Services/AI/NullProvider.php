<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services\AI;

use GladeHQ\QueryLens\Contracts\AIProvider;

/**
 * Null provider that returns empty results when no AI is configured.
 * This is the default provider ensuring graceful degradation.
 */
class NullProvider implements AIProvider
{
    public function analyze(string $sql, array $context = []): array
    {
        return [
            'suggestions' => [],
            'raw_response' => null,
            'provider' => $this->getName(),
        ];
    }

    public function getName(): string
    {
        return 'null';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
