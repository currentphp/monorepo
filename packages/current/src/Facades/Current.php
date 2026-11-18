<?php

declare(strict_types=1);

namespace Current\Facades;

use Current\Experience;
use Illuminate\Support\Facades\Facade;

/**
 * Reads the experience of the current request; see Current\Current.
 *
 * @method static \Current\Pages\PageDefinition|null page()
 * @method static string|null pageId()
 * @method static bool is(string ...$patterns)
 * @method static string|null title()
 * @method static string|null description()
 * @method static list<\Current\Navigation\Breadcrumb> breadcrumbs()
 * @method static list<\Current\Navigation\MenuItem> menu(string $name)
 * @method static \Current\Context context()
 * @method static \Illuminate\Contracts\Auth\Authenticatable|null user()
 * @method static \Current\Subjects\SubjectItem|null subject()
 *
 * @see \Current\Current
 */
final class Current extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Experience::class;
    }
}
