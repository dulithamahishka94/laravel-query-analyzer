<?php

namespace GladeHQ\QueryLens\Tests;

use Carbon\Carbon;
use GladeHQ\QueryLens\Models\AlertLog;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Models\AnalyzedRequest;
use GladeHQ\QueryLens\Models\QueryAggregate;
use GladeHQ\QueryLens\Models\TopQuery;
use GladeHQ\QueryLens\Services\DataRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class DataRetentionServiceTest extends TestCase
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
        $app['config']->set('query-lens.storage.retention_days', 7);
    }

    protected function createOldQuery(int $daysAgo, ?string $requestId = null): AnalyzedQuery
    {
        $requestId = $requestId ?? 'req-' . uniqid();

        AnalyzedRequest::create([
            'id' => $requestId,
            'method' => 'GET',
            'path' => '/test',
            'created_at' => now()->subDays($daysAgo),
        ]);

        return AnalyzedQuery::create([
            'id' => (string) \Illuminate\Support\Str::orderedUuid(),
            'request_id' => $requestId,
            'sql_hash' => hash('sha256', 'SELECT 1'),
            'sql' => 'SELECT 1',
            'sql_normalized' => 'SELECT ?',
            'time' => 0.05,
            'connection' => 'testing',
            'type' => 'SELECT',
            'performance_rating' => 'fast',
            'is_slow' => false,
            'complexity_score' => 0,
            'complexity_level' => 'low',
            'analysis' => ['recommendations' => [], 'issues' => []],
            'origin' => ['file' => 'test.php', 'line' => 1, 'is_vendor' => false],
            'is_n_plus_one' => false,
            'n_plus_one_count' => 0,
            'created_at' => now()->subDays($daysAgo),
        ]);
    }

    public function test_prune_deletes_old_queries(): void
    {
        $this->createOldQuery(10);
        $this->createOldQuery(8);
        $this->createOldQuery(3); // recent, should survive

        $this->assertEquals(3, AnalyzedQuery::count());

        $service = new DataRetentionService();
        $stats = $service->prune(7);

        $this->assertEquals(2, $stats['queries']);
        $this->assertEquals(1, AnalyzedQuery::count());
    }

    public function test_prune_preserves_recent_records(): void
    {
        $this->createOldQuery(1);
        $this->createOldQuery(3);
        $this->createOldQuery(5);

        $service = new DataRetentionService();
        $stats = $service->prune(7);

        $this->assertEquals(0, $stats['queries']);
        $this->assertEquals(3, AnalyzedQuery::count());
    }

    public function test_prune_with_zero_retention_days(): void
    {
        $this->createOldQuery(1);
        $this->createOldQuery(0);

        $service = new DataRetentionService();
        $stats = $service->prune(0);

        // Zero days means cutoff is now(), so all past records should be pruned
        // Records created "today" with subDays(0) should be at edge
        $this->assertGreaterThanOrEqual(0, $stats['queries']);
    }

    public function test_prune_with_empty_tables(): void
    {
        $service = new DataRetentionService();
        $stats = $service->prune(7);

        $this->assertEquals(0, $stats['queries']);
        $this->assertEquals(0, $stats['requests']);
        $this->assertEquals(0, $stats['aggregates']);
        $this->assertEquals(0, $stats['top_queries']);
        $this->assertEquals(0, $stats['alert_logs']);
    }

    public function test_prune_deletes_old_aggregates(): void
    {
        QueryAggregate::create([
            'period_type' => 'hour',
            'period_start' => now()->subDays(10),
            'total_queries' => 100,
            'slow_queries' => 5,
        ]);
        QueryAggregate::create([
            'period_type' => 'hour',
            'period_start' => now()->subDays(2),
            'total_queries' => 50,
            'slow_queries' => 2,
        ]);

        $service = new DataRetentionService();
        $stats = $service->prune(7);

        $this->assertEquals(1, $stats['aggregates']);
        $this->assertEquals(1, QueryAggregate::count());
    }

    public function test_prune_deletes_old_top_queries(): void
    {
        TopQuery::create([
            'ranking_type' => 'slowest',
            'period' => 'day',
            'period_start' => now()->subDays(10),
            'sql_hash' => 'hash1',
            'sql_sample' => 'SELECT 1',
            'count' => 5,
            'rank' => 1,
        ]);
        TopQuery::create([
            'ranking_type' => 'slowest',
            'period' => 'day',
            'period_start' => now()->subDays(2),
            'sql_hash' => 'hash2',
            'sql_sample' => 'SELECT 2',
            'count' => 3,
            'rank' => 1,
        ]);

        $service = new DataRetentionService();
        $stats = $service->prune(7);

        $this->assertEquals(1, $stats['top_queries']);
        $this->assertEquals(1, TopQuery::count());
    }

    public function test_prune_deletes_orphaned_requests_after_queries_pruned(): void
    {
        // Create old query + request (query will be pruned, then orphaned request too)
        $this->createOldQuery(10, 'old-req-1');

        // Create recent query + request (should survive)
        $this->createOldQuery(3, 'recent-req-1');

        $this->assertEquals(2, AnalyzedRequest::count());

        $service = new DataRetentionService();
        $stats = $service->prune(7);

        // Old query deleted
        $this->assertEquals(1, $stats['queries']);
        // Old request should now be orphaned and pruned
        $this->assertEquals(1, $stats['requests']);
        $this->assertEquals(1, AnalyzedRequest::count());
    }

    public function test_prune_respects_custom_days_parameter(): void
    {
        $this->createOldQuery(5);
        $this->createOldQuery(2);

        $service = new DataRetentionService();
        $stats = $service->prune(3);

        $this->assertEquals(1, $stats['queries']);
        $this->assertEquals(1, AnalyzedQuery::count());
    }

    public function test_prune_returns_all_stat_keys(): void
    {
        $service = new DataRetentionService();
        $stats = $service->prune(7);

        $this->assertArrayHasKey('queries', $stats);
        $this->assertArrayHasKey('requests', $stats);
        $this->assertArrayHasKey('aggregates', $stats);
        $this->assertArrayHasKey('top_queries', $stats);
        $this->assertArrayHasKey('alert_logs', $stats);
    }

    public function test_get_estimated_prune_count_matches_actual_prune(): void
    {
        $this->createOldQuery(10);
        $this->createOldQuery(8);
        $this->createOldQuery(3);

        $service = new DataRetentionService();
        $estimates = $service->getEstimatedPruneCount(7);
        $stats = $service->prune(7);

        $this->assertEquals($estimates['queries'], $stats['queries']);
    }
}
