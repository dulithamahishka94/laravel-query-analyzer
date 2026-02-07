<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyzedRequest extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'method',
        'path',
        'route_name',
        'query_count',
        'slow_count',
        'total_time',
        'avg_time',
        'max_time',
        'issue_count',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'query_count' => 'integer',
        'slow_count' => 'integer',
        'total_time' => 'float',
        'avg_time' => 'float',
        'max_time' => 'float',
        'issue_count' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'requests');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function queries(): HasMany
    {
        return $this->hasMany(AnalyzedQuery::class, 'request_id');
    }

    public function slowQueries(): HasMany
    {
        return $this->hasMany(AnalyzedQuery::class, 'request_id')->where('is_slow', true);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeWithSlowQueries($query)
    {
        return $query->where('slow_count', '>', 0);
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    public function scopeByPath($query, string $path)
    {
        return $query->where('path', 'like', "%{$path}%");
    }

    public function updateAggregates(): void
    {
        $queries = $this->queries()->get();

        $this->update([
            'query_count' => $queries->count(),
            'slow_count' => $queries->where('is_slow', true)->count(),
            'total_time' => $queries->sum('time'),
            'avg_time' => $queries->avg('time') ?? 0,
            'max_time' => $queries->max('time') ?? 0,
            'issue_count' => $queries->sum(fn($q) => count($q->analysis['issues'] ?? [])),
        ]);
    }
}
