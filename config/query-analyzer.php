<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Analyzer Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether the query analyzer is enabled. When enabled,
    | all database queries will be recorded and analyzed for performance
    | issues and optimization opportunities.
    |
    */
    'enabled' => env('QUERY_ANALYZER_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Web UI Settings
    |--------------------------------------------------------------------------
    |
    | Configure the web-based dashboard for viewing query analysis results.
    | The web UI provides a real-time interface similar to Laravel Clockwork.
    |
    */
    'web_ui' => [
        'enabled' => env('QUERY_ANALYZER_WEB_UI_ENABLED', true),
        'allowed_ips' => ['127.0.0.1', '::1'], // Only allow local access by default
        'auth_callback' => null, // Custom authentication callback
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
    'analysis' => [
        // Maximum number of queries to keep in memory
        'max_queries' => env('QUERY_ANALYZER_MAX_QUERIES', 1000),

        // Whether to analyze queries in real-time or defer to command
        'real_time_analysis' => env('QUERY_ANALYZER_REAL_TIME', true),

        // Minimum execution time to record a query (in seconds)
        'min_execution_time' => env('QUERY_ANALYZER_MIN_TIME', 0.001),
    ],

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
    | Alert Settings
    |--------------------------------------------------------------------------
    |
    | Configure when to trigger alerts for slow or problematic queries.
    |
    */
    'alerts' => [
        'enabled' => env('QUERY_ANALYZER_ALERTS', false),
        'slow_query_threshold' => 2.0, // seconds
        'channels' => ['log'], // Available: log, mail, slack
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Configure how query data should be stored.
    |
    */
    'storage' => [
        'driver' => env('QUERY_ANALYZER_STORAGE', 'memory'), // memory, database, file
        'path' => storage_path('query-analyzer'),
        'retention_days' => 7,
    ],
];