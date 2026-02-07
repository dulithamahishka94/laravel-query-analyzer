<?php

declare(strict_types=1);

namespace Coderflex\QueryLens\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'query_id',
        'alert_type',
        'alert_name',
        'message',
        'context',
        'notified_channels',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'notified_channels' => 'array',
        'created_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'alert_logs');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }

    public function analyzedQuery(): BelongsTo
    {
        return $this->belongsTo(AnalyzedQuery::class, 'query_id');
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    public function scopeByAlert($query, int $alertId)
    {
        return $query->where('alert_id', $alertId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('alert_type', $type);
    }

    public static function createFromAlert(Alert $alert, string $message, array $context = [], ?string $queryId = null): self
    {
        return self::create([
            'alert_id' => $alert->id,
            'query_id' => $queryId,
            'alert_type' => $alert->type,
            'alert_name' => $alert->name,
            'message' => $message,
            'context' => $context,
            'notified_channels' => $alert->channels,
            'created_at' => now(),
        ]);
    }
}
