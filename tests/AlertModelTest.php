<?php

namespace Coderflex\QueryLens\Tests;

use Coderflex\QueryLens\Models\Alert;
use Orchestra\Testbench\TestCase;

class AlertModelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Coderflex\QueryLens\QueryLensServiceProvider::class];
    }

    public function test_can_trigger_returns_true_when_never_triggered(): void
    {
        $alert = new Alert();
        $alert->enabled = true;
        $alert->last_triggered_at = null;
        $alert->cooldown_minutes = 5;

        $this->assertTrue($alert->canTrigger());
    }

    public function test_can_trigger_returns_false_when_disabled(): void
    {
        $alert = new Alert();
        $alert->enabled = false;
        $alert->last_triggered_at = null;
        $alert->cooldown_minutes = 5;

        $this->assertFalse($alert->canTrigger());
    }

    public function test_can_trigger_respects_cooldown(): void
    {
        $alert = new Alert();
        $alert->enabled = true;
        $alert->cooldown_minutes = 5;

        // Triggered 10 minutes ago - should be able to trigger
        $alert->last_triggered_at = now()->subMinutes(10);
        $this->assertTrue($alert->canTrigger());

        // Triggered 2 minutes ago - cooldown not passed
        $alert->last_triggered_at = now()->subMinutes(2);
        $this->assertFalse($alert->canTrigger());
    }

    public function test_get_condition_returns_value_or_default(): void
    {
        $alert = new Alert();
        $alert->conditions = ['threshold' => 1.5, 'operator' => '>='];

        $this->assertEquals(1.5, $alert->getCondition('threshold'));
        $this->assertEquals('>=', $alert->getCondition('operator'));
        $this->assertNull($alert->getCondition('nonexistent'));
        $this->assertEquals('default', $alert->getCondition('nonexistent', 'default'));
    }

    public function test_matches_conditions_with_greater_than(): void
    {
        $alert = new Alert();
        $alert->conditions = ['time' => 1.0, 'operator' => '>'];

        $this->assertTrue($alert->matchesConditions(['time' => 1.5]));
        $this->assertFalse($alert->matchesConditions(['time' => 1.0]));
        $this->assertFalse($alert->matchesConditions(['time' => 0.5]));
    }

    public function test_matches_conditions_with_greater_than_or_equal(): void
    {
        $alert = new Alert();
        $alert->conditions = ['time' => 1.0, 'operator' => '>='];

        $this->assertTrue($alert->matchesConditions(['time' => 1.5]));
        $this->assertTrue($alert->matchesConditions(['time' => 1.0]));
        $this->assertFalse($alert->matchesConditions(['time' => 0.5]));
    }

    public function test_matches_conditions_with_less_than(): void
    {
        $alert = new Alert();
        $alert->conditions = ['time' => 1.0, 'operator' => '<'];

        $this->assertTrue($alert->matchesConditions(['time' => 0.5]));
        $this->assertFalse($alert->matchesConditions(['time' => 1.0]));
        $this->assertFalse($alert->matchesConditions(['time' => 1.5]));
    }

    public function test_matches_conditions_with_equals(): void
    {
        $alert = new Alert();
        $alert->conditions = ['complexity' => 10, 'operator' => '='];

        $this->assertTrue($alert->matchesConditions(['complexity' => 10]));
        $this->assertFalse($alert->matchesConditions(['complexity' => 5]));
    }

    public function test_matches_conditions_with_not_equals(): void
    {
        $alert = new Alert();
        $alert->conditions = ['type' => 'SELECT', 'operator' => '!='];

        $this->assertTrue($alert->matchesConditions(['type' => 'INSERT']));
        $this->assertFalse($alert->matchesConditions(['type' => 'SELECT']));
    }

    public function test_matches_conditions_ignores_missing_context_keys(): void
    {
        $alert = new Alert();
        $alert->conditions = ['time' => 1.0, 'complexity' => 5, 'operator' => '>='];

        // Only time is provided, complexity is missing - should pass based on time
        $this->assertTrue($alert->matchesConditions(['time' => 1.5]));
    }

    public function test_get_available_types_returns_all_types(): void
    {
        $types = Alert::getAvailableTypes();

        $this->assertArrayHasKey('slow_query', $types);
        $this->assertArrayHasKey('threshold', $types);
        $this->assertArrayHasKey('n_plus_one', $types);
        $this->assertArrayHasKey('error_rate', $types);
    }

    public function test_get_available_channels_returns_all_channels(): void
    {
        $channels = Alert::getAvailableChannels();

        $this->assertArrayHasKey('log', $channels);
        $this->assertArrayHasKey('mail', $channels);
        $this->assertArrayHasKey('slack', $channels);
    }
}
