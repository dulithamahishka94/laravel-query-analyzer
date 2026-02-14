<?php

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

class FilamentPluginTest extends TestCase
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

    // ---------------------------------------------------------------
    // Plugin configuration
    // ---------------------------------------------------------------

    public function test_plugin_has_correct_id(): void
    {
        $plugin = QueryLensPlugin::make();
        $this->assertSame('query-lens', $plugin->getId());
    }

    public function test_plugin_defaults_all_features_enabled(): void
    {
        $plugin = QueryLensPlugin::make();
        $this->assertTrue($plugin->isDashboardEnabled());
        $this->assertTrue($plugin->isAlertsEnabled());
        $this->assertTrue($plugin->isTrendsEnabled());
    }

    public function test_plugin_can_disable_dashboard(): void
    {
        $plugin = QueryLensPlugin::make()->dashboard(false);
        $this->assertFalse($plugin->isDashboardEnabled());
        $this->assertTrue($plugin->isAlertsEnabled());
    }

    public function test_plugin_can_disable_alerts(): void
    {
        $plugin = QueryLensPlugin::make()->alerts(false);
        $this->assertTrue($plugin->isDashboardEnabled());
        $this->assertFalse($plugin->isAlertsEnabled());
    }

    public function test_plugin_can_disable_trends(): void
    {
        $plugin = QueryLensPlugin::make()->trends(false);
        $this->assertTrue($plugin->isDashboardEnabled());
        $this->assertFalse($plugin->isTrendsEnabled());
    }

    public function test_plugin_fluent_chaining(): void
    {
        $plugin = QueryLensPlugin::make()
            ->dashboard(false)
            ->alerts(false)
            ->trends(false);

        $this->assertFalse($plugin->isDashboardEnabled());
        $this->assertFalse($plugin->isAlertsEnabled());
        $this->assertFalse($plugin->isTrendsEnabled());
    }

    public function test_make_returns_static_instance(): void
    {
        $plugin = QueryLensPlugin::make();
        $this->assertInstanceOf(QueryLensPlugin::class, $plugin);
    }

    public function test_plugin_navigation_group_configurable(): void
    {
        $plugin = QueryLensPlugin::make();
        $this->assertSame('Query Lens', $plugin->getNavigationGroup());

        $plugin->navigationGroup('Monitoring');
        $this->assertSame('Monitoring', $plugin->getNavigationGroup());

        $plugin->navigationGroup(null);
        $this->assertNull($plugin->getNavigationGroup());
    }

    // ---------------------------------------------------------------
    // Filament detection
    // ---------------------------------------------------------------

    public function test_is_filament_installed_returns_false_when_absent(): void
    {
        $this->assertFalse(QueryLensPlugin::isFilamentInstalled());
    }

    // ---------------------------------------------------------------
    // Package works without Filament
    // ---------------------------------------------------------------

    public function test_package_boots_without_filament(): void
    {
        $this->assertTrue(true);
    }

    public function test_service_provider_registers_core_services_without_filament(): void
    {
        $this->assertNotNull(app(\GladeHQ\QueryLens\Contracts\QueryStorage::class));
        $this->assertNotNull(app(\GladeHQ\QueryLens\Services\AlertService::class));
        $this->assertNotNull(app(\GladeHQ\QueryLens\Services\AggregationService::class));
    }

    public function test_data_service_registered_in_container(): void
    {
        $this->assertNotNull(app(QueryLensDataService::class));
    }

    public function test_routes_register_without_filament(): void
    {
        $routes = app('router')->getRoutes();
        $routeNames = collect($routes->getRoutes())->map(fn ($r) => $r->getName())->filter()->toArray();

        $this->assertContains('query-lens.dashboard', $routeNames);
        $this->assertContains('query-lens.api.queries', $routeNames);
    }

    public function test_analyzer_works_independently_of_filament(): void
    {
        $analyzer = new \GladeHQ\QueryLens\QueryAnalyzer(
            ['performance_thresholds' => ['fast' => 0.1, 'moderate' => 0.5, 'slow' => 1.0]],
            new InMemoryQueryStorage()
        );

        $analyzer->recordQuery('SELECT * FROM users', [], 0.05);
        $this->assertCount(1, $analyzer->getQueries());
    }

    public function test_filament_page_classes_loadable_without_filament(): void
    {
        // These classes should be instantiable without Filament installed
        $dashboard = new QueryLensDashboard();
        $this->assertInstanceOf(QueryLensDashboard::class, $dashboard);

        $alerts = new QueryLensAlerts();
        $this->assertInstanceOf(QueryLensAlerts::class, $alerts);

        $trends = new QueryLensTrends();
        $this->assertInstanceOf(QueryLensTrends::class, $trends);
    }

    public function test_filament_widget_classes_loadable_without_filament(): void
    {
        $stats = new QueryLensStatsWidget();
        $this->assertInstanceOf(QueryLensStatsWidget::class, $stats);

        $perfChart = new QueryPerformanceChart();
        $this->assertInstanceOf(QueryPerformanceChart::class, $perfChart);

        $volChart = new QueryVolumeChart();
        $this->assertInstanceOf(QueryVolumeChart::class, $volChart);
    }

    // ---------------------------------------------------------------
    // QueryLensDataService
    // ---------------------------------------------------------------

    public function test_data_service_returns_stats_for_widget(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        $this->assertArrayHasKey('total_queries', $stats);
        $this->assertArrayHasKey('slow_queries', $stats);
        $this->assertArrayHasKey('avg_time', $stats);
        $this->assertArrayHasKey('p95_time', $stats);
        $this->assertArrayHasKey('query_change', $stats);
        $this->assertArrayHasKey('slow_change', $stats);
        $this->assertArrayHasKey('avg_time_change', $stats);
    }

    public function test_data_service_returns_empty_stats_when_no_data(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        $this->assertSame(0, $stats['total_queries']);
        $this->assertSame(0, $stats['slow_queries']);
        $this->assertSame(0.0, $stats['avg_time']);
    }

    public function test_data_service_recent_queries_returns_paginated_result(): void
    {
        $dataService = $this->makeDataService();
        $result = $dataService->getRecentQueries(['per_page' => 10]);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('per_page', $result);
    }

    public function test_data_service_recent_queries_with_type_filter(): void
    {
        $storage = new InMemoryQueryStorage();
        $storage->store([
            'id' => 'q1', 'sql' => 'SELECT * FROM users', 'time' => 0.05,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);
        $storage->store([
            'id' => 'q2', 'sql' => 'INSERT INTO users (name) VALUES ("test")', 'time' => 0.03,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'INSERT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);

        $dataService = $this->makeDataService($storage);
        $result = $dataService->getRecentQueries(['type' => 'SELECT']);
        $this->assertSame(1, $result['total']);
    }

    public function test_data_service_trends_returns_expected_structure(): void
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

    public function test_data_service_top_queries_returns_array(): void
    {
        $dataService = $this->makeDataService();
        $topQueries = $dataService->getTopQueries('slowest', 'day', 5);

        $this->assertIsArray($topQueries);
    }

    public function test_data_service_top_queries_with_data(): void
    {
        $storage = new InMemoryQueryStorage();
        $storage->store([
            'id' => 'q1', 'sql' => 'SELECT * FROM users', 'time' => 0.5,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);
        $storage->store([
            'id' => 'q2', 'sql' => 'SELECT * FROM users', 'time' => 0.8,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);

        $dataService = $this->makeDataService($storage);
        $topQueries = $dataService->getTopQueries('slowest', 'day', 5);

        $this->assertNotEmpty($topQueries);
        $this->assertArrayHasKey('sql_sample', $topQueries[0]);
    }

    public function test_data_service_trends_with_data(): void
    {
        $storage = new InMemoryQueryStorage();
        for ($i = 0; $i < 5; $i++) {
            $storage->store([
                'id' => "q{$i}", 'sql' => 'SELECT * FROM users',
                'time' => 0.05 + ($i * 0.01),
                'timestamp' => microtime(true) - ($i * 60),
                'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
            ]);
        }

        $dataService = $this->makeDataService($storage);
        $trends = $dataService->getTrendsData('hour', now()->subHours(2), now());

        $this->assertNotEmpty($trends['labels']);
        $this->assertNotEmpty($trends['throughput']);
    }

    public function test_data_service_stats_include_change_directions(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        $this->assertArrayHasKey('direction', $stats['query_change']);
        $this->assertContains($stats['query_change']['direction'], ['up', 'down', 'neutral']);
    }

    public function test_data_service_handles_empty_search_results(): void
    {
        $dataService = $this->makeDataService();
        $result = $dataService->getRecentQueries(['type' => 'NONEXISTENT']);

        $this->assertSame(0, $result['total']);
        $this->assertEmpty($result['data']);
    }

    // ---------------------------------------------------------------
    // Dashboard page -- navigation and configuration
    // ---------------------------------------------------------------

    public function test_dashboard_page_view_data(): void
    {
        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService();

        $viewData = $page->getViewData($dataService);

        $this->assertArrayHasKey('stats', $viewData);
        $this->assertArrayHasKey('recentQueries', $viewData);
        $this->assertArrayHasKey('total', $viewData);
        $this->assertArrayHasKey('page', $viewData);
    }

    public function test_dashboard_page_view_data_with_filters(): void
    {
        $storage = new InMemoryQueryStorage();
        $storage->store([
            'id' => 'q1', 'sql' => 'SELECT * FROM users', 'time' => 0.05,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);

        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService, ['type' => 'SELECT']);
        $this->assertSame(1, $viewData['total']);
    }

    public function test_dashboard_page_navigation_label(): void
    {
        $this->assertSame('Query Dashboard', QueryLensDashboard::getNavigationLabel());
    }

    public function test_dashboard_page_slug(): void
    {
        $this->assertSame('query-lens', QueryLensDashboard::getSlug());
    }

    public function test_dashboard_page_navigation_icon(): void
    {
        $this->assertSame('heroicon-o-circle-stack', QueryLensDashboard::getNavigationIcon());
    }

    public function test_dashboard_page_navigation_group(): void
    {
        $this->assertSame('Query Lens', QueryLensDashboard::getNavigationGroup());
    }

    public function test_dashboard_page_navigation_sort(): void
    {
        $this->assertSame(1, QueryLensDashboard::getNavigationSort());
    }

    public function test_dashboard_polling_interval(): void
    {
        $this->assertSame('10s', QueryLensDashboard::getPollingInterval());
    }

    // ---------------------------------------------------------------
    // Dashboard table column definitions
    // ---------------------------------------------------------------

    public function test_dashboard_table_has_required_columns(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $names = array_column($columns, 'name');

        $this->assertContains('sql', $names);
        $this->assertContains('type', $names);
        $this->assertContains('duration', $names);
        $this->assertContains('is_slow', $names);
        $this->assertContains('origin', $names);
        $this->assertContains('created_at', $names);
    }

    public function test_dashboard_sql_column_is_searchable(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $sqlCol = collect($columns)->firstWhere('name', 'sql');

        $this->assertTrue($sqlCol['searchable']);
    }

    public function test_dashboard_duration_column_is_sortable(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $durationCol = collect($columns)->firstWhere('name', 'duration');

        $this->assertTrue($durationCol['sortable']);
    }

    public function test_dashboard_is_slow_column_is_boolean_icon(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $isSlowCol = collect($columns)->firstWhere('name', 'is_slow');

        $this->assertSame('boolean_icon', $isSlowCol['type']);
    }

    public function test_dashboard_created_at_column_is_sortable_datetime(): void
    {
        $columns = QueryLensDashboard::getTableColumnDefinitions();
        $createdCol = collect($columns)->firstWhere('name', 'created_at');

        $this->assertTrue($createdCol['sortable']);
        $this->assertSame('datetime', $createdCol['type']);
    }

    // ---------------------------------------------------------------
    // Dashboard table filter definitions
    // ---------------------------------------------------------------

    public function test_dashboard_has_type_filter(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $typeFilter = collect($filters)->firstWhere('name', 'type');

        $this->assertNotNull($typeFilter);
        $this->assertSame('select', $typeFilter['type']);
        $this->assertContains('SELECT', $typeFilter['options']);
        $this->assertContains('DELETE', $typeFilter['options']);
    }

    public function test_dashboard_has_slow_query_filter(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $slowFilter = collect($filters)->firstWhere('name', 'is_slow');

        $this->assertNotNull($slowFilter);
        $this->assertSame('ternary', $slowFilter['type']);
    }

    public function test_dashboard_has_date_range_filter(): void
    {
        $filters = QueryLensDashboard::getTableFilterDefinitions();
        $dateFilter = collect($filters)->firstWhere('name', 'date_range');

        $this->assertNotNull($dateFilter);
        $this->assertSame('date_range', $dateFilter['type']);
    }

    // ---------------------------------------------------------------
    // Dashboard table action definitions
    // ---------------------------------------------------------------

    public function test_dashboard_has_view_action(): void
    {
        $actions = QueryLensDashboard::getTableActionDefinitions();
        $viewAction = collect($actions)->firstWhere('name', 'view');

        $this->assertNotNull($viewAction);
        $this->assertSame('modal', $viewAction['type']);
        $this->assertSame('heroicon-o-eye', $viewAction['icon']);
    }

    public function test_dashboard_has_explain_action(): void
    {
        $actions = QueryLensDashboard::getTableActionDefinitions();
        $explainAction = collect($actions)->firstWhere('name', 'explain');

        $this->assertNotNull($explainAction);
        $this->assertSame('modal', $explainAction['type']);
    }

    public function test_dashboard_has_export_header_action(): void
    {
        $actions = QueryLensDashboard::getHeaderActionDefinitions();
        $exportAction = collect($actions)->firstWhere('name', 'export');

        $this->assertNotNull($exportAction);
        $this->assertSame('heroicon-o-arrow-down-tray', $exportAction['icon']);
    }

    // ---------------------------------------------------------------
    // Dashboard header widgets
    // ---------------------------------------------------------------

    public function test_dashboard_header_widgets_include_stats(): void
    {
        $widgets = QueryLensDashboard::getHeaderWidgetDefinitions();
        $this->assertContains(QueryLensStatsWidget::class, $widgets);
    }

    // ---------------------------------------------------------------
    // Alerts page -- navigation and configuration
    // ---------------------------------------------------------------

    public function test_alerts_page_navigation_label(): void
    {
        $this->assertSame('Query Alerts', QueryLensAlerts::getNavigationLabel());
    }

    public function test_alerts_page_slug(): void
    {
        $this->assertSame('query-lens/alerts', QueryLensAlerts::getSlug());
    }

    public function test_alerts_page_navigation_icon(): void
    {
        $this->assertSame('heroicon-o-bell-alert', QueryLensAlerts::getNavigationIcon());
    }

    public function test_alerts_page_navigation_sort(): void
    {
        $this->assertSame(3, QueryLensAlerts::getNavigationSort());
    }

    // ---------------------------------------------------------------
    // Alerts table column definitions
    // ---------------------------------------------------------------

    public function test_alerts_table_has_required_columns(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $names = array_column($columns, 'name');

        $this->assertContains('name', $names);
        $this->assertContains('type', $names);
        $this->assertContains('channels', $names);
        $this->assertContains('enabled', $names);
        $this->assertContains('created_at', $names);
    }

    public function test_alerts_enabled_column_is_toggle(): void
    {
        $columns = QueryLensAlerts::getTableColumnDefinitions();
        $enabledCol = collect($columns)->firstWhere('name', 'enabled');

        $this->assertSame('toggle', $enabledCol['type']);
    }

    // ---------------------------------------------------------------
    // Alerts table filter definitions
    // ---------------------------------------------------------------

    public function test_alerts_has_type_filter(): void
    {
        $filters = QueryLensAlerts::getTableFilterDefinitions();
        $typeFilter = collect($filters)->firstWhere('name', 'type');

        $this->assertNotNull($typeFilter);
        $this->assertSame('select', $typeFilter['type']);
    }

    public function test_alerts_has_enabled_filter(): void
    {
        $filters = QueryLensAlerts::getTableFilterDefinitions();
        $enabledFilter = collect($filters)->firstWhere('name', 'enabled');

        $this->assertNotNull($enabledFilter);
        $this->assertSame('ternary', $enabledFilter['type']);
    }

    // ---------------------------------------------------------------
    // Alerts action definitions
    // ---------------------------------------------------------------

    public function test_alerts_has_edit_action(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $editAction = collect($actions)->firstWhere('name', 'edit');

        $this->assertNotNull($editAction);
        $this->assertSame('modal', $editAction['type']);
    }

    public function test_alerts_has_toggle_action(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $toggleAction = collect($actions)->firstWhere('name', 'toggle');

        $this->assertNotNull($toggleAction);
    }

    public function test_alerts_has_delete_action_with_confirmation(): void
    {
        $actions = QueryLensAlerts::getTableActionDefinitions();
        $deleteAction = collect($actions)->firstWhere('name', 'delete');

        $this->assertNotNull($deleteAction);
        $this->assertTrue($deleteAction['requiresConfirmation']);
        $this->assertSame('danger', $deleteAction['color']);
    }

    public function test_alerts_has_create_header_action(): void
    {
        $actions = QueryLensAlerts::getHeaderActionDefinitions();
        $createAction = collect($actions)->firstWhere('name', 'create');

        $this->assertNotNull($createAction);
        $this->assertSame('modal', $createAction['type']);
    }

    // ---------------------------------------------------------------
    // Alerts form definitions
    // ---------------------------------------------------------------

    public function test_alerts_form_has_required_fields(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $names = array_column($fields, 'name');

        $this->assertContains('name', $names);
        $this->assertContains('type', $names);
        $this->assertContains('conditions', $names);
        $this->assertContains('channels', $names);
        $this->assertContains('cooldown_minutes', $names);
        $this->assertContains('enabled', $names);
    }

    public function test_alerts_form_name_is_required(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $nameField = collect($fields)->firstWhere('name', 'name');

        $this->assertTrue($nameField['required']);
    }

    public function test_alerts_form_type_has_available_options(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $typeField = collect($fields)->firstWhere('name', 'type');

        $this->assertNotEmpty($typeField['options']);
        $this->assertArrayHasKey('slow_query', $typeField['options']);
    }

    public function test_alerts_form_channels_is_checkbox_list(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $channelsField = collect($fields)->firstWhere('name', 'channels');

        $this->assertSame('checkbox_list', $channelsField['type']);
        $this->assertArrayHasKey('log', $channelsField['options']);
        $this->assertArrayHasKey('mail', $channelsField['options']);
        $this->assertArrayHasKey('slack', $channelsField['options']);
    }

    public function test_alerts_form_cooldown_has_minimum(): void
    {
        $fields = QueryLensAlerts::getAlertFormDefinitions();
        $cooldownField = collect($fields)->firstWhere('name', 'cooldown_minutes');

        $this->assertSame(1, $cooldownField['min']);
    }

    // ---------------------------------------------------------------
    // Trends page -- navigation and configuration
    // ---------------------------------------------------------------

    public function test_trends_page_navigation_label(): void
    {
        $this->assertSame('Query Trends', QueryLensTrends::getNavigationLabel());
    }

    public function test_trends_page_slug(): void
    {
        $this->assertSame('query-lens/trends', QueryLensTrends::getSlug());
    }

    public function test_trends_page_navigation_icon(): void
    {
        $this->assertSame('heroicon-o-chart-bar', QueryLensTrends::getNavigationIcon());
    }

    public function test_trends_page_navigation_sort(): void
    {
        $this->assertSame(2, QueryLensTrends::getNavigationSort());
    }

    public function test_trends_page_view_data(): void
    {
        $page = new QueryLensTrends();
        $dataService = $this->makeDataService();

        $viewData = $page->getViewData($dataService);

        $this->assertArrayHasKey('trendsData', $viewData);
        $this->assertArrayHasKey('topQueries', $viewData);
    }

    public function test_trends_page_view_data_with_granularity(): void
    {
        $page = new QueryLensTrends();
        $dataService = $this->makeDataService();

        $viewData = $page->getViewData($dataService, 'day');

        $this->assertArrayHasKey('trendsData', $viewData);
    }

    // ---------------------------------------------------------------
    // Trends table column definitions
    // ---------------------------------------------------------------

    public function test_trends_table_has_required_columns(): void
    {
        $columns = QueryLensTrends::getTableColumnDefinitions();
        $names = array_column($columns, 'name');

        $this->assertContains('sql_sample', $names);
        $this->assertContains('count', $names);
        $this->assertContains('avg_time', $names);
        $this->assertContains('total_time', $names);
        $this->assertContains('impact_score', $names);
    }

    public function test_trends_impact_score_column_is_sortable(): void
    {
        $columns = QueryLensTrends::getTableColumnDefinitions();
        $impactCol = collect($columns)->firstWhere('name', 'impact_score');

        $this->assertTrue($impactCol['sortable']);
    }

    // ---------------------------------------------------------------
    // Trends header widgets
    // ---------------------------------------------------------------

    public function test_trends_header_widgets_include_charts(): void
    {
        $widgets = QueryLensTrends::getHeaderWidgetDefinitions();
        $this->assertContains(QueryPerformanceChart::class, $widgets);
        $this->assertContains(QueryVolumeChart::class, $widgets);
    }

    // ---------------------------------------------------------------
    // Trends period options
    // ---------------------------------------------------------------

    public function test_trends_period_options(): void
    {
        $options = QueryLensTrends::getPeriodOptions();
        $this->assertArrayHasKey('24h', $options);
        $this->assertArrayHasKey('7d', $options);
        $this->assertArrayHasKey('30d', $options);
    }

    // ---------------------------------------------------------------
    // Stats widget
    // ---------------------------------------------------------------

    public function test_widget_returns_four_stat_cards(): void
    {
        $widget = new QueryLensStatsWidget();
        $stats = $widget->buildArrayStats($this->makeDataService()->getStatsForWidget());

        $this->assertCount(4, $stats);
        $this->assertSame('Total Queries (24h)', $stats[0]['label']);
        $this->assertSame('Slow Queries', $stats[1]['label']);
        $this->assertSame('Avg Response Time', $stats[2]['label']);
        $this->assertSame('P95 Latency', $stats[3]['label']);
    }

    public function test_widget_stat_cards_have_expected_keys(): void
    {
        $widget = new QueryLensStatsWidget();
        $stats = $widget->buildArrayStats($this->makeDataService()->getStatsForWidget());

        foreach ($stats as $stat) {
            $this->assertArrayHasKey('label', $stat);
            $this->assertArrayHasKey('value', $stat);
            $this->assertArrayHasKey('change', $stat);
            $this->assertArrayHasKey('change_direction', $stat);
            $this->assertArrayHasKey('color', $stat);
        }
    }

    public function test_widget_format_change_neutral(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('No change', $widget->formatChange(['value' => 0, 'direction' => 'neutral']));
    }

    public function test_widget_format_change_up(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('+25.5% vs previous period', $widget->formatChange(['value' => 25.5, 'direction' => 'up']));
    }

    public function test_widget_format_change_down(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('-10% vs previous period', $widget->formatChange(['value' => 10, 'direction' => 'down']));
    }

    public function test_widget_format_change_zero_value_up_direction(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('No change', $widget->formatChange(['value' => 0, 'direction' => 'up']));
    }

    public function test_widget_format_change_missing_keys(): void
    {
        $widget = new QueryLensStatsWidget();
        $this->assertSame('No change', $widget->formatChange([]));
    }

    public function test_widget_change_color_logic(): void
    {
        $widget = new QueryLensStatsWidget();

        $this->assertSame('success', $widget->getChangeColor(['direction' => 'up']));
        $this->assertSame('danger', $widget->getChangeColor(['direction' => 'down']));
        $this->assertSame('gray', $widget->getChangeColor(['direction' => 'neutral']));
    }

    public function test_widget_slow_color_inverted(): void
    {
        $widget = new QueryLensStatsWidget();

        $this->assertSame('danger', $widget->getSlowColor(['direction' => 'up']));
        $this->assertSame('success', $widget->getSlowColor(['direction' => 'down']));
    }

    public function test_widget_time_color_inverted(): void
    {
        $widget = new QueryLensStatsWidget();

        $this->assertSame('danger', $widget->getTimeColor(['direction' => 'up']));
        $this->assertSame('success', $widget->getTimeColor(['direction' => 'down']));
    }

    public function test_widget_color_defaults_to_gray_for_unknown_direction(): void
    {
        $widget = new QueryLensStatsWidget();

        $this->assertSame('gray', $widget->getChangeColor(['direction' => 'unknown']));
        $this->assertSame('gray', $widget->getSlowColor(['direction' => 'unknown']));
        $this->assertSame('gray', $widget->getTimeColor(['direction' => 'unknown']));
    }

    public function test_widget_sort_and_polling(): void
    {
        $this->assertSame(10, QueryLensStatsWidget::getSort());
        $this->assertSame('30s', QueryLensStatsWidget::getPollingInterval());
    }

    // ---------------------------------------------------------------
    // QueryPerformanceChart widget
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

    public function test_performance_chart_sort(): void
    {
        $this->assertSame(20, QueryPerformanceChart::getSort());
    }

    public function test_performance_chart_period_defaults_to_24h(): void
    {
        $chart = new QueryPerformanceChart();
        $this->assertSame('24h', $chart->getPeriod());
    }

    public function test_performance_chart_period_can_be_set(): void
    {
        $chart = new QueryPerformanceChart();
        $chart->setPeriod('7d');
        $this->assertSame('7d', $chart->getPeriod());
    }

    public function test_performance_chart_data_structure(): void
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

    // ---------------------------------------------------------------
    // QueryVolumeChart widget
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

    public function test_volume_chart_sort(): void
    {
        $this->assertSame(21, QueryVolumeChart::getSort());
    }

    public function test_volume_chart_data_structure(): void
    {
        $chart = new QueryVolumeChart();
        $data = $chart->getChartData();

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(1, $data['datasets']);
        $this->assertSame('Query Count', $data['datasets'][0]['label']);
    }

    public function test_volume_chart_period_can_be_set(): void
    {
        $chart = new QueryVolumeChart();
        $chart->setPeriod('30d');
        $this->assertSame('30d', $chart->getPeriod());
    }

    // ---------------------------------------------------------------
    // Plugin register method (mock panel)
    // ---------------------------------------------------------------

    public function test_plugin_register_calls_panel_with_pages(): void
    {
        $plugin = QueryLensPlugin::make();

        $registeredPages = [];
        $registeredWidgets = [];

        $panel = new class ($registeredPages, $registeredWidgets) {
            public array $pages;
            public array $widgets;

            public function __construct(array &$pages, array &$widgets)
            {
                $this->pages = &$pages;
                $this->widgets = &$widgets;
            }

            public function pages(array $pages): self
            {
                $this->pages = $pages;

                return $this;
            }

            public function widgets(array $widgets): self
            {
                $this->widgets = $widgets;

                return $this;
            }
        };

        $plugin->register($panel);

        $this->assertContains(QueryLensDashboard::class, $panel->pages);
        $this->assertContains(QueryLensAlerts::class, $panel->pages);
        $this->assertContains(QueryLensTrends::class, $panel->pages);
        $this->assertContains(QueryLensStatsWidget::class, $panel->widgets);
    }

    public function test_plugin_register_includes_chart_widgets_when_trends_enabled(): void
    {
        $plugin = QueryLensPlugin::make();

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $pages): self
            {
                $this->pages = $pages;

                return $this;
            }

            public function widgets(array $widgets): self
            {
                $this->widgets = $widgets;

                return $this;
            }
        };

        $plugin->register($panel);

        $this->assertContains(QueryPerformanceChart::class, $panel->widgets);
        $this->assertContains(QueryVolumeChart::class, $panel->widgets);
    }

    public function test_plugin_register_excludes_chart_widgets_when_trends_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->trends(false);

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $pages): self
            {
                $this->pages = $pages;

                return $this;
            }

            public function widgets(array $widgets): self
            {
                $this->widgets = $widgets;

                return $this;
            }
        };

        $plugin->register($panel);

        $this->assertNotContains(QueryPerformanceChart::class, $panel->widgets);
        $this->assertNotContains(QueryVolumeChart::class, $panel->widgets);
    }

    public function test_plugin_register_respects_disabled_pages(): void
    {
        $plugin = QueryLensPlugin::make()
            ->dashboard(false)
            ->alerts(false);

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $pages): self
            {
                $this->pages = $pages;

                return $this;
            }

            public function widgets(array $widgets): self
            {
                $this->widgets = $widgets;

                return $this;
            }
        };

        $plugin->register($panel);

        $this->assertNotContains(QueryLensDashboard::class, $panel->pages);
        $this->assertNotContains(QueryLensAlerts::class, $panel->pages);
        $this->assertContains(QueryLensTrends::class, $panel->pages);
    }

    public function test_plugin_register_excludes_stats_widget_when_dashboard_disabled(): void
    {
        $plugin = QueryLensPlugin::make()->dashboard(false);

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $pages): self
            {
                $this->pages = $pages;

                return $this;
            }

            public function widgets(array $widgets): self
            {
                $this->widgets = $widgets;

                return $this;
            }
        };

        $plugin->register($panel);

        $this->assertNotContains(QueryLensStatsWidget::class, $panel->widgets);
    }

    public function test_plugin_boot_does_not_throw(): void
    {
        $plugin = QueryLensPlugin::make();
        $panel = new class {
            public function pages(array $p): self
            {
                return $this;
            }

            public function widgets(array $w): self
            {
                return $this;
            }
        };

        $plugin->boot($panel);
        $this->assertTrue(true);
    }

    // ---------------------------------------------------------------
    // Unhappy path / edge cases
    // ---------------------------------------------------------------

    public function test_data_service_stats_handles_division_by_zero(): void
    {
        $dataService = $this->makeDataService();
        $stats = $dataService->getStatsForWidget();

        // When previous period has 0 queries, change should be neutral
        $this->assertSame('neutral', $stats['query_change']['direction']);
        $this->assertSame(0, $stats['query_change']['value']);
    }

    public function test_dashboard_view_data_with_empty_filters(): void
    {
        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService();

        $viewData = $page->getViewData($dataService, []);
        $this->assertSame(0, $viewData['total']);
        $this->assertEmpty($viewData['recentQueries']);
    }

    public function test_dashboard_view_data_with_nonexistent_type_filter(): void
    {
        $storage = new InMemoryQueryStorage();
        $storage->store([
            'id' => 'q1', 'sql' => 'SELECT 1', 'time' => 0.01,
            'timestamp' => microtime(true),
            'analysis' => ['type' => 'SELECT', 'performance' => ['is_slow' => false], 'issues' => []],
        ]);

        $page = new QueryLensDashboard();
        $dataService = $this->makeDataService($storage);

        $viewData = $page->getViewData($dataService, ['type' => 'TRUNCATE']);
        $this->assertSame(0, $viewData['total']);
    }

    public function test_performance_chart_data_with_empty_storage(): void
    {
        $chart = new QueryPerformanceChart();
        $data = $chart->getChartData();

        $this->assertIsArray($data['datasets']);
        $this->assertIsArray($data['labels']);
    }

    public function test_volume_chart_data_with_empty_storage(): void
    {
        $chart = new QueryVolumeChart();
        $data = $chart->getChartData();

        $this->assertIsArray($data['datasets']);
        $this->assertIsArray($data['labels']);
    }

    public function test_plugin_all_disabled_registers_empty(): void
    {
        $plugin = QueryLensPlugin::make()
            ->dashboard(false)
            ->alerts(false)
            ->trends(false);

        $panel = new class {
            public array $pages = [];
            public array $widgets = [];

            public function pages(array $pages): self
            {
                $this->pages = $pages;

                return $this;
            }

            public function widgets(array $widgets): self
            {
                $this->widgets = $widgets;

                return $this;
            }
        };

        $plugin->register($panel);

        $this->assertEmpty($panel->pages);
        $this->assertEmpty($panel->widgets);
    }
}
