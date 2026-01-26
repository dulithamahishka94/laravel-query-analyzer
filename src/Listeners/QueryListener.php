<?php

namespace Laravel\QueryAnalyzer\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\QueryAnalyzer\QueryAnalyzer;

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
}