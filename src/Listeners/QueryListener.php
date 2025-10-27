<?php

namespace Laravel\QueryAnalyzer\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\QueryAnalyzer\QueryAnalyzer;

class QueryListener
{
    protected QueryAnalyzer $analyzer;

    public function __construct(QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function register(): void
    {
        Event::listen(QueryExecuted::class, [$this, 'handle']);
    }

    public function handle(QueryExecuted $event): void
    {
        $this->analyzer->recordQuery(
            $event->sql,
            $event->bindings,
            $event->time,
            $event->connectionName
        );
    }
}