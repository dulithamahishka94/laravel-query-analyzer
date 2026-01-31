<?php

namespace Coderflex\QueryLens\Tests;

use Orchestra\Testbench\TestCase;
use Coderflex\QueryLens\QueryAnalyzer;
use Coderflex\QueryLens\Http\Middleware\AnalyzeQueryMiddleware;
use Illuminate\Http\Request;
use Mockery;

class MiddlewareTest extends TestCase
{
    public function test_middleware_sets_request_id()
    {
        $analyzerDescriptor = Mockery::mock(QueryAnalyzer::class);
        $analyzerDescriptor->shouldReceive('getRequestId')
            ->once()
            ->andReturnNull();
        $analyzerDescriptor->shouldReceive('setRequestId')
            ->once()
            ->with(Mockery::type('string'))
            ->andReturnNull();

        $middleware = new AnalyzeQueryMiddleware($analyzerDescriptor);

        $request = Request::create('/test', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }
}
