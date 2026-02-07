<?php

namespace Coderflex\QueryLens\Tests;

use Coderflex\QueryLens\Models\AnalyzedQuery;
use Coderflex\QueryLens\QueryAnalyzer;
use Coderflex\QueryLens\Storage\DatabaseQueryStorage;
use Orchestra\Testbench\TestCase;

class NPlusOneTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('query-lens.storage.driver', 'database');
        
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
    public function it_detects_n_plus_one_after_threshold()
    {
        $storage = new DatabaseQueryStorage();
        $analyzer = new QueryAnalyzer(['analysis' => ['min_execution_time' => 0]], $storage);

        // We simulate a request ID
        $analyzer->setRequestId('req-test-1');

        // Sql with changing binding
        $sql = "select * from users where id = ?";

        // We expect threshold > 1 now. 
        // Iterate 5 times.
        // Q1: Count 1. Not N+1.
        // Q2: Count 2. N+1.
        
        for ($i = 1; $i <= 5; $i++) {
            $analyzer->recordQuery($sql, [$i], 0.01);
        }

        $queries = AnalyzedQuery::orderBy('created_at')->get();
        
        $this->assertCount(5, $queries);

        // Q1
        $this->assertFalse($queries[0]->is_n_plus_one, 'Q1 should not be N+1');
        $this->assertEquals(0, $queries[0]->n_plus_one_count);

        // Q2
        $this->assertTrue($queries[1]->is_n_plus_one, 'Q2 should be N+1');
        $this->assertEquals(2, $queries[1]->n_plus_one_count, 'Q2 count should be 2');

        // Q5
        $this->assertTrue($queries[4]->is_n_plus_one, 'Q5 should be N+1');
        $this->assertEquals(5, $queries[4]->n_plus_one_count, 'Q5 count should be 5');
    }
}
