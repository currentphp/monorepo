<?php

declare(strict_types=1);

namespace Current\Navigation;

use Closure;
use Current\Context;
use Current\Exceptions\InvalidDefinition;

/** A registered hierarchy-derived menu; chain exclusions before the registry is resolved. */
final class DerivedMenu
{
    /** @var list<string> */
    private array $except = [];

    /** @var array<string, list<Closure(Context): mixed>> */
    private array $exceptWhen = [];

    /**
     * @internal Created by MenuRegistry::derive().
     *
     * @param  Closure(): void  $assertMutable
     */
    public function __construct(private readonly Closure $assertMutable) {}

    /**
     * Omit these pages, and their subtrees, from this menu only.
     *
     * Breadcrumbs, other menus, and ancestor active state are unaffected; an
     * excluded page can still be placed in an explicit menu. Every ID must be a
     * registered page. This is presentation, never HTTP authorization.
     *
     * @throws InvalidDefinition If the menu registry has been resolved.
     */
    public function except(string ...$pageIds): self
    {
        ($this->assertMutable)();
        array_push($this->except, ...array_values($pageIds));

        return $this;
    }

    /**
     * Omit a page, and its subtree, from this menu for requests where the
     * callback returns true, for example for users without billing access.
     *
     * The callback receives the request Context and must return a bool. It runs
     * only when the menu reaches the page, before the page's label or URL.
     * Breadcrumbs, other menus, and ancestor active state are unaffected. This
     * is presentation, never HTTP authorization: protect the route as well.
     *
     * @param  callable(Context): mixed  $predicate
     *
     * @throws InvalidDefinition If the menu registry has been resolved.
     */
    public function exceptWhen(string $pageId, callable $predicate): self
    {
        ($this->assertMutable)();
        $this->exceptWhen[$pageId][] = Closure::fromCallable($predicate);

        return $this;
    }

    /**
     * @internal Read by MenuRegistry when it finalizes.
     *
     * @return array<string, list<Closure(Context): mixed>>
     */
    public function conditionalExclusions(): array
    {
        return $this->exceptWhen;
    }

    /**
     * @internal Read by MenuRegistry when it finalizes.
     *
     * @return list<string>
     */
    public function exclusions(): array
    {
        return $this->except;
    }
}
