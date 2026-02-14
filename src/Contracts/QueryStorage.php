<?php

namespace GladeHQ\QueryLens\Contracts;

use Carbon\Carbon;

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

    /**
     * Get queries for a specific request.
     *
     * @param string $requestId
     * @return array
     */
    public function getByRequest(string $requestId): array;

    /**
     * Get aggregated statistics for a time range.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public function getStats(Carbon $start, Carbon $end): array;

    /**
     * Get top queries by ranking type.
     *
     * @param string $type 'slowest', 'most_frequent', 'most_issues'
     * @param string $period 'hour', 'day', 'week'
     * @param int $limit
     * @return array
     */
    public function getTopQueries(string $type, string $period, int $limit = 10): array;

    /**
     * Get pre-computed aggregates for charting.
     *
     * @param string $periodType 'hour' or 'day'
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    public function getAggregates(string $periodType, Carbon $start, Carbon $end): array;

    /**
     * Store or update request metadata.
     *
     * @param string $requestId
     * @param array $data
     * @return void
     */
    public function storeRequest(string $requestId, array $data): void;

    /**
     * Get all requests with basic stats.
     *
     * @param int $limit
     * @param array $filters
     * @return array
     */
    public function getRequests(int $limit = 100, array $filters = []): array;

    /**
     * Get queries since a specific timestamp for polling.
     *
     * @param float $since Unix timestamp
     * @param int $limit
     * @return array
     */
    public function getQueriesSince(float $since, int $limit = 100): array;

    /**
     * Search historical queries with filters and pagination.
     *
     * Supported filters:
     *   - sql_like: LIKE search on normalized SQL
     *   - table_name: filter by table name
     *   - time_from / time_to: date range (Carbon-parseable strings)
     *   - min_duration / max_duration: execution time range (float seconds)
     *   - type: query type (SELECT, INSERT, UPDATE, DELETE)
     *   - is_slow: boolean filter
     *   - page / per_page: pagination
     *
     * @param array $filters
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function search(array $filters = []): array;

    /**
     * Finalize request aggregation at end of request lifecycle.
     * Called once from terminate middleware instead of per-query.
     *
     * @param string $requestId
     * @return void
     */
    public function finalizeRequest(string $requestId): void;

    /**
     * Check if storage driver supports persistence.
     *
     * @return bool
     */
    public function supportsPersistence(): bool;
}
