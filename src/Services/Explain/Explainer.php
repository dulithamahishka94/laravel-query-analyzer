<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer;

use GladeHQ\QueryLens\ExplainAnalyzer\Parser\ExplainAnalyzeParser;
use GladeHQ\QueryLens\ExplainAnalyzer\Analyzer\QueryAnalyzer;
use GladeHQ\QueryLens\ExplainAnalyzer\Analyzer\AnalysisResult;
use GladeHQ\QueryLens\ExplainAnalyzer\Formatter\HumanExplainer;

/**
 * Main entry point for MySQL EXPLAIN ANALYZE analysis.
 *
 * Usage:
 *   $analyzer = new ExplainAnalyzer();
 *   $explanation = $analyzer->explain($explainOutput);
 *
 * Or for more control:
 *   $result = $analyzer->analyze($explainOutput);
 *   $explanation = $analyzer->getExplainer()->explain($result);
 */
class ExplainAnalyzer
{
    private ExplainAnalyzeParser $parser;
    private QueryAnalyzer $analyzer;
    private HumanExplainer $explainer;

    public function __construct()
    {
        $this->parser = new ExplainAnalyzeParser();
        $this->analyzer = new QueryAnalyzer();
        $this->explainer = new HumanExplainer();
    }

    /**
     * Analyze and explain EXPLAIN ANALYZE output in one call.
     *
     * @param string $explainOutput Raw EXPLAIN ANALYZE output
     * @return string Human-readable explanation
     */
    public function explain(string $explainOutput): string
    {
        $result = $this->analyze($explainOutput);
        return $this->explainer->explain($result);
    }

    /**
     * Analyze EXPLAIN ANALYZE output and return structured results.
     *
     * @param string $explainOutput Raw EXPLAIN ANALYZE output
     * @return AnalysisResult
     */
    public function analyze(string $explainOutput): AnalysisResult
    {
        $nodes = $this->parser->parse($explainOutput);
        return $this->analyzer->analyze($nodes);
    }

    /**
     * Get the parser instance for advanced usage.
     */
    public function getParser(): ExplainAnalyzeParser
    {
        return $this->parser;
    }

    /**
     * Get the analyzer instance for advanced usage.
     */
    public function getAnalyzer(): QueryAnalyzer
    {
        return $this->analyzer;
    }

    /**
     * Get the explainer instance for customization.
     */
    public function getExplainer(): HumanExplainer
    {
        return $this->explainer;
    }

    /**
     * Configure the explainer to not use markdown.
     */
    public function withoutMarkdown(): self
    {
        $this->explainer->setUseMarkdown(false);
        return $this;
    }

    /**
     * Configure the explainer to not use emoji.
     */
    public function withoutEmoji(): self
    {
        $this->explainer->setUseEmoji(false);
        return $this;
    }

    /**
     * Configure the explainer for minimal output.
     */
    public function minimal(): self
    {
        $this->explainer->setVerbose(false);
        return $this;
    }

    /**
     * Static factory for fluent usage.
     */
    public static function create(): self
    {
        return new self();
    }
}
