<?php

namespace Coderflex\QueryLens\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Coderflex\QueryLens\QueryAnalyzer;
use Illuminate\Support\Str;

class AnalyzeQueryMiddleware
{
    protected QueryAnalyzer $analyzer;

    public function __construct(QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->is('query-lens*')) {
            $this->analyzer->disableRecording();
        }

        // Set a unique Request ID for this request cycle
        // This ensures queries are grouped by the actual HTTP request, 
        // regardless of the underlying PHP process reuse.
        // Initialize Request ID if not already set by Service Provider
        if (!$this->analyzer->getRequestId()) {
            $this->analyzer->setRequestId((string) Str::orderedUuid());
        }
        
        return $next($request);
    }
}
