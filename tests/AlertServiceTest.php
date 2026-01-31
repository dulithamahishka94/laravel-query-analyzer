<?php

namespace Coderflex\QueryLens\Tests;

use Coderflex\QueryLens\Models\Alert;
use Coderflex\QueryLens\Models\AnalyzedQuery;
use Coderflex\QueryLens\Services\AlertService;
use Orchestra\Testbench\TestCase;

class AlertServiceTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Coderflex\QueryLens\QueryLensServiceProvider::class];
    }

    public function test_check_slow_query_alert_triggers_when_exceeds_threshold(): void
    {
        $alert = new Alert();
        $alert->type = 'slow_query';
        $alert->conditions = ['threshold' => 1.0];

        $query = new AnalyzedQuery();
        $query->time = 1.5;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkSlowQueryAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, $alert, $query));
    }

    public function test_check_slow_query_alert_does_not_trigger_below_threshold(): void
    {
        $alert = new Alert();
        $alert->type = 'slow_query';
        $alert->conditions = ['threshold' => 1.0];

        $query = new AnalyzedQuery();
        $query->time = 0.5;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkSlowQueryAlert');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, $alert, $query));
    }

    public function test_check_n_plus_one_alert_triggers_when_detected(): void
    {
        $alert = new Alert();
        $alert->type = 'n_plus_one';
        $alert->conditions = ['min_count' => 5];

        $query = new AnalyzedQuery();
        $query->is_n_plus_one = true;
        $query->n_plus_one_count = 10;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkNPlusOneAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, $alert, $query));
    }

    public function test_check_n_plus_one_alert_does_not_trigger_when_not_n_plus_one(): void
    {
        $alert = new Alert();
        $alert->type = 'n_plus_one';
        $alert->conditions = ['min_count' => 5];

        $query = new AnalyzedQuery();
        $query->is_n_plus_one = false;
        $query->n_plus_one_count = 0;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkNPlusOneAlert');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, $alert, $query));
    }

    public function test_check_n_plus_one_alert_does_not_trigger_below_min_count(): void
    {
        $alert = new Alert();
        $alert->type = 'n_plus_one';
        $alert->conditions = ['min_count' => 10];

        $query = new AnalyzedQuery();
        $query->is_n_plus_one = true;
        $query->n_plus_one_count = 5;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkNPlusOneAlert');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, $alert, $query));
    }

    public function test_check_threshold_alert_with_time_metric(): void
    {
        $alert = new Alert();
        $alert->type = 'threshold';
        $alert->conditions = ['metric' => 'time', 'threshold' => 0.5, 'operator' => '>='];

        $query = new AnalyzedQuery();
        $query->time = 0.8;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkThresholdAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, $alert, $query));
    }

    public function test_check_threshold_alert_with_complexity_metric(): void
    {
        $alert = new Alert();
        $alert->type = 'threshold';
        $alert->conditions = ['metric' => 'complexity', 'threshold' => 10, 'operator' => '>='];

        $query = new AnalyzedQuery();
        $query->complexity_score = 15;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkThresholdAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, $alert, $query));
    }

    public function test_check_error_rate_alert_triggers_when_has_issues(): void
    {
        $alert = new Alert();
        $alert->type = 'error_rate';
        $alert->conditions = ['min_issues' => 1];

        $query = new AnalyzedQuery();
        $query->analysis = ['issues' => [['type' => 'n+1', 'message' => 'N+1 detected']]];

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkErrorRateAlert');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, $alert, $query));
    }

    public function test_check_error_rate_alert_does_not_trigger_without_issues(): void
    {
        $alert = new Alert();
        $alert->type = 'error_rate';
        $alert->conditions = ['min_issues' => 1];

        $query = new AnalyzedQuery();
        $query->analysis = ['issues' => []];

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'checkErrorRateAlert');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, $alert, $query));
    }

    public function test_build_alert_message_for_slow_query(): void
    {
        $alert = new Alert();
        $alert->type = 'slow_query';
        $alert->conditions = ['threshold' => 1.0];

        $query = new AnalyzedQuery();
        $query->time = 2.5;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'buildAlertMessage');
        $method->setAccessible(true);

        $message = $method->invoke($service, $alert, $query);

        $this->assertStringContainsString('2.5000s', $message);
        $this->assertStringContainsString('1.0000s', $message);
        $this->assertStringContainsString('Slow query detected', $message);
    }

    public function test_build_alert_message_for_n_plus_one(): void
    {
        $alert = new Alert();
        $alert->type = 'n_plus_one';

        $query = new AnalyzedQuery();
        $query->n_plus_one_count = 15;

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'buildAlertMessage');
        $method->setAccessible(true);

        $message = $method->invoke($service, $alert, $query);

        $this->assertStringContainsString('N+1', $message);
        $this->assertStringContainsString('15', $message);
    }

    public function test_build_alert_context_includes_query_details(): void
    {
        $alert = new Alert();
        $alert->type = 'slow_query';

        $query = new AnalyzedQuery();
        $query->sql = 'SELECT * FROM users WHERE id = 1';
        $query->time = 1.5;
        $query->connection = 'mysql';
        $query->type = 'SELECT';
        $query->analysis = ['issues' => []];
        $query->origin = ['file' => 'test.php', 'line' => 42];
        $query->request_id = 'request-123';

        $service = new AlertService();
        $method = new \ReflectionMethod($service, 'buildAlertContext');
        $method->setAccessible(true);

        $context = $method->invoke($service, $alert, $query);

        $this->assertEquals('SELECT * FROM users WHERE id = 1', $context['sql']);
        $this->assertEquals(1.5, $context['time']);
        $this->assertEquals('mysql', $context['connection']);
        $this->assertEquals('SELECT', $context['type']);
        $this->assertEquals('request-123', $context['request_id']);
        $this->assertArrayHasKey('origin', $context);
        $this->assertArrayHasKey('issues', $context);
    }
}
