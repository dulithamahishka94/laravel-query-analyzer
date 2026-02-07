<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\ExplainAnalyzer\Issues;

/**
 * Severity levels for detected issues.
 */
enum IssueSeverity: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFO = 'info';

    /**
     * Get a human-readable label.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::INFO => 'Info',
        };
    }

    /**
     * Get a color code for display purposes.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::HIGH => 'red',
            self::MEDIUM => 'orange',
            self::LOW => 'yellow',
            self::INFO => 'blue',
        };
    }

    /**
     * Check if this severity level requires attention.
     */
    public function requiresAttention(): bool
    {
        return match ($this) {
            self::HIGH, self::MEDIUM => true,
            self::LOW, self::INFO => false,
        };
    }
}
