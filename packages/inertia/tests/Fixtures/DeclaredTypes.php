<?php

declare(strict_types=1);

namespace WavyKit\Inertia\Tests\Fixtures;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;

/**
 * Reads the object and union types in resources/types/wavykit.d.ts and checks
 * decoded JSON against them, so the hand-written types cannot drift from the
 * PHP output unnoticed.
 *
 * Understands the forms the file uses: `string`, `boolean`, `null`, string
 * literals, `A | B`, `T[]`, `Record<K, T>`, and references to other declared
 * types. Anything else fails loudly.
 */
final class DeclaredTypes
{
    /** @var array<string, array<string, string>> Object types: name => field => type expression. */
    private array $objects = [];

    /** @var array<string, string> Alias types: name => type expression. */
    private array $aliases = [];

    public function __construct(string $path)
    {
        $source = preg_replace(['~/\*.*?\*/~s', '~//[^\n]*~'], '', (string) file_get_contents($path)) ?? '';

        preg_match_all('~export type (\w+)(?:<[^>]*>)? = \{(.*?)\};~s', $source, $objects, PREG_SET_ORDER);
        foreach ($objects as [, $name, $body]) {
            preg_match_all('~(\w+): ([^;]+);~', $body, $fields, PREG_SET_ORDER);
            foreach ($fields as [, $field, $type]) {
                $this->objects[$name][$field] = trim($type);
            }
        }

        preg_match_all('~export type (\w+) = ([^{;][^;]*);~', $source, $aliases, PREG_SET_ORDER);
        foreach ($aliases as [, $name, $type]) {
            $this->aliases[$name] = trim($type);
        }
    }

    /** @return list<string> */
    public function names(): array
    {
        return [...array_keys($this->objects), ...array_keys($this->aliases)];
    }

    /** Assert that a decoded JSON value matches a type expression, naming the path on failure. */
    public function assertMatches(string $type, mixed $value, string $path): void
    {
        Assert::assertTrue($this->matches($type, $value, $path), "{$path} does not match `{$type}`: ".json_encode($value));
    }

    private function matches(string $type, mixed $value, string $path): bool
    {
        $type = trim($type);
        $union = $this->splitTopLevel($type, '|');
        if (count($union) > 1) {
            foreach ($union as $member) {
                if ($this->matchesQuietly($member, $value, $path)) {
                    return true;
                }
            }

            return false;
        }

        if (str_ends_with($type, '[]')) {
            if (! is_array($value) || ! array_is_list($value)) {
                return false;
            }
            foreach ($value as $index => $item) {
                $this->assertMatches(substr($type, 0, -2), $item, "{$path}[{$index}]");
            }

            return true;
        }

        if (preg_match('~^Record<\s*\w+\s*,\s*(.+)>$~', $type, $record) === 1) {
            if (! is_array($value) || ($value !== [] && array_is_list($value))) {
                return false;
            }
            foreach ($value as $key => $item) {
                $this->assertMatches($record[1], $item, "{$path}.{$key}");
            }

            return true;
        }

        if (preg_match("~^'(.*)'$~", $type, $literal) === 1) {
            return $value === $literal[1];
        }

        return match (true) {
            $type === 'string' => is_string($value),
            $type === 'boolean' => is_bool($value),
            $type === 'null' => $value === null,
            isset($this->aliases[$type]) => $this->matches($this->aliases[$type], $value, $path),
            isset($this->objects[$type]) => $this->matchesObject($type, $value, $path),
            default => Assert::fail("Unknown type `{$type}` at {$path}; teach DeclaredTypes about it."),
        };
    }

    private function matchesObject(string $name, mixed $value, string $path): bool
    {
        if (! is_array($value) || ($value !== [] && array_is_list($value))) {
            return false;
        }
        $declared = array_keys($this->objects[$name]);
        $actual = array_keys($value);
        sort($declared);
        sort($actual);
        Assert::assertSame($declared, $actual, "{$path} has different fields from `{$name}` in wavykit.d.ts.");
        foreach ($this->objects[$name] as $field => $type) {
            $this->assertMatches($type, $value[$field], "{$path}.{$field}");
        }

        return true;
    }

    private function matchesQuietly(string $type, mixed $value, string $path): bool
    {
        try {
            return $this->matches($type, $value, $path);
        } catch (AssertionFailedError) {
            return false;
        }
    }

    /** @return list<string> */
    private function splitTopLevel(string $type, string $separator): array
    {
        $parts = [];
        $depth = 0;
        $current = '';
        foreach (str_split($type) as $char) {
            $depth += match ($char) {
                '<', '(' => 1,
                '>', ')' => -1,
                default => 0,
            };
            if ($char === $separator && $depth === 0) {
                $parts[] = trim($current);
                $current = '';

                continue;
            }
            $current .= $char;
        }
        $parts[] = trim($current);

        return $parts;
    }
}
