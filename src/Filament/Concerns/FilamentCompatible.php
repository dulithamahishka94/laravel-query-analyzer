<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Filament\Concerns;

/**
 * Provides Filament compatibility detection used across all Filament integration classes.
 */
trait FilamentCompatible
{
    public static function filamentIsInstalled(): bool
    {
        return class_exists(\Filament\Pages\Page::class);
    }
}
