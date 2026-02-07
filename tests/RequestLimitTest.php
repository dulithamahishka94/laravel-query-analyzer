<?php

namespace GladeHQ\QueryLens\Tests;

use Orchestra\Testbench\TestCase;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use Illuminate\Support\Facades\Cache;

class RequestLimitTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('query-lens.store', 'array');
    }

    public function test_it_returns_all_requests_within_storage_limit()
    {
        $storage = new CacheQueryStorage('array');
        $analyzer = new QueryAnalyzer([], $storage);

        // Record 10 distinct requests
        for ($i = 0; $i < 10; $i++) {
            $analyzer->setRequestId("req-{$i}");
            $analyzer->recordQuery("SELECT * FROM users WHERE id = {$i}", [], 0.01);
        }

        // Get requests exactly as the Controller does
        $requests = $analyzer->getQueries()
            ->groupBy('request_id')
            ->map(function ($group) {
                $first = $group->first();
                return [
                    'request_id' => $first['request_id'],
                    'timestamp' => $first['timestamp'],
                ];
            })
            ->sortByDesc('timestamp')
            ->values();

        $this->assertCount(10, $requests, 'Should return all 10 requests');
    }

    public function test_storage_respects_max_limit()
    {
        $storage = new CacheQueryStorage('array');
        $analyzer = new QueryAnalyzer([], $storage);

        // Record 11000 distinct requests (storage limit is hardcoded to 10000)
        // This is slow but verifies the hard execution path
        for ($i = 0; $i < 11000; $i++) {
            $analyzer->setRequestId("req-{$i}");
            $analyzer->recordQuery("SELECT 1", [], 0.01);
        }

        $queries = $storage->get(20000); // Ask for more than limit to see what we really have
        
        $this->assertLessThanOrEqual(10000, count($queries), 'Storage should not exceed hardcoded limit of 10000');
    }
}
