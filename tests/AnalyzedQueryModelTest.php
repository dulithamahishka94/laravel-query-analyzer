<?php

namespace Coderflex\QueryLens\Tests;

use Coderflex\QueryLens\Models\AnalyzedQuery;
use Orchestra\Testbench\TestCase;

class AnalyzedQueryModelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Coderflex\QueryLens\QueryLensServiceProvider::class];
    }

    public function test_normalize_sql_replaces_numeric_values(): void
    {
        $sql = 'SELECT * FROM users WHERE id = 123 AND age > 25';
        $normalized = AnalyzedQuery::normalizeSql($sql);

        $this->assertEquals('SELECT * FROM users WHERE id = ? AND age > ?', $normalized);
    }

    public function test_normalize_sql_replaces_single_quoted_strings(): void
    {
        $sql = "SELECT * FROM users WHERE name = 'John Doe' AND status = 'active'";
        $normalized = AnalyzedQuery::normalizeSql($sql);

        $this->assertEquals('SELECT * FROM users WHERE name = ? AND status = ?', $normalized);
    }

    public function test_normalize_sql_replaces_double_quoted_strings(): void
    {
        $sql = 'SELECT * FROM users WHERE name = "John Doe"';
        $normalized = AnalyzedQuery::normalizeSql($sql);

        $this->assertEquals('SELECT * FROM users WHERE name = ?', $normalized);
    }

    public function test_normalize_sql_replaces_in_lists(): void
    {
        $sql = 'SELECT * FROM users WHERE id IN (1, 2, 3, 4, 5)';
        $normalized = AnalyzedQuery::normalizeSql($sql);

        $this->assertEquals('SELECT * FROM users WHERE id IN (?)', $normalized);
    }

    public function test_normalize_sql_normalizes_whitespace(): void
    {
        $sql = "SELECT   *  FROM   users\n\tWHERE   id = 1";
        $normalized = AnalyzedQuery::normalizeSql($sql);

        $this->assertEquals('SELECT * FROM users WHERE id = ?', $normalized);
    }

    public function test_hash_sql_returns_consistent_hash(): void
    {
        $sql1 = 'SELECT * FROM users WHERE id = 1';
        $sql2 = 'SELECT * FROM users WHERE id = 999';

        $hash1 = AnalyzedQuery::hashSql($sql1);
        $hash2 = AnalyzedQuery::hashSql($sql2);

        // Same query structure, different values - should produce same hash
        $this->assertEquals($hash1, $hash2);
    }

    public function test_hash_sql_differs_for_different_queries(): void
    {
        $sql1 = 'SELECT * FROM users WHERE id = 1';
        $sql2 = 'SELECT * FROM posts WHERE id = 1';

        $hash1 = AnalyzedQuery::hashSql($sql1);
        $hash2 = AnalyzedQuery::hashSql($sql2);

        $this->assertNotEquals($hash1, $hash2);
    }

    public function test_hash_sql_returns_64_char_sha256(): void
    {
        $sql = 'SELECT * FROM users';
        $hash = AnalyzedQuery::hashSql($sql);

        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash);
    }

    public function test_normalize_handles_complex_queries(): void
    {
        $sql = "SELECT u.id, u.name FROM users u
                JOIN orders o ON o.user_id = u.id
                WHERE u.created_at > '2024-01-01'
                AND o.total > 100.50
                ORDER BY u.id LIMIT 10";

        $normalized = AnalyzedQuery::normalizeSql($sql);

        $this->assertStringContainsString('SELECT u.id, u.name FROM users u', $normalized);
        $this->assertStringContainsString('WHERE u.created_at > ?', $normalized);
        $this->assertStringContainsString('AND o.total > ?', $normalized);
    }

    public function test_to_api_array_returns_correct_structure(): void
    {
        $query = new AnalyzedQuery();
        $query->id = 'test-uuid-123';
        $query->request_id = 'request-uuid-456';
        $query->sql = 'SELECT * FROM users';
        $query->bindings = [];
        $query->time = 0.05;
        $query->connection = 'mysql';
        $query->type = 'SELECT';
        $query->performance_rating = 'fast';
        $query->is_slow = false;
        $query->complexity_score = 2;
        $query->complexity_level = 'low';
        $query->analysis = ['recommendations' => [], 'issues' => []];
        $query->origin = ['file' => 'test.php', 'line' => 10, 'is_vendor' => false];
        $query->is_n_plus_one = false;
        $query->created_at = now();

        $array = $query->toApiArray();

        $this->assertEquals('test-uuid-123', $array['id']);
        $this->assertEquals('request-uuid-456', $array['request_id']);
        $this->assertEquals('SELECT * FROM users', $array['sql']);
        $this->assertEquals(0.05, $array['time']);
        $this->assertEquals('SELECT', $array['analysis']['type']);
        $this->assertEquals('fast', $array['analysis']['performance']['rating']);
        $this->assertFalse($array['analysis']['performance']['is_slow']);
        $this->assertEquals(2, $array['analysis']['complexity']['score']);
        $this->assertEquals('low', $array['analysis']['complexity']['level']);
        $this->assertArrayHasKey('origin', $array);
        $this->assertFalse($array['is_n_plus_one']);
    }
}
