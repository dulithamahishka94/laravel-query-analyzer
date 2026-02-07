<?php

namespace Coderflex\QueryLens\Tests\Feature;

use Coderflex\QueryLens\Models\Alert;
use Coderflex\QueryLens\Models\AlertLog;
use Coderflex\QueryLens\QueryLensServiceProvider;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;

class AlertCooldownTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [QueryLensServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        Config::set('query-lens.storage.driver', 'database');
        
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    public function test_alert_cooldown_prevents_duplicate_triggers()
    {
        // 1. Create an alert with 5 minute cooldown
        $alert = Alert::create([
            'name' => 'Test Cooldown',
            'type' => 'slow_query',
            'conditions' => ['threshold' => 1.0],
            'channels' => ['log'],
            'enabled' => true,
            'cooldown_minutes' => 5,
        ]);

        $storage = app(\Coderflex\QueryLens\Contracts\QueryStorage::class);
        
        $queryData = [
            'sql' => 'SELECT SLEEP(2)',
            'time' => 2.0,
            'connection' => 'mysql',
            'analysis' => [
                'type' => 'SELECT',
                'performance' => ['rating' => 'slow', 'is_slow' => true],
            ]
        ];

        // 2. Trigger first time
        $storage->store($queryData);
        
        $this->assertEquals(1, $alert->fresh()->trigger_count);
        
        // 3. Trigger immediately again (should be blocked by cooldown)
        $storage->store($queryData);
        
        $this->assertEquals(1, $alert->fresh()->trigger_count, 'Alert triggered again despite cooldown');
        
        // 4. Travel forward in time > 5 minutes
        $this->travel(6)->minutes();
        
        // 5. Trigger again (should succeed)
        $storage->store($queryData);
        
        $this->assertEquals(2, $alert->fresh()->trigger_count, 'Alert failed to trigger after cooldown');
    }
}
