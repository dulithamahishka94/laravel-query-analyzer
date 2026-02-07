<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

enum QueryType: string
{
    case SELECT = 'SELECT';
    case INSERT = 'INSERT';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case CREATE = 'CREATE';
    case ALTER = 'ALTER';
    case DROP = 'DROP';
    case OTHER = 'OTHER';

    public static function fromSql(string $sql): self
    {
        $sql = trim(strtoupper($sql));

        return match (true) {
            str_starts_with($sql, 'SELECT') => self::SELECT,
            str_starts_with($sql, 'INSERT') => self::INSERT,
            str_starts_with($sql, 'UPDATE') => self::UPDATE,
            str_starts_with($sql, 'DELETE') => self::DELETE,
            str_starts_with($sql, 'CREATE') => self::CREATE,
            str_starts_with($sql, 'ALTER') => self::ALTER,
            str_starts_with($sql, 'DROP') => self::DROP,
            default => self::OTHER,
        };
    }

    public function getColorClass(): string
    {
        return match ($this) {
            self::SELECT => 'query-type-select',
            self::INSERT => 'query-type-insert',
            self::UPDATE => 'query-type-update',
            self::DELETE => 'query-type-delete',
            default => 'query-type-other',
        };
    }
}