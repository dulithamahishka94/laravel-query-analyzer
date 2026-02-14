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
 * Implements Filament\Contracts\Plugin when Filament is installed (via duck typing).
 * Filament's plugin resolution checks for getId(), register(), and boot() methods.
 * When Filament is absent, this class is still loadable and configurable but
 * register/boot become no-ops since there is no panel to attach to.
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
    protected ?string $navigationIcon = null;
    protected ?int $navigationSort = null;

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

    public function navigationIcon(?string $icon): static
    {
        $this->navigationIcon = $icon;
        return $this;
    }

    public function navigationSort(?int $sort): static
    {
        $this->navigationSort = $sort;
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

    public function getNavigationIcon(): ?string
    {
        return $this->navigationIcon;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    /**
     * Get the list of page classes that should be registered.
     *
     * @return array<class-string>
     */
    public function getPages(): array
    {
        $pages = [];

        if ($this->enableDashboard) {
            $pages[] = QueryLensDashboard::class;
        }
        if ($this->enableAlerts) {
            $pages[] = QueryLensAlerts::class;
        }
        if ($this->enableTrends) {
            $pages[] = QueryLensTrends::class;
        }

        return $pages;
    }

    /**
     * Get the list of widget classes that should be registered.
     *
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        $widgets = [];

        if ($this->enableDashboard) {
            $widgets[] = QueryLensStatsWidget::class;
        }
        if ($this->enableTrends) {
            $widgets[] = QueryPerformanceChart::class;
            $widgets[] = QueryVolumeChart::class;
        }

        return $widgets;
    }

    /**
     * Register plugin pages and widgets with the Filament panel.
     *
     * @param mixed $panel Filament\Panel instance (typed as mixed for non-Filament safety)
     */
    public function register($panel): void
    {
        $panel->pages($this->getPages())->widgets($this->getWidgets());
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
