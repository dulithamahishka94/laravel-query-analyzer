<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Concerns;

if (class_exists(\Filament\Widgets\ChartWidget::class)) {
    abstract class BaseChartWidgetResolver extends \Filament\Widgets\ChartWidget
    {
    }
} else {
    abstract class BaseChartWidgetResolver
    {
        protected static int $sort = 0;
        protected static string $pollingInterval = '30s';
        protected static ?string $heading = null;

        public static function getSort(): int
        {
            return static::$sort;
        }

        public static function getPollingInterval(): string
        {
            return static::$pollingInterval;
        }

        abstract protected function getData(): array;

        abstract protected function getType(): string;

        public static function getHeading(): ?string
        {
            return static::$heading;
        }

        public function getChartData(): array
        {
            return $this->getData();
        }

        public function getChartType(): string
        {
            return $this->getType();
        }
    }
}
