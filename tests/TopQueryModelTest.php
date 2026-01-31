<?php

namespace Coderflex\QueryLens\Tests;

use Carbon\Carbon;
use Coderflex\QueryLens\Models\TopQuery;
use Orchestra\Testbench\TestCase;

class TopQueryModelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\Coderflex\QueryLens\QueryLensServiceProvider::class];
    }

    public function test_get_period_start_for_hour(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 10, 30, 45));

        $periodStart = TopQuery::getPeriodStart('hour');

        $this->assertEquals('2024-01-15 10:00:00', $periodStart->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_get_period_start_for_day(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 15, 10, 30, 45));

        $periodStart = TopQuery::getPeriodStart('day');

        $this->assertEquals('2024-01-15 00:00:00', $periodStart->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_get_period_start_for_week(): void
    {
        // Set to Wednesday, January 17, 2024
        Carbon::setTestNow(Carbon::create(2024, 1, 17, 10, 30, 45));

        $periodStart = TopQuery::getPeriodStart('week');

        // Week starts on Monday (January 15, 2024)
        $this->assertEquals('Monday', $periodStart->format('l'));

        Carbon::setTestNow();
    }

    public function test_get_ranking_types_returns_all_types(): void
    {
        $types = TopQuery::getRankingTypes();

        $this->assertArrayHasKey('slowest', $types);
        $this->assertArrayHasKey('most_frequent', $types);
        $this->assertArrayHasKey('most_issues', $types);
        $this->assertCount(3, $types);
    }

    public function test_get_available_periods_returns_all_periods(): void
    {
        $periods = TopQuery::getAvailablePeriods();

        $this->assertArrayHasKey('hour', $periods);
        $this->assertArrayHasKey('day', $periods);
        $this->assertArrayHasKey('week', $periods);
        $this->assertCount(3, $periods);
    }
}
