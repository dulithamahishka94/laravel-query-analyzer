# Query Lens Documentation

A powerful Laravel package for analyzing, profiling, and optimizing database queries with real-time insights and performance monitoring.

---

## Table of Contents

- [Introduction](#introduction)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
  - [Basic Configuration](#basic-configuration)
  - [Environment Variables](#environment-variables)
  - [Performance Thresholds](#performance-thresholds)
  - [Storage Options](#storage-options)
  - [Alert Configuration](#alert-configuration)
- [Dashboard](#dashboard)
  - [Accessing the Dashboard](#accessing-the-dashboard)
  - [Dashboard Features](#dashboard-features)
  - [Security & Access Control](#security--access-control)
- [Features](#features)
  - [Query Analysis](#query-analysis)
  - [Performance Monitoring](#performance-monitoring)
  - [N+1 Detection](#n1-detection)
  - [EXPLAIN Analysis](#explain-analysis)
  - [Request Waterfall](#request-waterfall)
  - [Performance Trends](#performance-trends)
  - [Top Queries](#top-queries)
- [Storage Drivers](#storage-drivers)
  - [Cache Driver](#cache-driver)
  - [Database Driver](#database-driver)
- [Alerts](#alerts)
  - [Alert Types](#alert-types)
  - [Notification Channels](#notification-channels)
  - [Creating Alerts](#creating-alerts)
- [Artisan Commands](#artisan-commands)
- [Facade Usage](#facade-usage)
- [API Reference](#api-reference)
- [Customization](#customization)
  - [Custom Authentication](#custom-authentication)
  - [Excluding Queries](#excluding-queries)
- [Troubleshooting](#troubleshooting)
- [Upgrading](#upgrading)

---

## Introduction

Query Lens is a comprehensive database query analyzer for Laravel applications. It provides real-time monitoring, performance insights, and optimization recommendations for your database queries.

### Key Features

- **Real-time Query Monitoring** - Watch queries as they execute with live updates
- **Performance Analysis** - Automatic classification of queries by execution time
- **N+1 Detection** - Automatically detect and flag N+1 query problems
- **EXPLAIN Analysis** - Run and interpret MySQL/PostgreSQL EXPLAIN plans
- **Request Waterfall** - Visualize query timing within HTTP requests
- **Performance Trends** - Track P50, P95, P99 latency over time
- **Configurable Alerts** - Get notified about slow queries and issues
- **Origin Tracking** - See exactly where queries originate in your code
- **Export Capabilities** - Export query data for further analysis

---

## Requirements

- PHP 8.1 or higher
- Laravel 9.x, 10.x, 11.x, or 12.x
- MySQL 5.7+ / PostgreSQL 10+ / SQLite 3.8+

---

## Installation

Install the package via Composer:

```bash
composer require coderflex/query-lens
```

The package will automatically register its service provider.

### Publishing Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=query-lens-config
```

### Publishing Migrations (Optional)

If you want to use database storage for persistence:

```bash
php artisan vendor:publish --tag=query-lens-migrations
php artisan migrate
```

---

## Configuration

### Basic Configuration

After publishing, the configuration file will be located at `config/query-lens.php`.

Enable Query Lens in your `.env` file:

```env
QUERY_LENS_ENABLED=true
```

### Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `QUERY_LENS_ENABLED` | `false` | Enable/disable query monitoring |
| `QUERY_LENS_WEB_UI_ENABLED` | `true` | Enable/disable the web dashboard |
| `QUERY_LENS_STORAGE` | `cache` | Storage driver (`cache` or `database`) |
| `QUERY_LENS_DB_CONNECTION` | `null` | Database connection for storage |
| `QUERY_LENS_RETENTION_DAYS` | `7` | Days to retain data (database driver) |
| `QUERY_LENS_MAX_QUERIES` | `1000` | Maximum queries to keep in memory |
| `QUERY_LENS_ALERTS` | `false` | Enable alert system |
| `QUERY_LENS_POLL_INTERVAL` | `5000` | Dashboard polling interval (ms) |

### Performance Thresholds

Configure what constitutes slow queries in `config/query-lens.php`:

```php
'performance_thresholds' => [
    'fast' => 0.1,      // Under 100ms - Good
    'moderate' => 0.5,  // Under 500ms - Acceptable
    'slow' => 1.0,      // Under 1 second - Needs attention
    // Above 1 second is considered "very slow"
],
```

### Storage Options

#### Cache Storage (Default)

Best for development and debugging. Data is stored in Laravel's cache and doesn't persist across cache clears.

```php
'storage' => [
    'driver' => 'cache',
],
```

#### Database Storage

Best for production monitoring. Provides persistence, historical data, and enables aggregations.

```php
'storage' => [
    'driver' => 'database',
    'connection' => null, // Uses default connection
    'table_prefix' => 'query_lens_',
    'retention_days' => 7,
],
```

### Alert Configuration

```php
'alerts' => [
    'enabled' => true,
    'channels' => ['log', 'slack'], // Available: log, mail, slack
    'slack_webhook' => env('QUERY_LENS_SLACK_WEBHOOK'),
    'mail_to' => env('QUERY_LENS_MAIL_TO'),
    'cooldown_minutes' => 5,
],
```

---

## Dashboard

### Accessing the Dashboard

Once enabled, access the dashboard at:

```
https://your-app.com/query-lens
```

### Dashboard Features

#### Overview Stats
Four key metrics displayed at the top:
- **Total Queries** - Count of queries in the selected period
- **Slow Queries** - Queries exceeding the slow threshold
- **Average Time** - Mean execution time
- **P95 Time** - 95th percentile latency

#### Request Sidebar
Browse HTTP requests with:
- Request method and path
- Query count per request
- Total query time
- Timestamp

#### Query List
For each request, view:
- Query type badges (SELECT, INSERT, UPDATE, DELETE)
- Source indicator (App vs Vendor code)
- Issue badges (N+1, Slow, Security)
- Execution time
- Origin file and line number

#### Query Details Panel
Click any query to see:
- Full SQL statement
- Bound parameters
- Performance rating
- Complexity analysis
- Issues detected
- Recommendations
- Origin stack trace

#### Waterfall View
Visualize query timing within a request:
- Timeline showing query sequence
- Duration bars scaled to request time
- Color-coded by query type
- Click to view query details

#### Trends Tab
Performance trends over time:
- P50, P95, P99 latency charts
- Configurable time granularity (hourly/daily)
- Period selection (1 hour, 24 hours, 7 days)

#### Top Queries Tab
Rankings of problematic queries:
- **Slowest** - Highest average execution time
- **Most Frequent** - Most executed queries
- **Most Issues** - Queries with most detected problems

#### Alerts Tab
Manage alert configurations:
- View active alerts
- Create new alerts
- Toggle alerts on/off
- View triggered alert history

### Security & Access Control

By default, the dashboard is only accessible from localhost:

```php
'web_ui' => [
    'enabled' => true,
    'allowed_ips' => ['127.0.0.1', '::1'],
    'auth_callback' => null,
],
```

#### Custom Authentication

Provide a callback for custom access control:

```php
'web_ui' => [
    'auth_callback' => function ($request) {
        // Allow only admin users
        return auth()->check() && auth()->user()->isAdmin();
    },
],
```

#### IP Whitelist

Allow specific IP addresses:

```php
'web_ui' => [
    'allowed_ips' => [
        '127.0.0.1',
        '::1',
        '192.168.1.100',
        '10.0.0.*', // Wildcard supported
    ],
],
```

---

## Features

### Query Analysis

Every captured query is automatically analyzed for:

- **Query Type** - SELECT, INSERT, UPDATE, DELETE, or other
- **Complexity Score** - Based on joins, subqueries, conditions
- **Performance Rating** - fast, moderate, slow, or very_slow
- **Potential Issues** - Security risks, performance problems
- **Recommendations** - Actionable optimization suggestions

### Performance Monitoring

Queries are categorized by execution time:

| Rating | Threshold | Description |
|--------|-----------|-------------|
| Fast | < 100ms | Optimal performance |
| Moderate | < 500ms | Acceptable |
| Slow | < 1000ms | Needs optimization |
| Very Slow | > 1000ms | Critical attention required |

### N+1 Detection

Query Lens automatically detects N+1 query patterns by:

1. Tracking similar queries within the same request
2. Identifying repeated queries with different parameters
3. Flagging when the same query pattern executes 5+ times

When detected, you'll see:
- Purple "N+1" badge on affected queries
- Recommendation to use eager loading
- Count of duplicate query executions

### EXPLAIN Analysis

Run EXPLAIN on any SELECT query directly from the dashboard:

1. Click on a query to open details
2. Click "Run EXPLAIN" button
3. View interpreted results with:
   - Access type analysis
   - Index usage information
   - Row estimation accuracy
   - Optimization suggestions

Supported insights:
- Full table scans
- Missing indexes
- Filesort operations
- Temporary tables
- Inefficient joins

### Request Waterfall

The waterfall view shows query timing within a request:

```
Request: GET /api/users (Total: 245ms)

#  Type    Timeline                Duration   Query
1  SELECT  ████                    12.5ms     select * from users...
2  SELECT    ██                    8.2ms      select * from roles...
3  SELECT      ████████            45.3ms     select * from posts...
4  UPDATE          ██              5.1ms      update users set...
```

Features:
- Visual timeline bars
- Color-coded by query type
- Slow queries highlighted in red
- Click to view full query details

### Performance Trends

Track performance over time with:

- **P50 Latency** - Median query time
- **P95 Latency** - 95th percentile
- **P99 Latency** - 99th percentile
- **Throughput** - Queries per period

Granularity options:
- Hourly (for 1h and 24h views)
- Daily (for 7d view)

### Top Queries

Three ranking views to identify problems:

#### Slowest Queries
Ranked by average execution time. Helps identify:
- Queries needing optimization
- Missing indexes
- Inefficient joins

#### Most Frequent
Ranked by execution count. Helps identify:
- Caching opportunities
- N+1 patterns
- Hot paths in your application

#### Most Issues
Ranked by detected problems. Helps prioritize:
- Security vulnerabilities
- Performance anti-patterns
- Code quality improvements

---

## Storage Drivers

### Cache Driver

The default driver stores queries in Laravel's cache.

**Pros:**
- Zero configuration
- Fast read/write
- Good for development

**Cons:**
- Data lost on cache clear
- No historical persistence
- Limited to cache TTL (1 hour default)

**Configuration:**
```php
'storage' => [
    'driver' => 'cache',
],

// Optionally specify cache store
'store' => 'redis', // Uses default if null
```

### Database Driver

Persists data to database tables for production use.

**Pros:**
- Persistent storage
- Historical data retention
- Supports aggregations
- Better for production

**Cons:**
- Requires migration
- Additional database overhead
- Needs periodic cleanup

**Setup:**

1. Set the driver:
```env
QUERY_LENS_STORAGE=database
```

2. Run migrations:
```bash
php artisan migrate
```

**Tables Created:**

| Table | Purpose |
|-------|---------|
| `query_lens_queries` | Individual query records |
| `query_lens_requests` | HTTP request summaries |
| `query_lens_aggregates` | Pre-computed statistics |
| `query_lens_alerts` | Alert configurations |
| `query_lens_alert_logs` | Triggered alert history |
| `query_lens_top_queries` | Ranking caches |

**Data Retention:**

Configure automatic cleanup:
```php
'storage' => [
    'retention_days' => 7, // Keep 7 days of data
],
```

Schedule the cleanup command:
```php
// app/Console/Kernel.php
$schedule->command('query-lens:prune')->daily();
```

---

## Alerts

### Alert Types

#### Slow Query Alert
Triggers when a single query exceeds a time threshold.

```php
[
    'type' => 'slow_query',
    'conditions' => [
        'time' => ['operator' => '>', 'value' => 1.0], // > 1 second
    ],
]
```

#### N+1 Alert
Triggers when N+1 patterns are detected.

```php
[
    'type' => 'n_plus_one',
    'conditions' => [
        'min_count' => 5, // Minimum duplicate queries
    ],
]
```

#### Threshold Alert
Triggers based on aggregate metrics.

```php
[
    'type' => 'threshold',
    'conditions' => [
        'metric' => 'avg_time',
        'operator' => '>',
        'value' => 0.5, // Average > 500ms
        'period' => 'hour',
    ],
]
```

#### Error Rate Alert
Triggers when issue detection rate is high.

```php
[
    'type' => 'error_rate',
    'conditions' => [
        'threshold' => 0.1, // 10% of queries have issues
    ],
]
```

### Notification Channels

#### Log Channel
Writes alerts to Laravel's log:
```php
'channels' => ['log'],
```

#### Mail Channel
Sends email notifications:
```php
'channels' => ['mail'],
'mail_to' => 'alerts@yourapp.com',
```

#### Slack Channel
Posts to Slack webhook:
```php
'channels' => ['slack'],
'slack_webhook' => 'https://hooks.slack.com/services/...',
```

### Creating Alerts

#### Via Dashboard
1. Navigate to Alerts tab
2. Click "Create Alert"
3. Configure type and conditions
4. Save

#### Via API
```bash
POST /query-lens/api/v2/alerts
Content-Type: application/json

{
    "name": "Slow Query Alert",
    "type": "slow_query",
    "conditions": {
        "time": {"operator": ">", "value": 1.0}
    },
    "channels": ["log", "slack"],
    "is_enabled": true
}
```

---

## Artisan Commands

### Aggregate Statistics

Pre-compute hourly/daily statistics for trends:

```bash
php artisan query-lens:aggregate
```

Options:
- `--hourly` - Run hourly aggregation only
- `--daily` - Run daily aggregation only
- `--date=YYYY-MM-DD` - Aggregate specific date

Schedule for automatic aggregation:
```php
$schedule->command('query-lens:aggregate --hourly')->hourly();
$schedule->command('query-lens:aggregate --daily')->daily();
```

### Prune Old Data

Clean up old query data:

```bash
php artisan query-lens:prune
```

Options:
- `--days=7` - Override retention days
- `--force` - Skip confirmation

Schedule for automatic cleanup:
```php
$schedule->command('query-lens:prune')->daily();
```

### Analyze Queries

Run analysis on captured queries:

```bash
php artisan query:analyze
```

Options:
- `--slow` - Show only slow queries
- `--limit=50` - Limit results
- `--export=file.json` - Export to file

---

## Facade Usage

Use the facade for programmatic access:

```php
use Coderflex\QueryLens\Facades\QueryLens;

// Record a query manually
QueryLens::recordQuery(
    sql: 'SELECT * FROM users WHERE id = ?',
    bindings: [1],
    time: 0.045,
    connection: 'mysql'
);

// Analyze a query
$analysis = QueryLens::analyzeQuery(
    'SELECT * FROM users WHERE name LIKE ?',
    ['%john%'],
    0.250
);

// Get all recorded queries
$queries = QueryLens::getQueries();

// Get statistics
$stats = QueryLens::getStats();

// Reset/clear all queries
QueryLens::reset();
```

---

## API Reference

All API endpoints are prefixed with `/query-lens/api`.

### Legacy API (v1)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/requests` | List HTTP requests |
| GET | `/queries` | List queries with filters |
| GET | `/query/{id}` | Get single query details |
| GET | `/stats` | Get summary statistics |
| POST | `/reset` | Clear all query data |
| POST | `/analyze` | Analyze a SQL string |
| POST | `/explain` | Run EXPLAIN on a query |
| POST | `/export` | Export queries to JSON |

### Enhanced API (v2)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v2/trends` | Get performance trend data |
| GET | `/v2/top-queries` | Get top queries by type |
| GET | `/v2/request/{id}/waterfall` | Get request waterfall data |
| GET | `/v2/stats/overview` | Get overview statistics |
| GET | `/v2/poll` | Poll for new queries |
| GET | `/v2/requests` | List requests (enhanced) |
| GET | `/v2/storage` | Get storage driver info |

### Alerts API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/v2/alerts` | List all alerts |
| POST | `/v2/alerts` | Create new alert |
| GET | `/v2/alerts/{id}` | Get alert details |
| PUT | `/v2/alerts/{id}` | Update alert |
| DELETE | `/v2/alerts/{id}` | Delete alert |
| POST | `/v2/alerts/{id}/toggle` | Toggle alert status |
| GET | `/v2/alerts/{id}/logs` | Get alert trigger logs |

### Query Parameters

#### Filtering Queries
```
GET /query-lens/api/queries?type=SELECT&issue_type=n_plus_one
```

| Parameter | Values | Description |
|-----------|--------|-------------|
| `type` | SELECT, INSERT, UPDATE, DELETE | Filter by query type |
| `issue_type` | n_plus_one, slow, security | Filter by issue |
| `sort` | time, timestamp, complexity | Sort field |
| `order` | asc, desc | Sort direction |
| `limit` | integer | Max results |

#### Trends Parameters
```
GET /query-lens/api/v2/trends?start=2024-01-01&end=2024-01-07&granularity=day
```

| Parameter | Values | Description |
|-----------|--------|-------------|
| `start` | ISO 8601 date | Period start |
| `end` | ISO 8601 date | Period end |
| `granularity` | hour, day | Data granularity |

#### Top Queries Parameters
```
GET /query-lens/api/v2/top-queries?type=slowest&period=day&limit=10
```

| Parameter | Values | Description |
|-----------|--------|-------------|
| `type` | slowest, most_frequent, most_issues | Ranking type |
| `period` | hour, day, week | Time period |
| `limit` | integer | Max results |

---

## Customization

### Custom Authentication

Implement custom access control:

```php
// config/query-lens.php
'web_ui' => [
    'auth_callback' => function ($request) {
        // Check for specific header
        if ($request->header('X-Query-Lens-Key') === config('app.query_lens_key')) {
            return true;
        }

        // Check authenticated user
        if (auth()->check()) {
            return auth()->user()->can('view-query-lens');
        }

        return false;
    },
],
```

### Excluding Queries

Prevent certain queries from being recorded:

```php
// config/query-lens.php
'excluded_patterns' => [
    'SHOW TABLES',
    'DESCRIBE',
    'EXPLAIN',
    'SELECT VERSION()',
    'migrations',
    'sessions',           // Exclude session queries
    'jobs',               // Exclude job queries
    'telescope_entries',  // Exclude Telescope
    'pulse_',             // Exclude Pulse
],
```

### Minimum Execution Time

Only record queries above a threshold:

```php
'analysis' => [
    'min_execution_time' => 0.01, // Only queries > 10ms
],
```

### Custom Complexity Weights

Adjust how complexity is calculated:

```php
'complexity_weights' => [
    'joins' => 3,      // Joins are expensive
    'subqueries' => 5, // Subqueries are very expensive
    'conditions' => 1, // WHERE clauses
    'order_by' => 2,   // Sorting
    'group_by' => 2,   // Grouping
],
```

---

## Troubleshooting

### Dashboard Not Loading

1. **Check if enabled:**
   ```env
   QUERY_LENS_ENABLED=true
   QUERY_LENS_WEB_UI_ENABLED=true
   ```

2. **Check IP whitelist:**
   Ensure your IP is in `allowed_ips` or add a custom `auth_callback`.

3. **Clear route cache:**
   ```bash
   php artisan route:clear
   ```

### No Queries Appearing

1. **Verify enabled:**
   ```php
   dd(config('query-lens.enabled')); // Should be true
   ```

2. **Check middleware:**
   Query Lens automatically adds middleware to `web` and `api` groups.

3. **Check excluded patterns:**
   Your queries might match an excluded pattern.

### High Memory Usage

1. **Reduce max queries:**
   ```php
   'analysis' => [
       'max_queries' => 500, // Lower limit
   ],
   ```

2. **Use database storage:**
   Database driver is more memory efficient for high-volume apps.

3. **Increase min execution time:**
   ```php
   'analysis' => [
       'min_execution_time' => 0.01, // Skip fast queries
   ],
   ```

### Trends Not Showing

1. **For cache driver:** Trends require recent data; they compute on-the-fly.

2. **For database driver:** Run aggregation command:
   ```bash
   php artisan query-lens:aggregate
   ```

3. **Check time range:** Ensure selected period has data.

### Performance Impact

Query Lens is designed for minimal overhead, but in high-traffic production:

1. **Use database driver** for better performance
2. **Increase min_execution_time** to skip trivial queries
3. **Reduce poll_interval** in dashboard
4. **Schedule aggregations** during off-peak hours

---

## Upgrading

### From Laravel Query Analyzer

If upgrading from the previous `laravel/query-analyzer` package:

1. **Update composer.json:**
   ```json
   "require": {
       "coderflex/query-lens": "^1.0"
   }
   ```

2. **Update namespace imports:**
   ```php
   // Old
   use Laravel\QueryAnalyzer\Facades\QueryAnalyzer;

   // New
   use Coderflex\QueryLens\Facades\QueryLens;
   ```

3. **Update environment variables:**
   ```env
   # Old
   QUERY_ANALYZER_ENABLED=true

   # New
   QUERY_LENS_ENABLED=true
   ```

4. **Update config file:**
   ```bash
   # Republish config
   php artisan vendor:publish --tag=query-lens-config --force
   ```

5. **Update database tables (if using database driver):**
   ```bash
   # Publish and run new migrations
   php artisan vendor:publish --tag=query-lens-migrations
   php artisan migrate
   ```

6. **Update route references:**
   ```
   /query-analyzer → /query-lens
   ```

### Backwards Compatibility

The `QueryAnalyzer` facade is still available as a deprecated alias:
```php
use Coderflex\QueryLens\Facades\QueryAnalyzer; // Works but deprecated
```

---

## License

Query Lens is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

## Support

- **GitHub Issues:** [Report bugs and feature requests](https://github.com/coderflex/query-lens/issues)
- **Documentation:** [https://query-lens.coderflex.com](https://query-lens.coderflex.com)

---

## Credits

Query Lens is developed and maintained by [Coderflex](https://coderflex.com).

Inspired by:
- Laravel Telescope
- Laravel Pulse
- Clockwork
- Laravel Debugbar
