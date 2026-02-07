<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TopQuery extends Model
{
    protected $fillable = [
        'ranking_type',
        'period',
        'period_start',
        'sql_hash',
        'sql_sample',
        'count',
        'avg_time',
        'max_time',
        'total_time',
        'issue_count',
        'rank',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'count' => 'integer',
        'avg_time' => 'float',
        'max_time' => 'float',
        'total_time' => 'float',
        'issue_count' => 'integer',
        'rank' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'top_queries');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function scopeByRankingType($query, string $type)
    {
        return $query->where('ranking_type', $type);
    }

    public function scopeByPeriod($query, string $period)
    {
        return $query->where('period', $period);
    }

    public function scopeForPeriodStart($query, Carbon $start)
    {
        return $query->where('period_start', $start);
    }

    public function scopeTopN($query, int $limit = 10)
    {
        return $query->orderBy('rank')->limit($limit);
    }

    public function scopeCurrent($query, string $period)
    {
        $periodStart = self::getPeriodStart($period);
        return $query->where('period', $period)
            ->where('period_start', $periodStart);
    }

    public static function getPeriodStart(string $period): Carbon
    {
        return match ($period) {
            'hour' => now()->startOfHour(),
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            default => now()->startOfDay(),
        };
    }

    public static function getRankingTypes(): array
    {
        return [
            'slowest' => 'Slowest Queries',
            'most_frequent' => 'Most Frequent Queries',
            'most_issues' => 'Queries with Most Issues',
        ];
    }

    public static function getAvailablePeriods(): array
    {
        return [
            'hour' => 'Last Hour',
            'day' => 'Last 24 Hours',
            'week' => 'Last 7 Days',
        ];
    }
}
