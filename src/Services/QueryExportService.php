<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Illuminate\Support\Collection;

class QueryExportService
{
    /**
     * Export queries in the requested format.
     *
     * @return array{data: string|array, filename: string, stats?: array}
     */
    public function export(Collection $queries, string $format = 'json', array $stats = []): array
    {
        $timestamp = date('Y-m-d-H-i-s');

        if ($format === 'csv') {
            return [
                'data' => $this->toCsv($queries),
                'filename' => "query-analysis-{$timestamp}.csv",
            ];
        }

        return [
            'data' => $queries->toArray(),
            'stats' => $stats,
            'filename' => "query-analysis-{$timestamp}.json",
        ];
    }

    protected function toCsv(Collection $queries): string
    {
        $csv = "Index,Type,Time,Performance,Complexity,Issues,SQL\n";

        foreach ($queries as $index => $query) {
            $analysis = $query['analysis'];
            $csv .= sprintf(
                "%d,%s,%.3f,%s,%s,%d,\"%s\"\n",
                $index + 1,
                $analysis['type'],
                $query['time'],
                $analysis['performance']['rating'],
                $analysis['complexity']['level'],
                count($analysis['issues']),
                str_replace('"', '""', $query['sql'])
            );
        }

        return $csv;
    }
}
