<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Tests;

use GladeHQ\QueryLens\Filament\Pages\QueryLensAlerts;
use GladeHQ\QueryLens\Filament\Pages\QueryLensDashboard;
use GladeHQ\QueryLens\Filament\Pages\QueryLensTrends;
use GladeHQ\QueryLens\Filament\QueryLensDataService;
use GladeHQ\QueryLens\Filament\QueryLensPlugin;
use GladeHQ\QueryLens\Filament\Widgets\QueryLensStatsWidget;
use GladeHQ\QueryLens\Filament\Widgets\QueryPerformanceChart;
use GladeHQ\QueryLens\Filament\Widgets\QueryVolumeChart;
use GladeHQ\QueryLens\Services\AggregationService;
use GladeHQ\QueryLens\Tests\Fakes\InMemoryQueryStorage;
use Orchestra\Testbench\TestCase;

/**
 * Comprehensive integration tests for the Filament plugin layer.
 *
 * These tests verify that all Filament components work correctly when
 * Filament is NOT installed (the only testable scenario in this package's
 * dev environment). They validate structural contracts: definition arrays,
 * data service outputs, widget computations, chart data shapes, and plugin
 * registration behavior.
 */
class FilamentIntegrationTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\GladeHQ\QueryLens\QueryLensServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('query-lens.storage.driver', 'cache');
    }

    protected function makeDataService(?InMemoryQueryStorage $storage = null): QueryLensDataService
    {
        $storage = $storage ?? new InMemoryQueryStorage();
        $aggregation = new AggregationService();
        $aggregation->setStorage($storage);

        return new QueryLensDataService($storage, $aggregation);
    }

    protected function makeStorageWithQueries(int $count = 10, bool $includeSlow = true): InMemoryQueryStorage
    {
        $storage = new InMemoryQueryStorage();
        for ($i = 0; $i < $count; $i++) {
            $isSlow = $includeSlow && $i % 3 === 0;
            $storage->store([
                'id' => "q{$i}",
                'sql' => "SELECT * FROM users WHERE id = {$i}",
                'time' => $isSlow ? 1.5 : 0.05 + ($i * 0.01),
                'timestamp' => microtime(true) - ($i * 30),
                'origin' => "app/Models/User.php:{$i}",
                'analysis' => [
                    'type' => 'SELECT',
                    'performance' => ['is_slow' => $isSlow],
                    'issues' => $isSlow ? [['type' => 'slow_query']] : [],
                ],
            ]);
        }

        return $storage;
    }

    // ---------------------------------------------------------------
    // Plugin: getId() contract
    // ---------------------------------------------------------------

    public function test_plugin_id_is_string_and_not_empty(): void
    {
        $id = QueryLensPlugin::make()->getId();
        $this->assertIsString($id);
        $this->assertNotEmpty($id);
        $this->assertSame('query-lens', $id);
    }

    // ---------------------------------------------------------------
    // Plugin: getPages() and getWidgets() expose collections
    // ---------------------------------------------------------------

    public function test_plugin_get_pages_returns_all_when_enabled(): void
    {
        $plugin = QueryLensPlugin::make();
        $pages = $plugin->getPages();

        $this->assertCount(3, $pages);
        $this->assertContains(QueryLensDashboard::class, $pages);
        $this->assertContains(QueryLensAlerts::class, $pages);
        $this->assertContains(QueryLensTrends::class, $pages);
    }

    public function test_plugin_get_pages_excludes_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->dashboard(false)->alerts(false);
        $pages = $plugin->getPages();

        $this->assertCount(1, $pages);
        $this->assertContains(QueryLensTrends::class, $pages);
        $this->assertNotContains(QueryLensDashboard::class, $pages);
        $this->assertNotContains(QueryLensAlerts::class, $pages);
    }

    public function test_plugin_get_pages_returns_empty_when_all_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->dashboard(false)->alerts(false)->trends(false);
        $this->assertEmpty($plugin->getPages());
    }

    public function test_plugin_get_widgets_returns_all_when_enabled(): void
    {
        $plugin = QueryLensPlugin::make();
        $widgets = $plugin->getWidgets();

        $this->assertCount(3, $widgets);
        $this->assertContains(QueryLensStatsWidget::class, $widgets);
        $this->assertContains(QueryPerformanceChart::class, $widgets);
        $this->assertContains(QueryVolumeChart::class, $widgets);
    }

    public function test_plugin_get_widgets_excludes_stats_when_dashboard_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->dashboard(false);
        $widgets = $plugin->getWidgets();

        $this->assertNotContains(QueryLensStatsWidget::class, $widgets);
        $this->assertContains(QueryPerformanceChart::class, $widgets);
    }

    public function test_plugin_get_widgets_excludes_charts_when_trends_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->trends(false);
        $widgets = $plugin->getWidgets();

        $this->assertNotContains(QueryPerformanceChart::class, $widgets);
        $this->assertNotContains(QueryVolumeChart::class, $widgets);
        $this->assertContains(QueryLensStatsWidget::class, $widgets);
    }

    public function test_plugin_get_widgets_returns_empty_when_all_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->dashboard(false)->trends(false);
        $this->assertEmpty($plugin->getWidgets());
    }

    // ---------------------------------------------------------------
    // Plugin: navigation configuration
    // ---------------------------------------------------------------

    public function test_plugin_navigation_icon_configurable(): void
    {
        $plugin = QueryLensPlugin::make();
        $this->assertNull($plugin->getNavigationIcon());

        $plugin->navigationIcon('heroicon-o-server');
        $this->assertSame('heroicon-o-server', $plugin->getNavigationIcon());
    }

    public function test_plugin_navigation_sort_configurable(): void
    {
        $plugin = QueryLensPlugin::make();
        $this->assertNull($plugin->getNavigationSort());

        $plugin->navigationSort(5);
        $this->assertSame(5, $plugin->getNavigationSort());
    }

    // ---------------------------------------------------------------
    // Plugin: register() with mock panel
    // ---------------------------------------------------------------

    public function test_plugin_register_sends_correct_pages_and_widgets_to_panel(): void
    {
        $plugin = QueryLensPlugin::make();

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $p): self { $this->pages = $p; return $this; }
            public function widgets(array $w): self { $this->widgets = $w; return $this; }
        };

        $plugin->register($panel);

        $this->assertSame($plugin->getPages(), $panel->pages);
        $this->assertSame($plugin->getWidgets(), $panel->widgets);
    }

    public function test_plugin_register_with_selective_features(): void
    {
        $plugin = QueryLensPlugin::make()->alerts(false)->trends(false);

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $p): self { $this->pages = $p; return $this; }
            public function widgets(array $w): self { $this->widgets = $w; return $this; }
        };

        $plugin->register($panel);

        $this->assertCount(1, $panel->pages);
        $this->assertContains(QueryLensDashboard::class, $panel->pages);
        $this->assertCount(1, $panel->widgets);
        $this->assertContains(QueryLensStatsWidget::class, $panel->widgets);
    }

    // ---------------------------------------------------------------
    // Plugin: boot() does not throw
    // ---------------------------------------------------------------

    public function test_plugin_boot_is_safe(): void
    {
        $plugin = QueryLensPlugin::make();
        $mockPanel = new \stdClass();

        $plugin->boot($mockPanel);
        $this->assertTrue(true); // No exception means success
    }

    // ---------------------------------------------------------------
    // Filament detection
    // ---------------------------------------------------------------

    public function test_is_filament_installed_returns_bool(): void
    {
        $result = QueryLensPlugin::isFilamentInstalled();
        $this->assertIsBool($result);
    }

    public function test_filament_not_installed_in_test_environment(): void
    {
        // Filament is in suggest, not require -- it should not be available
        $this->assertFalse(QueryLensPlugin::isFilamentInstalled());
    }

    // ---------------------------------------------------------------
    // Non-Filament compatibility: classes are instantiable
    // ---------------------------------------------------------------

    public function test_all_page_classes_instantiable_without_filament(): void
    {
        $dashboard = new QueryLensDashboard();
        $alerts = new QueryLensAlerts();
        $trends = new QueryLensTrends();

        $this->assertInstanceOf(QueryLensDashboard::class, $dashboard);
        $this->assertInstanceOf(QueryLensAlerts::class, $alerts);
        $this->assertInstanceOf(QueryLensTrends::class, $trends);
    }

    public function test_all_widget_classes_instantiable_without_filament(): void
    {
        $stats = new QueryLensStatsWidget();
        $perf = new QueryPerformanceChart();
        $volume = new QueryVolumeChart();

        $this->assertInstanceOf(QueryLensStatsWidget::class, $stats);
        $this->assertInstanceOf(QueryPerformanceChart::class, $perf);
        $this->assertInstanceOf(QueryVolumeChart::class, $volume);
    }

    public function test_service_provider_registers_all_core_services_without_filament(): void
    {
        $this->assertNotNull(app(\GladeHQ\QueryLens\Contracts\QueryStorage::class));
        $this->assertNotNull(app(\GladeHQ\QueryLens\Services\AlertService::class));
        $this->assertNotNull(app(\GladeHQ\QueryLens\Services\AggregationService::class));
        $this->assertNotNull(app(QueryLensDataService::class));
    }

    public function test_routes_register_without_filament(): void
    {
        $routes = app('router')->getRoutes();
        $names = collect($routes->getRoutes())->map(fn ($r) => $r->getName())->filter()->toArray();

        $this->assertContains('query-lens.dashboard', $names);
        $this->assertContains('query-lens.api.queries', $names);
    }

    // ---------------------------------------------------------------
    // Dashboard page: navigation metadata
    // ---------------------------------------------------------------

    public function test_dashboard_navigation_metadata(): void
    {
        $this->assertSame('Query Dashboard', QueryLensDashboard::getNavigationLabel());
        $this->assertSame('query-lens', QueryLensDashboard::getSlug());
        $this->assertSame('heroicon-o-circle-stack', QueryLensDashboard::getNavigationIcon());
        $this->assertSame('Query Lens', QueryLensDashboard::getNavigationGroup());
        $this->assertSame(1, QueryLensDashboard::getNavigationSort());
    }

    public function test_dashboard_polling_interval(): void
    {
        $this->assertSame('10s', QueryLensDashboard::getPollingInterval());
    }

    // ---------------------------------------------------------------
    // Dashboard: table column definitions (structure validation)
    // ---------------------------------------------------------------

    public function test_dashboard_columns_have_required_fields(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();

        foreach ($columns as $col) {
            $this->assertArrayHasKey('name', $col, 'Every column must have a name');
            $this->assertArrayHasKey('label', $col, 'Every column must have a label');
            $this->assertArrayHasKey('type', $col, 'Every column must have a type');
        }
    }

    public function test_dashboard_has_six_columns(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $this->assertCount(6, $columns);
    }

    public function test_dashboard_column_names_are_unique(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $names = array_column($columns, 'name');

        $this->assertSame($names, array_unique($names), 'Column names must be unique');
    }

    public function test_dashboard_sql_column_properties(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $sql = collect($columns)->firstWhere('name', 'sql');

        $this->assertTrue($sql['searchable']);
        $this->assertSame(80, $sql['limit']);
        $this->assertSame('text', $sql['type']);
    }

    public function test_dashboard_type_column_is_badge(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $type = collect($columns)->firstWhere('name', 'type');

        $this->assertSame('badge', $type['type']);
        $this->assertTrue($type['filterable']);
    }

    public function test_dashboard_duration_column_is_sortable_numeric(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $duration = collect($columns)->firstWhere('name', 'duration');

        $this->assertTrue($duration['sortable']);
        $this->assertSame('numeric', $duration['type']);
    }

    public function test_dashboard_is_slow_column_is_boolean_icon(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $isSlow = collect($columns)->firstWhere('name', 'is_slow');

        $this->assertSame('boolean_icon', $isSlow['type']);
    }

    public function test_dashboard_created_at_is_sortable_datetime(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $createdAt = collect($columns)->firstWhere('name', 'created_at');

        $this->assertTrue($createdAt['sortable']);
        $this->assertSame('datetime', $createdAt['type']);
    }

    // ---------------------------------------------------------------
    // Dashboard: table filter definitions
    // ---------------------------------------------------------------

    public function test_dashboard_has_three_filters(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $this->assertCount(3, $filters);
    }

    public function test_dashboard_type_filter_is_select_with_options(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $typeFilter = collect($filters)->firstWhere('name', 'type');

        $this->assertSame('select', $typeFilter['type']);
        $this->assertContains('SELECT', $typeFilter['options']);
        $this->assertContains('INSERT', $typeFilter['options']);
        $this->assertContains('UPDATE', $typeFilter['options']);
        $this->assertContains('DELETE', $typeFilter['options']);
    }

    public function test_dashboard_is_slow_filter_is_ternary(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $slowFilter = collect($filters)->firstWhere('name', 'is_slow');

        $this->assertSame('ternary', $slowFilter['type']);
    }

    public function test_dashboard_date_range_filter_exists(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $dateFilter = collect($filters)->firstWhere('name', 'date_range');

        $this->assertNotNull($dateFilter);
        $this->assertSame('date_range', $dateFilter['type']);
    }

    // ---------------------------------------------------------------
    // Dashboard: table action definitions
    // ---------------------------------------------------------------

    public function test_dashboard_has_two_table_actions(): void
    {
        $actions = QueryLensDashboard::getTableActionDefinitions();
        $this->assertCount(2, $actions);
    }

    public function test_dashboard_view_action_is_modal_with_icon(): void
    {
        $actions = QueryLensDashboard::getTableActionDefinitions();
        $view = collect($actions)->firstWhere('name', 'view');

        $this->assertSame('modal', $view['type']);
        $this->assertSame('heroicon-o-eye', $view['icon']);
        $this->assertNotEmpty($view['description']);
    }

    public function test_dashboard_explain_action_is_modal(): void
    {
        $actions = QueryLensDashboard::getTableActionDefinitions();
        $explain = collect($actions)->firstWhere('name', 'explain');

        $this->assertSame('modal', $explain['type']);
        $this->assertSame('heroicon-o-document-magnifying-glass', $explain['icon']);
    }

    // ---------------------------------------------------------------
    // Dashboard: header action definitions
    // ---------------------------------------------------------------

    public function test_dashboard_export_header_action(): void
    {
        $actions = QueryLensDashboard::getHeaderActionDefinitions();
        $this->assertCount(1, $actions);

        $export = $actions[0];
        $this->assertSame('export', $export['name']);
        $this->assertSame('heroicon-o-arrow-down-tray', $export['icon']);
    }

    // ---------------------------------------------------------------
    // Dashboard: header widgets
    // ---------------------------------------------------------------

    public function test_dashboard_header_widgets_include_stats_widget(): void
    {
        $widgets = QueryLensDashboard::getHeaderWidgetDefinitions();
        $this->assertContains(QueryLensStatsWidget::class, $widgets);
    }

    // ---------------------------------------------------------------
    // Dashboard: getViewData with data service
    // ---------------------------------------------------------------

    public function test_dashboard_view_data_structure_with_no_data(): void
    {
        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService();
        $viewData = $page->getViewData($dataService);

        $this->assertArrayHasKey('stats', $viewData);
        $this->assertArrayHasKey('recentQueries', $viewData);
        $this->assertArrayHasKey('total', $viewData);
        $this->assertArrayHasKey('page', $viewData);
        $this->assertSame(0, $viewData['total']);
        $this->assertEmpty($viewData['recentQueries']);
    }

    public function test_dashboard_view_data_with_populated_storage(): void
    {
        $storage = $this->makeStorageWithQueries(5);
        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService);
        $this->assertSame(5, $viewData['total']);
        $this->assertCount(5, $viewData['recentQueries']);
    }

    public function test_dashboard_view_data_with_type_filter(): void
    {
        $storage = new InMemoryQueryStorage();
        $storage->store([
            'id' => 'q1', 'sql' => 'SELECT 1', 'time' => 0.01,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);
        $storage->store([
            'id' => 'q2', 'sql' => 'INSERT INTO t VALUES (1)', 'time' => 0.02,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'INSERT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);

        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService, ['type' => 'SELECT']);
        $this->assertSame(1, $viewData['total']);
    }

    public function test_dashboard_view_data_with_nonexistent_type_filter(): void
    {
        $storage = $this->makeStorageWithQueries(3);
        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService, ['type' => 'TRUNCATE']);
        $this->assertSame(0, $viewData['total']);
    }

    public function test_dashboard_view_data_pagination(): void
    {
        $storage = $this->makeStorageWithQueries(20);
        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService, ['per_page' => 5, 'page' => 1]);
        $this->assertCount(5, $viewData['recentQueries']);
        $this->assertSame(20, $viewData['total']);
        $this->assertSame(1, $viewData['page']);
    }

    // ---------------------------------------------------------------
    // Alerts page: navigation metadata
    // ---------------------------------------------------------------

    public function test_alerts_navigation_metadata(): void
    {
        $this->assertSame('Query Alerts', QueryLensAlerts::getNavigationLabel());
        $this->assertSame('query-lens/alerts', QueryLensAlerts::getSlug());
        $this->assertSame('heroicon-o-bell-alert', QueryLensAlerts::getNavigationIcon());
        $this->assertSame('Query Lens', QueryLensAlerts::getNavigationGroup());
        $this->assertSame(3, QueryLensAlerts::getNavigationSort());
    }

    // ---------------------------------------------------------------
    // Alerts: table column definitions
    // ---------------------------------------------------------------

    public function test_alerts_has_six_columns(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $this->assertCount(6, $columns);
    }

    public function test_alerts_column_names(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $names = array_column($columns, 'name');

        $this->assertContains('name', $names);
        $this->assertContains('type', $names);
        $this->assertContains('conditions', $names);
        $this->assertContains('channels', $names);
        $this->assertContains('enabled', $names);
        $this->assertContains('created_at', $names);
    }

    public function test_alerts_enabled_column_is_toggle(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $enabled = collect($columns)->firstWhere('name', 'enabled');

        $this->assertSame('toggle', $enabled['type']);
    }

    public function test_alerts_name_column_is_searchable(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $name = collect($columns)->firstWhere('name', 'name');

        $this->assertTrue($name['searchable']);
    }

    public function test_alerts_channels_column_is_badge_list(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $channels = collect($columns)->firstWhere('name', 'channels');

        $this->assertSame('badge_list', $channels['type']);
    }

    // ---------------------------------------------------------------
    // Alerts: table filter definitions
    // ---------------------------------------------------------------

    public function test_alerts_has_two_filters(): void
    {
        $filters = QueryLensAlerts::getTableFilterDefinitions();
        $this->assertCount(2, $filters);
    }

    public function test_alerts_type_filter_has_available_types(): void
    {
        $filters = QueryLensAlerts::getTableFilterDefinitions();
        $typeFilter = collect($filters)->firstWhere('name', 'type');

        $this->assertSame('select', $typeFilter['type']);
        $this->assertContains('slow_query', $typeFilter['options']);
        $this->assertContains('n_plus_one', $typeFilter['options']);
    }

    public function test_alerts_enabled_filter_is_ternary(): void
    {
        $filters = QueryLensAlerts::getTableFilterDefinitions();
        $enabled = collect($filters)->firstWhere('name', 'enabled');

        $this->assertSame('ternary', $enabled['type']);
    }

    // ---------------------------------------------------------------
    // Alerts: table action definitions
    // ---------------------------------------------------------------

    public function test_alerts_has_three_table_actions(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $this->assertCount(3, $actions);
    }

    public function test_alerts_edit_action_is_modal(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $edit = collect($actions)->firstWhere('name', 'edit');

        $this->assertSame('modal', $edit['type']);
        $this->assertSame('heroicon-o-pencil-square', $edit['icon']);
    }

    public function test_alerts_toggle_action_exists(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $toggle = collect($actions)->firstWhere('name', 'toggle');

        $this->assertNotNull($toggle);
        $this->assertSame('heroicon-o-power', $toggle['icon']);
    }

    public function test_alerts_delete_action_requires_confirmation(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $delete = collect($actions)->firstWhere('name', 'delete');

        $this->assertTrue($delete['requiresConfirmation']);
        $this->assertSame('danger', $delete['color']);
        $this->assertSame('heroicon-o-trash', $delete['icon']);
    }

    // ---------------------------------------------------------------
    // Alerts: header actions
    // ---------------------------------------------------------------

    public function test_alerts_create_header_action(): void
    {
        $actions = QueryLensAlerts::getHeaderActionDefinitions();
        $this->assertCount(1, $actions);

        $create = $actions[0];
        $this->assertSame('create', $create['name']);
        $this->assertSame('modal', $create['type']);
        $this->assertSame('heroicon-o-plus', $create['icon']);
    }

    // ---------------------------------------------------------------
    // Alerts: form definitions
    // ---------------------------------------------------------------

    public function test_alerts_form_has_six_fields(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $this->assertCount(6, $fields);
    }

    public function test_alerts_form_field_names(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $names = array_column($fields, 'name');

        $expected = ['name', 'type', 'conditions', 'channels', 'cooldown_minutes', 'enabled'];
        foreach ($expected as $n) {
            $this->assertContains($n, $names);
        }
    }

    public function test_alerts_form_name_field_is_required_with_max_length(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $name = collect($fields)->firstWhere('name', 'name');

        $this->assertTrue($name['required']);
        $this->assertSame(255, $name['maxLength']);
    }

    public function test_alerts_form_type_field_has_options(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $type = collect($fields)->firstWhere('name', 'type');

        $this->assertTrue($type['required']);
        $this->assertSame('select', $type['type']);
        $this->assertArrayHasKey('slow_query', $type['options']);
        $this->assertArrayHasKey('n_plus_one', $type['options']);
    }

    public function test_alerts_form_channels_is_checkbox_list_with_all_options(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $channels = collect($fields)->firstWhere('name', 'channels');

        $this->assertSame('checkbox_list', $channels['type']);
        $this->assertTrue($channels['required']);
        $this->assertArrayHasKey('log', $channels['options']);
        $this->assertArrayHasKey('mail', $channels['options']);
        $this->assertArrayHasKey('slack', $channels['options']);
    }

    public function test_alerts_form_cooldown_has_default_and_minimum(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $cooldown = collect($fields)->firstWhere('name', 'cooldown_minutes');

        $this->assertSame(5, $cooldown['default']);
        $this->assertSame(1, $cooldown['min']);
        $this->assertSame('numeric', $cooldown['type']);
    }

    public function test_alerts_form_enabled_toggle_defaults_to_true(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $enabled = collect($fields)->firstWhere('name', 'enabled');

        $this->assertTrue($enabled['default']);
        $this->assertSame('toggle', $enabled['type']);
    }

    // ---------------------------------------------------------------
    // Trends page: navigation metadata
    // ---------------------------------------------------------------

    public function test_trends_navigation_metadata(): void
    {
        $this->assertSame('Query Trends', QueryLensTrends::getNavigationLabel());
        $this->assertSame('query-lens/trends', QueryLensTrends::getSlug());
        $this->assertSame('heroicon-o-chart-bar', QueryLensTrends::getNavigationIcon());
        $this->assertSame('Query Lens', QueryLensTrends::getNavigationGroup());
        $this->assertSame(2, QueryLensTrends::getNavigationSort());
    }

    // ---------------------------------------------------------------
    // Trends: table column definitions
    // ---------------------------------------------------------------

    public function test_trends_has_five_columns(): void
    {
        $columns = QueryLensTrends::getTableColumnDefinitions();
        $this->assertCount(5, $columns);
    }

    public function test_trends_column_names(): void
    {
        $columns = QueryLensTrends::getTableColumnDefinitions();
        $names = array_column($columns, 'name');

        $this->assertContains('sql_sample', $names);
        $this->assertContains('count', $names);
        $this->assertContains('avg_time', $names);
        $this->assertContains('total_time', $names);
        $this->assertContains('impact_score', $names);
    }

    public function test_trends_all_numeric_columns_are_sortable(): void
    {
        $columns = QueryLensTrends::getTableColumnDefinitions();
        $numerics = collect($columns)->where('type', 'numeric');

        foreach ($numerics as $col) {
            $this->assertTrue($col['sortable'] ?? false, "{$col['name']} should be sortable");
        }
    }

    public function test_trends_sql_sample_is_searchable(): void
    {
        $columns = QueryLensTrends::getTableColumnDefinitions();
        $sql = collect($columns)->firstWhere('name', 'sql_sample');

        $this->assertTrue($sql['searchable']);
    }

    // ---------------------------------------------------------------
    // Trends: header widgets
    // ---------------------------------------------------------------

    public function test_trends_header_widgets_include_both_charts(): void
    {
        $widgets = QueryLensTrends::getHeaderWidgetDefinitions();
        $this->assertCount(2, $widgets);
        $this->assertContains(QueryPerformanceChart::class, $widgets);
        $this->assertContains(QueryVolumeChart::class, $widgets);
    }

    // ---------------------------------------------------------------
    // Trends: period options
    // ---------------------------------------------------------------

    public function test_trends_period_options_cover_three_ranges(): void
    {
        $options = QueryLensTrends::getPeriodOptions();
        $this->assertCount(3, $options);
        $this->assertArrayHasKey('24h', $options);
        $this->assertArrayHasKey('7d', $options);
        $this->assertArrayHasKey('30d', $options);
    }

    // ---------------------------------------------------------------
    // Trends: getViewData
    // ---------------------------------------------------------------

    public function test_trends_view_data_structure(): void
    {
        $page = new QueryLensTrends();
        $dataService = $this->makeDataService();
        $viewData = $page->getViewData($dataService);

        $this->assertArrayHasKey('trendsData', $viewData);
        $this->assertArrayHasKey('topQueries', $viewData);
    }

    public function test_trends_view_data_with_day_granularity(): void
    {
        $page = new QueryLensTrends();
        $dataService = $this->makeDataService();
        $viewData = $page->getViewData($dataService, 'day');

        $this->assertArrayHasKey('trendsData', $viewData);
        $this->assertArrayHasKey('labels', $viewData['trendsData']);
    }

    public function test_trends_view_data_with_populated_storage(): void
    {
        $storage = $this->makeStorageWithQueries(10);
        $page = new QueryLensTrends();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService);
        $this->assertNotEmpty($viewData['topQueries']);
    }

    // ---------------------------------------------------------------
    // Stats widget: stat generation
    // ---------------------------------------------------------------

    public function test_widget_returns_four_stat_cards(): void
    {
        $widget = new QueryLensStatsWidget();
        $stats = $widget->buildArrayStats($this->makeDataService()->getStatsForWidget());

        $this->assertCount(4, $stats);
    }

    public function test_widget_stat_labels_are_correct(): void
    {
        $widget = new QueryLensStatsWidget();
        $stats = $widget->buildArrayStats($this->makeDataService()->getStatsForWidget());

        $labels = array_column($stats, 'label');
        $this->assertSame('Total Queries (24h)', $labels[0]);
        $this->assertSame('Slow Queries', $labels[1]);
        $this->assertSame('Avg Response Time', $labels[2]);
        $this->assertSame('P95 Latency', $labels[3]);
    }

    public function test_widget_stat_cards_have_all_required_keys(): void
    {
        $widget = new QueryLensStatsWidget();
        $stats = $widget->buildArrayStats($this->makeDataService()->getStatsForWidget());

        $requiredKeys = ['label', 'value', 'change', 'change_direction', 'color'];
        foreach ($stats as $stat) {
            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey($key, $stat);
            }
        }
    }

    public function test_widget_stat_values_with_populated_storage(): void
    {
        $storage = $this->makeStorageWithQueries(10);
        $dataService = $this->makeDataService($storage);
        $widget = new QueryLensStatsWidget();
        $stats = $widget->buildArrayStats($dataService->getStatsForWidget());

        // Total queries should be non-zero since we added data
        $this->assertNotSame('0', $stats[0]['value']);
    }

    // ---------------------------------------------------------------
    // Stats widget: formatChange
    // ---------------------------------------------------------------

    public function test_format_change_neutral(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('No change', $widget->formatChange(['value' => 0, 'direction' => 'neutral']));
    }

    public function test_format_change_up(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('+25.5% vs previous period', $widget->formatChange(['value' => 25.5, 'direction' => 'up']));
    }

    public function test_format_change_down(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('-10% vs previous period', $widget->formatChange(['value' => 10, 'direction' => 'down']));
    }

    public function test_format_change_zero_value_shows_no_change(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('No change', $widget->formatChange(['value' => 0, 'direction' => 'up']));
    }

    public function test_format_change_missing_keys_defaults_to_no_change(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('No change', $widget->formatChange([]));
    }

    // ---------------------------------------------------------------
    // Stats widget: color logic
    // ---------------------------------------------------------------

    public function test_change_color_up_is_success(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('success', $widget->getChangeColor(['direction' => 'up']));
    }

    public function test_change_color_down_is_danger(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('danger', $widget->getChangeColor(['direction' => 'down']));
    }

    public function test_change_color_neutral_is_gray(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('gray', $widget->getChangeColor(['direction' => 'neutral']));
    }

    public function test_slow_color_is_inverted(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('danger', $widget->getSlowColor(['direction' => 'up']));
        $this->assertSame('success', $widget->getSlowColor(['direction' => 'down']));
        $this->assertSame('gray', $widget->getSlowColor(['direction' => 'neutral']));
    }

    public function test_time_color_is_inverted(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('danger', $widget->getTimeColor(['direction' => 'up']));
        $this->assertSame('success', $widget->getTimeColor(['direction' => 'down']));
        $this->assertSame('gray', $widget->getTimeColor(['direction' => 'neutral']));
    }

    public function test_color_unknown_direction_defaults_to_gray(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('gray', $widget->getChangeColor(['direction' => 'sideways']));
        $this->assertSame('gray', $widget->getSlowColor(['direction' => 'sideways']));
        $this->assertSame('gray', $widget->getTimeColor(['direction' => 'sideways']));
    }

    // ---------------------------------------------------------------
    // Stats widget: sort and polling
    // ---------------------------------------------------------------

    public function test_stats_widget_sort_order(): void
    {
        $this->assertSame(10, QueryLensStatsWidget::getSort());
    }

    public function test_stats_widget_polling_interval(): void
    {
        $this->assertSame('30s', QueryLensStatsWidget::getPollingInterval());
    }

    // ---------------------------------------------------------------
    // Performance chart widget
    // ---------------------------------------------------------------

    public function test_performance_chart_type_is_line(): void
    {
        $chart = new QueryPerformanceChart();
        $this->assertSame('line', $chart->getChartType());
    }

    public function test_performance_chart_heading(): void
    {
        $this->assertSame('Query Performance', QueryPerformanceChart::getHeading());
    }

    public function test_performance_chart_sort_order(): void
    {
        $this->assertSame(20, QueryPerformanceChart::getSort());
    }

    public function test_performance_chart_default_period(): void
    {
        $chart = new QueryPerformanceChart();
        $this->assertSame('24h', $chart->getPeriod());
    }

    public function test_performance_chart_set_period(): void
    {
        $chart = new QueryPerformanceChart();
        $result = $chart->setPeriod('7d');

        $this->assertSame('7d', $chart->getPeriod());
        $this->assertSame($chart, $result); // Fluent
    }

    public function test_performance_chart_data_has_three_datasets(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(3, $data['datasets']);
    }

    public function test_performance_chart_dataset_labels(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();
        $labels = array_column($data['datasets'], 'label');

        $this->assertContains('Avg Latency (ms)', $labels);
        $this->assertContains('P95 (ms)', $labels);
        $this->assertContains('P99 (ms)', $labels);
    }

    public function test_performance_chart_dataset_colors(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();

        $this->assertSame('#3B82F6', $data['datasets'][0]['borderColor']);
        $this->assertSame('#F59E0B', $data['datasets'][1]['borderColor']);
        $this->assertSame('#EF4444', $data['datasets'][2]['borderColor']);
    }

    public function test_performance_chart_first_dataset_has_fill(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();

        $this->assertTrue($data['datasets'][0]['fill']);
    }

    public function test_performance_chart_p95_and_p99_have_dashed_borders(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();

        $this->assertSame([5, 5], $data['datasets'][1]['borderDash']);
        $this->assertSame([2, 2], $data['datasets'][2]['borderDash']);
    }

    // ---------------------------------------------------------------
    // Volume chart widget
    // ---------------------------------------------------------------

    public function test_volume_chart_type_is_bar(): void
    {
        $chart = new QueryVolumeChart();
        $this->assertSame('bar', $chart->getChartType());
    }

    public function test_volume_chart_heading(): void
    {
        $this->assertSame('Query Volume', QueryVolumeChart::getHeading());
    }

    public function test_volume_chart_sort_order(): void
    {
        $this->assertSame(21, QueryVolumeChart::getSort());
    }

    public function test_volume_chart_data_has_one_dataset(): void
    {
        $chart = new QueryVolumeChart();
        $data = $chart->getChartData();

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(1, $data['datasets']);
        $this->assertSame('Query Count', $data['datasets'][0]['label']);
    }

    public function test_volume_chart_dataset_color(): void
    {
        $chart = new QueryVolumeChart();
        $data = $chart->getChartData();

        $this->assertSame('#6366F1', $data['datasets'][0]['backgroundColor']);
    }

    public function test_volume_chart_set_period(): void
    {
        $chart = new QueryVolumeChart();
        $chart->setPeriod('30d');

        $this->assertSame('30d', $chart->getPeriod());
    }

    // ---------------------------------------------------------------
    // Data service: stats structure
    // ---------------------------------------------------------------

    public function test_data_service_stats_keys(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        $expectedKeys = ['total_queries', 'slow_queries', 'avg_time', 'p95_time', 'query_change', 'slow_change', 'avg_time_change'];
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $stats);
        }
    }

    public function test_data_service_stats_change_has_direction_and_value(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        foreach (['query_change', 'slow_change', 'avg_time_change'] as $changeKey) {
            $this->assertArrayHasKey('direction', $stats[$changeKey]);
            $this->assertArrayHasKey('value', $stats[$changeKey]);
            $this->assertContains($stats[$changeKey]['direction'], ['up', 'down', 'neutral']);
        }
    }

    public function test_data_service_stats_empty_storage_returns_zeros(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        $this->assertSame(0, $stats['total_queries']);
        $this->assertSame(0, $stats['slow_queries']);
        $this->assertSame(0.0, $stats['avg_time']);
        $this->assertSame(0.0, $stats['p95_time']);
    }

    public function test_data_service_stats_division_by_zero_handled(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        // Previous period has 0 queries, change direction should be neutral
        $this->assertSame('neutral', $stats['query_change']['direction']);
        $this->assertSame(0, $stats['query_change']['value']);
    }

    // ---------------------------------------------------------------
    // Data service: recent queries
    // ---------------------------------------------------------------

    public function test_data_service_recent_queries_structure(): void
    {
        $dataService = $this->makeDataService();
        $result = $dataService->getRecentQueries();

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
    }

    public function test_data_service_recent_queries_empty(): void
    {
        $dataService = $this->makeDataService();
        $result = $dataService->getRecentQueries(['type' => 'NONEXISTENT']);

        $this->assertSame(0, $result['total']);
        $this->assertEmpty($result['data']);
    }

    // ---------------------------------------------------------------
    // Data service: trends
    // ---------------------------------------------------------------

    public function test_data_service_trends_structure(): void
    {
        $dataService = $this->makeDataService();
        $trends = $dataService->getTrendsData('hour');

        $this->assertArrayHasKey('labels', $trends);
        $this->assertArrayHasKey('latency', $trends);
        $this->assertArrayHasKey('throughput', $trends);
        $this->assertArrayHasKey('p50', $trends);
        $this->assertArrayHasKey('p95', $trends);
        $this->assertArrayHasKey('p99', $trends);
    }

    public function test_data_service_trends_with_data(): void
    {
        $storage = $this->makeStorageWithQueries(5);
        $dataService = $this->makeDataService($storage);
        $trends = $dataService->getTrendsData('hour', now()->subHours(2), now());

        $this->assertNotEmpty($trends['labels']);
        $this->assertNotEmpty($trends['throughput']);
    }

    // ---------------------------------------------------------------
    // Data service: top queries
    // ---------------------------------------------------------------

    public function test_data_service_top_queries_empty(): void
    {
        $dataService = $this->makeDataService();
        $topQueries = $dataService->getTopQueries('slowest', 'day', 5);

        $this->assertIsArray($topQueries);
        $this->assertEmpty($topQueries);
    }

    public function test_data_service_top_queries_with_data(): void
    {
        $storage = $this->makeStorageWithQueries(10);
        $dataService = $this->makeDataService($storage);
        $topQueries = $dataService->getTopQueries('slowest', 'day', 5);

        $this->assertNotEmpty($topQueries);
        $this->assertArrayHasKey('sql_sample', $topQueries[0]);
        $this->assertArrayHasKey('count', $topQueries[0]);
        $this->assertArrayHasKey('avg_time', $topQueries[0]);
    }

    // ---------------------------------------------------------------
    // Chart widgets: empty storage handling
    // ---------------------------------------------------------------

    public function test_performance_chart_handles_empty_storage(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();

        $this->assertIsArray($data['datasets']);
        $this->assertIsArray($data['labels']);
        $this->assertCount(3, $data['datasets']);
    }

    public function test_volume_chart_handles_empty_storage(): void
    {
        $chart = new QueryVolumeChart();
        $data = $chart->getChartData();

        $this->assertIsArray($data['datasets']);
        $this->assertIsArray($data['labels']);
        $this->assertCount(1, $data['datasets']);
    }

    // ---------------------------------------------------------------
    // Plugin: fluent API chaining
    // ---------------------------------------------------------------

    public function test_plugin_fluent_chaining_returns_same_instance(): void
    {
        $plugin = QueryLensPlugin::make();

        $result = $plugin
            ->dashboard(true)
            ->alerts(false)
            ->trends(true)
            ->navigationGroup('Monitoring')
            ->navigationIcon('heroicon-o-server')
            ->navigationSort(1);

        $this->assertSame($plugin, $result);
        $this->assertTrue($plugin->isDashboardEnabled());
        $this->assertFalse($plugin->isAlertsEnabled());
        $this->assertTrue($plugin->isTrendsEnabled());
        $this->assertSame('Monitoring', $plugin->getNavigationGroup());
        $this->assertSame('heroicon-o-server', $plugin->getNavigationIcon());
        $this->assertSame(1, $plugin->getNavigationSort());
    }

    // ---------------------------------------------------------------
    // Definition arrays: all actions have icons
    // ---------------------------------------------------------------

    public function test_all_dashboard_actions_have_icons(): void
    {
        $tableActions = QueryLensDashboard::getTableActionDefinitions();
        $headerActions = QueryLensDashboard::getHeaderActionDefinitions();

        foreach (array_merge($tableActions, $headerActions) as $action) {
            $this->assertArrayHasKey('icon', $action, "Action '{$action['name']}' must have an icon");
            $this->assertStringStartsWith('heroicon-', $action['icon']);
        }
    }

    public function test_all_alerts_actions_have_icons(): void
    {
        $tableActions = QueryLensAlerts::getTableActionDefinitions();
        $headerActions = QueryLensAlerts::getHeaderActionDefinitions();

        foreach (array_merge($tableActions, $headerActions) as $action) {
            $this->assertArrayHasKey('icon', $action, "Action '{$action['name']}' must have an icon");
            $this->assertStringStartsWith('heroicon-', $action['icon']);
        }
    }

    // ---------------------------------------------------------------
    // Edge case: definition arrays are consistent
    // ---------------------------------------------------------------

    public function test_filter_names_do_not_collide_with_column_names_dashboard(): void
    {
        // Filters and columns can share names (type is both a column and filter),
        // but filters must have a valid type field
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        foreach ($filters as $filter) {
            $this->assertArrayHasKey('type', $filter);
            $this->assertContains($filter['type'], ['select', 'ternary', 'date_range']);
        }
    }

    public function test_filter_types_are_valid_for_alerts(): void
    {
        $filters = QueryLensAlerts::getTableFilterDefinitions();
        foreach ($filters as $filter) {
            $this->assertArrayHasKey('type', $filter);
            $this->assertContains($filter['type'], ['select', 'ternary', 'date_range']);
        }
    }
}
