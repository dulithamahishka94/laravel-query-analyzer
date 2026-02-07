<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

enum ComplexityLevel: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score <= 5 => self::LOW,
            $score <= 10 => self::MEDIUM,
            default => self::HIGH,
        };
    }

    public function getColorClass(): string
    {
        return match ($this) {
            self::LOW => 'complexity-low',
            self::MEDIUM => 'complexity-medium',
            self::HIGH => 'complexity-high',
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::LOW => '🟢',
            self::MEDIUM => '🟡',
            self::HIGH => '🔴',
        };
    }
}