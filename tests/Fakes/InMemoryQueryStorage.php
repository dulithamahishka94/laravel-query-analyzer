<?php

namespace Laravel\QueryAnalyzer\Tests\Fakes;

use Laravel\QueryAnalyzer\Contracts\QueryStorage;

class InMemoryQueryStorage implements QueryStorage
{
    protected array $queries = [];

    public function store(array $query): void
    {
        $this->queries[] = $query;
    }

    public function get(int $limit = 100): array
    {
        return array_slice($this->queries, 0, $limit);
    }

    public function clear(): void
    {
        $this->queries = [];
    }
}
