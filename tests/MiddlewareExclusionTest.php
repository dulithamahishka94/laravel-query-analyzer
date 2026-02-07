<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware;
use GladeHQ\QueryLens\QueryAnalyzer;
use Illuminate\Http\Request;
use Mockery;
use Orchestra\Testbench\TestCase;

class MiddlewareExclusionTest extends TestCase
{
    /** @test */
    public function it_disables_analyzer_for_query_lens_routes()
    {
        // Mock the QueryAnalyzer
        $analyzerString = Mockery::mock(QueryAnalyzer::class);
        $analyzerString->shouldReceive('getRequestId')->andReturn(null);
        $analyzerString->shouldReceive('setRequestId');
        
        // EXPECT disableRecording to be called
        $analyzerString->shouldReceive('disableRecording')->once();

        $middleware = new AnalyzeQueryMiddleware($analyzerString);

        $request = Request::create('/query-lens/api/requests', 'GET');

        $middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_enables_analyzer_for_normal_routes()
    {
        // Mock the QueryAnalyzer
        $analyzerString = Mockery::mock(QueryAnalyzer::class);
        $analyzerString->shouldReceive('getRequestId')->andReturn(null);
        $analyzerString->shouldReceive('setRequestId');
        
        // EXPECT disableRecording to NOT be called
        $analyzerString->shouldReceive('disableRecording')->never();

        $middleware = new AnalyzeQueryMiddleware($analyzerString);

        $request = Request::create('/users', 'GET');

        $middleware->handle($request, function ($req) {
            return response('OK');
        });
    }
}
