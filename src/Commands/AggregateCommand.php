<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Coderflex\QueryLens\Services\AggregationService;

class AggregateCommand extends Command
{
    protected $signature = 'query-analyzer:aggregate
                            {--period=hour : Period to aggregate (hour, day, week)}
                            {--date= : Specific date/time to aggregate (default: now)}
                            {--backfill= : Number of periods to backfill}';

    protected $description = 'Aggregate query statistics for performance trends and top queries';

    public function handle(AggregationService $service): int
    {
        $period = $this->option('period');
        $date = $this->option('date');
        $backfill = (int) $this->option('backfill');

        $targetDate = $date ? Carbon::parse($date) : now();

        if ($backfill > 0) {
            $this->backfill($service, $period, $targetDate, $backfill);
        } else {
            $this->aggregate($service, $period, $targetDate);
        }

        return self::SUCCESS;
    }

    protected function aggregate(AggregationService $service, string $period, Carbon $date): void
    {
        $this->info("Aggregating {$period} statistics for {$date->toDateTimeString()}...");

        match ($period) {
            'hour' => $service->aggregateHourly($date),
            'day' => $service->aggregateDaily($date),
            'week' => $service->aggregateWeekly($date),
            default => $this->error("Unknown period: {$period}"),
        };

        $this->info('Aggregation complete.');
    }

    protected function backfill(AggregationService $service, string $period, Carbon $startDate, int $count): void
    {
        $this->info("Backfilling {$count} {$period}(s) starting from {$startDate->toDateTimeString()}...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        for ($i = 0; $i < $count; $i++) {
            $date = match ($period) {
                'hour' => $startDate->copy()->subHours($i),
                'day' => $startDate->copy()->subDays($i),
                'week' => $startDate->copy()->subWeeks($i),
                default => $startDate->copy()->subDays($i),
            };

            match ($period) {
                'hour' => $service->aggregateHourly($date),
                'day' => $service->aggregateDaily($date),
                'week' => $service->aggregateWeekly($date),
                default => null,
            };

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Backfill complete.');
    }
}
