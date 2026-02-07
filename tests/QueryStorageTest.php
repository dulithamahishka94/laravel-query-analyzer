<?php

namespace GladeHQ\QueryLens\Tests;

use Carbon\Carbon;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use PHPUnit\Framework\TestCase;

class QueryStorageTest extends TestCase
{
    protected InMemoryQueryStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new InMemoryQueryStorage();
    }

    public function test_store_and_get_queries(): void
    {
        $this->storage->store(['sql' => 'SELECT 1', 'time' => 0.1, 'timestamp' => time()]);
        $this->storage->store(['sql' => 'SELECT 2', 'time' => 0.2, 'timestamp' => time()]);

        $queries = $this->storage->get(10);

        $this->assertCount(2, $queries);
        // Most recent first
        $this->assertEquals('SELECT 2', $queries[0]['sql']);
        $this->assertEquals('SELECT 1', $queries[1]['sql']);
    }

    public function test_get_respects_limit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->storage->store(['sql' => "SELECT {$i}", 'timestamp' => time()]);
        }

        $queries = $this->storage->get(5);

        $this->assertCount(5, $queries);
    }

    public function test_clear_removes_all_queries(): void
    {
        $this->storage->store(['sql' => 'SELECT 1', 'timestamp' => time()]);
        $this->storage->store(['sql' => 'SELECT 2', 'timestamp' => time()]);

        $this->storage->clear();

        $this->assertCount(0, $this->storage->get(10));
    }

    public function test_get_by_request_filters_correctly(): void
    {
        $this->storage->store(['sql' => 'SELECT 1', 'request_id' => 'req-1', 'timestamp' => time()]);
        $this->storage->store(['sql' => 'SELECT 2', 'request_id' => 'req-2', 'timestamp' => time()]);
        $this->storage->store(['sql' => 'SELECT 3', 'request_id' => 'req-1', 'timestamp' => time()]);

        $queries = $this->storage->getByRequest('req-1');

        $this->assertCount(2, $queries);
        foreach ($queries as $q) {
            $this->assertEquals('req-1', $q['request_id']);
        }
    }

    public function test_get_stats_calculates_correctly(): void
    {
        $now = time();
        $this->storage->store([
            'sql' => 'SELECT 1',
            'time' => 0.1,
            'timestamp' => $now,
            'analysis' => ['performance' => ['is_slow' => false]],
        ]);
        $this->storage->store([
            'sql' => 'SELECT 2',
            'time' => 0.5,
            'timestamp' => $now,
            'analysis' => ['performance' => ['is_slow' => false]],
        ]);
        $this->storage->store([
            'sql' => 'SELECT 3',
            'time' => 2.0,
            'timestamp' => $now,
            'analysis' => ['performance' => ['is_slow' => true]],
        ]);

        $stats = $this->storage->getStats(
            Carbon::createFromTimestamp($now - 3600),
            Carbon::createFromTimestamp($now + 3600)
        );

        $this->assertEquals(3, $stats['total_queries']);
        $this->assertEquals(1, $stats['slow_queries']);
        $this->assertEqualsWithDelta(0.867, $stats['avg_time'], 0.01);
        $this->assertEquals(2.0, $stats['max_time']);
        $this->assertEquals(2.6, $stats['total_time']);
    }

    public function test_get_top_queries_by_slowest(): void
    {
        $now = time();
        // Query A: slow
        $this->storage->store(['sql' => 'SELECT A', 'time' => 1.0, 'timestamp' => $now, 'analysis' => []]);
        $this->storage->store(['sql' => 'SELECT A', 'time' => 1.2, 'timestamp' => $now, 'analysis' => []]);

        // Query B: fast
        $this->storage->store(['sql' => 'SELECT B', 'time' => 0.1, 'timestamp' => $now, 'analysis' => []]);
        $this->storage->store(['sql' => 'SELECT B', 'time' => 0.15, 'timestamp' => $now, 'analysis' => []]);

        $top = $this->storage->getTopQueries('slowest', 'day', 10);

        $this->assertCount(2, $top);
        $this->assertEquals('SELECT A', $top[0]['sql_sample']);
        $this->assertGreaterThan($top[1]['avg_time'], $top[0]['avg_time']);
    }

    public function test_get_top_queries_by_most_frequent(): void
    {
        $now = time();
        // Query A: 2 times
        $this->storage->store(['sql' => 'SELECT A', 'time' => 0.1, 'timestamp' => $now, 'analysis' => []]);
        $this->storage->store(['sql' => 'SELECT A', 'time' => 0.1, 'timestamp' => $now, 'analysis' => []]);

        // Query B: 5 times
        for ($i = 0; $i < 5; $i++) {
            $this->storage->store(['sql' => 'SELECT B', 'time' => 0.1, 'timestamp' => $now, 'analysis' => []]);
        }

        $top = $this->storage->getTopQueries('most_frequent', 'day', 10);

        $this->assertCount(2, $top);
        $this->assertEquals('SELECT B', $top[0]['sql_sample']);
        $this->assertEquals(5, $top[0]['count']);
    }

    public function test_get_top_queries_by_most_issues(): void
    {
        $now = time();
        // Query A: no issues
        $this->storage->store(['sql' => 'SELECT A', 'time' => 0.1, 'timestamp' => $now, 'analysis' => ['issues' => []]]);

        // Query B: has issues
        $this->storage->store(['sql' => 'SELECT B', 'time' => 0.1, 'timestamp' => $now, 'analysis' => ['issues' => [['type' => 'n+1']]]]);
        $this->storage->store(['sql' => 'SELECT B', 'time' => 0.1, 'timestamp' => $now, 'analysis' => ['issues' => [['type' => 'n+1'], ['type' => 'security']]]]);

        $top = $this->storage->getTopQueries('most_issues', 'day', 10);

        $this->assertCount(2, $top);
        $this->assertEquals('SELECT B', $top[0]['sql_sample']);
        $this->assertEquals(3, $top[0]['issue_count']);
    }

    public function test_store_and_get_requests(): void
    {
        $this->storage->storeRequest('req-1', ['method' => 'GET', 'path' => '/users']);
        $this->storage->storeRequest('req-2', ['method' => 'POST', 'path' => '/users']);

        $requests = $this->storage->getRequests(10);

        $this->assertCount(2, $requests);
    }

    public function test_get_requests_filters_by_method(): void
    {
        $this->storage->storeRequest('req-1', ['method' => 'GET', 'path' => '/users']);
        $this->storage->storeRequest('req-2', ['method' => 'POST', 'path' => '/users']);

        $requests = $this->storage->getRequests(10, ['method' => 'GET']);

        $this->assertCount(1, $requests);
        $this->assertEquals('GET', reset($requests)['method']);
    }

    public function test_get_requests_filters_by_path(): void
    {
        $this->storage->storeRequest('req-1', ['method' => 'GET', 'path' => '/users']);
        $this->storage->storeRequest('req-2', ['method' => 'GET', 'path' => '/posts']);

        $requests = $this->storage->getRequests(10, ['path' => 'users']);

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('users', reset($requests)['path']);
    }

    public function test_get_queries_since_returns_only_newer(): void
    {
        $now = time();
        $this->storage->store(['sql' => 'OLD', 'timestamp' => $now - 100]);
        $this->storage->store(['sql' => 'NEW', 'timestamp' => $now]);

        $queries = $this->storage->getQueriesSince($now - 50);

        $this->assertCount(1, $queries);
        $this->assertEquals('NEW', $queries[0]['sql']);
    }

    public function test_supports_persistence_returns_false(): void
    {
        $this->assertFalse($this->storage->supportsPersistence());
    }

    public function test_get_aggregates_groups_by_period(): void
    {
        $now = Carbon::now();
        $hourAgo = $now->copy()->subHour();

        $this->storage->store([
            'sql' => 'SELECT 1',
            'time' => 0.1,
            'timestamp' => $now->timestamp,
            'analysis' => ['performance' => ['is_slow' => false]],
        ]);
        $this->storage->store([
            'sql' => 'SELECT 2',
            'time' => 0.2,
            'timestamp' => $hourAgo->timestamp,
            'analysis' => ['performance' => ['is_slow' => false]],
        ]);

        $aggregates = $this->storage->getAggregates(
            'hour',
            $hourAgo->copy()->subHour(),
            $now->copy()->addHour()
        );

        $this->assertCount(2, $aggregates);

        foreach ($aggregates as $agg) {
            $this->assertEquals('hour', $agg['period_type']);
            $this->assertArrayHasKey('total_queries', $agg);
            $this->assertArrayHasKey('p50_time', $agg);
            $this->assertArrayHasKey('p95_time', $agg);
            $this->assertArrayHasKey('p99_time', $agg);
        }
    }
}
