<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Services\DashboardService;
use GladeHQ\QueryLens\Services\ExplainService;
use GladeHQ\QueryLens\Services\QueryExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;

class ServiceExtractionTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    // ---------------------------------------------------------------
    // ExplainService
    // ---------------------------------------------------------------

    public function test_explain_service_humanize_returns_summary_and_insights(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain(
            [['type' => 'ref', 'key' => 'idx_user', 'Extra' => '', 'rows' => 10, 'table' => 'users']],
            []
        );

        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('insights', $result);
        $this->assertIsString($result['summary']);
        $this->assertIsArray($result['insights']);
    }

    public function test_explain_service_humanize_empty_standard_result(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain([], []);

        $this->assertSame('No execution plan data was returned from the database.', $result['summary']);
        $this->assertEmpty($result['insights']);
    }

    public function test_explain_service_humanize_detects_index_usage(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain(
            [['type' => 'ref', 'key' => 'idx_email', 'Extra' => '', 'rows' => 1, 'table' => 'users']],
            []
        );

        $hasIndexInsight = false;
        foreach ($result['insights'] as $insight) {
            if (str_contains($insight, 'Index Used')) {
                $hasIndexInsight = true;
                break;
            }
        }
        $this->assertTrue($hasIndexInsight, 'Should detect index usage');
    }

    public function test_explain_service_humanize_detects_temporary_table(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain(
            [['type' => 'ALL', 'key' => null, 'Extra' => 'Using temporary', 'rows' => 100, 'table' => 'orders']],
            []
        );

        $hasTempInsight = false;
        foreach ($result['insights'] as $insight) {
            if (str_contains($insight, 'Temporary Table')) {
                $hasTempInsight = true;
                break;
            }
        }
        $this->assertTrue($hasTempInsight, 'Should detect temporary table');
    }

    public function test_explain_service_humanize_detects_disk_io_in_analyze(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain(
            [['type' => 'ALL', 'key' => null, 'Extra' => '']],
            [['EXPLAIN' => '-> Sort: rows=1000 using disk temporary']]
        );

        $hasDiskInsight = false;
        foreach ($result['insights'] as $insight) {
            if (str_contains($insight, 'Disk I/O')) {
                $hasDiskInsight = true;
                break;
            }
        }
        $this->assertTrue($hasDiskInsight, 'Should detect disk I/O');
    }

    public function test_explain_service_humanize_tree_format_table_scan(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain(
            [['EXPLAIN' => '-> Table scan on users (cost=10.0 rows=100)']],
            []
        );

        $hasScanInsight = false;
        foreach ($result['insights'] as $insight) {
            if (str_contains($insight, 'Full Table Scan')) {
                $hasScanInsight = true;
                break;
            }
        }
        $this->assertTrue($hasScanInsight, 'Should detect table scan in tree format');
    }

    public function test_explain_service_humanize_tree_format_index_scan(): void
    {
        $service = new ExplainService();
        $result = $service->humanizeExplain(
            [['EXPLAIN' => '-> Index lookup on users using idx_email (cost=1.0 rows=1)']],
            []
        );

        $hasIndexInsight = false;
        foreach ($result['insights'] as $insight) {
            if (str_contains($insight, 'Index Used')) {
                $hasIndexInsight = true;
                break;
            }
        }
        $this->assertTrue($hasIndexInsight, 'Should detect index scan in tree format');
    }

    // ---------------------------------------------------------------
    // QueryExportService
    // ---------------------------------------------------------------

    public function test_export_service_json_format(): void
    {
        $service = new QueryExportService();
        $queries = collect([
            [
                'sql' => 'SELECT * FROM users',
                'time' => 0.05,
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'fast'],
                    'complexity' => ['level' => 'low'],
                    'issues' => [],
                ],
            ],
        ]);

        $result = $service->export($queries, 'json', ['total_queries' => 1]);

        $this->assertIsArray($result['data']);
        $this->assertArrayHasKey('stats', $result);
        $this->assertStringContainsString('query-analysis-', $result['filename']);
        $this->assertStringEndsWith('.json', $result['filename']);
    }

    public function test_export_service_csv_format(): void
    {
        $service = new QueryExportService();
        $queries = collect([
            [
                'sql' => 'SELECT * FROM users',
                'time' => 0.05,
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'fast'],
                    'complexity' => ['level' => 'low'],
                    'issues' => [],
                ],
            ],
        ]);

        $result = $service->export($queries, 'csv');

        $this->assertIsString($result['data']);
        $this->assertStringContainsString('Index,Type,Time', $result['data']);
        $this->assertStringContainsString('SELECT', $result['data']);
        $this->assertStringEndsWith('.csv', $result['filename']);
    }

    public function test_export_service_csv_escapes_quotes(): void
    {
        $service = new QueryExportService();
        $queries = collect([
            [
                'sql' => 'SELECT * FROM users WHERE name = "test"',
                'time' => 0.05,
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'fast'],
                    'complexity' => ['level' => 'low'],
                    'issues' => [],
                ],
            ],
        ]);

        $result = $service->export($queries, 'csv');

        // Double quotes in SQL should be escaped as ""
        $this->assertStringContainsString('""test""', $result['data']);
    }

    public function test_export_service_empty_collection(): void
    {
        $service = new QueryExportService();
        $result = $service->export(collect([]), 'json');

        $this->assertIsArray($result['data']);
        $this->assertEmpty($result['data']);
    }

    public function test_export_service_csv_empty_collection(): void
    {
        $service = new QueryExportService();
        $result = $service->export(collect([]), 'csv');

        // Should still have header row
        $this->assertStringContainsString('Index,Type,Time', $result['data']);
        // But only the header, no data rows
        $this->assertSame(1, substr_count($result['data'], "\n"));
    }

    // ---------------------------------------------------------------
    // DashboardService: applyFilters
    // ---------------------------------------------------------------

    protected function makeSampleQueries(): Collection
    {
        return collect([
            [
                'sql' => 'SELECT * FROM users',
                'time' => 0.05,
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'fast', 'is_slow' => false],
                    'complexity' => ['score' => 1, 'level' => 'low'],
                    'issues' => [],
                ],
            ],
            [
                'sql' => 'UPDATE users SET name = "test"',
                'time' => 0.3,
                'analysis' => [
                    'type' => 'UPDATE',
                    'performance' => ['rating' => 'moderate', 'is_slow' => false],
                    'complexity' => ['score' => 2, 'level' => 'low'],
                    'issues' => [['type' => 'performance', 'message' => 'test issue']],
                ],
            ],
            [
                'sql' => 'SELECT * FROM orders',
                'time' => 1.5,
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['rating' => 'very_slow', 'is_slow' => true],
                    'complexity' => ['score' => 5, 'level' => 'medium'],
                    'issues' => [['type' => 'n+1', 'message' => 'N+1 detected']],
                ],
            ],
        ]);
    }

    public function test_dashboard_service_filter_by_type(): void
    {
        $service = new DashboardService();
        $request = Request::create('/', 'GET', ['type' => 'select']);
        $result = $service->applyFilters($this->makeSampleQueries(), $request);

        $this->assertCount(2, $result);
    }

    public function test_dashboard_service_filter_by_rating(): void
    {
        $service = new DashboardService();
        $request = Request::create('/', 'GET', ['rating' => 'fast']);
        $result = $service->applyFilters($this->makeSampleQueries(), $request);

        $this->assertCount(1, $result);
    }

    public function test_dashboard_service_filter_by_issue_type(): void
    {
        $service = new DashboardService();
        $request = Request::create('/', 'GET', ['issue_type' => 'n+1']);
        $result = $service->applyFilters($this->makeSampleQueries(), $request);

        $this->assertCount(1, $result);
    }

    public function test_dashboard_service_filter_slow_only(): void
    {
        $service = new DashboardService();
        $request = Request::create('/', 'GET', ['slow_only' => '1']);
        $result = $service->applyFilters($this->makeSampleQueries(), $request);

        $this->assertCount(1, $result);
    }

    public function test_dashboard_service_no_filters_returns_all(): void
    {
        $service = new DashboardService();
        $request = Request::create('/', 'GET');
        $result = $service->applyFilters($this->makeSampleQueries(), $request);

        $this->assertCount(3, $result);
    }

    // ---------------------------------------------------------------
    // DashboardService: buildWaterfallTimeline
    // ---------------------------------------------------------------

    public function test_dashboard_service_waterfall_timeline(): void
    {
        $service = new DashboardService();
        $now = microtime(true);

        $queries = [
            ['timestamp' => $now, 'time' => 0.05, 'sql' => 'SELECT 1', 'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false]]],
            ['timestamp' => $now + 0.1, 'time' => 0.1, 'sql' => 'SELECT 2', 'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false]]],
        ];

        $result = $service->buildWaterfallTimeline($queries);

        $this->assertSame(2, $result['total_queries']);
        $this->assertEqualsWithDelta(0.15, $result['total_time'], 0.001);
        $this->assertCount(2, $result['timeline_data']);
        $this->assertSame(1, $result['timeline_data'][0]['index']);
        $this->assertSame(2, $result['timeline_data'][1]['index']);
    }

    public function test_dashboard_service_waterfall_timeline_marks_slow(): void
    {
        $service = new DashboardService();
        $now = microtime(true);

        $queries = [
            ['timestamp' => $now, 'time' => 1.5, 'sql' => 'SELECT SLEEP(2)', 'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => true]]],
        ];

        $result = $service->buildWaterfallTimeline($queries);

        $this->assertTrue($result['timeline_data'][0]['is_slow']);
    }

    public function test_dashboard_service_waterfall_timeline_sql_preview_truncated(): void
    {
        $service = new DashboardService();
        $now = microtime(true);
        $longSql = 'SELECT ' . str_repeat('a', 200) . ' FROM users';

        $queries = [
            ['timestamp' => $now, 'time' => 0.05, 'sql' => $longSql, 'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false]]],
        ];

        $result = $service->buildWaterfallTimeline($queries);

        $this->assertLessThanOrEqual(100, strlen($result['timeline_data'][0]['sql_preview']));
    }
}
