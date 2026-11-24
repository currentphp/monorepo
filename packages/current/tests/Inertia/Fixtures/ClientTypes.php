<?php

declare(strict_types=1);

namespace Current\Tests\Inertia\Fixtures;

/**
 * Locates the client package's declarations and the cases the PHP tests write
 * for it: generated type cases, which the client compiles with `tsc`, and the
 * `is()` parity table, which its Node tests compare against.
 */
final class ClientTypes
{
    private const string FOLDER = 'client';

    public static function declarations(): string
    {
        return self::root().'/src/types.ts';
    }

    public static function generatedCases(): string
    {
        return self::root().'/tests/types/generated';
    }

    public static function parityCases(): string
    {
        return self::root().'/tests/parity/is-cases.json';
    }

    private static function root(): string
    {
        return dirname(__DIR__, 4).'/'.self::FOLDER;
    }
}
