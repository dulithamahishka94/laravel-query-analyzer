<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class BacktraceLimitTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function createAnalyzer(array $configOverrides = []): QueryAnalyzer
    {
        $config = array_merge([
            'performance_thresholds' => [
                'fast' => 0.1,
                'moderate' => 0.5,
                'slow' => 1.0,
            ],
            'analysis' => [
                'min_execution_time' => 0.0,
            ],
            'trace_origins' => true,
            'backtrace_limit' => 30,
        ], $configOverrides);

        return new QueryAnalyzer($config, new InMemoryQueryStorage());
    }

    public function test_find_origin_respects_backtrace_limit(): void
    {
        $analyzer = $this->createAnalyzer(['backtrace_limit' => 5]);

        $method = new \ReflectionMethod($analyzer, 'findOrigin');
        $method->setAccessible(true);

        $origin = $method->invoke($analyzer);

        // Should return an array with the expected keys regardless of limit
        $this->assertArrayHasKey('file', $origin);
        $this->assertArrayHasKey('line', $origin);
        $this->assertArrayHasKey('is_vendor', $origin);
    }

    public function test_find_origin_with_tracing_disabled_returns_unknown(): void
    {
        $analyzer = $this->createAnalyzer(['trace_origins' => false]);

        $method = new \ReflectionMethod($analyzer, 'findOrigin');
        $method->setAccessible(true);

        $origin = $method->invoke($analyzer);

        $this->assertEquals('unknown', $origin['file']);
        $this->assertEquals(0, $origin['line']);
        $this->assertFalse($origin['is_vendor']);
    }

    public function test_find_origin_with_default_config(): void
    {
        $analyzer = $this->createAnalyzer();

        $method = new \ReflectionMethod($analyzer, 'findOrigin');
        $method->setAccessible(true);

        $origin = $method->invoke($analyzer);

        // Should find the test file as origin since we're calling from test
        $this->assertArrayHasKey('file', $origin);
        $this->assertArrayHasKey('line', $origin);
        $this->assertArrayHasKey('is_vendor', $origin);
    }

    public function test_recorded_query_includes_origin_when_tracing_enabled(): void
    {
        $analyzer = $this->createAnalyzer(['trace_origins' => true]);

        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $queries = $analyzer->getQueries();

        $this->assertCount(1, $queries);
        $this->assertArrayHasKey('origin', $queries->first());
        $this->assertArrayHasKey('file', $queries->first()['origin']);
    }

    public function test_recorded_query_has_unknown_origin_when_tracing_disabled(): void
    {
        $analyzer = $this->createAnalyzer(['trace_origins' => false]);

        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $queries = $analyzer->getQueries();

        $this->assertCount(1, $queries);
        $this->assertEquals('unknown', $queries->first()['origin']['file']);
    }

    public function test_backtrace_limit_with_very_small_value(): void
    {
        // With limit of 1, the backtrace might only contain the findOrigin call itself
        $analyzer = $this->createAnalyzer(['backtrace_limit' => 1]);

        $method = new \ReflectionMethod($analyzer, 'findOrigin');
        $method->setAccessible(true);

        $origin = $method->invoke($analyzer);

        // Should gracefully handle not finding an origin
        $this->assertArrayHasKey('file', $origin);
        $this->assertArrayHasKey('line', $origin);
    }

    public function test_backtrace_limit_with_large_value(): void
    {
        $analyzer = $this->createAnalyzer(['backtrace_limit' => 200]);

        $method = new \ReflectionMethod($analyzer, 'findOrigin');
        $method->setAccessible(true);

        $origin = $method->invoke($analyzer);

        $this->assertArrayHasKey('file', $origin);
        $this->assertArrayHasKey('line', $origin);
    }

    public function test_config_defaults_when_keys_missing(): void
    {
        // No trace_origins or backtrace_limit in config
        $analyzer = $this->createAnalyzer([
            'trace_origins' => null,
            'backtrace_limit' => null,
        ]);

        // Should not throw and should use defaults
        $method = new \ReflectionMethod($analyzer, 'findOrigin');
        $method->setAccessible(true);

        $origin = $method->invoke($analyzer);
        $this->assertIsArray($origin);
    }
}
