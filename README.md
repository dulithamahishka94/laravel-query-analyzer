# GladeHQ Laravel Query Lens

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gladehq/laravel-query-lens.svg?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)
[![Total Downloads](https://img.shields.io/packagist/dt/gladehq/laravel-query-lens.svg?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)
[![License](https://img.shields.io/packagist/l/gladehq/laravel-query-lens.svg?style=flat-square)](https://packagist.org/packages/gladehq/laravel-query-lens)

**Query Lens** is a powerful observability dashboard for Laravel applications. It provides real-time insights into your database performance, helping you spot N+1 queries, slow database operations, and inefficient patterns instantly.

![Dashboard Overview](docs/images/dashboard_overview.png)

---

## 📚 Documentation

For a deep dive into all features, configuration, and advanced usage, please read the **[Official Documentation](docs/documentation.md)**.

---

## ✨ Features

- **🚀 Real-time Monitoring**: Watch queries execute live as you browse your app.
- **🔍 Deep Analysis**: Integrated `EXPLAIN` runner to visualize execution plans and index usage.
- **🌊 Request Waterfall**: Visualize query timing relative to your HTTP requests.
- **🚨 Intelligent Alerts**: Get notified via Slack, Email, or Logs when queries exceed thresholds.
- **📉 Trend Tracking**: Monitor P95/P99 latency over time to catch performance regressions.
- **📍 Code Origin**: Pinpoint exactly which file and line of code triggered a query.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require gladehq/laravel-query-lens
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=query-lens-config
```

Enable it in your `.env` file:

```env
QUERY_LENS_ENABLED=true
```

Visit `/query-lens` in your browser to start analyzing!

> **Note**: By default, access is restricted to non-local environments. See [Documentation](docs/documentation.md#security) for production configuration.

---

## ⚙️ Configuration (Quick Start)

Here are the most common options in `config/query-lens.php`:

```php
return [
    'enabled' => env('QUERY_LENS_ENABLED', false),

    // Define what "slow" means for your app
    'performance_thresholds' => [
        'slow' => 1.0, // Queries taking > 1s are marked slow
    ],

    // Choose 'cache' (ephemeral) or 'database' (persistent)
    'storage' => [
        'driver' => env('QUERY_LENS_STORAGE', 'cache'),
    ],

    // Configure Alerts
    'alerts' => [
        'enabled' => env('QUERY_LENS_ALERTS', false),
        'channels' => ['mail', 'slack'],
        'mail_to' => env('QUERY_LENS_MAIL_TO'),
        'slack_webhook' => env('QUERY_LENS_SLACK_WEBHOOK'),
    ],
];
```

---

## 🛠️ Advanced Usage

### Facade API

```php
use GladeHQ\QueryLens\Facades\QueryLens;

// Manually analyze a query string
$analysis = QueryLens::analyzeQuery('SELECT * FROM users WHERE active = 1');

// Get current session stats
$stats = QueryLens::getStats();
```

### Console Commands

- `php artisan query-lens:aggregate`: Pre-calculate hourly trends (for Database driver).
- `php artisan query-lens:prune`: Clean up old data.

---

## 🧪 Testing

Run the test suite to ensure everything is working correctly:

```bash
composer test
```

---

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

---

## 👨‍💻 Credits

Developed by **[Dulitha Rajapaksha](https://github.com/dulithamahishka94)** for **[GladeHQ](https://gladehq.dulitharajapaksha.com)**.
