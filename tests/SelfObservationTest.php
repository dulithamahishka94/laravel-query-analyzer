<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use GladeHQ\QueryLens\Storage\DatabaseQueryStorage;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

/**
 * Tests that Query Lens never records its own internal queries.
 *
 * The "self-observation problem" occurs when the package's own database
 * or cache operations fire QueryExecuted / CacheHit events that get
 * captured by the QueryListener. This pollutes the data with entries
 * like "select * from query_lens_requests" appearing in the requests list.
 *
 * Defense layers tested here:
 * 1. SQL table-prefix filtering in QueryAnalyzer::recordQuery()
 * 2. Cache key filtering in QueryAnalyzer::recordCacheInteraction()
 * 3. withoutRecording() guard in DatabaseQueryStorage
 * 4. withoutRecording() guard in CacheQueryStorage
 */
class SelfObservationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function makeAnalyzer(array $configOverrides = []): array
    {
        $storage = new InMemoryQueryStorage();
        $config = array_merge([
            'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
            'excluded_patterns' => [],
            'storage' => ['table_prefix' => 'query_lens_'],
        ], $configOverrides);

        $analyzer = new QueryAnalyzer($config, $storage);
        $analyzer->setRequestId('req-self-obs-test');

        return [$analyzer, $storage];
    }

    // ---------------------------------------------------------------
    // Layer 1: SQL table-prefix filtering in QueryAnalyzer
    // ---------------------------------------------------------------

    public function test_queries_to_query_lens_requests_table_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'select `id`, `method`, `path`, `query_count`, `created_at` from `query_lens_requests` order by `created_at` desc limit 10',
            [],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_to_query_lens_queries_table_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'insert into `query_lens_queries` (`id`, `request_id`, `sql`, `time`) values (?, ?, ?, ?)',
            ['uuid-1', 'req-1', 'SELECT 1', 0.01],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_to_query_lens_aggregates_table_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'select * from `query_lens_aggregates` where `period_type` = ? and `period_start` >= ?',
            ['hour', '2024-01-01'],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_to_query_lens_alerts_table_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'select * from `query_lens_alerts` where `enabled` = 1',
            [],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_to_query_lens_alert_logs_table_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'insert into `query_lens_alert_logs` (`alert_id`, `message`) values (?, ?)',
            [1, 'test'],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_to_query_lens_top_queries_table_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'select * from `query_lens_top_queries` where `ranking_type` = ?',
            ['slowest'],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_custom_table_prefix_is_respected(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer([
            'storage' => ['table_prefix' => 'ql_custom_'],
        ]);

        // Query using custom prefix should be excluded
        $analyzer->recordQuery('select * from `ql_custom_requests` order by `created_at` desc', [], 0.05);
        $this->assertCount(0, $storage->getAllQueries());

        // Query using default prefix should NOT be excluded (wrong prefix)
        $analyzer->recordQuery('select * from `query_lens_requests` order by `created_at` desc', [], 0.05);
        // But this is caught by the fallback laravel_query_lens_ check -- it won't match query_lens_
        // Actually query_lens_ is not the same as laravel_query_lens_ so this SHOULD be recorded
        // Let's check: the SQL "query_lens_requests" does NOT contain "ql_custom_" nor "laravel_query_lens_"
        $this->assertCount(1, $storage->getAllQueries());
    }

    public function test_table_prefix_in_bindings_is_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        // Database-backed cache drivers may put table names in bindings
        $analyzer->recordQuery(
            'select `value` from `cache` where `key` = ?',
            ['query_lens_some_key'],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Layer 1: Cache key filtering in recordCacheInteraction
    // ---------------------------------------------------------------

    public function test_cache_interactions_with_internal_keys_are_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordCacheInteraction('hit', 'laravel_query_lens_queries_v3', []);
        $analyzer->recordCacheInteraction('miss', 'laravel_query_lens_requests_v1', []);
        $analyzer->recordCacheInteraction('hit', 'laravel_query_lens_custom_key', []);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_non_internal_cache_interactions_are_recorded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordCacheInteraction('hit', 'users:profile:42', []);

        $this->assertCount(1, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Layer 1: Normal queries are NOT excluded
    // ---------------------------------------------------------------

    public function test_normal_user_queries_are_not_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery('SELECT * FROM users WHERE id = ?', [1], 0.05);
        $analyzer->recordQuery('UPDATE orders SET status = ? WHERE id = ?', ['shipped', 42], 0.05);
        $analyzer->recordQuery('INSERT INTO posts (title) VALUES (?)', ['Hello'], 0.05);

        $this->assertCount(3, $storage->getAllQueries());
    }

    public function test_queries_with_lens_in_column_name_are_not_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        // "lens" in a column name should not trigger the exclusion
        $analyzer->recordQuery('SELECT lens_type FROM cameras WHERE id = ?', [1], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Layer 2: DatabaseQueryStorage withoutRecording guard
    // ---------------------------------------------------------------

    public function test_database_storage_disables_recording_during_store(): void
    {
        $analyzer = \Mockery::mock(QueryAnalyzer::class);
        $analyzer->shouldReceive('isRecording')->andReturn(true);
        $analyzer->shouldReceive('disableRecording')->once();
        $analyzer->shouldReceive('enableRecording')->once();

        $storage = new DatabaseQueryStorage();
        $storage->setAnalyzer($analyzer);

        // store() will fail because there's no actual database, but the
        // recording guard should still be called. We catch the DB exception.
        try {
            $storage->store([
                'id' => 'test-id',
                'request_id' => 'req-1',
                'sql' => 'SELECT 1',
                'bindings' => [],
                'time' => 0.01,
                'connection' => 'default',
                'analysis' => [],
                'origin' => ['file' => 'test', 'line' => 1, 'is_vendor' => false],
            ]);
        } catch (\Exception $e) {
            // Expected -- no real database in unit test
        }

        // Mockery will verify the expectations
    }

    public function test_database_storage_restores_recording_state_on_exception(): void
    {
        $analyzer = \Mockery::mock(QueryAnalyzer::class);
        $analyzer->shouldReceive('isRecording')->andReturn(true);
        $analyzer->shouldReceive('disableRecording')->once();
        $analyzer->shouldReceive('enableRecording')->once();

        $storage = new DatabaseQueryStorage();
        $storage->setAnalyzer($analyzer);

        // get() will fail because there's no actual database
        try {
            $storage->get(10);
        } catch (\Exception $e) {
            // Expected
        }

        // enableRecording must be called even on exception (finally block)
    }

    public function test_database_storage_does_not_enable_if_was_already_disabled(): void
    {
        $analyzer = \Mockery::mock(QueryAnalyzer::class);
        $analyzer->shouldReceive('isRecording')->andReturn(false); // Already disabled
        $analyzer->shouldReceive('disableRecording')->once();
        $analyzer->shouldReceive('enableRecording')->never(); // Should NOT re-enable

        $storage = new DatabaseQueryStorage();
        $storage->setAnalyzer($analyzer);

        try {
            $storage->get(10);
        } catch (\Exception $e) {
            // Expected
        }
    }

    // ---------------------------------------------------------------
    // Layer 2: CacheQueryStorage withoutRecording guard
    // ---------------------------------------------------------------

    public function test_cache_storage_disables_recording_during_store(): void
    {
        $analyzer = \Mockery::mock(QueryAnalyzer::class);
        $analyzer->shouldReceive('isRecording')->andReturn(true);
        $analyzer->shouldReceive('disableRecording')->atLeast()->once();
        $analyzer->shouldReceive('enableRecording')->atLeast()->once();

        $storage = new CacheQueryStorage('array');
        $storage->setAnalyzer($analyzer);

        $storage->store([
            'id' => 'test-id',
            'request_id' => 'req-1',
            'sql' => 'SELECT 1',
            'bindings' => [],
            'time' => 0.01,
            'timestamp' => microtime(true),
            'analysis' => ['issues' => []],
        ]);
    }

    public function test_cache_storage_disables_recording_during_get(): void
    {
        $analyzer = \Mockery::mock(QueryAnalyzer::class);
        $analyzer->shouldReceive('isRecording')->andReturn(true);
        $analyzer->shouldReceive('disableRecording')->once();
        $analyzer->shouldReceive('enableRecording')->once();

        $storage = new CacheQueryStorage('array');
        $storage->setAnalyzer($analyzer);

        $result = $storage->get(10);

        $this->assertIsArray($result);
    }

    public function test_cache_storage_disables_recording_during_clear(): void
    {
        $analyzer = \Mockery::mock(QueryAnalyzer::class);
        $analyzer->shouldReceive('isRecording')->andReturn(true);
        $analyzer->shouldReceive('disableRecording')->once();
        $analyzer->shouldReceive('enableRecording')->once();

        $storage = new CacheQueryStorage('array');
        $storage->setAnalyzer($analyzer);

        $storage->clear();
    }

    // ---------------------------------------------------------------
    // Integration: Service provider wires analyzer into storage
    // ---------------------------------------------------------------

    public function test_service_provider_wires_analyzer_into_storage(): void
    {
        $this->app['config']->set('query-lens.enabled', true);
        $this->app['config']->set('query-lens.storage.driver', 'cache');

        // Force re-resolution
        $this->app->forgetInstance(\GladeHQ\QueryLens\Contracts\QueryStorage::class);
        $this->app->forgetInstance(QueryAnalyzer::class);

        // Resolve the analyzer (which triggers the wiring)
        $analyzer = $this->app->make(QueryAnalyzer::class);
        $storage = $this->app->make(\GladeHQ\QueryLens\Contracts\QueryStorage::class);

        // Use reflection to verify the analyzer was injected
        $reflection = new \ReflectionClass($storage);
        if ($reflection->hasProperty('analyzer')) {
            $prop = $reflection->getProperty('analyzer');
            $prop->setAccessible(true);
            $this->assertSame($analyzer, $prop->getValue($storage));
        } else {
            $this->markTestSkipped('Storage driver does not have analyzer property');
        }
    }

    // ---------------------------------------------------------------
    // Hardcoded exclusions still work
    // ---------------------------------------------------------------

    public function test_legacy_cache_key_exclusion_still_works(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'SELECT value FROM cache_store WHERE key = ?',
            ['laravel_query_lens_queries_v3'],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_explain_queries_still_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery('EXPLAIN SELECT * FROM users', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Layer 3: Route-path safety net in recordQuery()
    // ---------------------------------------------------------------

    public function test_session_queries_excluded_on_query_lens_routes(): void
    {
        // Simulate a request to the Query Lens dashboard
        $request = \Illuminate\Http\Request::create('/query-lens/api/requests', 'GET');
        $this->app->instance('request', $request);

        [$analyzer, $storage] = $this->makeAnalyzer();

        // These are the exact queries that were leaking through:
        // Session INSERT and SELECT during dashboard access
        $analyzer->recordQuery(
            'insert into `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) values (?, ?, ?, ?, ?, ?)',
            ['abc123', null, '127.0.0.1', 'Mozilla', 'payload', time()],
            0.05
        );

        $analyzer->recordQuery(
            'select * from `sessions` where `id` = ? limit 1',
            ['abc123'],
            0.05
        );

        // Neither should be recorded
        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_any_query_excluded_on_query_lens_routes(): void
    {
        // Even non-session queries should be excluded during dashboard requests
        $request = \Illuminate\Http\Request::create('/query-lens', 'GET');
        $this->app->instance('request', $request);

        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery('SELECT * FROM users WHERE id = ?', [1], 0.05);
        $analyzer->recordQuery('UPDATE settings SET value = ? WHERE key = ?', ['dark', 'theme'], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_excluded_on_filament_panel_query_lens_routes(): void
    {
        // Filament panel pages use paths like /admin/query-lens
        $request = \Illuminate\Http\Request::create('/admin/query-lens', 'GET');
        $this->app->instance('request', $request);

        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery(
            'select * from `sessions` where `id` = ? limit 1',
            ['abc123'],
            0.05
        );

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_queries_not_excluded_on_non_query_lens_routes(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/users', 'GET');
        $this->app->instance('request', $request);

        [$analyzer, $storage] = $this->makeAnalyzer();

        $analyzer->recordQuery('SELECT * FROM users WHERE id = ?', [1], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Layer 0: Early container-level detection
    // ---------------------------------------------------------------

    public function test_service_provider_is_query_lens_request_detects_standalone_routes(): void
    {
        // Test the static detection method directly via reflection.
        // In production, this runs inside the scoped binding when !runningInConsole().
        $request = \Illuminate\Http\Request::create('/query-lens/api/stats', 'GET');
        $this->app->instance('request', $request);

        $method = new \ReflectionMethod(\GladeHQ\QueryLens\QueryLensServiceProvider::class, 'isQueryLensRequest');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, $this->app));
    }

    public function test_service_provider_is_query_lens_request_detects_filament_panel_routes(): void
    {
        $request = \Illuminate\Http\Request::create('/admin/query-lens', 'GET');
        $this->app->instance('request', $request);

        $method = new \ReflectionMethod(\GladeHQ\QueryLens\QueryLensServiceProvider::class, 'isQueryLensRequest');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke(null, $this->app));
    }

    public function test_service_provider_is_query_lens_request_ignores_normal_routes(): void
    {
        $request = \Illuminate\Http\Request::create('/api/users', 'GET');
        $this->app->instance('request', $request);

        $method = new \ReflectionMethod(\GladeHQ\QueryLens\QueryLensServiceProvider::class, 'isQueryLensRequest');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke(null, $this->app));
    }

    public function test_middleware_disables_recording_for_filament_panel_routes(): void
    {
        $analyzerMock = \Mockery::mock(QueryAnalyzer::class);
        $analyzerMock->shouldReceive('getRequestId')->andReturn('test-id');
        $analyzerMock->shouldReceive('disableRecording')->once();

        $storageMock = \Mockery::mock(\GladeHQ\QueryLens\Contracts\QueryStorage::class);

        $middleware = new \GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware($analyzerMock, $storageMock);

        // Filament panel route: /admin/query-lens
        $request = \Illuminate\Http\Request::create('/admin/query-lens', 'GET');

        $middleware->handle($request, function ($req) {
            return response('OK');
        });
    }
}
