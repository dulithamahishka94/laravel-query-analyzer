<?php

namespace GladeHQ\QueryLens;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use GladeHQ\QueryLens\Commands\AggregateCommand;
use GladeHQ\QueryLens\Commands\AnalyzeQueriesCommand;
use GladeHQ\QueryLens\Commands\PruneCommand;
use GladeHQ\QueryLens\Contracts\QueryStorage;
use GladeHQ\QueryLens\Http\Controllers\AlertController;
use GladeHQ\QueryLens\Http\Controllers\QueryLensController;
use GladeHQ\QueryLens\Http\Middleware\QueryLensMiddleware;
use GladeHQ\QueryLens\Listeners\QueryListener;
use GladeHQ\QueryLens\Services\AggregationService;
use GladeHQ\QueryLens\Services\AlertService;
use GladeHQ\QueryLens\Services\DataRetentionService;
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
            $analyzer = new QueryAnalyzer(
                $app['config']['query-lens'],
                $app->make(QueryStorage::class)
            );

            // Initialize Request ID immediately for HTTP requests to catch early queries
            // (e.g., Service Provider boot queries) that run before Middleware.
            if (!$app->runningInConsole()) {
                $analyzer->setRequestId((string) \Illuminate\Support\Str::orderedUuid());
            }

            return $analyzer;
        });

        $this->app->singleton(QueryListener::class);
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
            ]);
        }

        $this->registerRoutes();

        if (config('query-lens.enabled', false)) {
            $this->app->make(QueryListener::class)->register();

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