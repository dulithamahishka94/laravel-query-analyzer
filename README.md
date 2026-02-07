# Query Lens

[![Latest Version on Packagist](https://img.shields.io/packagist/v/coderflex/query-lens.svg?style=flat-square)](https://packagist.org/packages/coderflex/query-lens)
[![Total Downloads](https://img.shields.io/packagist/dt/coderflex/query-lens.svg?style=flat-square)](https://packagist.org/packages/coderflex/query-lens)
[![License](https://img.shields.io/packagist/l/coderflex/query-lens.svg?style=flat-square)](https://packagist.org/packages/coderflex/query-lens)

A powerful Laravel package for analyzing, profiling, and optimizing database queries with real-time insights, performance monitoring, and a beautiful dashboard inspired by Laravel Pulse.

---

## Features

- **Real-time Dashboard** - Modern UI with live query monitoring
- **Performance Trends** - Track P50, P95, P99 latency over time
- **N+1 Detection** - Automatic detection of N+1 query problems
- **EXPLAIN Analysis** - Run and interpret EXPLAIN plans from the dashboard
- **Request Waterfall** - Visualize query timing within HTTP requests
- **Code Origin Tracking** - See exactly which file and line triggered each query
- **Top Queries Rankings** - Find slowest, most frequent, and most problematic queries
- **Configurable Alerts** - Get notified about slow queries via log, email, or Slack
- **App vs Vendor** - Distinguish between your code and package queries
- **Export Capabilities** - Export query data for further analysis

---

## Installation

```bash
composer require coderflex/query-lens
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=query-lens-config
```

Enable in your `.env`:

```env
QUERY_LENS_ENABLED=true
```

---

## Usage

Visit `/query-lens` in your browser to access the dashboard.

> By default, access is restricted to localhost. Configure `allowed_ips` or `auth_callback` in the config for other environments.

---

## Configuration

Key options in `config/query-lens.php`:

```php
return [
    'enabled' => env('QUERY_LENS_ENABLED', false),

    // Performance thresholds (in seconds)
    'performance_thresholds' => [
        'fast' => 0.1,      // < 100ms
        'moderate' => 0.5,  // < 500ms
        'slow' => 1.0,      // < 1 second
    ],

    // Storage driver: 'cache' or 'database'
    'storage' => [
        'driver' => env('QUERY_LENS_STORAGE', 'cache'),
        'retention_days' => 7,
    ],

    // Dashboard access control
    'web_ui' => [
        'enabled' => true,
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_callback' => null,
    ],

    // Alert notifications
    'alerts' => [
        'enabled' => env('QUERY_LENS_ALERTS', false),
        'channels' => ['log'], // Available: log, mail, slack
        'mail_to' => env('QUERY_LENS_MAIL_TO'),
        'slack_webhook' => env('QUERY_LENS_SLACK_WEBHOOK'),
    ],
];
```

Configure the environment variables in your `.env`:

```env
QUERY_LENS_ALERTS=true
QUERY_LENS_MAIL_TO=admin@example.com
QUERY_LENS_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

---

## Database Storage (Optional)

For persistent storage and historical data, use the database driver:

```env
QUERY_LENS_STORAGE=database
```

Publish and run migrations:

```bash
php artisan vendor:publish --tag=query-lens-migrations
php artisan migrate
```

Schedule aggregation and cleanup:

```php
// app/Console/Kernel.php
$schedule->command('query-lens:aggregate')->hourly();
$schedule->command('query-lens:prune')->daily();
```

---

## Dashboard Features

### Overview Stats
- Total queries, slow queries, average time, P95 latency
- Comparison with previous period

### Query Analysis
- Query type identification (SELECT, INSERT, UPDATE, DELETE)
- Performance rating with color-coded badges
- Complexity scoring
- Issue detection (N+1, security, performance)
- Actionable recommendations

### Request Waterfall
- Visual timeline of queries within a request
- Duration bars scaled to total request time
- Click-through to query details

### Performance Trends
- P50, P95, P99 latency charts
- Hourly and daily granularity
- Configurable time periods

### Top Queries
- Slowest by average execution time
- Most frequent by call count
- Most issues by detected problems

### Alerts Management
- Create custom alert rules
- Multiple notification channels
- Trigger history and logs

---

## Facade Usage

```php
use Coderflex\QueryLens\Facades\QueryLens;

// Get all recorded queries
$queries = QueryLens::getQueries();

// Get statistics
$stats = QueryLens::getStats();

// Analyze a specific query
$analysis = QueryLens::analyzeQuery($sql, $bindings, $time);

// Clear all data
QueryLens::reset();
```

---

## Documentation

For complete documentation, visit [docs/documentation.md](docs/documentation.md).

---

## Requirements

- PHP 8.1+
- Laravel 9.x, 10.x, 11.x, or 12.x

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## Credits

Developed by [Coderflex](https://coderflex.com).
