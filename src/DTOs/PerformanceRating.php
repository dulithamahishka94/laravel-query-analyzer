<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

enum PerformanceRating: string
{
    case FAST = 'fast';
    case MODERATE = 'moderate';
    case SLOW = 'slow';
    case VERY_SLOW = 'very_slow';

    public static function fromTime(float $time, array $thresholds): self
    {
        return match (true) {
            $time <= $thresholds['fast'] => self::FAST,
            $time <= $thresholds['moderate'] => self::MODERATE,
            $time <= $thresholds['slow'] => self::SLOW,
            default => self::VERY_SLOW,
        };
    }

    public function toArray(): array
    {
        return [
            'rating' => $this->value,
            'is_slow' => $this->isSlow(),
        ];
    }

    public function isSlow(): bool
    {
        return $this === self::SLOW || $this === self::VERY_SLOW;
    }

    public function getColorClass(): string
    {
        return match ($this) {
            self::FAST => 'performance-fast',
            self::MODERATE => 'performance-moderate',
            self::SLOW => 'performance-slow',
            self::VERY_SLOW => 'performance-very-slow',
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::FAST => '🟢',
            self::MODERATE => '🟡',
            self::SLOW => '🟠',
            self::VERY_SLOW => '🔴',
        };
    }
}