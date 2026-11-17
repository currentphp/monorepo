<?php

declare(strict_types=1);

namespace Current\Navigation;

/**
 * A registered explicit menu, returned by MenuRegistry::define().
 *
 * It has no options yet; it exists so menu-level settings can be added without
 * changing what define() returns.
 */
final class DefinedMenu
{
    /** @internal Created by MenuRegistry::define(). */
    public function __construct() {}
}
