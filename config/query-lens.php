<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Lens Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether Query Lens is enabled. When enabled,
    | all database queries will be recorded and analyzed for performance
    | issues and optimization opportunities.
    |
    */
    'enabled' => env('QUERY_LENS_ENABLED', false),
    'store' => env('CACHE_STORE', null),

    /*
    |--------------------------------------------------------------------------
    | Web UI Settings
    |--------------------------------------------------------------------------
    |
    | Configure the web-based dashboard for viewing query analysis results.
    | The web UI provides a real-time interface.
    |
    */
    'web_ui' => [
        'enabled' => env('QUERY_LENS_WEB_UI_ENABLED', true),
        'allowed_ips' => ['127.0.0.1', '::1'], // Only allow local access by default
        'auth_callback' => null, // Custom authentication callback
        'auth_gate' => env('QUERY_LENS_AUTH_GATE', null), // Laravel Gate name (null = disabled)
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Thresholds
    |--------------------------------------------------------------------------
    |
    | These thresholds define what constitutes fast, moderate, slow, and very
    | slow queries. Times are in seconds. Queries exceeding these thresholds
    | will be flagged in the analysis.
    |
    */
    'performance_thresholds' => [
        'fast' => 0.1,      // Queries under 100ms
        'moderate' => 0.5,  // Queries under 500ms
        'slow' => 1.0,      // Queries under 1 second
        // Anything above 1 second is considered very slow
    ],

    /*
    |--------------------------------------------------------------------------
    | Analysis Settings
    |--------------------------------------------------------------------------
    |
    | Configure various aspects of the query analysis behavior.
    |
    */
    /*
    |--------------------------------------------------------------------------
    | Sampling Rate
    |--------------------------------------------------------------------------
    |
    | Controls what fraction of requests have their queries recorded.
    | 1.0 = record every request (default), 0.1 = record 10% of requests,
    | 0.0 = disable recording entirely. The decision is made once per
    | request so all queries within a sampled request are captured.
    |
    */
    'sampling_rate' => env('QUERY_LENS_SAMPLING_RATE', 1.0),

    'analysis' => [
        // Maximum number of queries to keep in memory
        'max_queries' => env('QUERY_LENS_MAX_QUERIES', 1000),

        // Whether to analyze queries in real-time or defer to command
        'real_time_analysis' => env('QUERY_LENS_REAL_TIME', true),

        // Minimum execution time to record a query (in seconds)
        'min_execution_time' => env('QUERY_LENS_MIN_TIME', 0.0),
    ],

    /*
    |--------------------------------------------------------------------------
    | Origin Tracing
    |--------------------------------------------------------------------------
    |
    | Controls how query origin (file/line) is determined via debug_backtrace.
    | Disable in production for maximum performance. The backtrace_limit caps
    | stack depth to prevent expensive full-stack walks on every query.
    |
    */
    'trace_origins' => env('QUERY_LENS_TRACE_ORIGINS', true),
    'backtrace_limit' => env('QUERY_LENS_BACKTRACE_LIMIT', 30),

    /*
    |--------------------------------------------------------------------------
    | Complexity Scoring
    |--------------------------------------------------------------------------
    |
    | Weights used to calculate query complexity scores.
    |
    */
    'complexity_weights' => [
        'joins' => 2,
        'subqueries' => 3,
        'conditions' => 1,
        'order_by' => 1,
        'group_by' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Patterns
    |--------------------------------------------------------------------------
    |
    | SQL patterns to exclude from analysis. Useful for ignoring framework
    | queries or migration queries that you don't want to analyze.
    |
    */
    'excluded_patterns' => [
        'SHOW TABLES',
        'DESCRIBE',
        'EXPLAIN',
        'SELECT VERSION()',
        'migrations',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configure how query data should be stored.
    |
    | Drivers:
    | - 'cache': Uses Laravel cache (default, no persistence across restarts)
    | - 'database': Persists to database tables (requires migration)
    |
    */
    'storage' => [
        'driver' => env('QUERY_LENS_STORAGE', 'database'), // 'cache' or 'database'
        'connection' => env('QUERY_LENS_DB_CONNECTION', null), // null = default connection
        'table_prefix' => 'query_lens_',
        'retention_days' => env('QUERY_LENS_RETENTION_DAYS', 365),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aggregation Settings
    |--------------------------------------------------------------------------
    |
    | Configure automatic aggregation of query statistics for performance
    | trends and top queries rankings.
    |
    */
    'aggregation' => [
        'enabled' => env('QUERY_LENS_AGGREGATION', true),
        'schedule' => 'hourly', // Run via Laravel scheduler
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Settings
    |--------------------------------------------------------------------------
    |
    | Configure when to trigger alerts for slow or problematic queries.
    | Alerts can be sent via multiple channels.
    |
    */
    'alerts' => [
        'enabled' => env('QUERY_LENS_ALERTS', false),
        'channels' => ['log'], // Available: log, mail, slack
        'slack_webhook' => env('QUERY_LENS_SLACK_WEBHOOK'),
        'mail_to' => env('QUERY_LENS_MAIL_TO'),
        'cooldown_minutes' => 5, // Minimum time between same alert triggers
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Settings
    |--------------------------------------------------------------------------
    |
    | Configure the dashboard UI behavior and defaults.
    |
    */
    'dashboard' => [
        'poll_interval' => env('QUERY_LENS_POLL_INTERVAL', 5000), // milliseconds
        'default_period' => '24h',
        'max_queries_display' => 100,
        'enable_trends' => true,
        'enable_top_queries' => true,
        'enable_alerts_panel' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Regression Detection
    |--------------------------------------------------------------------------
    |
    | Configure regression detection thresholds and webhook notifications.
    | Use with the query-lens:check-regression artisan command for CI/CD.
    |
    */
    'regression' => [
        'webhook_url' => env('QUERY_LENS_REGRESSION_WEBHOOK_URL'),
        'threshold' => env('QUERY_LENS_REGRESSION_THRESHOLD', 0.2),
        'headers' => [],
    ],
];
