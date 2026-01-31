<?php

namespace Coderflex\QueryLens\Tests;

use Coderflex\QueryLens\QueryAnalyzer;
use PHPUnit\Framework\TestCase;

class AdvancedRecommendationsTest extends TestCase
{
    protected QueryAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new QueryAnalyzer([
            'performance_thresholds' => ['slow' => 1.0]
        ], new \Coderflex\QueryLens\Tests\Fakes\InMemoryQueryStorage());
    }

    public function test_it_detects_random_order()
    {
        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users ORDER BY RAND()');
        $this->assertStringContainsString('CRITICAL: "ORDER BY RAND()"', implode('', $analysis['recommendations']));
    }

    public function test_it_detects_leading_wildcard()
    {
        $analysis = $this->analyzer->analyzeQuery("SELECT * FROM users WHERE name LIKE '%john'");
        $this->assertStringContainsString('Leading wildcard in LIKE', implode('', $analysis['recommendations']));
    }

    public function test_it_suggests_indexes_for_slow_queries()
    {
        // Simulate a slow query (> 1.0s) involving WHERE and ORDER BY
        $sql = "SELECT * FROM users WHERE email = 'test@example.com' ORDER BY created_at";
        $analysis = $this->analyzer->analyzeQuery($sql, [], 1.5);
        
        $recommendations = implode(' ', $analysis['recommendations']);
        
        // Should suggest index on email
        $this->assertStringContainsString("Consider adding an INDEX on table `users` columns: (email)", $recommendations);
        // Should suggest index on created_at
        $this->assertStringContainsString("Consider adding an INDEX on `users` column: (created_at) for sorting", $recommendations);
    }
}
