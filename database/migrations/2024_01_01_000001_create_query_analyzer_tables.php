<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('query-lens.storage.table_prefix', 'query_lens_');
        $connection = config('query-lens.storage.connection');

        // Main requests table - HTTP request aggregations
        if (!Schema::connection($connection)->hasTable($prefix . 'requests')) {
            Schema::connection($connection)->create($prefix . 'requests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('method', 10)->default('GET');
                $table->string('path', 700)->nullable();
                $table->string('route_name', 255)->nullable();
                $table->integer('query_count')->default(0);
                $table->integer('slow_count')->default(0);
                $table->float('total_time', 12, 6)->default(0);
                $table->float('avg_time', 12, 6)->default(0);
                $table->float('max_time', 12, 6)->default(0);
                $table->integer('issue_count')->default(0);
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('created_at', 'req_created_at_idx');
                $table->index(['method', 'path'], 'req_method_path_idx');
            });
        }

        // Main fact table - stores all captured queries with analysis
        if (!Schema::connection($connection)->hasTable($prefix . 'queries')) {
            Schema::connection($connection)->create($prefix . 'queries', function (Blueprint $table) use ($prefix) {
                $table->uuid('id')->primary();
                $table->uuid('request_id')->nullable();
                $table->string('sql_hash', 64)->index('q_sql_hash_idx');
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

                $table->index('request_id', 'q_request_id_idx');
                $table->index('created_at', 'q_created_at_idx');
                $table->index(['type', 'created_at'], 'q_type_dt_idx');
                $table->index(['is_slow', 'created_at'], 'q_is_slow_dt_idx');
                $table->index(['sql_hash', 'created_at'], 'q_hash_dt_idx');

                $table->foreign('request_id')
                    ->references('id')
                    ->on($prefix . 'requests')
                    ->onDelete('cascade');
            });
        }

        // Pre-computed hourly/daily stats for trend charts
        if (!Schema::connection($connection)->hasTable($prefix . 'aggregates')) {
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

                $table->unique(['period_type', 'period_start'], 'agg_unique');
                $table->index(['period_type', 'period_start'], 'agg_period_idx');
            });
        }

        // Alert configuration
        if (!Schema::connection($connection)->hasTable($prefix . 'alerts')) {
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

                $table->index('enabled', 'alerts_enabled_idx');
                $table->index('type', 'alerts_type_idx');
            });
        }

        // Triggered alert history
        if (!Schema::connection($connection)->hasTable($prefix . 'alert_logs')) {
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

                $table->index('alert_id', 'al_alert_id_idx');
                $table->index('created_at', 'al_created_at_idx');
                $table->index('query_id', 'al_query_id_idx');

                $table->foreign('alert_id')
                    ->references('id')
                    ->on($prefix . 'alerts')
                    ->onDelete('cascade');
            });
        }

        // Pre-computed rankings
        if (!Schema::connection($connection)->hasTable($prefix . 'top_queries')) {
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

                $table->index(['ranking_type', 'period', 'period_start', 'rank'], 'tq_rank_idx');
                $table->index(['sql_hash', 'period_start'], 'tq_hash_period_idx');
            });
        }
    }

    public function down(): void
    {
        $prefix = config('query-lens.storage.table_prefix', 'query_lens_');
        $connection = config('query-lens.storage.connection');

        Schema::connection($connection)->dropIfExists($prefix . 'top_queries');
        Schema::connection($connection)->dropIfExists($prefix . 'alert_logs');
        Schema::connection($connection)->dropIfExists($prefix . 'alerts');
        Schema::connection($connection)->dropIfExists($prefix . 'aggregates');
        Schema::connection($connection)->dropIfExists($prefix . 'queries');
        Schema::connection($connection)->dropIfExists($prefix . 'requests');
    }
};
