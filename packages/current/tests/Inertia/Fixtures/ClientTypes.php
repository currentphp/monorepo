<?php

declare(strict_types=1);

namespace Current\Tests\Inertia\Fixtures;

/**
 * Locates the TypeScript declarations and the generated type cases, which live
 * with the client types outside this package. The type tests compile those
 * cases with `tsc` after the PHP tests write them.
 */
final class ClientTypes
{
    private const string FOLDER = 'inertia';

    public static function declarations(): string
    {
        return self::root().'/resources/types/current.d.ts';
    }

    public static function generatedCases(): string
    {
        return self::root().'/tests/types/generated';
    }

    private static function root(): string
    {
        return dirname(__DIR__, 4).'/'.self::FOLDER;
    }
}
