<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyzedQuery extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'request_id',
        'sql_hash',
        'sql',
        'sql_normalized',
        'bindings',
        'time',
        'connection',
        'type',
        'performance_rating',
        'is_slow',
        'complexity_score',
        'complexity_level',
        'analysis',
        'origin',
        'is_n_plus_one',
        'n_plus_one_count',
        'created_at',
    ];

    protected $casts = [
        'bindings' => 'array',
        'time' => 'float',
        'is_slow' => 'boolean',
        'complexity_score' => 'integer',
        'analysis' => 'array',
        'origin' => 'array',
        'is_n_plus_one' => 'boolean',
        'n_plus_one_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'queries');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AnalyzedRequest::class, 'request_id');
    }

    public function scopeSlow($query)
    {
        return $query->where('is_slow', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', strtoupper($type));
    }

    public function scopeByPerformance($query, string $rating)
    {
        return $query->where('performance_rating', $rating);
    }

    public function scopeNPlusOne($query)
    {
        return $query->where('is_n_plus_one', true);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeByConnection($query, string $connection)
    {
        return $query->where('connection', $connection);
    }

    public function scopeByHash($query, string $hash)
    {
        return $query->where('sql_hash', $hash);
    }

    public function scopeWithIssues($query)
    {
        return $query->whereJsonLength('analysis->issues', '>', 0);
    }

    public function scopeInPeriod($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }

    public function getIssues(): array
    {
        return $this->analysis['issues'] ?? [];
    }

    public function getRecommendations(): array
    {
        return $this->analysis['recommendations'] ?? [];
    }

    public function hasIssues(): bool
    {
        return count($this->getIssues()) > 0;
    }

    public static function normalizeSql(string $sql): string
    {
        // Replace numeric values
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);
        // Replace string literals
        $normalized = preg_replace("/'[^']*'/", '?', $normalized);
        $normalized = preg_replace('/"[^"]*"/', '?', $normalized);
        // Replace IN lists
        $normalized = preg_replace('/\bIN\s*\([^)]+\)/i', 'IN (?)', $normalized);
        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    public static function hashSql(string $sql): string
    {
        return hash('sha256', self::normalizeSql($sql));
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'request_id' => $this->request_id,
            'sql' => $this->sql,
            'bindings' => $this->bindings ?? [],
            'time' => $this->time,
            'connection' => $this->connection,
            'timestamp' => $this->created_at?->timestamp ?? time(),
            'analysis' => [
                'type' => $this->type,
                'performance' => [
                    'rating' => $this->performance_rating,
                    'is_slow' => $this->is_slow,
                ],
                'complexity' => [
                    'score' => $this->complexity_score,
                    'level' => $this->complexity_level,
                ],
                'recommendations' => $this->analysis['recommendations'] ?? [],
                'issues' => $this->analysis['issues'] ?? [],
            ],
            'origin' => $this->origin ?? ['file' => 'unknown', 'line' => 0, 'is_vendor' => false],
            'is_n_plus_one' => $this->is_n_plus_one,
        ];
    }
}
