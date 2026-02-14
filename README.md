# QueryLens

**The self-hosted query performance platform for Laravel -- with AI-powered optimization, automated index recommendations, and CI pipeline regression detection.**

[![PHP Version](https://img.shields.io/packagist/php-v/gladehq/laravel-query-lens.svg?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)
[![Laravel Version](https://img.shields.io/badge/laravel-9.x%20%7C%2010.x%20%7C%2011.x%20%7C%2012.x-blue?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)
[![Tests](https://img.shields.io/badge/tests-691%20passing-brightgreen?style=flat-square)](https://github.com/dulithamahishka94/laravel-query-analyzer)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/gladehq/laravel-query-lens.svg?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)
[![License](https://img.shields.io/packagist/l/gladehq/laravel-query-lens.svg?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)

---

## Why QueryLens

- **Self-hosted.** Your query data never leaves your servers. No external SaaS dependency, no data privacy concerns.
- **Query-focused.** Not another broad monitoring tool. QueryLens is purpose-built for database query performance -- EXPLAIN analysis, index recommendations, regression detection, and AI optimization.
- **Production-ready.** Configurable sampling rate, Octane-safe architecture, Laravel Gate authentication, and per-request isolation. Designed for high-traffic applications.
- **Actionable.** Every feature produces a concrete next step -- an index to create, a query to rewrite, a regression to investigate. Not just dashboards.

---

## How QueryLens Compares

| Feature | QueryLens | Debugbar | Telescope | Pulse | Nightwatch |
|---|---|---|---|---|---|
| Deployment | Self-hosted | Self-hosted | Self-hosted | Self-hosted | Cloud |
| Price | One-time | Free | Free | Free | Subscription |
| EXPLAIN Analysis | Deep + human-readable | No | No | No | No |
| Index Recommendations | Automated | No | No | No | No |
| Regression Detection | CI-integrated | No | No | No | Limited |
| AI Query Optimization | OpenAI-compatible | No | No | No | No |
| Transaction Tracking | Full lifecycle | No | Yes | No | No |
| Filament Plugin | Native panel integration | No | No | No | No |
| Request Sampling | Configurable rate | No | Yes | Yes | N/A |
| Alerting | Log, Mail, Slack | No | No | No | Yes |
| N+1 Detection | Automatic | Yes | Yes | No | No |
| Code Origin Tracing | File + line number | Yes | Yes | No | No |
| Request Waterfall | Visual timeline | Yes | No | No | No |

---

## Features

### Dashboard Overview

Real-time query monitoring dashboard with stats overview, query list, and performance ratings. Supports live polling, search, and filtering by query type, duration, and slow status.

<!-- Screenshot: Dashboard overview showing stats cards, query table with type badges, duration, and performance ratings -->

### EXPLAIN Analysis with Human-Readable Output

Run EXPLAIN on any captured query directly from the dashboard. Results are parsed into human-readable descriptions explaining what the database engine is doing -- table scans, index usage, join strategies, and row estimates.

<!-- Screenshot: EXPLAIN modal showing raw EXPLAIN output alongside human-readable interpretation -->

### Index Recommendation Engine

Analyzes query patterns over configurable time windows and recommends specific indexes to create. Outputs ready-to-run `CREATE INDEX` statements with impact estimates based on actual query frequency and duration.

<!-- Screenshot: Index suggestion output showing recommended indexes with SQL statements and impact scores -->

### Query Regression Detection and CI Integration

Compare query performance between time periods to detect regressions. Integrates with CI/CD pipelines via the `query-lens:check-regression` command. Supports webhook notifications for automated alerting when performance degrades.

<!-- Screenshot: Regression detection output showing degraded queries with percentage changes -->

### AI-Powered Query Optimization

Submit slow queries to any OpenAI-compatible API for optimization suggestions. Returns rewritten SQL, explanation of changes, and estimated performance improvement. Results are cached to avoid redundant API calls.

<!-- Screenshot: AI optimization modal showing original query, optimized version, and explanation -->

### Transaction Tracking

Track database transaction lifecycle -- begin, commit, rollback events with duration, nesting depth, and query count. Associates queries with their enclosing transaction for debugging deadlocks and long-running transactions.

<!-- Screenshot: Transaction tracking view showing transaction timeline with nested queries -->

### Filament Panel Integration

Native Filament v3 plugin with real pages, Table Builder, Chart Widgets, and Stats Overview. Register with a single line in your panel configuration. Works alongside the standalone dashboard.

```php
use GladeHQ\QueryLens\Filament\QueryLensPlugin;

$panel->plugin(QueryLensPlugin::make());
```

<!-- Screenshot: Filament panel showing QueryLens dashboard with stats widgets and table builder -->

### Request Waterfall Visualization

Visual timeline of all queries within a single HTTP request, showing execution order, duration bars, and overlap detection. Identifies sequential queries that could be parallelized.

<!-- Screenshot: Request waterfall showing query execution timeline with duration bars -->

### Performance Trend Tracking

Track P50, P95, and P99 latency over time with configurable granularity (hourly or daily). Identify slow degradation patterns before they become incidents. Top queries ranked by total impact (frequency multiplied by duration).

<!-- Screenshot: Trends page showing latency charts and top queries table -->

---

## Quick Start

### 1. Install the package

```bash
composer require gladehq/laravel-query-lens
```

### 2. Publish configuration and migrations

```bash
php artisan vendor:publish --tag=query-lens-config
php artisan vendor:publish --tag=query-lens-migrations
```

### 3. Run migrations

```bash
php artisan migrate
```

### 4. Enable in your environment

```env
QUERY_LENS_ENABLED=true
```

### 5. Visit the dashboard

Open `/query-lens` in your browser.

---

## Configuration

The configuration file is published to `config/query-lens.php`. Key options:

### Storage Driver

```php
'storage' => [
    'driver' => env('QUERY_LENS_STORAGE', 'database'), // 'cache' or 'database'
    'connection' => env('QUERY_LENS_DB_CONNECTION', null),
    'table_prefix' => 'query_lens_',
    'retention_days' => env('QUERY_LENS_RETENTION_DAYS', 365),
],
```

- **cache** -- Ephemeral storage using Laravel's cache. No persistence across restarts. Good for development.
- **database** -- Persistent storage with full query history, aggregation, and trend tracking. Required for production features like regression detection and index recommendations.

### Sampling Rate

```php
'sampling_rate' => env('QUERY_LENS_SAMPLING_RATE', 1.0),
```

Controls what fraction of requests have their queries recorded. `1.0` records every request. `0.1` records 10%. The decision is made once per request so all queries within a sampled request are captured together.

### Authentication

```php
'web_ui' => [
    'allowed_ips' => ['127.0.0.1', '::1'],
    'auth_gate' => env('QUERY_LENS_AUTH_GATE', null),
],
```

By default, the dashboard is restricted to localhost. For production, configure a Laravel Gate:

```php
// In AuthServiceProvider
Gate::define('viewQueryLens', function ($user) {
    return $user->isAdmin();
});
```

```env
QUERY_LENS_AUTH_GATE=viewQueryLens
```

### AI Provider Setup

```php
'ai' => [
    'enabled' => env('QUERY_LENS_AI_ENABLED', false),
    'provider' => env('QUERY_LENS_AI_PROVIDER', 'openai'),
    'api_key' => env('QUERY_LENS_AI_KEY'),
    'model' => env('QUERY_LENS_AI_MODEL', 'gpt-4o-mini'),
    'endpoint' => env('QUERY_LENS_AI_ENDPOINT', 'https://api.openai.com/v1/chat/completions'),
    'cache_ttl' => 3600,
    'rate_limit' => 10,
],
```

Works with any OpenAI-compatible API endpoint. AI features are entirely optional -- the package is fully functional without them.

### Transaction Tracking

```env
QUERY_LENS_TRACK_TRANSACTIONS=true
```

### Alert Channels

```php
'alerts' => [
    'enabled' => env('QUERY_LENS_ALERTS', false),
    'channels' => ['log', 'mail', 'slack'],
    'slack_webhook' => env('QUERY_LENS_SLACK_WEBHOOK'),
    'mail_to' => env('QUERY_LENS_MAIL_TO'),
    'cooldown_minutes' => 5,
],
```

---

## Artisan Commands

### `query-lens:aggregate`

Pre-calculates hourly and daily performance aggregates for trend charts and top query rankings. Schedule this to run hourly in your `app/Console/Kernel.php`:

```php
$schedule->command('query-lens:aggregate')->hourly();
```

### `query-lens:prune`

Cleans up old query data based on the configured retention period. Schedule daily:

```php
$schedule->command('query-lens:prune')->daily();
```

### `query-lens:suggest-indexes`

Analyzes recorded query patterns and recommends database indexes:

```bash
php artisan query-lens:suggest-indexes --days=7
php artisan query-lens:suggest-indexes --sql="SELECT * FROM orders WHERE user_id = ? AND status = ?"
php artisan query-lens:suggest-indexes --format=json
```

### `query-lens:check-regression`

Detects query performance regressions by comparing current period against the previous period. Designed for CI/CD pipelines:

```bash
php artisan query-lens:check-regression --period=daily --threshold=0.2
php artisan query-lens:check-regression --webhook --format=json
```

Returns exit code `1` when regressions are detected, making it suitable for CI pipeline gates.

---

## Filament Integration

QueryLens includes a native Filament v3 plugin. Filament is optional -- the package works without it.

### Register the plugin

```php
use GladeHQ\QueryLens\Filament\QueryLensPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            QueryLensPlugin::make()
                ->dashboard()    // Query Dashboard page
                ->alerts()       // Alert Management page
                ->trends(),      // Performance Trends page
        ]);
}
```

### Customize

```php
QueryLensPlugin::make()
    ->dashboard(true)
    ->alerts(false)          // Disable alerts page
    ->trends(true)
    ->navigationGroup('Monitoring');
```

The plugin provides:
- **Dashboard** -- Stats overview widget, query table with search/filter/sort, View and Explain actions
- **Alerts** -- CRUD management for alert rules with modal forms
- **Trends** -- Performance and volume chart widgets, top queries table with period selector

---

## Requirements

- PHP 8.1+
- Laravel 9.x, 10.x, 11.x, or 12.x
- Filament 3.x (optional, for panel integration)

---

## Contributing

Contributions are welcome. Please submit pull requests against the `master` branch. Run the test suite before submitting:

```bash
./vendor/bin/phpunit
```

---

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

---

## Credits

Developed by [Dulitha Rajapaksha](https://github.com/dulithamahishka94) for [GladeHQ](https://profile.dulitharajapaksha.com).
