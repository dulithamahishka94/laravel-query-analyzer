<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer\Formatter;

use GladeHQ\QueryLens\ExplainAnalyzer\Analyzer\AnalysisResult;

/**
 * Formats analysis results as JSON.
 */
class JsonFormatter
{
    private bool $prettyPrint = true;

    public function setPrettyPrint(bool $prettyPrint): self
    {
        $this->prettyPrint = $prettyPrint;
        return $this;
    }

    /**
     * Format the analysis result as JSON.
     */
    public function format(AnalysisResult $result): string
    {
        $flags = JSON_THROW_ON_ERROR;

        if ($this->prettyPrint) {
            $flags |= JSON_PRETTY_PRINT;
        }

        return json_encode($result->toArray(), $flags);
    }

    /**
     * Format as a PHP array.
     */
    public function toArray(AnalysisResult $result): array
    {
        return $result->toArray();
    }
}
