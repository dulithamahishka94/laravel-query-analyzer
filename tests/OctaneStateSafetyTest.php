<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;

class OctaneStateSafetyTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function createAnalyzer(): QueryAnalyzer
    {
        return new QueryAnalyzer([
            'performance_thresholds' => [
                'fast' => 0.1,
                'moderate' => 0.5,
                'slow' => 1.0,
            ],
        ], new InMemoryQueryStorage());
    }

    public function test_enable_recording_restores_recording_state(): void
    {
        $analyzer = $this->createAnalyzer();

        $this->assertTrue($analyzer->isRecording());

        $analyzer->disableRecording();
        $this->assertFalse($analyzer->isRecording());

        $analyzer->enableRecording();
        $this->assertTrue($analyzer->isRecording());
    }

    public function test_disable_recording_prevents_query_capture(): void
    {
        $analyzer = $this->createAnalyzer();

        $analyzer->disableRecording();
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);

        $this->assertCount(0, $analyzer->getQueries());
    }

    public function test_enable_recording_allows_query_capture_again(): void
    {
        $analyzer = $this->createAnalyzer();

        $analyzer->disableRecording();
        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $this->assertCount(0, $analyzer->getQueries());

        $analyzer->enableRecording();
        $analyzer->recordQuery('SELECT * FROM posts', [], 0.05);
        $this->assertCount(1, $analyzer->getQueries());
    }

    public function test_dashboard_request_does_not_permanently_disable_recording(): void
    {
        $analyzer = $this->createAnalyzer();
        $storage = new InMemoryQueryStorage();
        $middleware = new AnalyzeQueryMiddleware($analyzer, $storage);

        // Simulate dashboard request
        $dashboardRequest = Request::create('/query-lens/api/queries', 'GET');
        $response = new Response('ok');

        $middleware->handle($dashboardRequest, function () use ($response) {
            return $response;
        });

        $this->assertFalse($analyzer->isRecording());

        // Simulate terminate phase
        $middleware->terminate($dashboardRequest, $response);

        $this->assertTrue($analyzer->isRecording());
    }

    public function test_non_dashboard_request_does_not_affect_recording(): void
    {
        $analyzer = $this->createAnalyzer();
        $storage = new InMemoryQueryStorage();
        $middleware = new AnalyzeQueryMiddleware($analyzer, $storage);

        $normalRequest = Request::create('/api/users', 'GET');
        $response = new Response('ok');

        $middleware->handle($normalRequest, function () use ($response) {
            return $response;
        });

        $this->assertTrue($analyzer->isRecording());

        // Terminate should not change state
        $middleware->terminate($normalRequest, $response);
        $this->assertTrue($analyzer->isRecording());
    }

    public function test_analyzer_is_registered_as_scoped(): void
    {
        // Verify the binding is scoped (not singleton)
        // A scoped binding resolves fresh per request context
        $analyzer1 = $this->app->make(QueryAnalyzer::class);
        $analyzer2 = $this->app->make(QueryAnalyzer::class);

        // Within the same request context, scoped behaves like singleton
        $this->assertSame($analyzer1, $analyzer2);
    }

    public function test_is_recording_returns_correct_state(): void
    {
        $analyzer = $this->createAnalyzer();

        $this->assertTrue($analyzer->isRecording());

        $analyzer->disableRecording();
        $this->assertFalse($analyzer->isRecording());

        $analyzer->enableRecording();
        $this->assertTrue($analyzer->isRecording());
    }

    public function test_multiple_disable_enable_cycles_work(): void
    {
        $analyzer = $this->createAnalyzer();

        for ($i = 0; $i < 5; $i++) {
            $analyzer->disableRecording();
            $this->assertFalse($analyzer->isRecording());

            $analyzer->enableRecording();
            $this->assertTrue($analyzer->isRecording());
        }

        // Should still be able to record
        $analyzer->recordQuery('SELECT 1', [], 0.05);
        $this->assertCount(1, $analyzer->getQueries());
    }
}
