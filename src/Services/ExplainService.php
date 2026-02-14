<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExplainService
{
    /**
     * Run EXPLAIN (and optionally EXPLAIN ANALYZE) on a SELECT query.
     *
     * @return array{standard: array, analyze: array, raw_analyze: ?string, supports_analyze: bool, summary: string, insights: array, result: array, type: string}
     */
    public function explain(string $sql, array $bindings = [], ?string $connection = null): array
    {
        $standardResult = DB::connection($connection)->select('EXPLAIN ' . $sql, $bindings);

        $analyzeResult = [];
        $supportsAnalyze = false;
        $rawAnalyze = null;

        try {
            $analyzeResult = DB::connection($connection)->select('EXPLAIN ANALYZE ' . $sql, $bindings);
            $supportsAnalyze = true;
        } catch (\Exception) {
            // Silently fail if ANALYZE is not supported
        }

        $humanized = $this->humanizeExplain((array) $standardResult, (array) $analyzeResult);

        if ($supportsAnalyze && !empty($analyzeResult)) {
            try {
                $rawAnalyze = (string) (reset($analyzeResult[0]) ?: '');
                $deepAnalyzer = new \GladeHQ\QueryLens\Services\Explain\Explainer();
                $analysisResult = $deepAnalyzer->analyze($rawAnalyze);

                $fullExplanation = $deepAnalyzer->getExplainer()->explain($analysisResult);

                $firstKey = array_key_first((array) $analyzeResult[0]);
                $analyzeResult = [[$firstKey => $fullExplanation]];

                $summaryGenerator = new \GladeHQ\QueryLens\Services\Explain\Formatter\CompactFormatter();
                $compactStats = explode("\n", $summaryGenerator->format($analysisResult))[0] ?? '';

                $healthStatus = $analysisResult->getHealthStatus();
                $healthMsg = match ($healthStatus) {
                    'critical' => 'Critical issues detected.',
                    'warning' => 'Performance warnings found.',
                    'needs_attention' => 'Some optimization opportunities identified.',
                    'good' => 'Query appears healthy.',
                    default => 'Analysis complete.',
                };

                $humanized['summary'] = "{$healthMsg} {$compactStats}";

                $deepIssues = [];
                foreach ($analysisResult->getIssuesBySeverity() as $issue) {
                    $severity = strtoupper($issue->getSeverity()->value);
                    $deepIssues[] = "{$issue->getSeverityEmoji()} **{$issue->getTitle()}** ({$severity}): {$issue->getMessage()}";
                }

                if (!empty($deepIssues)) {
                    $humanized['insights'] = array_merge($deepIssues, $humanized['insights']);
                }
            } catch (\Exception $e) {
                Log::warning('Deep Explain Analyzer failed: ' . $e->getMessage());
            }
        }

        $isStandardTree = count((array) ($standardResult[0] ?? [])) === 1;

        return [
            'standard' => array_values((array) $standardResult),
            'analyze' => array_values((array) $analyzeResult),
            'raw_analyze' => $rawAnalyze,
            'supports_analyze' => $supportsAnalyze,
            'summary' => $humanized['summary'] ?? 'No summary available.',
            'insights' => array_values($humanized['insights'] ?? []),
            'result' => !empty($analyzeResult) ? $analyzeResult : $standardResult,
            'type' => (!empty($analyzeResult) || $isStandardTree) ? 'analyze' : 'standard',
        ];
    }

    /**
     * Humanize a standard EXPLAIN result into a summary and insights.
     */
    public function humanizeExplain(array $standard, array $analyze): array
    {
        $insights = [];
        $summaryParts = [];

        if (empty($standard)) {
            return ['summary' => 'No execution plan data was returned from the database.', 'insights' => []];
        }

        foreach ($standard as $row) {
            $row = (array) $row;
            if (empty($row)) {
                continue;
            }

            // Handle tree format (Standard EXPLAIN in some MySQL 8 configs)
            if (count($row) === 1 && !isset($row['type'])) {
                $treePlan = (string) (reset($row) ?: '');
                if (str_contains($treePlan, 'Table scan') || str_contains($treePlan, 'Full scan')) {
                    $summaryParts[] = "The database is performing a **Full Table Scan**.";
                    $insights[] = "❌ **Full Table Scan**: Database is checking every single row because no suitable index was found.";
                }
                if (str_contains($treePlan, 'Index lookup') || str_contains($treePlan, 'Index scan')) {
                    $summaryParts[] = "The database is using an **Index** to look up data.";
                    $insights[] = "✅ **Index Used**: The query is using an index for filtering.";
                }
                continue;
            }

            $type = $row['type'] ?? '';
            $extra = $row['Extra'] ?? '';
            $key = $row['key'] ?? '';
            $rows = $row['rows'] ?? 'unknown number of';
            $table = $row['table'] ?? 'your table';

            if ($type === 'ALL') {
                $summaryParts[] = "The database is performing a **Full Table Scan** on `$table`.";
                $insights[] = "❌ **Full Table Scan**: Database is checking every single row because no suitable index was found.";
            } elseif ($key) {
                $summaryParts[] = "The database is using the **`$key` index** to look up data in `$table`.";
                $insights[] = "✅ **Index Used**: The query is efficiently filtered using the `$key` index.";
            }

            if ($type === 'ALL' || (is_numeric($rows) && $rows > 1000)) {
                $summaryParts[] = "It expects to scan approximately **$rows rows** to resolve this part of the query.";
            }

            if (str_contains($extra, 'Using filesort')) {
                $summaryParts[] = "It is also performing a **Filesort**, meaning results are being sorted in memory or on disk.";
                $insights[] = "🐌 **Filesort**: Consider adding an index on your `ORDER BY` columns to avoid expensive memory/disk sorting.";
            }

            if (str_contains($extra, 'Using temporary')) {
                $summaryParts[] = "An **Internal Temporary Table** is being created to resolve this query.";
                $insights[] = "🛠️ **Temporary Table**: This is often caused by complex GROUP BY or DISTINCT operations. Efficiency could be improved.";
            }
        }

        if (!empty($analyze)) {
            $firstRow = (array) ($analyze[0] ?? []);
            $plan = (string) (reset($firstRow) ?: '');
            if ($plan && str_contains($plan, 'disk')) {
                $insights[] = "🔥 **Disk I/O**: The profiler detected that temporary data was written to disk, which is a major performance killer.";
            }
        }

        $summary = !empty($summaryParts)
            ? implode(' ', array_unique($summaryParts)) . "."
            : "The database is resolving this query using standard index lookups. No major performance red flags were detected during optimization.";

        return [
            'summary' => $summary,
            'insights' => array_unique($insights),
        ];
    }
}
