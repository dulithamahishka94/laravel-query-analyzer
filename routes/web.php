<?php

use Illuminate\Support\Facades\Route;
use Laravel\QueryAnalyzer\Http\Controllers\QueryAnalyzerController;

Route::middleware(['web', \Laravel\QueryAnalyzer\Http\Middleware\QueryAnalyzerMiddleware::class])
    ->prefix('query-analyzer')
    ->group(function () {
        Route::get('/', [QueryAnalyzerController::class, 'dashboard'])->name('query-analyzer.dashboard');

        Route::prefix('api')->group(function () {
            Route::get('requests', [QueryAnalyzerController::class, 'requests'])->name('query-analyzer.api.requests');
            Route::get('queries', [QueryAnalyzerController::class, 'queries'])->name('query-analyzer.api.queries');
            Route::get('query/{id}', [QueryAnalyzerController::class, 'query'])->name('query-analyzer.api.query');
            Route::get('stats', [QueryAnalyzerController::class, 'stats'])->name('query-analyzer.api.stats');
            Route::post('reset', [QueryAnalyzerController::class, 'reset'])->name('query-analyzer.api.reset');
            Route::post('analyze', [QueryAnalyzerController::class, 'analyze'])->name('query-analyzer.api.analyze');
            Route::post('explain', [QueryAnalyzerController::class, 'explain'])->name('query-analyzer.api.explain');
            Route::post('export', [QueryAnalyzerController::class, 'export'])->name('query-analyzer.api.export');
        });
    });
