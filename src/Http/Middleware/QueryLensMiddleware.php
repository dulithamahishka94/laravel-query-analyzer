<?php

namespace GladeHQ\QueryLens\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QueryLensMiddleware
{
    protected \GladeHQ\QueryLens\QueryAnalyzer $analyzer;

    public function __construct(\GladeHQ\QueryLens\QueryAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function handle(Request $request, Closure $next): Response
    {
        // 1. Initialize Request ID for grouping
        $requestId = $request->header('X-Request-ID') ?? \Illuminate\Support\Str::uuid()->toString();
        $this->analyzer->setRequestId($requestId);

        // 2. Authorization for DASHBOARD access
        if (str_contains($request->getPathInfo(), 'query-lens')) {
            if (!$this->isAuthorized($request)) {
                abort(403, 'Access denied to Query Analyzer. Please check your configuration.');
            }
        }

        $response = $next($request);

        // 3. Add Request ID to response header for debugging
        if ($response instanceof Response) {
            $response->headers->set('X-Query-Analyzer-ID', $requestId);
        }

        return $response;
    }

    protected function isAuthorized(Request $request): bool
    {
        if (!config('query-lens.web_ui.enabled', true)) {
            return false;
        }

        if (!app()->environment(['local', 'testing'])) {
            $allowedIps = config('query-lens.web_ui.allowed_ips', ['127.0.0.1', '::1']);
            if (!in_array($request->ip(), $allowedIps)) {
                return false;
            }
        }

        $authCallback = config('query-lens.web_ui.auth_callback');
        if ($authCallback && is_callable($authCallback)) {
            return (bool) call_user_func($authCallback, $request);
        }

        return true;
    }
}