<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class QuerySearchTest extends TestCase
{
    protected InMemoryQueryStorage $storage;
    protected QueryAnalyzer $analyzer;

    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = new InMemoryQueryStorage();
        $this->analyzer = new QueryAnalyzer(
            [
                'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
            ],
            $this->storage
        );

        $this->seedQueries();
    }

    protected function seedQueries(): void
    {
        $this->analyzer->setRequestId('req-1');
        $this->analyzer->recordQuery('SELECT * FROM users WHERE id = 1', [], 0.05);
        $this->analyzer->recordQuery('SELECT * FROM posts WHERE user_id = 1', [], 0.3);
        $this->analyzer->recordQuery('INSERT INTO logs (message) VALUES ("test")', [], 0.02);
        $this->analyzer->recordQuery('UPDATE users SET name = "updated" WHERE id = 1', [], 1.5);
        $this->analyzer->recordQuery('DELETE FROM sessions WHERE expired = 1', [], 0.8);
    }

    // ---------------------------------------------------------------
    // Filter by SQL LIKE
    // ---------------------------------------------------------------

    public function test_search_by_sql_like(): void
    {
        $result = $this->storage->search(['sql_like' => 'users']);

        $this->assertGreaterThan(0, $result['total']);
        foreach ($result['data'] as $query) {
            $this->assertStringContainsStringIgnoringCase('users', $query['sql']);
        }
    }

    public function test_search_by_sql_like_no_match(): void
    {
        $result = $this->storage->search(['sql_like' => 'nonexistent_table']);

        $this->assertSame(0, $result['total']);
        $this->assertEmpty($result['data']);
    }

    // ---------------------------------------------------------------
    // Filter by table name
    // ---------------------------------------------------------------

    public function test_search_by_table_name(): void
    {
        $result = $this->storage->search(['table_name' => 'posts']);

        $this->assertGreaterThan(0, $result['total']);
        foreach ($result['data'] as $query) {
            $this->assertStringContainsStringIgnoringCase('posts', $query['sql']);
        }
    }

    // ---------------------------------------------------------------
    // Filter by query type
    // ---------------------------------------------------------------

    public function test_search_by_type_select(): void
    {
        $result = $this->storage->search(['type' => 'SELECT']);

        $this->assertSame(2, $result['total']);
        foreach ($result['data'] as $query) {
            $this->assertSame('SELECT', $query['analysis']['type']);
        }
    }

    public function test_search_by_type_insert(): void
    {
        $result = $this->storage->search(['type' => 'INSERT']);

        $this->assertSame(1, $result['total']);
    }

    public function test_search_by_type_case_insensitive(): void
    {
        $result = $this->storage->search(['type' => 'select']);

        $this->assertSame(2, $result['total']);
    }

    // ---------------------------------------------------------------
    // Filter by duration
    // ---------------------------------------------------------------

    public function test_search_by_min_duration(): void
    {
        $result = $this->storage->search(['min_duration' => 0.3]);

        foreach ($result['data'] as $query) {
            $this->assertGreaterThanOrEqual(0.3, $query['time']);
        }
    }

    public function test_search_by_max_duration(): void
    {
        $result = $this->storage->search(['max_duration' => 0.1]);

        foreach ($result['data'] as $query) {
            $this->assertLessThanOrEqual(0.1, $query['time']);
        }
    }

    public function test_search_by_duration_range(): void
    {
        $result = $this->storage->search(['min_duration' => 0.1, 'max_duration' => 1.0]);

        foreach ($result['data'] as $query) {
            $this->assertGreaterThanOrEqual(0.1, $query['time']);
            $this->assertLessThanOrEqual(1.0, $query['time']);
        }
    }

    // ---------------------------------------------------------------
    // Filter by is_slow
    // ---------------------------------------------------------------

    public function test_search_by_is_slow_true(): void
    {
        $result = $this->storage->search(['is_slow' => true]);

        foreach ($result['data'] as $query) {
            $this->assertTrue($query['analysis']['performance']['is_slow']);
        }
    }

    public function test_search_by_is_slow_false(): void
    {
        $result = $this->storage->search(['is_slow' => false]);

        foreach ($result['data'] as $query) {
            $this->assertFalse($query['analysis']['performance']['is_slow']);
        }
    }

    // ---------------------------------------------------------------
    // Combined filters
    // ---------------------------------------------------------------

    public function test_search_with_multiple_filters(): void
    {
        $result = $this->storage->search([
            'type' => 'SELECT',
            'min_duration' => 0.1,
        ]);

        foreach ($result['data'] as $query) {
            $this->assertSame('SELECT', $query['analysis']['type']);
            $this->assertGreaterThanOrEqual(0.1, $query['time']);
        }
    }

    // ---------------------------------------------------------------
    // Pagination
    // ---------------------------------------------------------------

    public function test_search_pagination_defaults(): void
    {
        $result = $this->storage->search([]);

        $this->assertSame(1, $result['page']);
        $this->assertSame(15, $result['per_page']);
        $this->assertSame(5, $result['total']);
    }

    public function test_search_pagination_custom_per_page(): void
    {
        $result = $this->storage->search(['per_page' => 2, 'page' => 1]);

        $this->assertCount(2, $result['data']);
        $this->assertSame(5, $result['total']);
        $this->assertSame(1, $result['page']);
        $this->assertSame(2, $result['per_page']);
    }

    public function test_search_pagination_second_page(): void
    {
        $result = $this->storage->search(['per_page' => 2, 'page' => 2]);

        $this->assertCount(2, $result['data']);
        $this->assertSame(2, $result['page']);
    }

    public function test_search_pagination_last_page(): void
    {
        $result = $this->storage->search(['per_page' => 2, 'page' => 3]);

        $this->assertCount(1, $result['data']);
    }

    public function test_search_pagination_beyond_total(): void
    {
        $result = $this->storage->search(['per_page' => 2, 'page' => 100]);

        $this->assertEmpty($result['data']);
        $this->assertSame(5, $result['total']);
    }

    public function test_search_per_page_capped_at_100(): void
    {
        $result = $this->storage->search(['per_page' => 500]);

        $this->assertSame(100, $result['per_page']);
    }

    // ---------------------------------------------------------------
    // Empty / no filters
    // ---------------------------------------------------------------

    public function test_search_no_filters_returns_all(): void
    {
        $result = $this->storage->search([]);

        $this->assertSame(5, $result['total']);
    }

    public function test_search_empty_storage_returns_empty(): void
    {
        $emptyStorage = new InMemoryQueryStorage();
        $result = $emptyStorage->search(['type' => 'SELECT']);

        $this->assertSame(0, $result['total']);
        $this->assertEmpty($result['data']);
    }

    // ---------------------------------------------------------------
    // Invalid filters are ignored gracefully
    // ---------------------------------------------------------------

    public function test_search_with_unknown_filter_keys(): void
    {
        // Unknown keys should not cause errors
        $result = $this->storage->search(['unknown_filter' => 'value']);

        $this->assertSame(5, $result['total']);
    }

    // ---------------------------------------------------------------
    // Cache storage returns empty gracefully
    // ---------------------------------------------------------------

    public function test_cache_storage_search_returns_empty_result(): void
    {
        $cacheStorage = new CacheQueryStorage();
        $result = $cacheStorage->search(['type' => 'SELECT']);

        $this->assertSame(0, $result['total']);
        $this->assertEmpty($result['data']);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
    }

    // ---------------------------------------------------------------
    // Route registration
    // ---------------------------------------------------------------

    public function test_search_route_is_registered(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Route::has('query-lens.api.v2.search'),
            'Search route should be registered'
        );
    }
}
