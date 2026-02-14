<?php

namespace GladeHQ\QueryLens\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\QueryAnalyzer;
use Illuminate\Support\Str;

class AnalyzeQueryMiddleware
{
    protected QueryAnalyzer $analyzer;
    protected QueryStorage $storage;
    protected bool $wasDisabledForDashboard = false;

    public function __construct(QueryAnalyzer $analyzer, QueryStorage $storage)
    {
        $this->analyzer = $analyzer;
        $this->storage = $storage;
    }

    public function handle(Request $request, Closure $next)
    {
        // Detect Query Lens routes: both standalone (/query-lens/*) and
        // Filament panel pages (/<panel>/query-lens*). The container-level
        // check in QueryLensServiceProvider already disables recording before
        // session middleware runs, but this serves as the definitive middleware
        // layer that also controls the terminate() finalization logic.
        $isQueryLensRoute = $request->is('query-lens*')
            || str_contains($request->getPathInfo(), '/query-lens');

        if ($isQueryLensRoute) {
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
        // Finalize request aggregates once at end of request (not per-query)
        $requestId = $this->analyzer->getRequestId();
        if ($requestId && !$this->wasDisabledForDashboard) {
            $this->storage->finalizeRequest($requestId);
        }

        // Re-enable recording after dashboard requests to prevent state leak
        // in long-lived processes (Octane). With scoped binding this is a safety net.
        if ($this->wasDisabledForDashboard) {
            $this->analyzer->enableRecording();
            $this->wasDisabledForDashboard = false;
        }
    }
}
