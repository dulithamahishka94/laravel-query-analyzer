<?php

namespace GladeHQ\QueryLens\Commands;

use Illuminate\Console\Command;
use GladeHQ\QueryLens\QueryAnalyzer;

class AnalyzeQueriesCommand extends Command
{
    protected $signature = 'query:analyze
                            {--reset : Reset the query collection after analysis}
                            {--format=table : Output format (table, json)}
                            {--slow-only : Show only slow queries}';

    protected $description = 'Analyze collected database queries for performance issues';

    protected QueryAnalyzer $analyzer;

    public function __construct(QueryAnalyzer $analyzer)
    {
        parent::__construct();
        $this->analyzer = $analyzer;
    }

    public function handle(): int
    {
        $queries = $this->analyzer->getQueries();

        if ($queries->isEmpty()) {
            $this->warn('No queries have been recorded. Make sure query analysis is enabled.');
            return self::FAILURE;
        }

        if ($this->option('slow-only')) {
            $slowThreshold = config('query-lens.performance_thresholds.slow', 1.0);
            $queries = $queries->where('time', '>', $slowThreshold);

            if ($queries->isEmpty()) {
                $this->info('No slow queries found!');
                return self::SUCCESS;
            }
        }

        $this->displayStats();

        if ($this->option('format') === 'json') {
            $this->displayJsonOutput($queries);
        } else {
            $this->displayTableOutput($queries);
        }

        if ($this->option('reset')) {
            $this->analyzer->reset();
            $this->info('Query collection has been reset.');
        }

        return self::SUCCESS;
    }

    protected function displayStats(): void
    {
        $stats = $this->analyzer->getStats();

        $this->info('Query Analysis Statistics:');
        $this->table(['Metric', 'Value'], [
            ['Total Queries', $stats['total_queries']],
            ['Total Execution Time', number_format($stats['total_time'], 3) . 's'],
            ['Average Execution Time', number_format($stats['average_time'], 3) . 's'],
            ['Slow Queries', $stats['slow_queries']],
        ]);

        if (!empty($stats['query_types'])) {
            $this->info('Query Types:');
            $this->table(['Type', 'Count'],
                collect($stats['query_types'])->map(fn($count, $type) => [$type, $count])->toArray()
            );
        }

        $this->newLine();
    }

    protected function displayTableOutput($queries): void
    {
        $tableData = $queries->map(function ($query, $index) {
            $analysis = $query['analysis'];
            return [
                $index + 1,
                $analysis['type'],
                number_format($query['time'], 3) . 's',
                $analysis['performance']['rating'],
                $analysis['complexity']['level'],
                count($analysis['issues']),
                substr($query['sql'], 0, 50) . (strlen($query['sql']) > 50 ? '...' : ''),
            ];
        })->toArray();

        $this->table([
            '#', 'Type', 'Time', 'Performance', 'Complexity', 'Issues', 'SQL Preview'
        ], $tableData);

        $this->displayRecommendations($queries);
    }

    protected function displayJsonOutput($queries): void
    {
        $this->line(json_encode($queries->toArray(), JSON_PRETTY_PRINT));
    }

    protected function displayRecommendations($queries): void
    {
        $allRecommendations = $queries->flatMap(fn($query) => $query['analysis']['recommendations'])->unique();
        $allIssues = $queries->flatMap(fn($query) => $query['analysis']['issues']);

        if ($allRecommendations->isNotEmpty()) {
            $this->newLine();
            $this->warn('Recommendations:');
            foreach ($allRecommendations as $recommendation) {
                $this->line("• {$recommendation}");
            }
        }

        if ($allIssues->isNotEmpty()) {
            $this->newLine();
            $this->error('Issues Found:');
            foreach ($allIssues->groupBy('type') as $type => $issues) {
                $this->line("• {$type}: " . $issues->pluck('message')->unique()->implode(', '));
            }
        }
    }
}