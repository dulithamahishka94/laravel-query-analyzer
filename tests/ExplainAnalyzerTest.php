<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\Tests;

use Orchestra\Testbench\TestCase;
use Coderflex\QueryLens\ExplainAnalyzer\ExplainAnalyzer;
use Coderflex\QueryLens\ExplainAnalyzer\Parser\ExplainAnalyzeParser;
use Coderflex\QueryLens\ExplainAnalyzer\Analyzer\QueryAnalyzer;
use Coderflex\QueryLens\ExplainAnalyzer\Issues\IssueType;
use Coderflex\QueryLens\ExplainAnalyzer\Issues\IssueSeverity;

class ExplainAnalyzerTest extends TestCase
{
    private ExplainAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new ExplainAnalyzer();
    }

    public function testBasicParsing(): void
    {
        $input = <<<'EXPLAIN'
-> Limit: 1 row(s)  (cost=1853 rows=1) (actual time=7.91..7.91 rows=1 loops=1)
    -> Aggregate: count(0)  (cost=1853 rows=1) (actual time=7.91..7.91 rows=1 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $this->assertNotEmpty($result->getNodes());
        $this->assertEquals(7.91, $result->getTotalTime());
    }

    public function testDetectsRowEstimationError(): void
    {
        $input = <<<'EXPLAIN'
-> Filter: (cast(attendance.`date` as date) = '2026-01-26')  (cost=980 rows=3791) (actual time=7.84..7.89 rows=19 loops=1)
    -> Index lookup on attendance using idx (class_id = 19)  (cost=980 rows=3791) (actual time=0.116..7.46 rows=3791 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $hasEstimationError = false;
        foreach ($result->getIssues() as $issue) {
            if ($issue->getType() === IssueType::ROW_ESTIMATION_ERROR) {
                $hasEstimationError = true;
                break;
            }
        }

        $this->assertTrue($hasEstimationError, 'Should detect row estimation error');
    }

    public function testDetectsFunctionOnColumn(): void
    {
        $input = <<<'EXPLAIN'
-> Filter: (cast(date as date) = '2026-01-26')  (cost=980 rows=3791) (actual time=7.84..7.89 rows=19 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $hasFunctionIssue = false;
        foreach ($result->getIssues() as $issue) {
            if ($issue->getType() === IssueType::FUNCTION_ON_COLUMN) {
                $hasFunctionIssue = true;
                break;
            }
        }

        $this->assertTrue($hasFunctionIssue, 'Should detect function on column');
    }

    public function testDetectsFullTableScan(): void
    {
        $input = <<<'EXPLAIN'
-> Table scan on users  (cost=5000 rows=50000) (actual time=0.1..100 rows=50000 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $hasTableScan = false;
        foreach ($result->getIssues() as $issue) {
            if ($issue->getType() === IssueType::FULL_TABLE_SCAN) {
                $hasTableScan = true;
                break;
            }
        }

        $this->assertTrue($hasTableScan, 'Should detect full table scan');
    }

    public function testHealthStatusCritical(): void
    {
        $input = <<<'EXPLAIN'
-> Table scan on users  (cost=5000 rows=50000) (actual time=0.1..100 rows=50000 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $this->assertEquals('critical', $result->getHealthStatus());
    }

    public function testHealthStatusGood(): void
    {
        $input = <<<'EXPLAIN'
-> Index lookup on users using primary (id = 1)  (cost=1 rows=1) (actual time=0.01..0.01 rows=1 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $this->assertEquals('good', $result->getHealthStatus());
    }

    public function testExplainGeneratesOutput(): void
    {
        $input = <<<'EXPLAIN'
-> Limit: 1 row(s)  (cost=1853 rows=1) (actual time=7.91..7.91 rows=1 loops=1)
EXPLAIN;

        $explanation = $this->analyzer->explain($input);

        $this->assertNotEmpty($explanation);
        $this->assertStringContainsString('Summary', $explanation);
    }

    public function testWithoutMarkdown(): void
    {
        $input = <<<'EXPLAIN'
-> Limit: 1 row(s)  (cost=1853 rows=1) (actual time=7.91..7.91 rows=1 loops=1)
EXPLAIN;

        $explanation = ExplainAnalyzer::create()
            ->withoutMarkdown()
            ->explain($input);

        $this->assertStringNotContainsString('##', $explanation);
    }

    public function testParserExtractsTableInfo(): void
    {
        $parser = new ExplainAnalyzeParser();
        $input = <<<'EXPLAIN'
-> Index lookup on attendance using attendance_class_id_index (class_id = 19)  (cost=980 rows=3791)
EXPLAIN;

        $nodes = $parser->parse($input);

        $this->assertNotEmpty($nodes);
        $this->assertEquals('attendance', $nodes[0]->getTableName());
        $this->assertEquals('attendance_class_id_index', $nodes[0]->getIndexName());
    }

    public function testIssueHasSuggestion(): void
    {
        $input = <<<'EXPLAIN'
-> Filter: (cast(date as date) = '2026-01-26')  (cost=980 rows=3791) (actual time=7.84..7.89 rows=19 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        foreach ($result->getIssues() as $issue) {
            $this->assertNotEmpty($issue->getSuggestion());
        }
    }

    public function testComplexQueryParsing(): void
    {
        $input = <<<'EXPLAIN'
-> Limit: 1 row(s)  (cost=1853 rows=1) (actual time=7.91..7.91 rows=1 loops=1)
    -> Aggregate: count(0), sum((case when (attendance.`status` = 'present') then 1 else 0 end))  (cost=1853 rows=1) (actual time=7.91..7.91 rows=1 loops=1)
        -> Filter: (cast(attendance.`date` as date) = '2026-01-26')  (cost=980 rows=3791) (actual time=7.84..7.89 rows=19 loops=1)
            -> Index lookup on attendance using attendance_class_id_index (class_id = 19)  (cost=980 rows=3791) (actual time=0.116..7.46 rows=3791 loops=1)
EXPLAIN;

        $result = $this->analyzer->analyze($input);

        $this->assertNotEmpty($result->getNodes());
        $this->assertTrue($result->hasIssues());

        // Should have detected the function on column issue
        $functionIssues = array_filter(
            $result->getIssues(),
            fn($i) => $i->getType() === IssueType::FUNCTION_ON_COLUMN
        );
        $this->assertNotEmpty($functionIssues);
    }
}
