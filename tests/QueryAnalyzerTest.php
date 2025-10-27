<?php

namespace Laravel\QueryAnalyzer\Tests;

use Laravel\QueryAnalyzer\QueryAnalyzer;
use PHPUnit\Framework\TestCase;

class QueryAnalyzerTest extends TestCase
{
    protected QueryAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new QueryAnalyzer([
            'performance_thresholds' => [
                'fast' => 0.1,
                'moderate' => 0.5,
                'slow' => 1.0,
            ]
        ]);
    }

    public function test_it_can_record_queries(): void
    {
        $this->analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $queries = $this->analyzer->getQueries();
        $this->assertCount(1, $queries);
        $this->assertEquals('SELECT * FROM users', $queries->first()['sql']);
    }

    public function test_it_analyzes_query_type(): void
    {
        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users');
        $this->assertEquals('SELECT', $analysis['type']);

        $analysis = $this->analyzer->analyzeQuery('INSERT INTO users (name) VALUES ("John")');
        $this->assertEquals('INSERT', $analysis['type']);

        $analysis = $this->analyzer->analyzeQuery('UPDATE users SET name = "Jane"');
        $this->assertEquals('UPDATE', $analysis['type']);

        $analysis = $this->analyzer->analyzeQuery('DELETE FROM users WHERE id = 1');
        $this->assertEquals('DELETE', $analysis['type']);
    }

    public function test_it_rates_query_performance(): void
    {
        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users', [], 0.05);
        $this->assertEquals('fast', $analysis['performance']['rating']);
        $this->assertFalse($analysis['performance']['is_slow']);

        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users', [], 0.3);
        $this->assertEquals('moderate', $analysis['performance']['rating']);

        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users', [], 0.8);
        $this->assertEquals('slow', $analysis['performance']['rating']);

        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users', [], 1.5);
        $this->assertEquals('very_slow', $analysis['performance']['rating']);
        $this->assertTrue($analysis['performance']['is_slow']);
    }

    public function test_it_calculates_complexity(): void
    {
        $simpleQuery = 'SELECT id FROM users';
        $analysis = $this->analyzer->analyzeQuery($simpleQuery);
        $this->assertEquals('low', $analysis['complexity']['level']);

        $complexQuery = 'SELECT u.*, p.name FROM users u JOIN profiles p ON u.id = p.user_id WHERE u.active = 1 ORDER BY u.created_at';
        $analysis = $this->analyzer->analyzeQuery($complexQuery);
        $this->assertGreaterThan(0, $analysis['complexity']['score']);
        $this->assertEquals(1, $analysis['complexity']['joins']);
    }

    public function test_it_provides_recommendations(): void
    {
        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users ORDER BY created_at');
        $recommendations = $analysis['recommendations'];

        $this->assertContains('Avoid SELECT * - specify only needed columns', $recommendations);
        $this->assertContains('Consider adding LIMIT when using ORDER BY', $recommendations);
    }

    public function test_it_detects_issues(): void
    {
        $analysis = $this->analyzer->analyzeQuery('SELECT * FROM users u JOIN posts p ON u.id = p.user_id');
        $issues = $analysis['issues'];

        $this->assertNotEmpty($issues);
        $hasSelectStarIssue = collect($issues)->contains(function ($issue) {
            return str_contains($issue['message'], 'SELECT * with JOINs');
        });
        $this->assertTrue($hasSelectStarIssue);
    }

    public function test_it_generates_stats(): void
    {
        $this->analyzer->recordQuery('SELECT * FROM users', [], 0.1);
        $this->analyzer->recordQuery('INSERT INTO posts (title) VALUES ("Test")', [], 0.05);
        $this->analyzer->recordQuery('SELECT * FROM posts', [], 1.5); // slow query

        $stats = $this->analyzer->getStats();

        $this->assertEquals(3, $stats['total_queries']);
        $this->assertEquals(1.65, $stats['total_time']);
        $this->assertEquals(0.55, $stats['average_time']);
        $this->assertEquals(1, $stats['slow_queries']);
        $this->assertArrayHasKey('SELECT', $stats['query_types']);
        $this->assertArrayHasKey('INSERT', $stats['query_types']);
    }

    public function test_it_can_reset_queries(): void
    {
        $this->analyzer->recordQuery('SELECT * FROM users', [], 0.1);
        $this->assertCount(1, $this->analyzer->getQueries());

        $this->analyzer->reset();
        $this->assertCount(0, $this->analyzer->getQueries());
    }
}