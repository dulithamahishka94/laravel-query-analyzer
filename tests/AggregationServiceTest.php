<?php

namespace Coderflex\QueryLens\Tests;

use Carbon\Carbon;
use Coderflex\QueryLens\Services\AggregationService;
use Coderflex\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

class AggregationServiceTest extends TestCase
{
    protected InMemoryQueryStorage $storage;

    protected function getPackageProviders($app): array
    {
        return [\Coderflex\QueryLens\QueryLensServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new InMemoryQueryStorage();
    }

    public function test_percentile_calculation_with_empty_array(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'percentile');
        $method->setAccessible(true);

        $result = $method->invoke($service, collect([]), 50);
        $this->assertEquals(0, $result);
    }

    public function test_percentile_calculation_with_single_value(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'percentile');
        $method->setAccessible(true);

        $result = $method->invoke($service, collect([0.5]), 50);
        $this->assertEquals(0.5, $result);
    }

    public function test_percentile_calculation_p50(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'percentile');
        $method->setAccessible(true);

        // For 10 values, P50 should be around the 5th value
        $values = collect([0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0]);
        $result = $method->invoke($service, $values, 50);

        $this->assertGreaterThanOrEqual(0.4, $result);
        $this->assertLessThanOrEqual(0.6, $result);
    }

    public function test_percentile_calculation_p95(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'percentile');
        $method->setAccessible(true);

        // For 100 values, P95 should be around the 95th value
        $values = collect(range(1, 100));
        $result = $method->invoke($service, $values, 95);

        $this->assertGreaterThanOrEqual(94, $result);
        $this->assertLessThanOrEqual(96, $result);
    }

    public function test_percentile_calculation_p99(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'percentile');
        $method->setAccessible(true);

        // For 100 values, P99 should be around the 99th value
        $values = collect(range(1, 100));
        $result = $method->invoke($service, $values, 99);

        $this->assertGreaterThanOrEqual(98, $result);
        $this->assertLessThanOrEqual(100, $result);
    }

    public function test_calculate_change_with_zero_previous(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'calculateChange');
        $method->setAccessible(true);

        $result = $method->invoke($service, 100.0, 0.0);

        $this->assertEquals(0, $result['value']);
        $this->assertEquals('neutral', $result['direction']);
    }

    public function test_calculate_change_with_increase(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'calculateChange');
        $method->setAccessible(true);

        $result = $method->invoke($service, 150.0, 100.0);

        $this->assertEquals(50.0, $result['value']);
        $this->assertEquals('up', $result['direction']);
    }

    public function test_calculate_change_with_decrease(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'calculateChange');
        $method->setAccessible(true);

        $result = $method->invoke($service, 50.0, 100.0);

        $this->assertEquals(50.0, $result['value']);
        $this->assertEquals('down', $result['direction']);
    }

    public function test_calculate_change_with_no_change(): void
    {
        $service = new AggregationService();
        $method = new \ReflectionMethod($service, 'calculateChange');
        $method->setAccessible(true);

        $result = $method->invoke($service, 100.0, 100.0);

        $this->assertEquals(0, $result['value']);
        $this->assertEquals('neutral', $result['direction']);
    }
}
