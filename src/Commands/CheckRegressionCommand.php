<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Commands;

use Illuminate\Console\Command;
use GladeHQ\QueryLens\Services\RegressionDetector;
use GladeHQ\QueryLens\Services\WebhookNotifier;

class CheckRegressionCommand extends Command
{
    protected $signature = 'query-lens:check-regression
                            {--period=daily : Comparison period (daily, hourly)}
                            {--threshold=0.2 : Regression threshold (0.2 = 20%)}
                            {--webhook : Send results to configured webhook}
                            {--format=table : Output format (table, json)}';

    protected $description = 'Check for query performance regressions against the previous period';

    public function handle(RegressionDetector $detector, WebhookNotifier $notifier): int
    {
        $period = $this->option('period');
        $threshold = (float) $this->option('threshold');

        $this->info("Checking for regressions ({$period} comparison, threshold: " . ($threshold * 100) . '%)...');

        $result = $detector->detect($period, $threshold);
        $regressions = $result['regressions'];
        $summary = $result['summary'];

        if ($this->option('format') === 'json') {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $this->displayResults($regressions, $summary);
        }

        // Send webhook if requested
        if ($this->option('webhook')) {
            $webhookUrl = config('query-lens.regression.webhook_url');

            if (!$webhookUrl) {
                $this->warn('No webhook URL configured. Set QUERY_LENS_REGRESSION_WEBHOOK_URL or query-lens.regression.webhook_url.');
            } else {
                $headers = config('query-lens.regression.headers', []);
                $success = $notifier->notify($webhookUrl, $result, ['headers' => $headers]);

                if ($success) {
                    $this->info('Webhook notification sent successfully.');
                } else {
                    $this->error('Failed to send webhook notification.');
                }
            }
        }

        // Exit with non-zero code if critical regressions found (for CI)
        if ($summary['has_critical'] ?? false) {
            $this->error('CRITICAL regressions detected! Exiting with error code.');
            return self::FAILURE;
        }

        if (empty($regressions)) {
            $this->info('No regressions detected. All metrics within threshold.');
        }

        return self::SUCCESS;
    }

    protected function displayResults(array $regressions, array $summary): void
    {
        $this->info('Period: ' . ($summary['period'] ?? 'daily'));
        $this->info('Current: ' . ($summary['current_period']['start'] ?? '') . ' to ' . ($summary['current_period']['end'] ?? ''));
        $this->info('Previous: ' . ($summary['previous_period']['start'] ?? '') . ' to ' . ($summary['previous_period']['end'] ?? ''));
        $this->newLine();

        // Show current vs previous stats
        $current = $summary['current_stats'] ?? [];
        $previous = $summary['previous_stats'] ?? [];

        $this->table(['Metric', 'Previous', 'Current'], [
            ['Total Queries', $previous['total_queries'] ?? 0, $current['total_queries'] ?? 0],
            ['Slow Queries', $previous['slow_queries'] ?? 0, $current['slow_queries'] ?? 0],
            ['Avg Time', round(($previous['avg_time'] ?? 0) * 1000, 2) . 'ms', round(($current['avg_time'] ?? 0) * 1000, 2) . 'ms'],
            ['P95', round(($previous['p95'] ?? 0) * 1000, 2) . 'ms', round(($current['p95'] ?? 0) * 1000, 2) . 'ms'],
        ]);

        if (empty($regressions)) {
            return;
        }

        $this->newLine();
        $this->warn('Regressions detected:');

        $rows = [];
        foreach ($regressions as $r) {
            $pct = round($r['change_pct'] * 100, 1);
            $rows[] = [
                strtoupper($r['severity']),
                $r['label'],
                round($r['previous'], 4),
                round($r['current'], 4),
                "+{$pct}%",
            ];
        }

        $this->table(['Severity', 'Metric', 'Previous', 'Current', 'Change'], $rows);
    }
}
