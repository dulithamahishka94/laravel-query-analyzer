<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('query-analyzer.storage.table_prefix', 'query_analyzer_');
        $connection = config('query-analyzer.storage.connection');

        // Main requests table - HTTP request aggregations
        Schema::connection($connection)->create($prefix . 'requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('method', 10)->default('GET');
            $table->string('path', 2048)->nullable();
            $table->string('route_name', 255)->nullable();
            $table->integer('query_count')->default(0);
            $table->integer('slow_count')->default(0);
            $table->float('total_time', 12, 6)->default(0);
            $table->float('avg_time', 12, 6)->default(0);
            $table->float('max_time', 12, 6)->default(0);
            $table->integer('issue_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['method', 'path']);
        });

        // Main fact table - stores all captured queries with analysis
        Schema::connection($connection)->create($prefix . 'queries', function (Blueprint $table) use ($prefix) {
            $table->uuid('id')->primary();
            $table->uuid('request_id')->nullable();
            $table->string('sql_hash', 64)->index();
            $table->text('sql');
            $table->text('sql_normalized')->nullable();
            $table->json('bindings')->nullable();
            $table->float('time', 12, 6);
            $table->string('connection', 64)->default('default');
            $table->string('type', 20)->default('SELECT');
            $table->string('performance_rating', 20)->default('fast');
            $table->boolean('is_slow')->default(false);
            $table->integer('complexity_score')->default(0);
            $table->string('complexity_level', 20)->default('low');
            $table->json('analysis')->nullable();
            $table->json('origin')->nullable();
            $table->boolean('is_n_plus_one')->default(false);
            $table->integer('n_plus_one_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index('request_id');
            $table->index('created_at');
            $table->index(['type', 'created_at']);
            $table->index(['is_slow', 'created_at']);
            $table->index(['sql_hash', 'created_at']);

            $table->foreign('request_id')
                ->references('id')
                ->on($prefix . 'requests')
                ->onDelete('cascade');
        });

        // Pre-computed hourly/daily stats for trend charts
        Schema::connection($connection)->create($prefix . 'aggregates', function (Blueprint $table) {
            $table->id();
            $table->string('period_type', 10); // 'hour' or 'day'
            $table->timestamp('period_start');
            $table->integer('total_queries')->default(0);
            $table->integer('slow_queries')->default(0);
            $table->float('avg_time', 12, 6)->default(0);
            $table->float('p50_time', 12, 6)->default(0);
            $table->float('p95_time', 12, 6)->default(0);
            $table->float('p99_time', 12, 6)->default(0);
            $table->float('max_time', 12, 6)->default(0);
            $table->float('min_time', 12, 6)->default(0);
            $table->float('total_time', 12, 6)->default(0);
            $table->integer('issue_count')->default(0);
            $table->integer('n_plus_one_count')->default(0);
            $table->json('type_breakdown')->nullable();
            $table->json('performance_breakdown')->nullable();
            $table->timestamps();

            $table->unique(['period_type', 'period_start']);
            $table->index(['period_type', 'period_start']);
        });

        // Alert configuration
        Schema::connection($connection)->create($prefix . 'alerts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('type', 50); // 'slow_query', 'threshold', 'n_plus_one', 'error_rate'
            $table->boolean('enabled')->default(true);
            $table->json('conditions'); // {"threshold": 1000, "operator": ">", "metric": "time"}
            $table->json('channels'); // ["log", "mail", "slack"]
            $table->integer('cooldown_minutes')->default(5);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->timestamps();

            $table->index('enabled');
            $table->index('type');
        });

        // Triggered alert history
        Schema::connection($connection)->create($prefix . 'alert_logs', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->unsignedBigInteger('alert_id');
            $table->uuid('query_id')->nullable();
            $table->string('alert_type', 50);
            $table->string('alert_name', 255);
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('notified_channels')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('alert_id');
            $table->index('created_at');
            $table->index('query_id');

            $table->foreign('alert_id')
                ->references('id')
                ->on($prefix . 'alerts')
                ->onDelete('cascade');
        });

        // Pre-computed rankings
        Schema::connection($connection)->create($prefix . 'top_queries', function (Blueprint $table) {
            $table->id();
            $table->string('ranking_type', 30); // 'slowest', 'most_frequent', 'most_issues'
            $table->string('period', 10); // 'hour', 'day', 'week'
            $table->timestamp('period_start');
            $table->string('sql_hash', 64);
            $table->text('sql_sample');
            $table->integer('count')->default(0);
            $table->float('avg_time', 12, 6)->default(0);
            $table->float('max_time', 12, 6)->default(0);
            $table->float('total_time', 12, 6)->default(0);
            $table->integer('issue_count')->default(0);
            $table->integer('rank')->default(0);
            $table->timestamps();

            $table->index(['ranking_type', 'period', 'period_start', 'rank']);
            $table->index(['sql_hash', 'period_start']);
        });
    }

    public function down(): void
    {
        $prefix = config('query-analyzer.storage.table_prefix', 'query_analyzer_');
        $connection = config('query-analyzer.storage.connection');

        Schema::connection($connection)->dropIfExists($prefix . 'top_queries');
        Schema::connection($connection)->dropIfExists($prefix . 'alert_logs');
        Schema::connection($connection)->dropIfExists($prefix . 'alerts');
        Schema::connection($connection)->dropIfExists($prefix . 'aggregates');
        Schema::connection($connection)->dropIfExists($prefix . 'queries');
        Schema::connection($connection)->dropIfExists($prefix . 'requests');
    }
};
