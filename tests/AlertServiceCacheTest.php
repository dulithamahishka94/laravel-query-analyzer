<?php

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Models\Alert;
use GladeHQ\QueryLens\Models\AnalyzedQuery;
use GladeHQ\QueryLens\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase;

class AlertServiceCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('query-lens.storage.connection', 'testing');
        $app['config']->set('query-lens.alerts', []);
    }

    public function test_alerts_are_loaded_once_and_cached(): void
    {
        Alert::create([
            'name' => 'Slow Query',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 100.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        $service = new AlertService();

        $query1 = new AnalyzedQuery();
        $query1->time = 0.01;
        $query1->analysis = ['issues' => []];

        $query2 = new AnalyzedQuery();
        $query2->time = 0.02;
        $query2->analysis = ['issues' => []];

        // Call checkAlerts multiple times
        $service->checkAlerts($query1);
        $service->checkAlerts($query2);

        // Access the cached property via reflection to verify it's populated
        $reflection = new \ReflectionProperty($service, 'cachedAlerts');
        $reflection->setAccessible(true);
        $cached = $reflection->getValue($service);

        $this->assertNotNull($cached);
        $this->assertCount(1, $cached);
    }

    public function test_cache_is_used_on_subsequent_calls(): void
    {
        // Create an enabled alert
        Alert::create([
            'name' => 'Test Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 100.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        $service = new AlertService();

        $query = new AnalyzedQuery();
        $query->time = 0.01;
        $query->analysis = ['issues' => []];

        // First call loads from DB
        $service->checkAlerts($query);

        // Now add another alert to DB -- it should NOT appear because cache is in use
        Alert::create([
            'name' => 'New Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 50.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        $reflection = new \ReflectionProperty($service, 'cachedAlerts');
        $reflection->setAccessible(true);
        $cached = $reflection->getValue($service);

        // Cache should still have only 1 alert (the original)
        $this->assertCount(1, $cached);

        // But DB has 2
        $this->assertEquals(2, Alert::enabled()->count());
    }

    public function test_clear_cache_forces_reload(): void
    {
        Alert::create([
            'name' => 'First Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 100.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        $service = new AlertService();

        $query = new AnalyzedQuery();
        $query->time = 0.01;
        $query->analysis = ['issues' => []];

        // Load cache
        $service->checkAlerts($query);

        // Add second alert
        Alert::create([
            'name' => 'Second Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 50.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        // Clear cache
        $service->clearAlertCache();

        // Next call should reload from DB
        $service->checkAlerts($query);

        $reflection = new \ReflectionProperty($service, 'cachedAlerts');
        $reflection->setAccessible(true);
        $cached = $reflection->getValue($service);

        $this->assertCount(2, $cached);
    }

    public function test_no_enabled_alerts_returns_empty_collection(): void
    {
        $service = new AlertService();

        $query = new AnalyzedQuery();
        $query->time = 0.01;
        $query->analysis = ['issues' => []];

        // No alerts in DB
        $service->checkAlerts($query);

        $reflection = new \ReflectionProperty($service, 'cachedAlerts');
        $reflection->setAccessible(true);
        $cached = $reflection->getValue($service);

        $this->assertNotNull($cached);
        $this->assertCount(0, $cached);
    }

    public function test_disabled_alerts_are_not_cached(): void
    {
        Alert::create([
            'name' => 'Disabled Alert',
            'type' => 'slow_query',
            'enabled' => false,
            'conditions' => ['threshold' => 1.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        Alert::create([
            'name' => 'Enabled Alert',
            'type' => 'slow_query',
            'enabled' => true,
            'conditions' => ['threshold' => 100.0],
            'channels' => ['log'],
            'cooldown_minutes' => 5,
        ]);

        $service = new AlertService();

        $query = new AnalyzedQuery();
        $query->time = 0.01;
        $query->analysis = ['issues' => []];

        $service->checkAlerts($query);

        $reflection = new \ReflectionProperty($service, 'cachedAlerts');
        $reflection->setAccessible(true);
        $cached = $reflection->getValue($service);

        // Only the enabled alert should be cached
        $this->assertCount(1, $cached);
        $this->assertEquals('Enabled Alert', $cached->first()->name);
    }

    public function test_multiple_alerts_cached_correctly(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            Alert::create([
                'name' => "Alert {$i}",
                'type' => 'slow_query',
                'enabled' => true,
                'conditions' => ['threshold' => 100.0],
                'channels' => ['log'],
                'cooldown_minutes' => 5,
            ]);
        }

        $service = new AlertService();

        $query = new AnalyzedQuery();
        $query->time = 0.01;
        $query->analysis = ['issues' => []];

        $service->checkAlerts($query);

        $reflection = new \ReflectionProperty($service, 'cachedAlerts');
        $reflection->setAccessible(true);
        $cached = $reflection->getValue($service);

        $this->assertCount(5, $cached);
    }
}
