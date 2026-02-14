<?php

declare(strict_types=1);

/**
 * This file resolves the base class for Filament pages.
 *
 * When Filament is installed, pages extend Filament\Pages\Page.
 * When Filament is absent, pages extend a minimal stub that provides
 * the same public API surface without Livewire/Filament dependencies.
 *
 * This allows the package to be loaded safely regardless of whether
 * Filament is installed, while delivering full Filament integration
 * when available.
 */

namespace GladeHQ\QueryLens\Filament\Concerns;

if (class_exists(\Filament\Pages\Page::class)) {
    abstract class BasePageResolver extends \Filament\Pages\Page
    {
    }
} else {
    abstract class BasePageResolver
    {
        protected static string $navigationIcon = '';
        protected static string $navigationGroup = '';
        protected static ?string $title = null;
        protected static ?string $slug = null;
        protected static int $navigationSort = 0;
        protected static string $view = '';
        protected static ?string $navigationLabel = null;

        public static function getNavigationLabel(): string
        {
            return static::$navigationLabel ?? static::$title ?? '';
        }

        public static function getSlug(): string
        {
            return static::$slug ?? '';
        }

        public static function getNavigationIcon(): ?string
        {
            return static::$navigationIcon ?: null;
        }

        public static function getNavigationGroup(): ?string
        {
            return static::$navigationGroup ?: null;
        }

        public static function getNavigationSort(): ?int
        {
            return static::$navigationSort;
        }
    }
}
