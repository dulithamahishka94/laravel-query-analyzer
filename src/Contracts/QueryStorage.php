<?php

namespace Laravel\QueryAnalyzer\Contracts;

interface QueryStorage
{
    /**
     * Store a query analysis result.
     *
     * @param array $query
     * @return void
     */
    public function store(array $query): void;

    /**
     * Retrieve stored queries.
     *
     * @param int $limit
     * @return array
     */
    public function get(int $limit = 100): array;

    /**
     * Clear all stored queries.
     *
     * @return void
     */
    public function clear(): void;
}
