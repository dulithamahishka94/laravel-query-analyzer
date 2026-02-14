<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\DTOs;

enum PerformanceRating: string
{
    case FAST = 'fast';
    case MODERATE = 'moderate';
    case SLOW = 'slow';
    case VERY_SLOW = 'very_slow';

    public static function fromTime(float $time, array $thresholds = []): self
    {
        $fast = $thresholds['fast'] ?? 0.1;
        $moderate = $thresholds['moderate'] ?? 0.5;
        $slow = $thresholds['slow'] ?? 1.0;

        return match (true) {
            $time <= $fast => self::FAST,
            $time <= $moderate => self::MODERATE,
            $time <= $slow => self::SLOW,
            default => self::VERY_SLOW,
        };
    }

    public function toArray(float $executionTime = 0.0): array
    {
        return [
            'execution_time' => $executionTime,
            'rating' => $this->value,
            'is_slow' => $this->isSlow(),
        ];
    }

    public function isSlow(): bool
    {
        return $this === self::VERY_SLOW;
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