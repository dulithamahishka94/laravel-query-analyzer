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

        // Transaction tracking table
        if (!Schema::connection($connection)->hasTable($prefix . 'transactions')) {
            Schema::connection($connection)->create($prefix . 'transactions', function (Blueprint $table) use ($prefix) {
                $table->uuid('id')->primary();
                $table->string('connection', 64)->default('default');
                $table->double('started_at');
                $table->double('ended_at')->nullable();
                $table->float('duration_ms', 12, 3)->default(0);
                $table->string('status', 20)->default('active'); // active, committed, rolled_back
                $table->unsignedSmallInteger('nesting_depth')->default(1);
                $table->unsignedInteger('query_count')->default(0);
                $table->uuid('request_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('request_id', 'txn_request_id_idx');
                $table->index('status', 'txn_status_idx');
                $table->index('created_at', 'txn_created_at_idx');
                $table->index(['connection', 'created_at'], 'txn_conn_dt_idx');
                $table->index(['status', 'created_at'], 'txn_status_dt_idx');

                $table->foreign('request_id')
                    ->references('id')
                    ->on($prefix . 'requests')
                    ->onDelete('set null');
            });
        }

        // Add transaction_id to queries table if it does not already exist
        if (Schema::connection($connection)->hasTable($prefix . 'queries')
            && !Schema::connection($connection)->hasColumn($prefix . 'queries', 'transaction_id')
        ) {
            Schema::connection($connection)->table($prefix . 'queries', function (Blueprint $table) {
                $table->uuid('transaction_id')->nullable()->after('request_id');
                $table->index('transaction_id', 'q_transaction_id_idx');
            });
        }
    }

    public function down(): void
    {
        $prefix = config('query-lens.storage.table_prefix', 'query_lens_');
        $connection = config('query-lens.storage.connection');

        // Remove transaction_id from queries table
        if (Schema::connection($connection)->hasTable($prefix . 'queries')
            && Schema::connection($connection)->hasColumn($prefix . 'queries', 'transaction_id')
        ) {
            Schema::connection($connection)->table($prefix . 'queries', function (Blueprint $table) {
                $table->dropIndex('q_transaction_id_idx');
                $table->dropColumn('transaction_id');
            });
        }

        Schema::connection($connection)->dropIfExists($prefix . 'transactions');
    }
};
