<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    protected $fillable = [
        'name',
        'type',
        'enabled',
        'conditions',
        'channels',
        'cooldown_minutes',
        'last_triggered_at',
        'trigger_count',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'conditions' => 'array',
        'channels' => 'array',
        'cooldown_minutes' => 'integer',
        'last_triggered_at' => 'datetime',
        'trigger_count' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('query-lens.storage.table_prefix', 'query_lens_') . 'alerts');
        $this->setConnection(config('query-lens.storage.connection'));
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AlertLog::class, 'alert_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function canTrigger(): bool
    {
        if (!$this->enabled) {
            return false;
        }

        if (!$this->last_triggered_at) {
            return true;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isPast();
    }

    public function markTriggered(): void
    {
        $this->update([
            'last_triggered_at' => now(),
            'trigger_count' => $this->trigger_count + 1,
        ]);
    }

    public function getCondition(string $key, $default = null)
    {
        return $this->conditions[$key] ?? $default;
    }

    public function matchesConditions(array $context): bool
    {
        $conditions = $this->conditions;

        foreach ($conditions as $key => $expected) {
            if ($key === 'operator') {
                continue;
            }

            $actual = $context[$key] ?? null;
            if ($actual === null) {
                continue;
            }

            $operator = $conditions['operator'] ?? '>=';

            $matched = match ($operator) {
                '>' => $actual > $expected,
                '>=' => $actual >= $expected,
                '<' => $actual < $expected,
                '<=' => $actual <= $expected,
                '=' => $actual == $expected,
                '!=' => $actual != $expected,
                default => $actual >= $expected,
            };

            if (!$matched) {
                return false;
            }
        }

        return true;
    }

    public static function getAvailableTypes(): array
    {
        return [
            'slow_query' => 'Single query exceeds time threshold',
            'threshold' => 'Aggregate metric exceeds threshold',
            'n_plus_one' => 'N+1 pattern detected',
            'error_rate' => 'High issue detection rate',
        ];
    }

    public static function getAvailableChannels(): array
    {
        return [
            'log' => 'Laravel Log',
            'mail' => 'Email',
            'slack' => 'Slack Webhook',
        ];
    }
}
