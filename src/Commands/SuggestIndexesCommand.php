<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Commands;

use Illuminate\Console\Command;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Services\IndexAdvisor;

class SuggestIndexesCommand extends Command
{
    protected $signature = 'query-lens:suggest-indexes
                            {--days=7 : Number of days to analyze}
                            {--sql= : Analyze a single SQL query}
                            {--format=table : Output format (table, json)}';

    protected $description = 'Analyze query patterns and suggest database indexes for optimization';

    public function handle(IndexAdvisor $advisor, QueryStorage $storage): int
    {
        $advisor->setStorage($storage);

        if ($sql = $this->option('sql')) {
            return $this->analyzeSingleQuery($advisor, $sql);
        }

        return $this->analyzePatterns($advisor);
    }

    protected function analyzeSingleQuery(IndexAdvisor $advisor, string $sql): int
    {
        $this->info('Analyzing query...');
        $result = $advisor->analyzeQuery($sql);

        if (empty($result['suggestions'])) {
            $this->info('No index suggestions for this query.');
            return self::SUCCESS;
        }

        $this->displaySuggestions($result['suggestions']);

        return self::SUCCESS;
    }

    protected function analyzePatterns(IndexAdvisor $advisor): int
    {
        $days = (int) $this->option('days');
        $this->info("Analyzing query patterns from the last {$days} days...");

        $suggestions = $advisor->analyzePatterns($days);

        if (empty($suggestions)) {
            $this->info('No index suggestions found. Either no slow queries exist or all queries are well-indexed.');
            return self::SUCCESS;
        }

        $this->info(count($suggestions) . ' index suggestion(s) found:');
        $this->newLine();

        if ($this->option('format') === 'json') {
            $this->line(json_encode($suggestions, JSON_PRETTY_PRINT));
            return self::SUCCESS;
        }

        $this->displaySuggestions($suggestions);

        // Output CREATE INDEX statements
        $this->newLine();
        $this->info('SQL Statements:');
        $this->newLine();

        foreach ($suggestions as $suggestion) {
            $this->line($suggestion['create_index_sql'] . ';');
        }

        return self::SUCCESS;
    }

    protected function displaySuggestions(array $suggestions): void
    {
        $rows = [];
        foreach ($suggestions as $i => $suggestion) {
            $rows[] = [
                $i + 1,
                $suggestion['table'] ?? 'N/A',
                implode(', ', $suggestion['columns'] ?? []),
                $suggestion['type'] ?? 'N/A',
                $suggestion['impact'] ?? 'N/A',
                $suggestion['reason'] ?? '',
            ];
        }

        $this->table(
            ['#', 'Table', 'Columns', 'Type', 'Impact', 'Reason'],
            $rows
        );
    }
}
