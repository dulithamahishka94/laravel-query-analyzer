<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use GladeHQ\QueryLens\Support\SqlNormalizer;
use PHPUnit\Framework\TestCase;

class SqlNormalizerTest extends TestCase
{
    // ---------------------------------------------------------------
    // Normalization: numeric literals
    // ---------------------------------------------------------------

    public function test_normalize_replaces_numeric_literals(): void
    {
        $result = SqlNormalizer::normalize('SELECT * FROM users WHERE id = 42');

        $this->assertSame('SELECT * FROM users WHERE id = ?', $result);
    }

    public function test_normalize_replaces_multiple_numeric_literals(): void
    {
        $result = SqlNormalizer::normalize('SELECT * FROM users WHERE id = 1 AND age > 25');

        $this->assertSame('SELECT * FROM users WHERE id = ? AND age > ?', $result);
    }

    // ---------------------------------------------------------------
    // Normalization: string literals
    // ---------------------------------------------------------------

    public function test_normalize_replaces_single_quoted_strings(): void
    {
        $result = SqlNormalizer::normalize("SELECT * FROM users WHERE name = 'John'");

        $this->assertSame('SELECT * FROM users WHERE name = ?', $result);
    }

    public function test_normalize_replaces_double_quoted_strings(): void
    {
        $result = SqlNormalizer::normalize('SELECT * FROM users WHERE name = "Jane"');

        $this->assertSame('SELECT * FROM users WHERE name = ?', $result);
    }

    public function test_normalize_replaces_empty_string_literal(): void
    {
        $result = SqlNormalizer::normalize("SELECT * FROM users WHERE name = ''");

        $this->assertSame('SELECT * FROM users WHERE name = ?', $result);
    }

    // ---------------------------------------------------------------
    // Normalization: IN lists
    // ---------------------------------------------------------------

    public function test_normalize_collapses_in_list(): void
    {
        $result = SqlNormalizer::normalize('SELECT * FROM users WHERE id IN (1, 2, 3, 4, 5)');

        $this->assertSame('SELECT * FROM users WHERE id IN (?)', $result);
    }

    public function test_normalize_collapses_in_list_with_strings(): void
    {
        $result = SqlNormalizer::normalize("SELECT * FROM users WHERE name IN ('a', 'b', 'c')");

        $this->assertSame('SELECT * FROM users WHERE name IN (?)', $result);
    }

    public function test_normalize_collapses_in_list_case_insensitive(): void
    {
        $result = SqlNormalizer::normalize('SELECT * FROM users WHERE id in (1, 2, 3)');

        $this->assertSame('SELECT * FROM users WHERE id IN (?)', $result);
    }

    // ---------------------------------------------------------------
    // Normalization: whitespace
    // ---------------------------------------------------------------

    public function test_normalize_collapses_whitespace(): void
    {
        $result = SqlNormalizer::normalize("SELECT *   FROM    users   WHERE  id = 1");

        $this->assertSame('SELECT * FROM users WHERE id = ?', $result);
    }

    public function test_normalize_trims_leading_and_trailing_whitespace(): void
    {
        $result = SqlNormalizer::normalize('   SELECT * FROM users   ');

        $this->assertSame('SELECT * FROM users', $result);
    }

    public function test_normalize_handles_newlines_and_tabs(): void
    {
        $result = SqlNormalizer::normalize("SELECT *\nFROM\tusers\nWHERE id = 1");

        $this->assertSame('SELECT * FROM users WHERE id = ?', $result);
    }

    // ---------------------------------------------------------------
    // Hashing
    // ---------------------------------------------------------------

    public function test_hash_produces_consistent_sha256(): void
    {
        $hash1 = SqlNormalizer::hash('SELECT * FROM users WHERE id = 1');
        $hash2 = SqlNormalizer::hash('SELECT * FROM users WHERE id = 42');

        // Both should normalize to the same SQL and therefore same hash
        $this->assertSame($hash1, $hash2);
        $this->assertSame(64, strlen($hash1)); // SHA-256 produces 64 hex chars
    }

    public function test_hash_differs_for_different_structures(): void
    {
        $hash1 = SqlNormalizer::hash('SELECT * FROM users WHERE id = 1');
        $hash2 = SqlNormalizer::hash('SELECT * FROM posts WHERE id = 1');

        $this->assertNotSame($hash1, $hash2);
    }

    public function test_hash_same_for_different_in_list_sizes(): void
    {
        $hash1 = SqlNormalizer::hash('SELECT * FROM users WHERE id IN (1, 2, 3)');
        $hash2 = SqlNormalizer::hash('SELECT * FROM users WHERE id IN (4, 5, 6, 7, 8)');

        $this->assertSame($hash1, $hash2);
    }

    // ---------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------

    public function test_normalize_empty_string(): void
    {
        $result = SqlNormalizer::normalize('');

        $this->assertSame('', $result);
    }

    public function test_hash_empty_string(): void
    {
        $hash = SqlNormalizer::hash('');

        $this->assertSame(64, strlen($hash));
    }

    public function test_normalize_very_long_sql(): void
    {
        $columns = implode(', ', array_map(fn($i) => "col_$i", range(1, 100)));
        $sql = "SELECT $columns FROM very_long_table WHERE id = 999";

        $result = SqlNormalizer::normalize($sql);

        // Column names like col_1 should be preserved (underscore is a word char, no \b boundary)
        $this->assertStringContainsString('col_1', $result);
        // Standalone numeric literal in WHERE should be replaced
        $this->assertStringNotContainsString('999', $result);
        $this->assertStringEndsWith('WHERE id = ?', $result);
    }

    public function test_normalize_special_characters_in_strings(): void
    {
        $result = SqlNormalizer::normalize("SELECT * FROM users WHERE bio = 'He said \"hello\" & goodbye'");

        $this->assertSame('SELECT * FROM users WHERE bio = ?', $result);
    }

    // ---------------------------------------------------------------
    // Consistency: AnalyzedQuery delegates to SqlNormalizer
    // ---------------------------------------------------------------

    public function test_analyzed_query_normalize_matches_sql_normalizer(): void
    {
        $sql = "SELECT * FROM users WHERE id IN (1, 2, 3) AND name = 'test'";

        $this->assertSame(
            SqlNormalizer::normalize($sql),
            AnalyzedQuery::normalizeSql($sql)
        );
    }

    public function test_analyzed_query_hash_matches_sql_normalizer(): void
    {
        $sql = "SELECT * FROM orders WHERE total > 100";

        $this->assertSame(
            SqlNormalizer::hash($sql),
            AnalyzedQuery::hashSql($sql)
        );
    }

    // ---------------------------------------------------------------
    // Consistency: same hash across storage drivers
    // ---------------------------------------------------------------

    public function test_hash_consistency_between_analyzed_query_and_normalizer(): void
    {
        $queries = [
            'SELECT * FROM users WHERE id = 1',
            "INSERT INTO users (name) VALUES ('test')",
            'SELECT * FROM orders WHERE id IN (1, 2, 3)',
        ];

        foreach ($queries as $sql) {
            $this->assertSame(
                SqlNormalizer::hash($sql),
                AnalyzedQuery::hashSql($sql),
                "Hash mismatch for: $sql"
            );
        }
    }
}
