<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class DTOIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    public function test_analyzer_recorded_query_has_dto_compatible_analysis(): void
    {
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            ['performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0]],
            $storage
        );

        $analyzer->recordQuery('SELECT * FROM users', [], 1.5);

        $stored = $storage->getAllQueries()[0];
        $analysis = $stored['analysis'];

        $this->assertSame('SELECT', $analysis['type']);
        $this->assertSame('very_slow', $analysis['performance']['rating']);
        $this->assertTrue($analysis['performance']['is_slow']);
        $this->assertSame(1.5, $analysis['performance']['execution_time']);
        $this->assertIsInt($analysis['complexity']['score']);
    }

    public function test_recorded_query_performance_ratings_match_dto_enum(): void
    {
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            ['performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0]],
            $storage
        );

        $analyzer->recordQuery('SELECT 1', [], 0.05);
        $analyzer->recordQuery('SELECT 2', [], 0.3);
        $analyzer->recordQuery('SELECT 3', [], 0.8);
        $analyzer->recordQuery('SELECT 4', [], 1.5);

        $queries = $storage->getAllQueries();

        // Most recent first (unshift)
        $this->assertSame('very_slow', $queries[0]['analysis']['performance']['rating']);
        $this->assertSame('slow', $queries[1]['analysis']['performance']['rating']);
        $this->assertSame('moderate', $queries[2]['analysis']['performance']['rating']);
        $this->assertSame('fast', $queries[3]['analysis']['performance']['rating']);
    }

    public function test_recorded_query_type_matches_dto_enum(): void
    {
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            ['performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0]],
            $storage
        );

        $analyzer->recordQuery('INSERT INTO users (name) VALUES ("test")', [], 0.05);

        $stored = $storage->getAllQueries()[0];
        $this->assertSame('INSERT', $stored['analysis']['type']);
    }
}
