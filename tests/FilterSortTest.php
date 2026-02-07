<?php

namespace GladeHQ\QueryLens\Tests;

use Orchestra\Testbench\TestCase;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use GladeHQ\QueryLens\Http\Controllers\QueryLensController;
use Illuminate\Http\Request;

class FilterSortTest extends TestCase
{
    protected $analyzer;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $storage = new CacheQueryStorage('array');
        $this->analyzer = new QueryAnalyzer([], $storage);
        $this->controller = new QueryLensController($this->analyzer, $storage);
        
        // Seed Data
        $this->analyzer->setRequestId('req-1');
        
        // 1. N+1 Query (Issue)
        $this->analyzer->recordQuery("SELECT * FROM posts WHERE user_id = 1", [], 0.001);
        // Force N+1 detection by repeating structure > 5 times
        for($i=0; $i<6; $i++) {
            $this->analyzer->recordQuery("SELECT * FROM comments WHERE post_id = $i", [], 0.001);
        }

        // 2. Slow Query (Performance)
        $this->analyzer->recordQuery("SELECT SLEEP(2)", [], 2.0);

        // 3. Normal Query
        $this->analyzer->recordQuery("SELECT * FROM users", [], 0.002);
    }

    public function test_filter_by_issue_type_n_plus_one()
    {
        $request = Request::create('/api/queries', 'GET', ['issue_type' => 'n+1']);
        $response = $this->controller->queries($request);
        $data = $response->getData(true);
        
        // The repeated comments queries should be flagged as N+1
        $this->assertNotEmpty($data['queries'], 'Should invoke queries with N+1 issue');
        foreach ($data['queries'] as $q) {
            $issues = $q['analysis']['issues'];
            $types = array_column($issues, 'type');
            $this->assertContains('n+1', $types);
        }
    }

    public function test_sort_by_time_desc()
    {
        $request = Request::create('/api/queries', 'GET', ['sort' => 'time', 'order' => 'desc']);
        $response = $this->controller->queries($request);
        $data = $response->getData(true);

        $first = $data['queries'][0];
        $this->assertEquals(2.0, $first['time'], 'Slowest query should be first');
    }
}
