<?php

namespace Laravel\QueryAnalyzer;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\QueryAnalyzer\Commands\AnalyzeQueriesCommand;
use Laravel\QueryAnalyzer\Http\Controllers\QueryAnalyzerController;
use Laravel\QueryAnalyzer\Http\Middleware\QueryAnalyzerMiddleware;
use Laravel\QueryAnalyzer\Listeners\QueryListener;

class QueryAnalyzerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/query-analyzer.php',
            'query-analyzer'
        );

        $this->app->singleton(QueryAnalyzer::class, function ($app) {
            return new QueryAnalyzer($app['config']['query-analyzer']);
        });

        $this->app->singleton(QueryListener::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/resources/views', 'query-analyzer');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/query-analyzer.php' => config_path('query-analyzer.php'),
            ], 'query-analyzer-config');

            $this->publishes([
                __DIR__.'/resources/views' => resource_path('views/vendor/query-analyzer'),
            ], 'query-analyzer-views');

            $this->commands([
                AnalyzeQueriesCommand::class,
            ]);
        }

        $this->registerRoutes();

        if (config('query-analyzer.enabled', false)) {
            $this->app->make(QueryListener::class)->register();
        }
    }

    protected function registerRoutes(): void
    {
        if (!config('query-analyzer.web_ui.enabled', true)) {
            return;
        }

        Route::middleware(['web', QueryAnalyzerMiddleware::class])
            ->prefix('query-analyzer')
            ->group(function () {
                Route::get('/', [QueryAnalyzerController::class, 'dashboard'])->name('query-analyzer.dashboard');

                Route::prefix('api')->group(function () {
                    Route::get('queries', [QueryAnalyzerController::class, 'queries'])->name('query-analyzer.api.queries');
                    Route::get('query/{index}', [QueryAnalyzerController::class, 'query'])->name('query-analyzer.api.query');
                    Route::get('stats', [QueryAnalyzerController::class, 'stats'])->name('query-analyzer.api.stats');
                    Route::post('reset', [QueryAnalyzerController::class, 'reset'])->name('query-analyzer.api.reset');
                    Route::post('analyze', [QueryAnalyzerController::class, 'analyze'])->name('query-analyzer.api.analyze');
                    Route::post('export', [QueryAnalyzerController::class, 'export'])->name('query-analyzer.api.export');
                });
            });
    }
}