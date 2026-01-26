# Laravel Query Analyzer 🚀

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel/query-analyzer.svg?style=flat-square)](https://packagist.org/packages/laravel/query-analyzer)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel/query-analyzer.svg?style=flat-square)](https://packagist.org/packages/laravel/query-analyzer)
[![License](https://img.shields.io/packagist/l/laravel/query-analyzer.svg?style=flat-square)](https://packagist.org/packages/laravel/query-analyzer)

A premium Laravel package that acts like an **Automated Database Administrator (DBA)**. It doesn't just list your queries; it analyzes them, finds performance killers, and suggests specific indexes.

---

## 🔥 Key Features

-   **💎 Premium Dashboard**: A modern, real-time UI built with Tailwind CSS.
-   **💾 Persistence Layer**: All queries are persisted via Laravel Cache—view background jobs and API calls effortlessly.
-   **📍 Code Origin (Stack Trace)**: See exactly which **file and line number** triggered every query.
-   **🛠️ Index Suggestions**: Automated advice on which columns to index for slow queries.
-   **🔍 EXPLAIN Integration**: Run `EXPLAIN` or `EXPLAIN ANALYZE` directly from the dashboard to see raw execution plans.
-   **🔄 Request Grouping**: Queries are tagged with unique Request IDs to easily debug single page loads.
-   **🎯 N+1 Detection**: Heuristic detection of repeated query structures within a single request.

---

## 🚀 Quick Start

### 1. Installation
```bash
composer require laravel/query-analyzer
```

### 2. Publish Configuration
```bash
php artisan vendor:publish --tag=query-analyzer-config
```

### 3. Usage
Visit `/query-analyzer` in your browser. (Ensure you are on `localhost` or have configured `allowed_ips`).

---

## 🛠️ Performance Analysis

The analyzer automatically flags:
-   **Leading Wildcards**: `LIKE '%abc'` (kills indexing).
-   **Random Sorting**: `ORDER BY RAND()` (catastrophic on large tables).
-   **Deep Pagination**: High `OFFSET` values.
-   **Redundant Columns**: `SELECT *` usage.
-   **Functions in WHERE**: Non-sargable conditions like `WHERE DATE(created_at)`.

### Automatic Indexing Suggestions
If a query is slow, the analyzer parses the SQL and recommends:
> "Consider adding an INDEX on table `users` columns: (email, status)"

---

## ⚙️ Configuration

Key options in `config/query-analyzer.php`:

```php
return [
    'enabled' => env('QUERY_ANALYZER_ENABLED', true),
    
    // Thresholds for color-coded feedback
    'performance_thresholds' => [
        'fast' => 0.05,     // 50ms
        'moderate' => 0.2, // 200ms
        'slow' => 1.0,     // 1s
    ],

    'web_ui' => [
        'allowed_ips' => ['127.0.0.1', '::1'],
        'auth_callback' => null, // Add custom Gates/Logic here
    ],
];
```

---

## 📝 Performance Note
This package performs **synchronous regex analysis** on queries to provide deep insights. It is intended for **local development and staging environments**. It is not recommended for high-traffic production environments unless `enabled` is set to `false`.

---

## ⚖️ License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.