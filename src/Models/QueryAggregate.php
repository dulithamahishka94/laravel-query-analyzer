<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class QueryAggregate extends Model
{
    protected $fillable = [
        'period_type',
        'period_start',
        'total_queries',
        'slow_queries',
        'avg_time',
        'p50_time',
        'p95_time',
        'p99_time',
        'max_time',
        'min_time',
        'total_time',
        'issue_count',
        'n_plus_one_count',
        'type_breakdown',
        'performance_breakdown',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'total_queries' => 'integer',
        'slow_queries' => 'integer',
        'avg_time' => 'float',
        'p50_time' => 'float',
        'p95_time' => 'float',
        'p99_time' => 'float',
        'max_time' => 'float',
        'min_time' => 'float',
        'total_time' => 'float',
        'issue_count' => 'integer',
        'n_plus_one_count' => 'integer',
        'type_breakdown' => 'array',
        'performance_breakdown' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'aggregates');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function scopeHourly($query)
    {
        return $query->where('period_type', 'hour');
    }

    public function scopeDaily($query)
    {
        return $query->where('period_type', 'day');
    }

    public function scopeInRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    public function scopeForPeriod($query, string $type, Carbon $periodStart)
    {
        return $query->where('period_type', $type)
            ->where('period_start', $periodStart);
    }

    public static function getOrCreateForPeriod(string $type, Carbon $periodStart): self
    {
        return self::firstOrCreate(
            [
                'period_type' => $type,
                'period_start' => $periodStart,
            ],
            [
                'total_queries' => 0,
                'slow_queries' => 0,
                'avg_time' => 0,
                'p50_time' => 0,
                'p95_time' => 0,
                'p99_time' => 0,
                'max_time' => 0,
                'min_time' => 0,
                'total_time' => 0,
                'issue_count' => 0,
                'n_plus_one_count' => 0,
                'type_breakdown' => [],
                'performance_breakdown' => [],
            ]
        );
    }

    public function getThroughput(): float
    {
        if ($this->period_type === 'hour') {
            return $this->total_queries / 3600;
        }

        return $this->total_queries / 86400;
    }

    public function getSlowPercentage(): float
    {
        if ($this->total_queries === 0) {
            return 0;
        }

        return ($this->slow_queries / $this->total_queries) * 100;
    }
}
