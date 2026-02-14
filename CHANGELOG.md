# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-02-14

### Added
- Real-time query monitoring dashboard with stats overview, query list, and performance ratings
- EXPLAIN analysis with human-readable output for any captured query
- Intelligent index recommendation engine with ready-to-run CREATE INDEX statements
- Query regression detection with CI/CD pipeline integration via `query-lens:check-regression` command
- AI-powered query optimization with pluggable OpenAI-compatible provider system
- Transaction tracking with begin/commit/rollback lifecycle monitoring and nesting depth
- Filament v3 panel plugin with Table Builder, Chart Widgets, and Stats Overview
- Filament dashboard page with searchable/filterable/sortable query table and View/Explain actions
- Filament alerts page with CRUD modal forms for alert rule management
- Filament trends page with performance and volume chart widgets and top queries table
- Filament stats overview widget with trend indicators and contextual colors
- Historical query search with filters (type, duration, slow status, date range, SQL pattern)
- Configurable query sampling rate for production use (per-request sampling)
- Gate-based authentication for dashboard access control
- Request waterfall visualization showing query execution timeline within HTTP requests
- Performance trend tracking with P50, P95, and P99 latency over configurable time granularity
- N+1 query detection with automatic pattern recognition
- Code origin tracing with file and line number for each query
- Alert system with configurable channels (log, mail, Slack webhook)
- Alert cooldown to prevent notification flooding
- `query-lens:aggregate` command for pre-computing hourly/daily performance aggregates
- `query-lens:prune` command for data retention management
- `query-lens:suggest-indexes` command with SQL-specific and pattern-based analysis
- Configurable excluded_patterns to filter unwanted queries from recording
- Database and cache storage drivers with automatic driver selection
- Request-scoped query grouping with per-request metadata tracking
- Webhook notifications for regression alerts
- V2 API endpoints for trends, top queries, search, and storage info
- Comprehensive test suite with 691 tests covering all components

### Fixed
- Missing $store property declaration in CacheQueryStorage
- Data pruning disabled in DataRetentionService
- Octane state leak via incorrect QueryAnalyzer binding (changed to scoped)
- Orphaned routes/web.php with stale namespace references
- Unconstrained debug_backtrace depth causing performance overhead
- O(n^2) request aggregation by deferring to terminate phase
- Repeated database queries for enabled alerts (added in-memory caching)

### Changed
- QueryAnalyzer uses DTOs instead of raw arrays for type safety
- SQL normalization consolidated into SqlNormalizer utility class
- Controller logic extracted into dedicated service classes
- Filament pages use conditional base class resolvers for non-Filament compatibility
- README rewritten as product page with competitive positioning and feature documentation
