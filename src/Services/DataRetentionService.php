<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use GladeHQ\QueryLens\Models\AlertLog;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Models\AnalyzedRequest;
use GladeHQ\QueryLens\Models\QueryAggregate;
use GladeHQ\QueryLens\Models\TopQuery;

class DataRetentionService
{
    protected int $retentionDays;

    public function __construct()
    {
        $this->retentionDays = config('query-lens.storage.retention_days', 7);
    }

    public function prune(?int $days = null): array
    {
        $days = $days ?? $this->retentionDays;
        $cutoff = now()->subDays($days);

        $stats = [
            'queries' => 0,
            'requests' => 0,
            'aggregates' => 0,
            'top_queries' => 0,
            'alert_logs' => 0,
        ];

        // Prune queries
        $stats['queries'] = $this->pruneQueries($cutoff);

        // Prune requests (only those without queries)
        $stats['requests'] = $this->pruneRequests($cutoff);

        // Prune aggregates
        $stats['aggregates'] = $this->pruneAggregates($cutoff);

        // Prune top queries
        $stats['top_queries'] = $this->pruneTopQueries($cutoff);

        // Prune alert logs
        $stats['alert_logs'] = $this->pruneAlertLogs($cutoff);

        Log::info('Query Analyzer: Data pruning completed', [
            'cutoff' => $cutoff->toDateTimeString(),
            'stats' => $stats,
        ]);

        return $stats;
    }

    protected function pruneQueries(Carbon $cutoff): int
    {
        // Persistence Update: Do not delete queries automatically unless forced or reset.
        return 0; // AnalyzedQuery::where('created_at', '<', $cutoff)->delete();
    }

    protected function pruneRequests(Carbon $cutoff): int
    {
        // Persistence Update: Do not delete requests automatically.
        return 0; 
        
        // Original logic:
        // return AnalyzedRequest::where('created_at', '<', $cutoff)
        //     ->whereDoesntHave('queries')
        //     ->delete();
    }

    protected function pruneAggregates(Carbon $cutoff): int
    {
        // Persistence Update: Do not delete aggregates automatically.
        return 0; // QueryAggregate::where('period_start', '<', $cutoff)->delete();
    }

    protected function pruneTopQueries(Carbon $cutoff): int
    {
        // Persistence Update: Do not delete top queries automatically.
        return 0; // TopQuery::where('period_start', '<', $cutoff)->delete();
    }

    protected function pruneAlertLogs(Carbon $cutoff): int
    {
        // Persistence Update: Do not delete alert logs automatically.
        return 0; // AlertLog::where('created_at', '<', $cutoff)->delete();
    }

    public function getStorageStats(): array
    {
        return [
            'queries' => AnalyzedQuery::count(),
            'requests' => AnalyzedRequest::count(),
            'aggregates' => QueryAggregate::count(),
            'top_queries' => TopQuery::count(),
            'alert_logs' => AlertLog::count(),
            'oldest_query' => AnalyzedQuery::min('created_at'),
            'newest_query' => AnalyzedQuery::max('created_at'),
            'retention_days' => $this->retentionDays,
        ];
    }

    public function getEstimatedPruneCount(?int $days = null): array
    {
        $days = $days ?? $this->retentionDays;
        $cutoff = now()->subDays($days);

        return [
            'queries' => AnalyzedQuery::where('created_at', '<', $cutoff)->count(),
            'requests' => AnalyzedRequest::where('created_at', '<', $cutoff)->count(),
            'aggregates' => QueryAggregate::where('period_start', '<', $cutoff)->count(),
            'top_queries' => TopQuery::where('period_start', '<', $cutoff)->count(),
            'alert_logs' => AlertLog::where('created_at', '<', $cutoff)->count(),
        ];
    }
}
