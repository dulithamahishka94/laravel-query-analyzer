<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Services\IndexAdvisor;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class IndexAdvisorTest extends TestCase
{
    protected IndexAdvisor $advisor;

    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('query-lens.storage.driver', 'cache');
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->advisor = new IndexAdvisor();
    }

    // ---------------------------------------------------------------
    // Single query analysis: WHERE clause
    // ---------------------------------------------------------------

    public function test_suggests_index_for_simple_where_clause(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM users WHERE email = ? AND status = ?'
        );

        $this->assertNotEmpty($result['suggestions']);
        $suggestion = $result['suggestions'][0];
        $this->assertSame('users', $suggestion['table']);
        $this->assertContains('email', $suggestion['columns']);
        $this->assertContains('status', $suggestion['columns']);
    }

    public function test_generates_create_index_sql(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM users WHERE email = ?'
        );

        $this->assertNotEmpty($result['suggestions']);
        $sql = $result['suggestions'][0]['create_index_sql'];
        $this->assertStringContainsString('CREATE INDEX', $sql);
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function test_returns_sql_in_result(): void
    {
        $inputSql = 'SELECT * FROM orders WHERE id = ?';
        $result = $this->advisor->analyzeQuery($inputSql);

        $this->assertSame($inputSql, $result['sql']);
    }

    public function test_returns_analysis_metadata(): void
    {
        $result = $this->advisor->analyzeQuery('SELECT * FROM users WHERE id = ?');

        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('tables', $result['analysis']);
        $this->assertArrayHasKey('candidates', $result['analysis']);
    }

    // ---------------------------------------------------------------
    // JOIN analysis
    // ---------------------------------------------------------------

    public function test_suggests_index_for_join_columns(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT u.name, o.total FROM users u JOIN orders o ON u.id = o.user_id WHERE u.active = 1'
        );

        $this->assertNotEmpty($result['suggestions']);
        $allColumns = [];
        foreach ($result['suggestions'] as $s) {
            $allColumns = array_merge($allColumns, $s['columns']);
        }
        $this->assertContains('user_id', $allColumns);
    }

    // ---------------------------------------------------------------
    // ORDER BY analysis
    // ---------------------------------------------------------------

    public function test_suggests_index_including_order_by_columns(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM products WHERE category_id = ? ORDER BY price DESC'
        );

        $this->assertNotEmpty($result['suggestions']);
        $allColumns = [];
        foreach ($result['suggestions'] as $s) {
            $allColumns = array_merge($allColumns, $s['columns']);
        }
        $this->assertContains('category_id', $allColumns);
        $this->assertContains('price', $allColumns);
    }

    // ---------------------------------------------------------------
    // GROUP BY analysis
    // ---------------------------------------------------------------

    public function test_suggests_composite_index_for_group_by(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT department, COUNT(*) FROM employees WHERE status = ? GROUP BY department'
        );

        $this->assertNotEmpty($result['suggestions']);
        $allColumns = [];
        foreach ($result['suggestions'] as $s) {
            $allColumns = array_merge($allColumns, $s['columns']);
        }
        $this->assertContains('status', $allColumns);
        $this->assertContains('department', $allColumns);
    }

    // ---------------------------------------------------------------
    // Composite index suggestions
    // ---------------------------------------------------------------

    public function test_suggests_composite_index_for_multi_column_where(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM users WHERE email = ? AND status = ? AND role = ?'
        );

        $this->assertNotEmpty($result['suggestions']);
        $composite = $result['suggestions'][0];
        $this->assertSame('composite', $composite['type']);
        $this->assertGreaterThan(1, count($composite['columns']));
    }

    public function test_single_column_where_gets_single_type(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM users WHERE email = ?'
        );

        $this->assertNotEmpty($result['suggestions']);
        $this->assertSame('single', $result['suggestions'][0]['type']);
    }

    // ---------------------------------------------------------------
    // EXPLAIN-based analysis
    // ---------------------------------------------------------------

    public function test_detects_full_table_scan_from_explain(): void
    {
        $explain = [
            ['type' => 'ALL', 'key' => null, 'Extra' => '', 'rows' => 50000, 'table' => 'users'],
        ];

        $result = $this->advisor->analyzeQuery('SELECT * FROM users WHERE name = ?', $explain);

        $insights = $result['analysis']['explain_insights'];
        $this->assertNotEmpty($insights);
        $this->assertSame('full_table_scan', $insights[0]['type']);
        $this->assertSame('high', $insights[0]['severity']);
    }

    public function test_detects_filesort_from_explain(): void
    {
        $explain = [
            ['type' => 'ref', 'key' => 'idx_status', 'Extra' => 'Using filesort', 'rows' => 100, 'table' => 'orders'],
        ];

        $result = $this->advisor->analyzeQuery('SELECT * FROM orders WHERE status = ? ORDER BY created_at', $explain);

        $insights = $result['analysis']['explain_insights'];
        $hasFilesort = collect($insights)->contains(fn($i) => $i['type'] === 'filesort');
        $this->assertTrue($hasFilesort);
    }

    public function test_detects_temporary_table_from_explain(): void
    {
        $explain = [
            ['type' => 'ALL', 'key' => null, 'Extra' => 'Using temporary', 'rows' => 500, 'table' => 'orders'],
        ];

        $result = $this->advisor->analyzeQuery('SELECT status, COUNT(*) FROM orders GROUP BY status', $explain);

        $insights = $result['analysis']['explain_insights'];
        $hasTemporary = collect($insights)->contains(fn($i) => $i['type'] === 'temporary_table');
        $this->assertTrue($hasTemporary);
    }

    public function test_full_table_scan_low_rows_is_medium_severity(): void
    {
        $explain = [
            ['type' => 'ALL', 'key' => null, 'Extra' => '', 'rows' => 50, 'table' => 'config'],
        ];

        $result = $this->advisor->analyzeQuery('SELECT * FROM config WHERE key = ?', $explain);

        $insights = $result['analysis']['explain_insights'];
        $scan = collect($insights)->firstWhere('type', 'full_table_scan');
        $this->assertSame('medium', $scan['severity']);
    }

    public function test_explain_with_high_severity_increases_impact(): void
    {
        $explain = [
            ['type' => 'ALL', 'key' => null, 'Extra' => '', 'rows' => 100000, 'table' => 'logs'],
        ];

        $result = $this->advisor->analyzeQuery('SELECT * FROM logs WHERE level = ?', $explain);

        $this->assertNotEmpty($result['suggestions']);
        $this->assertSame('high', $result['suggestions'][0]['impact']);
    }

    // ---------------------------------------------------------------
    // Pattern analysis with historical data
    // ---------------------------------------------------------------

    public function test_analyze_patterns_with_no_data(): void
    {
        $storage = new InMemoryQueryStorage();
        $this->advisor->setStorage($storage);

        $suggestions = $this->advisor->analyzePatterns(7);
        $this->assertEmpty($suggestions);
    }

    public function test_analyze_patterns_with_slow_queries(): void
    {
        $storage = new InMemoryQueryStorage();
        for ($i = 0; $i < 10; $i++) {
            $storage->store([
                'id' => "q{$i}",
                'sql' => 'SELECT * FROM users WHERE email = ?',
                'time' => 0.5,
                'timestamp' => microtime(true),
                'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
            ]);
        }

        $this->advisor->setStorage($storage);
        $suggestions = $this->advisor->analyzePatterns(7);

        $this->assertNotEmpty($suggestions);
        $this->assertArrayHasKey('estimated_impact_score', $suggestions[0]);
        $this->assertArrayHasKey('query_count', $suggestions[0]);
    }

    public function test_pattern_analysis_sorted_by_impact(): void
    {
        $storage = new InMemoryQueryStorage();

        // High-impact: slow and frequent
        for ($i = 0; $i < 20; $i++) {
            $storage->store([
                'id' => "high_{$i}",
                'sql' => 'SELECT * FROM orders WHERE status = ?',
                'time' => 2.0,
                'timestamp' => microtime(true),
                'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => true], 'issues' => []],
            ]);
        }

        // Low-impact: fast and infrequent
        for ($i = 0; $i < 2; $i++) {
            $storage->store([
                'id' => "low_{$i}",
                'sql' => 'SELECT * FROM config WHERE key = ?',
                'time' => 0.01,
                'timestamp' => microtime(true),
                'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
            ]);
        }

        $this->advisor->setStorage($storage);
        $suggestions = $this->advisor->analyzePatterns(7);

        $this->assertNotEmpty($suggestions);
        // First suggestion should have higher impact
        if (count($suggestions) > 1) {
            $this->assertGreaterThanOrEqual(
                $suggestions[1]['estimated_impact_score'],
                $suggestions[0]['estimated_impact_score']
            );
        }
    }

    public function test_pattern_analysis_deduplicates_suggestions(): void
    {
        $storage = new InMemoryQueryStorage();

        // Same query appears in both slowest and most_frequent
        for ($i = 0; $i < 15; $i++) {
            $storage->store([
                'id' => "q_{$i}",
                'sql' => 'SELECT * FROM users WHERE email = ?',
                'time' => 1.0,
                'timestamp' => microtime(true),
                'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => true], 'issues' => []],
            ]);
        }

        $this->advisor->setStorage($storage);
        $suggestions = $this->advisor->analyzePatterns(7);

        // Should not have duplicate CREATE INDEX statements
        $createStatements = array_column($suggestions, 'create_index_sql');
        $this->assertSame(count($createStatements), count(array_unique($createStatements)));
    }

    // ---------------------------------------------------------------
    // Redundancy detection
    // ---------------------------------------------------------------

    public function test_detects_redundant_index(): void
    {
        $suggestion = ['columns' => ['email']];
        $existing = [
            ['columns' => ['email', 'status']],
        ];

        $this->assertTrue($this->advisor->isRedundant($suggestion, $existing));
    }

    public function test_non_redundant_index(): void
    {
        $suggestion = ['columns' => ['name']];
        $existing = [
            ['columns' => ['email', 'status']],
        ];

        $this->assertFalse($this->advisor->isRedundant($suggestion, $existing));
    }

    public function test_exact_match_is_redundant(): void
    {
        $suggestion = ['columns' => ['email', 'status']];
        $existing = [
            ['columns' => ['email', 'status']],
        ];

        $this->assertTrue($this->advisor->isRedundant($suggestion, $existing));
    }

    public function test_longer_suggestion_not_redundant(): void
    {
        $suggestion = ['columns' => ['email', 'status', 'role']];
        $existing = [
            ['columns' => ['email', 'status']],
        ];

        $this->assertFalse($this->advisor->isRedundant($suggestion, $existing));
    }

    public function test_empty_existing_indexes(): void
    {
        $suggestion = ['columns' => ['email']];
        $this->assertFalse($this->advisor->isRedundant($suggestion, []));
    }

    // ---------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------

    public function test_handles_insert_query(): void
    {
        $result = $this->advisor->analyzeQuery('INSERT INTO users (name, email) VALUES (?, ?)');

        // INSERT queries should not generate index suggestions
        $this->assertEmpty($result['suggestions']);
    }

    public function test_handles_delete_query(): void
    {
        $result = $this->advisor->analyzeQuery('DELETE FROM users WHERE id = ?');

        $this->assertArrayHasKey('suggestions', $result);
    }

    public function test_handles_update_query_with_where(): void
    {
        $result = $this->advisor->analyzeQuery('UPDATE users SET name = ? WHERE id = ?');

        $this->assertArrayHasKey('suggestions', $result);
    }

    public function test_handles_query_with_backticks(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM `users` WHERE `email` = ? AND `status` = ?'
        );

        $this->assertNotEmpty($result['suggestions']);
        $this->assertContains('email', $result['suggestions'][0]['columns']);
    }

    public function test_handles_query_without_where(): void
    {
        $result = $this->advisor->analyzeQuery('SELECT * FROM users');

        // No filter columns, no suggestions
        $this->assertEmpty($result['suggestions']);
    }

    public function test_handles_subquery(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM users WHERE id IN (SELECT user_id FROM orders WHERE total > ?)'
        );

        // Should still extract candidates from the outer query
        $this->assertArrayHasKey('suggestions', $result);
    }

    public function test_handles_empty_explain_result(): void
    {
        $result = $this->advisor->analyzeQuery('SELECT * FROM users WHERE id = ?', []);

        $this->assertEmpty($result['analysis']['explain_insights']);
    }

    public function test_handles_explain_with_good_index_usage(): void
    {
        $explain = [
            ['type' => 'ref', 'key' => 'idx_email', 'Extra' => '', 'rows' => 1, 'table' => 'users'],
        ];

        $result = $this->advisor->analyzeQuery('SELECT * FROM users WHERE email = ?', $explain);

        // No full table scan insight
        $insights = $result['analysis']['explain_insights'];
        $hasFullScan = collect($insights)->contains(fn($i) => $i['type'] === 'full_table_scan');
        $this->assertFalse($hasFullScan);
    }

    public function test_index_name_truncated_to_64_chars(): void
    {
        $result = $this->advisor->analyzeQuery(
            'SELECT * FROM very_long_table_name_that_goes_on WHERE extremely_long_column_name_first = ? AND extremely_long_column_name_second = ? AND extremely_long_column_name_third = ?'
        );

        if (!empty($result['suggestions'])) {
            $sql = $result['suggestions'][0]['create_index_sql'];
            // Extract index name from CREATE INDEX statement
            preg_match('/CREATE INDEX (\S+)/', $sql, $match);
            $this->assertLessThanOrEqual(64, strlen($match[1] ?? ''));
        }
    }

    // ---------------------------------------------------------------
    // Service provider registration
    // ---------------------------------------------------------------

    public function test_index_advisor_registered_in_container(): void
    {
        $advisor = app(IndexAdvisor::class);
        $this->assertInstanceOf(IndexAdvisor::class, $advisor);
    }

    public function test_suggest_indexes_command_registered(): void
    {
        $this->artisan('query-lens:suggest-indexes')
            ->assertSuccessful();
    }

    public function test_suggest_indexes_command_with_sql_option(): void
    {
        $this->artisan('query-lens:suggest-indexes', ['--sql' => 'SELECT * FROM users WHERE email = ?'])
            ->assertSuccessful();
    }

    public function test_suggest_indexes_command_json_format(): void
    {
        $this->artisan('query-lens:suggest-indexes', ['--format' => 'json'])
            ->assertSuccessful();
    }

    // ---------------------------------------------------------------
    // API route
    // ---------------------------------------------------------------

    public function test_index_suggestions_route_registered(): void
    {
        $routes = app('router')->getRoutes();
        $routeNames = collect($routes->getRoutes())->map(fn($r) => $r->getName())->filter()->toArray();

        $this->assertContains('query-lens.api.v2.index-suggestions', $routeNames);
    }
}
