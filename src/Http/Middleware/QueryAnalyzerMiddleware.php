<?php

namespace Laravel\QueryAnalyzer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QueryAnalyzerMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->isAuthorized($request)) {
            abort(403, 'Access denied to Query Analyzer. Please check your configuration.');
        }

        return $next($request);
    }

    protected function isAuthorized(Request $request): bool
    {
        if (!config('query-analyzer.web_ui.enabled', true)) {
            return false;
        }

        if (!app()->environment(['local', 'testing'])) {
            $allowedIps = config('query-analyzer.web_ui.allowed_ips', ['127.0.0.1', '::1']);
            if (!in_array($request->ip(), $allowedIps)) {
                return false;
            }
        }

        $authCallback = config('query-analyzer.web_ui.auth_callback');
        if ($authCallback && is_callable($authCallback)) {
            return call_user_func($authCallback, $request);
        }

        return true;
    }
}