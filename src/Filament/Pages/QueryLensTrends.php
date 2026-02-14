<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Pages;

use GladeHQ\QueryLens\Filament\Concerns\BasePageResolver;
use GladeHQ\QueryLens\Filament\QueryLensDataService;
use GladeHQ\QueryLens\Filament\Widgets\QueryPerformanceChart;
use GladeHQ\QueryLens\Filament\Widgets\QueryVolumeChart;

/**
 * Trends page for the Filament plugin.
 *
 * When Filament is installed, this extends Filament\Pages\Page and provides
 * Chart Widgets and a Table Builder for top queries.
 * When Filament is absent, it serves as a standalone data assembler.
 */
class QueryLensTrends extends BasePageResolver
{
    protected static string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $navigationGroup = 'Query Lens';
    protected static ?string $title = 'Query Trends';
    protected static ?string $slug = 'query-lens/trends';
    protected static int $navigationSort = 2;
    protected static string $view = 'query-lens::filament.trends';
    protected static ?string $navigationLabel = 'Query Trends';

    /**
     * Header widgets for the trends page (chart widgets).
     */
    public static function getHeaderWidgetDefinitions(): array
    {
        return [
            QueryPerformanceChart::class,
            QueryVolumeChart::class,
        ];
    }

    /**
     * Define table columns for the top queries table.
     */
    public static function getTableColumnDefinitions(): array
    {
        return [
            [
                'name' => 'sql_sample',
                'label' => 'Normalized SQL',
                'type' => 'text',
                'limit' => 80,
                'searchable' => true,
            ],
            [
                'name' => 'count',
                'label' => 'Count',
                'type' => 'numeric',
                'sortable' => true,
            ],
            [
                'name' => 'avg_time',
                'label' => 'Avg Time (ms)',
                'type' => 'numeric',
                'sortable' => true,
            ],
            [
                'name' => 'total_time',
                'label' => 'Total Time (ms)',
                'type' => 'numeric',
                'sortable' => true,
            ],
            [
                'name' => 'impact_score',
                'label' => 'Impact Score',
                'type' => 'numeric',
                'sortable' => true,
                'description' => 'count * avg_time',
            ],
        ];
    }

    /**
     * Define the period selector options.
     */
    public static function getPeriodOptions(): array
    {
        return [
            '24h' => 'Last 24 Hours',
            '7d' => 'Last 7 Days',
            '30d' => 'Last 30 Days',
        ];
    }

    /**
     * Assemble view data from the data service.
     */
    public function getViewData(
        QueryLensDataService $dataService,
        string $granularity = 'hour',
        string $topQueryType = 'slowest',
    ): array {
        return [
            'trendsData' => $dataService->getTrendsData($granularity),
            'topQueries' => $dataService->getTopQueries($topQueryType),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? 'Query Trends';
    }
}
