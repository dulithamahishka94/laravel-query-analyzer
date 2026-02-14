<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Concerns;

if (class_exists(\Filament\Widgets\StatsOverviewWidget::class)) {
    abstract class BaseStatsWidgetResolver extends \Filament\Widgets\StatsOverviewWidget
    {
    }
} else {
    abstract class BaseStatsWidgetResolver
    {
        protected static int $sort = 0;
        protected static string $pollingInterval = '30s';

        public static function getSort(): int
        {
            return static::$sort;
        }

        public static function getPollingInterval(): string
        {
            return static::$pollingInterval;
        }

        /** Stub for getStats() -- real implementation returns Filament Stat objects. */
        public function getStats(): array
        {
            return [];
        }
    }
}
