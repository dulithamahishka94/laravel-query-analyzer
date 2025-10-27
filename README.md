# Laravel Query Analyzer

A comprehensive Laravel package for analyzing and optimizing database queries with performance insights and recommendations.

## Features

- **Real-time Query Analysis**: Automatically analyzes all database queries executed in your Laravel application
- **Web Dashboard**: Beautiful web interface similar to Laravel Clockwork for viewing query analysis
- **Performance Monitoring**: Tracks query execution times and identifies slow queries
- **Complexity Assessment**: Evaluates query complexity based on JOINs, subqueries, and conditions
- **Smart Recommendations**: Provides actionable suggestions for query optimization
- **Issue Detection**: Identifies potential performance bottlenecks and security concerns
- **Interactive UI**: Real-time updates, filtering, and detailed query inspection
- **Export Functionality**: Export analysis results to JSON or CSV formats
- **Artisan Command**: Comprehensive CLI tool for viewing analysis results
- **Configurable Thresholds**: Customize performance thresholds and analysis settings

## Quick Start

Get up and running in 60 seconds:

```bash
# 1. Install the package
composer require laravel/query-analyzer

# 2. Publish configuration
php artisan vendor:publish --tag=query-analyzer-config

# 3. Enable in .env
echo "QUERY_ANALYZER_ENABLED=true" >> .env
echo "QUERY_ANALYZER_WEB_UI_ENABLED=true" >> .env

# 4. Visit the dashboard
# http://your-app.test/query-analyzer

# 5. Execute some queries to see them analyzed
php artisan tinker
>>> App\Models\User::all();
```

That's it! Your queries will now appear in the beautiful web dashboard with real-time analysis.

## Installation

Install the package via Composer:

```bash
composer require laravel/query-analyzer
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=query-analyzer-config
```

Enable query analysis in your `.env` file:

```env
QUERY_ANALYZER_ENABLED=true
QUERY_ANALYZER_WEB_UI_ENABLED=true
```

## Usage

### Web Dashboard

The package provides a beautiful web interface for real-time query analysis. Once enabled, visit:

```
http://your-app.test/query-analyzer
```

#### Getting Started with the Dashboard

1. **Access the Dashboard**
   - Navigate to `http://your-app.test/query-analyzer` in your browser
   - Ensure your `.env` has `QUERY_ANALYZER_ENABLED=true` and `QUERY_ANALYZER_WEB_UI_ENABLED=true`
   - The dashboard is accessible only from localhost by default

2. **Understanding the Interface**
   - **Status Bar**: Shows real-time metrics including analyzer status, total queries, slow queries, and average execution time
   - **Control Panel**: Contains filtering options, refresh controls, and export functionality
   - **Query List**: Displays all captured queries with performance indicators

#### How to Use the Dashboard

**📊 Real-time Monitoring**
```bash
# Enable query collection
QUERY_ANALYZER_ENABLED=true

# Execute some queries in your application
php artisan tinker
>>> User::all();
>>> User::where('active', true)->with('posts')->get();
>>> DB::table('users')->join('posts', 'users.id', '=', 'posts.user_id')->get();
```

**🔍 Filtering and Analysis**
- **Query Type Filter**: Use the dropdown to filter by SELECT, INSERT, UPDATE, DELETE, or view all types
- **Slow Queries Only**: Check this box to focus on performance bottlenecks
- **Auto-refresh**: Enable to automatically update the dashboard every 5 seconds

**📋 Query Details**
- **Click any query** in the list to open the detailed analysis modal
- **View Performance Metrics**: Execution time, performance rating (fast/moderate/slow/very slow)
- **Complexity Analysis**: See JOINs, subqueries, and complexity scoring
- **Recommendations**: Get actionable optimization suggestions
- **Issue Detection**: Identify performance, security, and efficiency problems

**💾 Export and Management**
- **Export JSON**: Download complete analysis data for external processing
- **Export CSV**: Get spreadsheet-friendly format for reporting
- **Reset Queries**: Clear the current query collection to start fresh
- **Refresh**: Manually update the dashboard with latest queries

#### Dashboard Features in Detail

**Performance Color Coding:**
- 🟢 **Green (Fast)**: Queries under 100ms
- 🟡 **Yellow (Moderate)**: Queries 100ms-500ms
- 🟠 **Orange (Slow)**: Queries 500ms-1s
- 🔴 **Red (Very Slow)**: Queries over 1 second

**Query Type Indicators:**
- **SELECT**: Blue background - data retrieval queries
- **INSERT**: Green background - data creation queries
- **UPDATE**: Pink background - data modification queries
- **DELETE**: Red background - data removal queries

**Issue Warnings:**
- ⚠️ **Performance Issues**: OR conditions, functions in WHERE clauses
- ⚠️ **Efficiency Issues**: SELECT * with JOINs, unnecessary data retrieval
- ⚠️ **Security Issues**: Potential SQL injection vulnerabilities

#### Best Practices for Using the Dashboard

1. **Development Workflow**
   ```bash
   # Start with a clean slate
   php artisan query:analyze --reset

   # Enable auto-refresh on dashboard
   # Execute your application features
   # Monitor queries in real-time
   ```

2. **Performance Optimization**
   - Focus on queries marked as "slow" or "very slow"
   - Click slow queries to see specific recommendations
   - Look for queries with high complexity scores
   - Check for queries with multiple issues

3. **Security Auditing**
   - Filter to see all query types
   - Look for queries flagged with security issues
   - Ensure all queries use parameter binding

4. **Regular Monitoring**
   - Enable auto-refresh during development
   - Check average execution times regularly
   - Monitor the ratio of slow queries to total queries
   - Use export functionality for reporting

**Security Note:** The web UI is restricted to localhost (127.0.0.1, ::1) by default. For production environments, configure additional access controls in the config file or disable the web UI entirely.

#### Dashboard Troubleshooting

**🚫 Dashboard shows "No queries found"**
```bash
# Check if analyzer is enabled
php artisan config:show query-analyzer.enabled

# Execute some test queries
php artisan tinker
>>> App\Models\User::first();

# Refresh the dashboard
```

**🚫 Dashboard not accessible (404 error)**
```bash
# Clear config cache
php artisan config:clear

# Ensure package is properly installed
composer show laravel/query-analyzer

# Check if web UI is enabled
php artisan config:show query-analyzer.web_ui.enabled
```

**🚫 Queries not appearing in real-time**
- Ensure auto-refresh is enabled (checkbox in controls)
- Check minimum execution time threshold in config
- Verify queries are actually being executed in your application

#### Advanced Dashboard Usage

**🎯 Performance Optimization Workflow**
```bash
# 1. Start monitoring
curl http://your-app.test/query-analyzer/api/stats

# 2. Load test your application
ab -n 100 -c 10 http://your-app.test/users

# 3. Review slow queries in dashboard
# Filter by "Show slow queries only"

# 4. Export data for analysis
# Use "Export CSV" for spreadsheet analysis
```

**📊 Integration with Development Tools**
```bash
# Monitor during feature development
php artisan serve &
# Visit dashboard in browser
# Run your feature tests
php artisan test --filter=UserTest

# Check query impact in real-time
```

**🔧 Custom Authentication Example**
```php
// In config/query-analyzer.php
'web_ui' => [
    'enabled' => true,
    'auth_callback' => function($request) {
        return $request->user() && $request->user()->isAdmin();
    },
],
```

### Command Line Interface

In addition to the web dashboard, you can use Artisan commands:

```bash
# View all analyzed queries
php artisan query:analyze

# View only slow queries
php artisan query:analyze --slow-only

# Output as JSON
php artisan query:analyze --format=json

# Reset query collection after analysis
php artisan query:analyze --reset
```

### Programmatic Usage

```php
use Laravel\QueryAnalyzer\QueryAnalyzer;

$analyzer = app(QueryAnalyzer::class);

// Get all recorded queries
$queries = $analyzer->getQueries();

// Get analysis statistics
$stats = $analyzer->getStats();

// Analyze a specific query
$analysis = $analyzer->analyzeQuery('SELECT * FROM users WHERE active = 1');

// Reset the query collection
$analyzer->reset();
```

### Configuration

The package provides extensive configuration options in `config/query-analyzer.php`:

```php
return [
    'enabled' => env('QUERY_ANALYZER_ENABLED', false),

    // Web UI Configuration
    'web_ui' => [
        'enabled' => env('QUERY_ANALYZER_WEB_UI_ENABLED', true),
        'allowed_ips' => ['127.0.0.1', '::1'], // Restrict access to local only
        'auth_callback' => null, // Custom authentication callback
    ],

    'performance_thresholds' => [
        'fast' => 0.1,      // Under 100ms
        'moderate' => 0.5,  // Under 500ms
        'slow' => 1.0,      // Under 1 second
    ],

    'analysis' => [
        'max_queries' => 1000,
        'real_time_analysis' => true,
        'min_execution_time' => 0.001,
    ],

    // ... more options
];
```

## Analysis Features

### Performance Rating

Queries are rated based on execution time:
- **Fast**: Under 100ms (default)
- **Moderate**: 100ms - 500ms
- **Slow**: 500ms - 1s
- **Very Slow**: Over 1s

### Complexity Analysis

The package evaluates query complexity based on:
- Number of JOINs (weight: 2)
- Number of subqueries (weight: 3)
- WHERE/HAVING conditions (weight: 1)
- ORDER BY clauses (weight: 1)
- GROUP BY clauses (weight: 1)

### Recommendations

Automatic recommendations include:
- Avoiding `SELECT *` statements
- Adding `LIMIT` clauses with `ORDER BY`
- Breaking down complex queries with many JOINs
- Proper indexing for `LIKE` queries
- Performance optimization suggestions

### Issue Detection

The analyzer identifies:
- **Performance Issues**: OR conditions, functions in WHERE clauses
- **Efficiency Issues**: Unnecessary data retrieval with JOINs
- **Security Issues**: Potential SQL injection vulnerabilities

## Screenshots

### Web Dashboard
![Query Analyzer Dashboard](docs/dashboard.png)

The main dashboard provides:
- Real-time query statistics
- Performance overview with color-coded metrics
- Filter controls for query types and performance levels
- Auto-refresh capabilities

### Query Details
![Query Details Modal](docs/query-details.png)

Detailed query analysis includes:
- Complete SQL query with syntax highlighting
- Performance metrics and recommendations
- Complexity breakdown
- Issue detection and optimization suggestions

## Example CLI Output

```bash
$ php artisan query:analyze

Query Analysis Statistics:
+---------------------------+--------+
| Metric                    | Value  |
+---------------------------+--------+
| Total Queries            | 45     |
| Total Execution Time     | 2.341s |
| Average Execution Time   | 0.052s |
| Slow Queries             | 3      |
+---------------------------+--------+

Query Types:
+--------+-------+
| Type   | Count |
+--------+-------+
| SELECT | 38    |
| INSERT | 4     |
| UPDATE | 2     |
| DELETE | 1     |
+--------+-------+

+---+--------+--------+-------------+------------+--------+---------------------------+
| # | Type   | Time   | Performance | Complexity | Issues | SQL Preview               |
+---+--------+--------+-------------+------------+--------+---------------------------+
| 1 | SELECT | 0.045s | fast        | low        | 1      | SELECT * FROM users WHE... |
| 2 | SELECT | 1.234s | very_slow   | high       | 2      | SELECT u.*, p.* FROM u... |
+---+--------+--------+-------------+------------+--------+---------------------------+

Recommendations:
• Avoid SELECT * - specify only needed columns
• Consider adding LIMIT when using ORDER BY
• Query has many JOINs - consider breaking into smaller queries

Issues Found:
• performance: OR conditions can prevent index usage
• efficiency: SELECT * with JOINs can return unnecessary data
```

## Testing

Run the test suite:

```bash
composer test
```

Or with PHPUnit directly:

```bash
vendor/bin/phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- **Laravel Query Analyzer Contributors**
- All contributors who help improve this package

---

*"Here's to the crazy ones. The misfits. The rebels. The troublemakers. The round pegs in the square holes. The ones who see things differently... Because they change things."*