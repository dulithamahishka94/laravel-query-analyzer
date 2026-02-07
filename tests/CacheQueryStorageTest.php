<?php

namespace GladeHQ\QueryLens\Tests;

use Illuminate\Support\Facades\Cache;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use Orchestra\Testbench\TestCase;

class CacheQueryStorageTest extends TestCase
{
    public function test_it_stores_and_retrieves_queries()
    {
        $storage = new CacheQueryStorage();
        
        // Mock the store() call which is now used
        Cache::shouldReceive('store')->with(null)->andReturnSelf();
        
        Cache::shouldReceive('get')->once()->andReturn([]);
        Cache::shouldReceive('put')->once()->withArgs(function ($key, $value) {
            return $key === 'laravel_query_lens_queries_v3' && count($value) === 1;
        });

        $storage->store(['sql' => 'SELECT 1']);
    }
}
