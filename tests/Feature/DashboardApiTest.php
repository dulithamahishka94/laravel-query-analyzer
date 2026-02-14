<?php

namespace GladeHQ\QueryLens\Tests\Feature;

use GladeHQ\QueryLens\QueryLensServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class DashboardApiTest extends TestCase
{
    // use RefreshDatabase; // Orchestra handles this via setup

    protected function getPackageProviders($app)
    {
        return [QueryLensServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('query-lens.storage.driver', 'database');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_overview_stats_endpoint_returns_200()
    {
        $response = $this->get('/query-lens/api/v2/stats/overview');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'today',
                     'yesterday',
                     'comparison'
                 ]);
    }

    public function test_trends_endpoint_returns_200()
    {
        $response = $this->get('/query-lens/api/v2/trends');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'labels',
                     'latency',
                     'throughput',
                     'p95'
                 ]);
    }

    public function test_storage_endpoint_returns_200()
    {
        $response = $this->get('/query-lens/api/v2/storage');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'driver',
                     'supports_persistence'
                 ]);
    }

    public function test_top_queries_endpoint_returns_200()
    {
        $response = $this->get('/query-lens/api/v2/top-queries?type=slowest');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'queries',
                     'type',
                     'period',
                 ]);
    }

    public function test_requests_endpoint_returns_200()
    {
        $response = $this->get('/query-lens/api/requests');
        $response->assertStatus(200);
    }

    public function test_dashboard_page_loads()
    {
        $response = $this->get('/query-lens');
        $response->assertStatus(200)
                 ->assertSee('Query Lens')
                 ->assertSee('Observability Dashboard');
    }
}
