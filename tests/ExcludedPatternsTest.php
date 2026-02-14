<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class ExcludedPatternsTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function makeAnalyzer(array $excludedPatterns): array
    {
        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            [
                'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
                'excluded_patterns' => $excludedPatterns,
            ],
            $storage
        );
        $analyzer->setRequestId('req-test');

        return [$analyzer, $storage];
    }

    // ---------------------------------------------------------------
    // Matching patterns are excluded
    // ---------------------------------------------------------------

    public function test_show_tables_is_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['SHOW TABLES']);

        $analyzer->recordQuery('SHOW TABLES', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_describe_is_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['DESCRIBE']);

        $analyzer->recordQuery('DESCRIBE users', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_select_version_is_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['SELECT VERSION()']);

        $analyzer->recordQuery('SELECT VERSION()', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_migrations_table_is_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['migrations']);

        $analyzer->recordQuery('SELECT * FROM migrations WHERE batch = 1', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Non-matching queries are recorded
    // ---------------------------------------------------------------

    public function test_normal_query_is_not_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['SHOW TABLES', 'DESCRIBE']);

        $analyzer->recordQuery('SELECT * FROM users WHERE id = 1', [], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Case insensitivity
    // ---------------------------------------------------------------

    public function test_excluded_patterns_are_case_insensitive(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['SHOW TABLES']);

        $analyzer->recordQuery('show tables', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_pattern_in_lowercase_matches_uppercase_sql(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['show tables']);

        $analyzer->recordQuery('SHOW TABLES', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Empty patterns
    // ---------------------------------------------------------------

    public function test_empty_excluded_patterns_records_everything(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer([]);

        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $analyzer->recordQuery('SHOW TABLES', [], 0.05);

        $this->assertCount(2, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Custom patterns
    // ---------------------------------------------------------------

    public function test_custom_pattern_excludes_matching_queries(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['information_schema']);

        $analyzer->recordQuery('SELECT * FROM information_schema.tables', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_custom_pattern_does_not_exclude_non_matching(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['information_schema']);

        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Multiple patterns
    // ---------------------------------------------------------------

    public function test_multiple_patterns_exclude_all_matching(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['SHOW TABLES', 'DESCRIBE', 'migrations']);

        $analyzer->recordQuery('SHOW TABLES', [], 0.05);
        $analyzer->recordQuery('DESCRIBE users', [], 0.05);
        $analyzer->recordQuery('SELECT * FROM migrations', [], 0.05);
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
        $this->assertSame('SELECT * FROM users', $storage->getAllQueries()[0]['sql']);
    }

    // ---------------------------------------------------------------
    // Hardcoded exclusions still work as fallback
    // ---------------------------------------------------------------

    public function test_explain_queries_still_excluded_even_without_config_pattern(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer([]);

        $analyzer->recordQuery('EXPLAIN SELECT * FROM users', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_internal_cache_table_still_excluded(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer([]);

        $analyzer->recordQuery('SELECT * FROM laravel_query_lens_queries_v3', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Config integration
    // ---------------------------------------------------------------

    public function test_config_excluded_patterns_are_used(): void
    {
        $this->app['config']->set('query-lens.excluded_patterns', ['CUSTOM_PATTERN']);

        $storage = new InMemoryQueryStorage();
        $analyzer = new QueryAnalyzer(
            $this->app['config']['query-lens'],
            $storage
        );
        $analyzer->setRequestId('req-config');

        $analyzer->recordQuery('SELECT CUSTOM_PATTERN FROM test', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    // ---------------------------------------------------------------
    // Partial match works (pattern can be a substring)
    // ---------------------------------------------------------------

    public function test_partial_pattern_match_excludes(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['telescope_']);

        $analyzer->recordQuery('SELECT * FROM telescope_entries', [], 0.05);

        $this->assertCount(0, $storage->getAllQueries());
    }

    public function test_partial_pattern_does_not_match_unrelated(): void
    {
        [$analyzer, $storage] = $this->makeAnalyzer(['telescope_']);

        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $this->assertCount(1, $storage->getAllQueries());
    }
}
