<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackedTransaction extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'connection',
        'started_at',
        'ended_at',
        'duration_ms',
        'status',
        'nesting_depth',
        'query_count',
        'request_id',
    ];

    protected $casts = [
        'started_at' => 'float',
        'ended_at' => 'float',
        'duration_ms' => 'float',
        'nesting_depth' => 'integer',
        'query_count' => 'integer',
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'transactions');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(AnalyzedRequest::class, 'request_id');
    }

    public function queries(): HasMany
    {
        return $this->hasMany(AnalyzedQuery::class, 'transaction_id');
    }

    public function scopeByConnection($query, string $connection)
    {
        return $query->where('connection', $connection);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCommitted($query)
    {
        return $query->where('status', 'committed');
    }

    public function scopeRolledBack($query)
    {
        return $query->where('status', 'rolled_back');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeSlow($query, float $thresholdMs = 1000.0)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }

    public function scopeNested($query)
    {
        return $query->where('nesting_depth', '>', 1);
    }

    public function isRolledBack(): bool
    {
        return $this->status === 'rolled_back';
    }

    public function isCommitted(): bool
    {
        return $this->status === 'committed';
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'connection' => $this->getAttribute('connection'),
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'duration_ms' => $this->duration_ms,
            'status' => $this->status,
            'nesting_depth' => $this->nesting_depth,
            'query_count' => $this->query_count,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
