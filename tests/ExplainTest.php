<?php

namespace Laravel\QueryAnalyzer\Tests;

use Illuminate\Support\Facades\DB;
use Laravel\QueryAnalyzer\QueryAnalyzer;
use Orchestra\Testbench\TestCase;

class ExplainTest extends TestCase
{
    /** @test */
    public function it_can_explain_a_query()
    {
        // Mock DB connection for 'sqlite' (default in testbench)
        // Since sqlite uses 'EXPLAIN QUERY PLAN...', we will just mock the select method
        // to verify our controller logic handles the call.

        // Note: In a real integration test we would hit the route. 
        // Here we will simulate the behavior by mocking DB facade.
        
        DB::shouldReceive('connection')->andReturnSelf();
        
        // Expect fallback behavior (standard EXPLAIN) if ANALYZE fails or is not supported
        // We will simulate the fallback path directly
        DB::shouldReceive('select')
          ->once()
          ->with('EXPLAIN ANALYZE SELECT * FROM users', [])
          ->andThrow(new \Exception('Syntax error')); // Simulate MySQL 5.7 error

        DB::shouldReceive('select')
          ->once()
          ->with('EXPLAIN SELECT * FROM users', [])
          ->andReturn([['id' => 1, 'select_type' => 'SIMPLE']]);

        // Instantiate logic manually since we are not setting up full HTTP test here for speed
        $controller = new \Laravel\QueryAnalyzer\Http\Controllers\QueryAnalyzerController(
            new QueryAnalyzer([], new \Laravel\QueryAnalyzer\Tests\Fakes\InMemoryQueryStorage())
        );

        $request = new \Illuminate\Http\Request();
        $request->merge([
            'sql' => 'SELECT * FROM users', 
            'bindings' => [], 
            'connection' => 'sqlite'
        ]);

        $response = $controller->explain($request);
        $data = $response->getData(true);

        $this->assertFalse($data['supports_analyze']);
        $this->assertEquals(1, $data['standard'][0]['id']);
        $this->assertIsString($data['summary']);
    }
}
