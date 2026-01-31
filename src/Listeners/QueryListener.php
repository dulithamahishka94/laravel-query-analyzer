<?php

namespace Coderflex\QueryLens\Listeners;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMiss;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Coderflex\QueryLens\QueryAnalyzer;

class QueryListener
{
    protected QueryAnalyzer $analyzer;
    protected bool $handling = false;

    public function __construct(QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function register(): void
    {
        Event::listen(QueryExecuted::class, [$this, 'handle']);
        Event::listen(CacheHit::class, [$this, 'handleCacheHit']);
        Event::listen(CacheMiss::class, [$this, 'handleCacheMiss']);
    }

    public function handle(QueryExecuted $event): void
    {
        if ($this->handling) {
            return;
        }

        $this->handling = true;

        try {
            $this->analyzer->recordQuery(
                $event->sql,
                $event->bindings,
                $event->time / 1000, // Convert ms to seconds
                $event->connectionName
            );
        } finally {
            $this->handling = false;
        }
    }

    public function handleCacheHit(CacheHit $event): void
    {
        $this->analyzer->recordCacheInteraction('hit', $event->key, $event->tags, $event->value);
    }

    public function handleCacheMiss(CacheMiss $event): void
    {
        $this->analyzer->recordCacheInteraction('miss', $event->key, $event->tags);
    }
}