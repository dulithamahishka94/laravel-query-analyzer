<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Models\AlertLog;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Models\AnalyzedRequest;
use GladeHQ\QueryLens\Models\QueryAggregate;
use GladeHQ\QueryLens\Models\TopQuery;
use GladeHQ\QueryLens\Storage\DatabaseQueryStorage;
use Orchestra\Testbench\TestCase;

class ResetTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('query-lens.storage.driver', 'database');
        
        // Setup default config for connection and table prefix
        $app['config']->set('query-lens.storage.connection', 'sqlite');
        $app['config']->set('query-lens.storage.table_prefix', 'query_lens_');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /** @test */
    public function it_clears_all_tables_on_reset()
    {
        // 1. Seed data
        AnalyzedRequest::create(['id' => 'req-1', 'method' => 'GET', 'path' => '/']);
        AnalyzedQuery::create([
            'id' => 'q-1', 'request_id' => 'req-1', 'sql' => 'select *', 'sql_hash' => 'hash', 
            'sql_normalized' => 'select *', 'bindings' => [], 'time' => 1
        ]);
        QueryAggregate::create([
            'period_type' => 'hour', 'period_start' => now(), 'total_queries' => 1, 'slow_queries' => 0, 
            'avg_time' => 1, 'p50_time' => 1, 'p95_time' => 1, 'p99_time' => 1, 'max_time' => 1, 'min_time' => 1
        ]);
        TopQuery::create([
            'ranking_type' => 'slowest', 'period' => 'day', 'period_start' => now(), 'sql_hash' => 'hash', 
            'sql_sample' => 'select *', 'rank' => 1, 'count' => 1, 'avg_time' => 1, 'max_time' => 1, 'total_time' => 1, 'issue_count' => 0
        ]);
        AlertLog::create([
            'alert_id' => 'a-1', 'alert_name' => 'Test Alert', 'alert_type' => 'slow_query', 'message' => 'alert', 'context' => [], 'occurred_at' => now()
        ]);

        $this->assertEquals(1, AnalyzedRequest::count());
        $this->assertEquals(1, AnalyzedQuery::count());
        $this->assertEquals(1, QueryAggregate::count());
        $this->assertEquals(1, TopQuery::count());
        $this->assertEquals(1, AlertLog::count());

        // 2. Clear
        $storage = new DatabaseQueryStorage();
        $storage->clear();

        // 3. Verify empty
        $this->assertEquals(0, AnalyzedRequest::count(), 'Requests not cleared');
        $this->assertEquals(0, AnalyzedQuery::count(), 'Queries not cleared');
        $this->assertEquals(0, QueryAggregate::count(), 'Aggregates not cleared');
        $this->assertEquals(0, TopQuery::count(), 'Top queries not cleared');
        $this->assertEquals(0, AlertLog::count(), 'Alert logs not cleared');
    }
}
