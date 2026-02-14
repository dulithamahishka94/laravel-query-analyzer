<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Services\ExplainService;
use Orchestra\Testbench\TestCase;

class HumanizedExplainTest extends TestCase
{
    protected ExplainService $explainService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->explainService = new ExplainService();
    }

    public function test_it_detects_full_table_scan_in_standard_explain()
    {
        $result = [['type' => 'ALL', 'key' => null, 'Extra' => '']];
        $data = $this->explainService->humanizeExplain($result, []);
        $insights = $data['insights'];

        $this->assertContains('❌ **Full Table Scan**: Database is checking every single row because no suitable index was found.', $insights);
    }

    public function test_it_detects_filesort_in_standard_explain()
    {
        $result = [['type' => 'index', 'key' => 'primary', 'Extra' => 'Using filesort']];
        $data = $this->explainService->humanizeExplain($result, []);
        $insights = $data['insights'];

        $this->assertContains('🐌 **Filesort**: Consider adding an index on your `ORDER BY` columns to avoid expensive memory/disk sorting.', $insights);
    }

    public function test_it_detects_full_scan_in_analyze_explain()
    {
        // MySQL 8+ EXPLAIN ANALYZE returns a single row with the plan tree
        $result = [['EXPLAIN' => '-> Table scan on users  (cost=0.55 rows=3)']];
        // We still need standard result for the base loop
        $standard = [['type' => 'ALL', 'table' => 'users']];
        $data = $this->explainService->humanizeExplain($standard, $result);
        $insights = $data['insights'];

        $this->assertContains('❌ **Full Table Scan**: Database is checking every single row because no suitable index was found.', $insights);
    }
}
