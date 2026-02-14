<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament;

use GladeHQ\QueryLens\Filament\Pages\QueryLensAlerts;
use GladeHQ\QueryLens\Filament\Pages\QueryLensDashboard;
use GladeHQ\QueryLens\Filament\Pages\QueryLensTrends;
use GladeHQ\QueryLens\Filament\Widgets\QueryLensStatsWidget;
use GladeHQ\QueryLens\Filament\Widgets\QueryPerformanceChart;
use GladeHQ\QueryLens\Filament\Widgets\QueryVolumeChart;

/**
 * Filament Panel Plugin for QueryLens.
 *
 * Implements Filament\Contracts\Plugin via duck typing so the class can be loaded
 * safely when Filament is not installed. Filament's plugin resolution checks for
 * getId(), register(), and boot() methods without requiring the interface.
 *
 * Usage:
 *   $panel->plugin(QueryLensPlugin::make())
 */
class QueryLensPlugin
{
    protected bool $enableDashboard = true;
    protected bool $enableAlerts = true;
    protected bool $enableTrends = true;
    protected ?string $navigationGroup = 'Query Lens';

    public static function make(): static
    {
        return new static();
    }

    public function getId(): string
    {
        return 'query-lens';
    }

    public function dashboard(bool $enabled = true): static
    {
        $this->enableDashboard = $enabled;
        return $this;
    }

    public function alerts(bool $enabled = true): static
    {
        $this->enableAlerts = $enabled;
        return $this;
    }

    public function trends(bool $enabled = true): static
    {
        $this->enableTrends = $enabled;
        return $this;
    }

    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;
        return $this;
    }

    public function isDashboardEnabled(): bool
    {
        return $this->enableDashboard;
    }

    public function isAlertsEnabled(): bool
    {
        return $this->enableAlerts;
    }

    public function isTrendsEnabled(): bool
    {
        return $this->enableTrends;
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    /**
     * Register plugin pages and widgets with the Filament panel.
     *
     * @param mixed $panel Filament\Panel instance (typed as mixed for non-Filament safety)
     */
    public function register($panel): void
    {
        $pages = [];
        $widgets = [];

        if ($this->enableDashboard) {
            $pages[] = QueryLensDashboard::class;
            $widgets[] = QueryLensStatsWidget::class;
        }

        if ($this->enableAlerts) {
            $pages[] = QueryLensAlerts::class;
        }

        if ($this->enableTrends) {
            $pages[] = QueryLensTrends::class;
            $widgets[] = QueryPerformanceChart::class;
            $widgets[] = QueryVolumeChart::class;
        }

        $panel->pages($pages)->widgets($widgets);
    }

    /**
     * Boot-time operations for the plugin.
     *
     * @param mixed $panel Filament\Panel instance
     */
    public function boot($panel): void
    {
        // No boot-time operations needed
    }

    public static function isFilamentInstalled(): bool
    {
        return class_exists(\Filament\Pages\Page::class);
    }
}
