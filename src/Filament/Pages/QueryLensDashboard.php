<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Pages;

use GladeHQ\QueryLens\Filament\Concerns\BasePageResolver;
use GladeHQ\QueryLens\Filament\QueryLensDataService;
use GladeHQ\QueryLens\Filament\Widgets\QueryLensStatsWidget;

/**
 * Dashboard page for the Filament plugin.
 *
 * When Filament is installed, this extends Filament\Pages\Page with HasTable
 * integration, providing real Table Builder columns, filters, and actions.
 * When Filament is absent, it serves as a standalone data assembler with
 * static definition arrays that document the intended Filament schema.
 */
class QueryLensDashboard extends BasePageResolver
{
    protected static string $navigationIcon = 'heroicon-o-circle-stack';
    protected static string $navigationGroup = 'Query Lens';
    protected static ?string $title = 'Query Dashboard';
    protected static ?string $slug = 'query-lens';
    protected static int $navigationSort = 1;
    protected static string $view = 'query-lens::filament.dashboard';
    protected static ?string $navigationLabel = 'Query Dashboard';
    protected static string $pollingInterval = '10s';

    /**
     * Header widgets shown above the page content.
     *
     * When Filament is installed, these are rendered as real Filament widgets.
     * The getHeaderWidgets() method is the Filament convention; we also expose
     * getHeaderWidgetDefinitions() for testability without Filament.
     */
    public static function getHeaderWidgetDefinitions(): array
    {
        return [
            QueryLensStatsWidget::class,
        ];
    }

    /**
     * Define table columns for the query list.
     *
     * When Filament is installed, the table() method on this page builds real
     * Filament\Tables\Columns objects. These definitions serve as the canonical
     * schema and are used by both the Filament Table Builder and non-Filament views.
     */
    public static function getTableColumnDefinitions(): array
    {
        return [
            [
                'name' => 'sql',
                'label' => 'SQL',
                'searchable' => true,
                'limit' => 80,
                'type' => 'text',
            ],
            [
                'name' => 'type',
                'label' => 'Type',
                'type' => 'badge',
                'filterable' => true,
            ],
            [
                'name' => 'duration',
                'label' => 'Duration (ms)',
                'sortable' => true,
                'type' => 'numeric',
            ],
            [
                'name' => 'is_slow',
                'label' => 'Is Slow',
                'type' => 'boolean_icon',
            ],
            [
                'name' => 'origin',
                'label' => 'Origin',
                'type' => 'text',
                'description' => 'file:line',
            ],
            [
                'name' => 'created_at',
                'label' => 'Created At',
                'sortable' => true,
                'type' => 'datetime',
            ],
        ];
    }

    /**
     * Define table filters.
     *
     * Maps to Filament SelectFilter, TernaryFilter, and custom date range filter.
     */
    public static function getTableFilterDefinitions(): array
    {
        return [
            [
                'name' => 'type',
                'label' => 'Query Type',
                'type' => 'select',
                'options' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
            ],
            [
                'name' => 'is_slow',
                'label' => 'Slow Queries',
                'type' => 'ternary',
            ],
            [
                'name' => 'date_range',
                'label' => 'Date Range',
                'type' => 'date_range',
            ],
        ];
    }

    /**
     * Define table actions available per row.
     *
     * When Filament is installed, these map to Filament Action objects with
     * modal rendering, icons, and event handling.
     */
    public static function getTableActionDefinitions(): array
    {
        return [
            [
                'name' => 'view',
                'label' => 'View Details',
                'icon' => 'heroicon-o-eye',
                'type' => 'modal',
                'description' => 'Shows full query detail and recommendations',
            ],
            [
                'name' => 'explain',
                'label' => 'Explain',
                'icon' => 'heroicon-o-document-magnifying-glass',
                'type' => 'modal',
                'description' => 'Runs EXPLAIN and shows formatted output',
            ],
        ];
    }

    /**
     * Define header actions for the page.
     *
     * When Filament is installed, these render as Action buttons in the page header.
     */
    public static function getHeaderActionDefinitions(): array
    {
        return [
            [
                'name' => 'export',
                'label' => 'Export',
                'icon' => 'heroicon-o-arrow-down-tray',
                'type' => 'action',
            ],
        ];
    }

    /**
     * Get polling interval for real-time updates.
     */
    public static function getPollingInterval(): string
    {
        return static::$pollingInterval;
    }

    /**
     * Assemble view data from the data service.
     *
     * Used by both Filament (via mount/getViewData) and standalone views.
     */
    public function getViewData(QueryLensDataService $dataService, array $filters = []): array
    {
        $stats = $dataService->getStatsForWidget();

        $queryFilters = array_filter([
            'type' => $filters['type'] ?? null,
            'is_slow' => isset($filters['is_slow']) ? (bool) $filters['is_slow'] : null,
            'sql_like' => $filters['sql_like'] ?? null,
            'page' => $filters['page'] ?? 1,
            'per_page' => $filters['per_page'] ?? 15,
        ], fn ($v) => $v !== null);

        $result = $dataService->getRecentQueries($queryFilters);

        return [
            'stats' => $stats,
            'recentQueries' => $result['data'] ?? [],
            'total' => $result['total'] ?? 0,
            'page' => $result['page'] ?? 1,
        ];
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel ?? 'Query Dashboard';
    }
}
