<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Http\Middleware\QueryLensMiddleware;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardAuthTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('query-lens.web_ui.enabled', true);
        $app['config']->set('query-lens.web_ui.auth_gate', null);
        $app['config']->set('query-lens.web_ui.auth_callback', null);
        $app['config']->set('query-lens.web_ui.allowed_ips', ['127.0.0.1', '::1']);
    }

    protected function createMiddleware(): QueryLensMiddleware
    {
        $analyzer = new QueryAnalyzer([
            'performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0],
        ], new InMemoryQueryStorage());

        return new QueryLensMiddleware($analyzer);
    }

    protected function createDashboardRequest(string $ip = '127.0.0.1'): Request
    {
        $request = Request::create('/query-lens', 'GET');
        $request->server->set('REMOTE_ADDR', $ip);

        return $request;
    }

    public function test_access_allowed_with_no_gate_configured(): void
    {
        $this->app['config']->set('query-lens.web_ui.auth_gate', null);

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest();

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_gate_check_allows_authorized_user(): void
    {
        $this->app['config']->set('query-lens.web_ui.auth_gate', 'viewQueryLens');

        Gate::define('viewQueryLens', function ($user = null) {
            return true;
        });

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest();

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_gate_check_blocks_unauthorized_user(): void
    {
        $this->app['config']->set('query-lens.web_ui.auth_gate', 'viewQueryLens');

        Gate::define('viewQueryLens', function ($user = null) {
            return false;
        });

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {
            return new Response('ok');
        });
    }

    public function test_ip_allowlist_still_works(): void
    {
        // Force non-local environment to trigger IP check
        $this->app['env'] = 'production';
        $this->app['config']->set('query-lens.web_ui.allowed_ips', ['10.0.0.1']);

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest('192.168.1.100');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {
            return new Response('ok');
        });
    }

    public function test_ip_allowlist_allows_listed_ip(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('query-lens.web_ui.allowed_ips', ['10.0.0.1']);

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest('10.0.0.1');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_callback_still_works(): void
    {
        $this->app['config']->set('query-lens.web_ui.auth_callback', function (Request $request) {
            return $request->hasHeader('X-Custom-Auth');
        });

        $middleware = $this->createMiddleware();

        // Without the header -- should be denied
        $request = $this->createDashboardRequest();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {
            return new Response('ok');
        });
    }

    public function test_callback_allows_when_returns_true(): void
    {
        $this->app['config']->set('query-lens.web_ui.auth_callback', function (Request $request) {
            return true;
        });

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest();

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_gate_plus_ip_combined(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('query-lens.web_ui.auth_gate', 'viewQueryLens');
        $this->app['config']->set('query-lens.web_ui.allowed_ips', ['10.0.0.1']);

        Gate::define('viewQueryLens', function ($user = null) {
            return true;
        });

        $middleware = $this->createMiddleware();

        // Gate passes but IP fails
        $request = $this->createDashboardRequest('192.168.1.100');

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {
            return new Response('ok');
        });
    }

    public function test_gate_fails_ip_passes_combined(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('query-lens.web_ui.auth_gate', 'viewQueryLens');
        $this->app['config']->set('query-lens.web_ui.allowed_ips', ['10.0.0.1']);

        Gate::define('viewQueryLens', function ($user = null) {
            return false;
        });

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest('10.0.0.1');

        // Gate fails -- should be denied even though IP is allowed
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {
            return new Response('ok');
        });
    }

    public function test_gate_and_ip_both_pass(): void
    {
        $this->app['env'] = 'production';
        $this->app['config']->set('query-lens.web_ui.auth_gate', 'viewQueryLens');
        $this->app['config']->set('query-lens.web_ui.allowed_ips', ['10.0.0.1']);

        Gate::define('viewQueryLens', function ($user = null) {
            return true;
        });

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest('10.0.0.1');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_web_ui_disabled_denies_all_access(): void
    {
        $this->app['config']->set('query-lens.web_ui.enabled', false);

        $middleware = $this->createMiddleware();
        $request = $this->createDashboardRequest();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function () {
            return new Response('ok');
        });
    }

    public function test_non_dashboard_routes_bypass_auth(): void
    {
        // Even with restrictive settings, non-dashboard routes pass through
        $this->app['config']->set('query-lens.web_ui.auth_gate', 'viewQueryLens');

        Gate::define('viewQueryLens', function ($user = null) {
            return false;
        });

        $middleware = $this->createMiddleware();
        $request = Request::create('/api/users', 'GET');

        $response = $middleware->handle($request, function () {
            return new Response('ok');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_config_has_auth_gate_key(): void
    {
        $config = config('query-lens.web_ui');

        $this->assertArrayHasKey('auth_gate', $config);
        $this->assertNull($config['auth_gate']);
    }
}
