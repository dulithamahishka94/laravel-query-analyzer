<?php

declare(strict_types=1);

namespace GladeHQ\QueryLens\Support;

/**
 * Canonical SQL normalization and hashing utility.
 *
 * All storage drivers and analysis code MUST use this class
 * to ensure consistent query grouping across the codebase.
 */
final class SqlNormalizer
{
    /**
     * Normalize SQL by replacing literal values with placeholders.
     *
     * Handles: numeric literals, string literals (single and double quoted),
     * IN(...) lists, and excessive whitespace.
     */
    public static function normalize(string $sql): string
    {
        // Replace numeric literals
        $normalized = preg_replace('/\b\d+\b/', '?', $sql);

        // Replace single-quoted string literals
        $normalized = preg_replace("/'[^']*'/", '?', $normalized);

        // Replace double-quoted string literals
        $normalized = preg_replace('/"[^"]*"/', '?', $normalized);

        // Collapse IN(...) lists to a single placeholder
        $normalized = preg_replace('/\bIN\s*\([^)]+\)/i', 'IN (?)', $normalized);

        // Normalize whitespace
        $normalized = preg_replace('/\s+/', ' ', $normalized);

        return trim($normalized);
    }

    /**
     * Produce a consistent SHA-256 hash of the normalized SQL.
     */
    public static function hash(string $sql): string
    {
        return hash('sha256', self::normalize($sql));
    }
}
