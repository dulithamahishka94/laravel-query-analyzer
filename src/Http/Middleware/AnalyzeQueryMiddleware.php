<?php

namespace GladeHQ\QueryLens\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GladeHQ\QueryLens\QueryAnalyzer;
use Illuminate\Support\Str;

class AnalyzeQueryMiddleware
{
    protected QueryAnalyzer $analyzer;
    protected bool $wasDisabledForDashboard = false;

    public function __construct(QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function handle(Request $request, Closure $next)
    {
        if ($request->is('query-lens*')) {
            $this->analyzer->disableRecording();
            $this->wasDisabledForDashboard = true;
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

    public function terminate(Request $request, $response): void
    {
        // Re-enable recording after dashboard requests to prevent state leak
        // in long-lived processes (Octane). With scoped binding this is a safety net.
        if ($this->wasDisabledForDashboard) {
            $this->analyzer->enableRecording();
            $this->wasDisabledForDashboard = false;
        }
    }
}
