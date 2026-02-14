<?php

namespace GladeHQ\QueryLens\Tests;

use Orchestra\Testbench\TestCase;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\QueryAnalyzer;
use GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware;
use Illuminate\Http\Request;
use Mockery;

class MiddlewareTest extends TestCase
{
    public function test_middleware_sets_request_id()
    {
        $analyzerMock = Mockery::mock(QueryAnalyzer::class);
        $analyzerMock->shouldReceive('getRequestId')
            ->once()
            ->andReturnNull();
        $analyzerMock->shouldReceive('setRequestId')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturnNull();

        $storageMock = Mockery::mock(QueryStorage::class);

        $middleware = new AnalyzeQueryMiddleware($analyzerMock, $storageMock);

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }
}
