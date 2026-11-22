<?php

declare(strict_types=1);

namespace Current\Support;

use Closure;
use Current\Exceptions\ResolutionFailed;
use Throwable;

/** @internal Resolves string-or-callback labels, titles, and descriptions; shared by every reader. */
final class Label
{
    /**
     * @template TArgument
     *
     * @param  string|Closure(TArgument): mixed  $value
     * @param  TArgument  $argument  Passed to a callback: the request Context for pages and menus, the object for subjects.
     * @param  string  $owner  Names the page, menu entry, or subject in error messages.
     * @param  string  $fact  The value being resolved, such as "label" or "title", for error messages.
     *
     * @throws ResolutionFailed If the callback throws or does not return a string.
     */
    public static function resolve(string|Closure $value, mixed $argument, string $owner, string $fact = 'label'): string
    {
        $resolved = self::call($value, $argument, $owner, $fact);

        if (! is_string($resolved)) {
            throw new ResolutionFailed(ucfirst($fact)." callback for {$owner} must return a string.");
        }

        return $resolved;
    }

    /** @throws ResolutionFailed If a required presentation value is not a non-empty string. */
    public static function nonEmpty(mixed $value, string $owner, string $fact): string
    {
        return is_string($value) && trim($value) !== ''
            ? $value
            : throw new ResolutionFailed(ucfirst($fact)." callback for {$owner} must return a non-empty string.");
    }

    /**
     * Resolve a value whose callback may return null, such as a description.
     *
     * Callbacks must return a non-empty string or null.
     *
     * @template TArgument
     *
     * @param  string|Closure(TArgument): mixed  $value
     * @param  TArgument  $argument
     *
     * @throws ResolutionFailed If the callback throws or returns anything else.
     */
    public static function resolveOptional(string|Closure $value, mixed $argument, string $owner, string $fact): ?string
    {
        $resolved = self::call($value, $argument, $owner, $fact);

        if ($resolved !== null && (! is_string($resolved) || trim($resolved) === '')) {
            throw new ResolutionFailed(ucfirst($fact)." callback for {$owner} must return a non-empty string or null.");
        }

        return $resolved;
    }

    /**
     * @template TArgument
     *
     * @param  string|Closure(TArgument): mixed  $value
     * @param  TArgument  $argument
     */
    private static function call(string|Closure $value, mixed $argument, string $owner, string $fact): mixed
    {
        if (is_string($value)) {
            return $value;
        }

        try {
            return $value($argument);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not resolve {$fact} for {$owner}: {$exception->getMessage()}", 0, $exception);
        }
    }
}
