<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Models\AnalyzedRequest;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Storage\DatabaseQueryStorage;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Mockery;
use Orchestra\Testbench\TestCase;

class DeferredAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('query-lens.storage.connection', 'testing');
        $app['config']->set('query-lens.storage.driver', 'database');
    }

    public function test_store_does_not_update_aggregates_per_query(): void
    {
        $storage = new DatabaseQueryStorage();
        $requestId = (string) Str::orderedUuid();

        // Create the request record first
        AnalyzedRequest::create([
            'id' => $requestId,
            'method' => 'GET',
            'path' => '/test',
            'created_at' => now(),
        ]);

        // Store multiple queries
        for ($i = 0; $i < 5; $i++) {
            $storage->store([
                'id' => (string) Str::orderedUuid(),
                'request_id' => $requestId,
                'request_method' => 'GET',
                'request_path' => '/test',
                'sql' => "SELECT * FROM users WHERE id = {$i}",
                'bindings' => [],
                'time' => 0.1,
                'connection' => 'testing',
                'timestamp' => microtime(true),
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'fast', 'is_slow' => false, 'execution_time' => 0.1],
                    'complexity' => ['score' => 1, 'level' => 'low'],
                    'recommendations' => [],
                    'issues' => [],
                ],
                'origin' => ['file' => 'test.php', 'line' => 1, 'is_vendor' => false],
            ]);
        }

        // Aggregates should NOT be updated yet (query_count should still be 0)
        $request = AnalyzedRequest::find($requestId);
        $this->assertEquals(0, $request->query_count);
    }

    public function test_finalize_request_updates_aggregates_once(): void
    {
        $storage = new DatabaseQueryStorage();
        $requestId = (string) Str::orderedUuid();

        // Create request
        AnalyzedRequest::create([
            'id' => $requestId,
            'method' => 'GET',
            'path' => '/test',
            'created_at' => now(),
        ]);

        // Store multiple queries without aggregation
        for ($i = 0; $i < 3; $i++) {
            $storage->store([
                'id' => (string) Str::orderedUuid(),
                'request_id' => $requestId,
                'request_method' => 'GET',
                'request_path' => '/test',
                'sql' => "SELECT * FROM posts WHERE id = {$i}",
                'bindings' => [],
                'time' => 0.2,
                'connection' => 'testing',
                'timestamp' => microtime(true),
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'fast', 'is_slow' => false, 'execution_time' => 0.2],
                    'complexity' => ['score' => 1, 'level' => 'low'],
                    'recommendations' => [],
                    'issues' => [],
                ],
                'origin' => ['file' => 'test.php', 'line' => 1, 'is_vendor' => false],
            ]);
        }

        // Now finalize
        $storage->finalizeRequest($requestId);

        $request = AnalyzedRequest::find($requestId);
        $this->assertEquals(3, $request->query_count);
        $this->assertEqualsWithDelta(0.2, $request->avg_time, 0.01);
    }

    public function test_finalize_request_with_zero_queries(): void
    {
        $storage = new DatabaseQueryStorage();
        $requestId = (string) Str::orderedUuid();

        AnalyzedRequest::create([
            'id' => $requestId,
            'method' => 'GET',
            'path' => '/empty',
            'created_at' => now(),
        ]);

        // Finalize with no queries stored
        $storage->finalizeRequest($requestId);

        $request = AnalyzedRequest::find($requestId);
        $this->assertEquals(0, $request->query_count);
    }

    public function test_terminate_middleware_calls_finalize_request(): void
    {
        $storageMock = Mockery::mock(QueryStorage::class);
        $storageMock->shouldReceive('finalizeRequest')
            ->once()
            ->with('test-request-123');

        $analyzer = $this->createAnalyzer();
        $analyzer->setRequestId('test-request-123');

        $middleware = new AnalyzeQueryMiddleware($analyzer, $storageMock);

        $request = Request::create('/api/users', 'GET');
        $response = new Response('ok');

        $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $middleware->terminate($request, $response);
    }

    public function test_terminate_middleware_skips_finalize_for_dashboard_requests(): void
    {
        $storageMock = Mockery::mock(QueryStorage::class);
        $storageMock->shouldNotReceive('finalizeRequest');

        $analyzer = $this->createAnalyzer();

        $middleware = new AnalyzeQueryMiddleware($analyzer, $storageMock);

        $request = Request::create('/query-lens/api/queries', 'GET');
        $response = new Response('ok');

        $middleware->handle($request, function () use ($response) {
            return $response;
        });

        $middleware->terminate($request, $response);
    }

    public function test_cache_storage_finalize_is_noop(): void
    {
        $cacheStorage = new \GladeHQ\QueryLens\Storage\CacheQueryStorage();

        // Should not throw or error
        $cacheStorage->finalizeRequest('any-request-id');

        $this->assertTrue(true); // No exception means success
    }

    protected function createAnalyzer(): QueryAnalyzer
    {
        return new QueryAnalyzer([
            'performance_thresholds' => [
                'fast' => 0.1,
                'moderate' => 0.5,
                'slow' => 1.0,
            ],
            'analysis' => [
                'min_execution_time' => 0.0,
            ],
            'trace_origins' => false,
        ], new InMemoryQueryStorage());
    }
}
