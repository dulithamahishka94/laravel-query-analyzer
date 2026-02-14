<?php

namespace GladeHQ\QueryLens;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use GladeHQ\QueryLens\Commands\AggregateCommand;
use GladeHQ\QueryLens\Commands\AnalyzeQueriesCommand;
use GladeHQ\QueryLens\Commands\PruneCommand;
use GladeHQ\QueryLens\Commands\CheckRegressionCommand;
use GladeHQ\QueryLens\Commands\SuggestIndexesCommand;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Http\Controllers\AlertController;
use GladeHQ\QueryLens\Http\Controllers\QueryLensController;
use GladeHQ\QueryLens\Http\Middleware\QueryLensMiddleware;
use GladeHQ\QueryLens\Listeners\QueryListener;
use GladeHQ\QueryLens\Listeners\TransactionListener;
use GladeHQ\QueryLens\Services\AggregationService;
use GladeHQ\QueryLens\Services\AlertService;
use GladeHQ\QueryLens\Services\DataRetentionService;
use GladeHQ\QueryLens\Services\IndexAdvisor;
use GladeHQ\QueryLens\Services\AIQueryOptimizer;
use GladeHQ\QueryLens\Services\RegressionDetector;
use GladeHQ\QueryLens\Services\WebhookNotifier;
use GladeHQ\QueryLens\Filament\QueryLensDataService;
use GladeHQ\QueryLens\Filament\QueryLensPlugin;
use GladeHQ\QueryLens\Storage\CacheQueryStorage;
use GladeHQ\QueryLens\Storage\DatabaseQueryStorage;

class QueryLensServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/query-lens.php',
            'query-lens'
        );

        // Register storage based on driver config
        $this->app->singleton(QueryStorage::class, function ($app) {
            $driver = config('query-lens.storage.driver', 'cache');

            if ($driver === 'database') {
                $storage = new DatabaseQueryStorage();

                $storage->setAlertService($app->make(AlertService::class));

                return $storage;
            }

            $storage = new CacheQueryStorage(config('query-lens.store'));
            $storage->setAlertService($app->make(AlertService::class));

            return $storage;
        });

        // Register services
        $this->app->singleton(AlertService::class, function ($app) {
            return new AlertService();
        });

        $this->app->singleton(AggregationService::class, function ($app) {
            return new AggregationService();
        });

        $this->app->singleton(DataRetentionService::class, function ($app) {
            return new DataRetentionService();
        });

        $this->app->scoped(QueryAnalyzer::class, function ($app) {
            $storage = $app->make(QueryStorage::class);

            $analyzer = new QueryAnalyzer(
                $app['config']['query-lens'],
                $storage
            );

            // Wire the analyzer back into the storage driver so it can
            // temporarily disable recording during its own internal operations.
            // This breaks the circular dependency: Storage is created first
            // (without analyzer), then Analyzer is created (with storage),
            // and finally the analyzer reference is injected back.
            if (method_exists($storage, 'setAnalyzer')) {
                $storage->setAnalyzer($analyzer);
            }

            // Initialize Request ID immediately for HTTP requests to catch early queries
            // (e.g., Service Provider boot queries) that run before Middleware.
            if (!$app->runningInConsole()) {
                $analyzer->setRequestId((string) \Illuminate\Support\Str::orderedUuid());

                // CRITICAL: Disable recording immediately if this request targets
                // Query Lens routes. This MUST happen here (at resolution time)
                // because session/auth middleware fire QueryExecuted events BEFORE
                // AnalyzeQueryMiddleware::handle() runs. Without this early check,
                // session queries, CSRF token lookups, and other framework-level
                // side-effect queries get recorded when viewing the dashboard.
                if (static::isQueryLensRequest($app)) {
                    $analyzer->disableRecording();
                }
            }

            return $analyzer;
        });

        $this->app->singleton(QueryListener::class);
        $this->app->singleton(TransactionListener::class);

        $this->app->singleton(IndexAdvisor::class, function ($app) {
            $advisor = new IndexAdvisor();
            $advisor->setStorage($app->make(QueryStorage::class));
            return $advisor;
        });

        $this->app->singleton(RegressionDetector::class, function ($app) {
            return new RegressionDetector($app->make(QueryStorage::class));
        });

        $this->app->singleton(WebhookNotifier::class);

        $this->app->singleton(AIQueryOptimizer::class, function ($app) {
            return new AIQueryOptimizer(config('query-lens.ai', []));
        });

        // Register Filament data service (usable with or without Filament panel)
        $this->app->singleton(QueryLensDataService::class, function ($app) {
            return new QueryLensDataService(
                $app->make(QueryStorage::class),
                $app->make(AggregationService::class)
            );
        });
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'query-lens');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/query-lens.php' => config_path('query-lens.php'),
            ], 'query-lens-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'query-lens-migrations');

            // Views are explicitly NOT published to ensure updates are always reflected immediately.
            // users cannot override the dashboard view.

            $this->commands([
                AnalyzeQueriesCommand::class,
                AggregateCommand::class,
                PruneCommand::class,
                SuggestIndexesCommand::class,
                CheckRegressionCommand::class,
            ]);
        }

        $this->registerRoutes();

        if (config('query-lens.enabled', false)) {
            $queryListener = $this->app->make(QueryListener::class);
            $queryListener->register();

            // Register transaction tracking if enabled
            if (config('query-lens.track_transactions', false)) {
                $transactionListener = $this->app->make(TransactionListener::class);
                $transactionListener->register();
                $queryListener->setTransactionListener($transactionListener);
            }

            // Register the middleware to track Request IDs
            $router = $this->app['router'];
            if ($router->hasMiddlewareGroup('web')) {
                $router->pushMiddlewareToGroup('web', \GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware::class);
            }
            if ($router->hasMiddlewareGroup('api')) {
                $router->pushMiddlewareToGroup('api', \GladeHQ\QueryLens\Http\Middleware\AnalyzeQueryMiddleware::class);
            }
        }
    }

    /**
     * Determine if the current HTTP request targets a Query Lens route.
     *
     * This is called very early -- during service container resolution -- before
     * the router has matched the request. We therefore inspect the raw request
     * URI path rather than relying on route names or matched route objects.
     *
     * The check covers both the standalone web UI routes (/query-lens/...)
     * and Filament panel pages (which use the "query-lens" slug).
     */
    protected static function isQueryLensRequest($app): bool
    {
        try {
            $request = $app['request'] ?? null;

            if (!$request) {
                return false;
            }

            $path = $request->getPathInfo();

            // Standalone web UI: /query-lens or /query-lens/*
            if (str_starts_with($path, '/query-lens')) {
                return true;
            }

            // Filament panel pages typically live under /<panel-path>/query-lens*
            // e.g. /admin/query-lens, /admin/query-lens-alerts, /admin/query-lens-trends
            if (str_contains($path, '/query-lens')) {
                return true;
            }

            return false;
        } catch (\Throwable) {
            // If the request object is not yet available (e.g. during console
            // bootstrapping or testing edge cases), fail open -- recording will
            // be enabled, which is the safe default for non-dashboard contexts.
            return false;
        }
    }

    protected function registerRoutes(): void
    {
        if (!config('query-lens.web_ui.enabled', true)) {
            return;
        }

        Route::middleware(['web', QueryLensMiddleware::class])
            ->prefix('query-lens')
            ->group(function () {
                Route::get('/', [QueryLensController::class, 'dashboard'])->name('query-lens.dashboard');

                // Legacy v1 API endpoints
                Route::prefix('api')->group(function () {
                    Route::get('requests', [QueryLensController::class, 'requests'])->name('query-lens.api.requests');
                    Route::get('queries', [QueryLensController::class, 'queries'])->name('query-lens.api.queries');
                    Route::get('query/{id}', [QueryLensController::class, 'query'])->name('query-lens.api.query');
                    Route::get('stats', [QueryLensController::class, 'stats'])->name('query-lens.api.stats');
                    Route::post('reset', [QueryLensController::class, 'reset'])->name('query-lens.api.reset');
                    Route::post('analyze', [QueryLensController::class, 'analyze'])->name('query-lens.api.analyze');
                    Route::post('explain', [QueryLensController::class, 'explain'])->name('query-lens.api.explain');
                    Route::post('export', [QueryLensController::class, 'export'])->name('query-lens.api.export');
                });

                // V2 API endpoints with enhanced features
                Route::prefix('api/v2')->group(function () {
                    Route::get('trends', [QueryLensController::class, 'trends'])->name('query-lens.api.v2.trends');
                    Route::get('top-queries', [QueryLensController::class, 'topQueries'])->name('query-lens.api.v2.top-queries');
                    Route::get('request/{id}/waterfall', [QueryLensController::class, 'requestWaterfall'])->name('query-lens.api.v2.waterfall');
                    Route::get('stats/overview', [QueryLensController::class, 'overview'])->name('query-lens.api.v2.overview');
                    Route::get('poll', [QueryLensController::class, 'poll'])->name('query-lens.api.v2.poll');
                    Route::get('requests', [QueryLensController::class, 'requestsV2'])->name('query-lens.api.v2.requests');
                    Route::get('storage', [QueryLensController::class, 'storageInfo'])->name('query-lens.api.v2.storage');
                    Route::get('search', [QueryLensController::class, 'search'])->name('query-lens.api.v2.search');

                    // Index suggestions
                    Route::get('index-suggestions', [QueryLensController::class, 'indexSuggestions'])
                        ->name('query-lens.api.v2.index-suggestions');

                    // Regression detection
                    Route::get('regressions', [QueryLensController::class, 'regressions'])
                        ->name('query-lens.api.v2.regressions');

                    // AI optimization
                    Route::post('ai-optimize', [QueryLensController::class, 'aiOptimize'])
                        ->name('query-lens.api.v2.ai-optimize');

                    // Transaction tracking
                    Route::get('transactions', [QueryLensController::class, 'transactions'])
                        ->name('query-lens.api.v2.transactions');

                    // Alert management endpoints
                    Route::get('alerts', [AlertController::class, 'index'])->name('query-lens.api.v2.alerts.index');
                    Route::post('alerts', [AlertController::class, 'store'])->name('query-lens.api.v2.alerts.store');
                    Route::get('alerts/logs', [AlertController::class, 'recentLogs'])->name('query-lens.api.v2.alerts.logs');
                    Route::delete('alerts/logs', [AlertController::class, 'clearLogs'])->name('query-lens.api.v2.alerts.clear-logs');
                    Route::get('alerts/{id}', [AlertController::class, 'show'])->name('query-lens.api.v2.alerts.show');
                    Route::put('alerts/{id}', [AlertController::class, 'update'])->name('query-lens.api.v2.alerts.update');
                    Route::delete('alerts/{id}', [AlertController::class, 'destroy'])->name('query-lens.api.v2.alerts.destroy');
                    Route::get('alerts/{id}/logs', [AlertController::class, 'logs'])->name('query-lens.api.v2.alerts.alert-logs');
                    Route::post('alerts/{id}/toggle', [AlertController::class, 'toggle'])->name('query-lens.api.v2.alerts.toggle');
                    Route::post('alerts/{id}/test', [AlertController::class, 'test'])->name('query-lens.api.v2.alerts.test');
                });
            });
    }
}