<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\ExplainAnalyzer\Issues;

/**
 * Types of issues that can be detected in an execution plan.
 */
enum IssueType: string
{
    case FULL_TABLE_SCAN = 'full_table_scan';
    case ROW_ESTIMATION_ERROR = 'row_estimation_error';
    case INEFFICIENT_FILTER = 'inefficient_filter';
    case FILESORT = 'filesort';
    case TEMPORARY_TABLE = 'temporary_table';
    case FUNCTION_ON_COLUMN = 'function_on_column';
    case HIGH_LOOP_COUNT = 'high_loop_count';
    case SLOW_OPERATION = 'slow_operation';
    case MISSING_INDEX = 'missing_index';
    case IMPLICIT_CONVERSION = 'implicit_conversion';

    /**
     * Get a description of what this issue type means.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::FULL_TABLE_SCAN => 'MySQL is reading every row in the table instead of using an index.',
            self::ROW_ESTIMATION_ERROR => 'The query optimizer\'s row count estimate was significantly different from reality.',
            self::INEFFICIENT_FILTER => 'Many rows are being fetched and then discarded by a filter operation.',
            self::FILESORT => 'MySQL needs to sort rows using an external algorithm instead of an index.',
            self::TEMPORARY_TABLE => 'MySQL created a temporary table to process the query.',
            self::FUNCTION_ON_COLUMN => 'A function is applied to a column, preventing index usage.',
            self::HIGH_LOOP_COUNT => 'An operation is being executed many times, possibly due to nested loops.',
            self::SLOW_OPERATION => 'An individual operation is taking a significant amount of time.',
            self::MISSING_INDEX => 'An appropriate index appears to be missing for this query.',
            self::IMPLICIT_CONVERSION => 'Data types are being implicitly converted, which may prevent index usage.',
        };
    }

    /**
     * Get the relative impact level (1-10).
     */
    public function getImpactLevel(): int
    {
        return match ($this) {
            self::FULL_TABLE_SCAN => 9,
            self::FUNCTION_ON_COLUMN => 8,
            self::INEFFICIENT_FILTER => 7,
            self::ROW_ESTIMATION_ERROR => 6,
            self::FILESORT => 6,
            self::HIGH_LOOP_COUNT => 7,
            self::SLOW_OPERATION => 5,
            self::TEMPORARY_TABLE => 5,
            self::MISSING_INDEX => 8,
            self::IMPLICIT_CONVERSION => 7,
        };
    }
}
