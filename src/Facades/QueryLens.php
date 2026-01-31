<?php

namespace Coderflex\QueryLens\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void recordQuery(string $sql, array $bindings = [], float $time = 0.0, string $connection = 'default')
 * @method static array analyzeQuery(string $sql, array $bindings = [], float $time = 0.0)
 * @method static \Illuminate\Support\Collection getQueries()
 * @method static array getStats()
 * @method static void reset()
 */
class QueryLens extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Coderflex\QueryLens\QueryAnalyzer::class;
    }
}
