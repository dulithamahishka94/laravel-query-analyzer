<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Contracts\QueryStorage;
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
        $analyzerMock = Mockery::mock(QueryAnalyzer::class);
        $analyzerMock->shouldReceive('getRequestId')->andReturn(null);
        $analyzerMock->shouldReceive('setRequestId');
        $analyzerMock->shouldReceive('disableRecording')->once();

        $storageMock = Mockery::mock(QueryStorage::class);

        $middleware = new AnalyzeQueryMiddleware($analyzerMock, $storageMock);

        $request = Request::create('/query-lens/api/requests', 'GET');

        $middleware->handle($request, function ($req) {
            return response('OK');
        });
    }

    /** @test */
    public function it_enables_analyzer_for_normal_routes()
    {
        $analyzerMock = Mockery::mock(QueryAnalyzer::class);
        $analyzerMock->shouldReceive('getRequestId')->andReturn(null);
        $analyzerMock->shouldReceive('setRequestId');
        $analyzerMock->shouldReceive('disableRecording')->never();

        $storageMock = Mockery::mock(QueryStorage::class);

        $middleware = new AnalyzeQueryMiddleware($analyzerMock, $storageMock);

        $request = Request::create('/users', 'GET');

        $middleware->handle($request, function ($req) {
            return response('OK');
        });
    }
}
