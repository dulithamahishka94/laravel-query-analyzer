<?php

namespace Laravel\QueryAnalyzer\Storage;

use Illuminate\Support\Facades\Cache;
use Laravel\QueryAnalyzer\Contracts\QueryStorage;

class CacheQueryStorage implements QueryStorage
{
    protected string $cacheKey = 'laravel_query_analyzer_queries_v3';
    protected int $ttl = 3600; // 1 hour
    protected ?string $store;

    public function __construct(?string $store = null)
    {
        $this->store = $store;
    }

    public function store(array $query): void
    {
        $queries = $this->get();
        
        // Add new query to the beginning
        array_unshift($queries, $query);
        
        // Limit to max items (e.g. 1000) to prevent cache explosion
        $queries = array_slice($queries, 0, 1000);
        
        Cache::store($this->store)->put($this->cacheKey, $queries, $this->ttl);
    }

    public function get(int $limit = 100): array
    {
        $queries = Cache::store($this->store)->get($this->cacheKey, []);
        
        return array_slice($queries, 0, $limit);
    }

    public function clear(): void
    {
        Cache::store($this->store)->forget($this->cacheKey);
    }
}
