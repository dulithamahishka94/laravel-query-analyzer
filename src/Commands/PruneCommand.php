<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Commands;

use Illuminate\Console\Command;
use GladeHQ\QueryLens\Services\DataRetentionService;

class PruneCommand extends Command
{
    protected $signature = 'query-lens:prune
                            {--days= : Number of days to retain (default: from config)}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Prune old query analyzer data based on retention policy';

    public function handle(DataRetentionService $service): int
    {
        $days = $this->option('days')
            ? (int) $this->option('days')
            : config('query-lens.storage.retention_days', 7);

        $dryRun = $this->option('dry-run');

        // Show storage stats
        $stats = $service->getStorageStats();
        $this->info('Current storage statistics:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Queries', number_format($stats['queries'])],
                ['Requests', number_format($stats['requests'])],
                ['Aggregates', number_format($stats['aggregates'])],
                ['Top Queries', number_format($stats['top_queries'])],
                ['Alert Logs', number_format($stats['alert_logs'])],
                ['Oldest Query', $stats['oldest_query'] ?? 'N/A'],
                ['Newest Query', $stats['newest_query'] ?? 'N/A'],
            ]
        );

        $this->newLine();

        // Show estimated prune counts
        $estimates = $service->getEstimatedPruneCount($days);
        $total = array_sum($estimates);

        if ($total === 0) {
            $this->info("No data older than {$days} days found. Nothing to prune.");
            return self::SUCCESS;
        }

        $this->warn("Records older than {$days} days to be pruned:");
        $this->table(
            ['Type', 'Count'],
            [
                ['Queries', number_format($estimates['queries'])],
                ['Requests', number_format($estimates['requests'])],
                ['Aggregates', number_format($estimates['aggregates'])],
                ['Top Queries', number_format($estimates['top_queries'])],
                ['Alert Logs', number_format($estimates['alert_logs'])],
                ['Total', number_format($total)],
            ]
        );

        if ($dryRun) {
            $this->info('Dry run complete. No data was deleted.');
            return self::SUCCESS;
        }

        // Confirm unless forced
        if (!$this->option('force') && !$this->confirm('Do you want to proceed with pruning?')) {
            $this->info('Pruning cancelled.');
            return self::SUCCESS;
        }

        $this->info('Pruning data...');

        $results = $service->prune($days);

        $this->newLine();
        $this->info('Pruning complete!');
        $this->table(
            ['Type', 'Deleted'],
            [
                ['Queries', number_format($results['queries'])],
                ['Requests', number_format($results['requests'])],
                ['Aggregates', number_format($results['aggregates'])],
                ['Top Queries', number_format($results['top_queries'])],
                ['Alert Logs', number_format($results['alert_logs'])],
            ]
        );

        return self::SUCCESS;
    }
}
