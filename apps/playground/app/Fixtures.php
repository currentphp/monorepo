<?php

declare(strict_types=1);

namespace App;

use JsonException;
use LogicException;

/**
 * Read-only demo data from database/fixtures/<name>.json, shared by the app and tests.
 *
 * Fixtures need no migrations or seeding; edit the JSON to change demo content.
 */
final class Fixtures
{
    /** @var array<string, list<array<string, mixed>>> */
    private static array $loaded = [];

    /**
     * @return list<array<string, mixed>>
     *
     * @throws LogicException If the file is missing or is not a JSON list of objects.
     */
    public static function load(string $name): array
    {
        return self::$loaded[$name] ??= self::read(database_path("fixtures/{$name}.json"));
    }

    /** @return list<array<string, mixed>> */
    private static function read(string $path): array
    {
        $json = is_file($path) ? file_get_contents($path) : false;
        if ($json === false) {
            throw new LogicException("Fixture file [{$path}] is missing.");
        }
        try {
            $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new LogicException("Fixture file [{$path}] is not valid JSON.", 0, $exception);
        }

        $records = [];
        foreach (is_array($data) && array_is_list($data) ? $data : [null] as $record) {
            if (! is_array($record) || array_is_list($record)) {
                throw new LogicException("Fixture file [{$path}] must contain a list of objects.");
            }
            /** @var array<string, mixed> $record */
            $records[] = $record;
        }

        return $records;
    }
}
