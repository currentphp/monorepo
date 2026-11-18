<?php

declare(strict_types=1);

namespace Current\Pages;

use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;

/** Declaration store shared across requests; contains no resolved user or resource output. */
final class PageRegistry
{
    /** @var array<string, PageDefinition> */
    private array $definitions = [];

    private bool $finalized = false;

    /** @var list<PageDefinition> Root pages, sorted; built at finalization. */
    private array $roots = [];

    /** @var array<string, list<PageDefinition>> Children by parent ID, sorted; built at finalization. */
    private array $childrenOf = [];

    /** @var array<string, PageDefinition> Pages by route name; built at finalization. */
    private array $byRoute = [];

    /**
     * Snapshot declarations without resolving labels, URLs, or visibility.
     *
     * Multiple calls may contribute pages, including parents referenced by earlier
     * calls. Complete registration before any read finalizes the registry.
     *
     * @throws InvalidDefinition If finalized, or IDs, route names, or nested parents are invalid.
     */
    public function register(Page ...$pages): void
    {
        $this->assertMutable();
        $pending = [];
        foreach ($pages as $page) {
            $this->collect($page, null, $pending);
        }

        $this->definitions += $pending;
    }

    /**
     * Validate references and freeze registrations; repeated calls are harmless.
     *
     * @internal All registry reads invoke this automatically. Laravel route
     * existence is checked separately when resolving output through Pages.
     *
     * @throws InvalidDefinition For missing or cyclic parents or duplicate route associations.
     */
    public function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        $byRoute = [];
        $roots = [];
        $childrenOf = [];
        foreach ($this->definitions as $page) {
            if ($page->routeName !== null) {
                if (isset($byRoute[$page->routeName])) {
                    throw new InvalidDefinition("Route '{$page->routeName}' belongs to multiple pages.");
                }
                $byRoute[$page->routeName] = $page;
            }
            $this->walk($page);
            if ($page->parentId === null) {
                $roots[] = $page;
            } else {
                $childrenOf[$page->parentId][] = $page;
            }
        }

        // Frozen from here, so lookups are indexed once; usort is stable, so ties keep registration order.
        $byOrder = fn (PageDefinition $a, PageDefinition $b): int => $a->order <=> $b->order;
        usort($roots, $byOrder);
        foreach ($childrenOf as &$children) {
            usort($children, $byOrder);
        }
        unset($children);

        $this->byRoute = $byRoute;
        $this->roots = $roots;
        $this->childrenOf = $childrenOf;
        $this->finalized = true;
    }

    /**
     * Read all immutable definitions in registration order, retaining callbacks.
     *
     * @return list<PageDefinition>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function all(): array
    {
        $this->finalize();

        return array_values($this->definitions);
    }

    /**
     * Find a definition by stable page ID without evaluating its callbacks.
     *
     * @throws InvalidDefinition If registry finalization fails.
     * @throws ResolutionFailed If the ID is unknown.
     */
    public function page(string $id): PageDefinition
    {
        $this->finalize();

        return $this->definitions[$id] ?? throw new ResolutionFailed("Unknown page '{$id}'.");
    }

    /**
     * Find an exact named-route association; null or unknown names return null.
     *
     * @internal Read by Pages; resolve the current page through Pages::current().
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function forRoute(?string $name): ?PageDefinition
    {
        $this->finalize();

        return $name === null ? null : ($this->byRoute[$name] ?? null);
    }

    /**
     * Read the complete page ancestry, including route-less pages.
     *
     * @internal Read by Pages; resolve ancestry through Pages::activeTrail().
     *
     * @return list<PageDefinition> Root first, including the requested page.
     *
     * @throws InvalidDefinition If registry finalization fails.
     * @throws ResolutionFailed If the ID is unknown.
     */
    public function trail(string $id): array
    {
        return array_reverse($this->walk($this->page($id)));
    }

    /**
     * Read immediate children in ascending order; ties retain registration order.
     *
     * Pass null for roots. An unknown parent ID returns an empty list.
     *
     * @internal Read by Navigation when it builds derived menus.
     *
     * @return list<PageDefinition>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function children(?string $parentId): array
    {
        $this->finalize();

        return $parentId === null ? $this->roots : ($this->childrenOf[$parentId] ?? []);
    }

    /** @param array<string, PageDefinition> $pending */
    private function collect(Page $page, ?string $nestedParent, array &$pending): void
    {
        if (trim($page->id) === '' || isset($pending[$page->id]) || isset($this->definitions[$page->id])) {
            throw new InvalidDefinition("Page '{$page->id}' must have a unique, non-empty ID.");
        }
        $declaredParent = $page->declaredParent();
        if ($nestedParent !== null && $declaredParent !== null && $nestedParent !== $declaredParent) {
            throw new InvalidDefinition("Page '{$page->id}' has conflicting parents '{$nestedParent}' and '{$declaredParent}'.");
        }
        $route = $page->declaredRoute();
        if ($route !== null && trim($route) === '') {
            throw new InvalidDefinition("Page '{$page->id}' has an empty route name.");
        }
        $definition = $page->toDefinition($nestedParent ?? $declaredParent);
        foreach (['title' => $definition->title, 'description' => $definition->description] as $fact => $value) {
            if (is_string($value) && trim($value) === '') {
                throw new InvalidDefinition("Page '{$page->id}' has an empty {$fact}.");
            }
        }
        $pending[$page->id] = $definition;
        foreach ($page->declaredChildren() as $child) {
            $this->collect($child, $page->id, $pending);
        }
    }

    /** @return list<PageDefinition> Leaf first. */
    private function walk(PageDefinition $page): array
    {
        $trail = [];
        $seen = [];
        while (true) {
            if (isset($seen[$page->id])) {
                throw new InvalidDefinition("Page hierarchy contains a cycle at '{$page->id}'.");
            }
            $seen[$page->id] = true;
            $trail[] = $page;
            if ($page->parentId === null) {
                return $trail;
            }
            $page = $this->definitions[$page->parentId]
                ?? throw new InvalidDefinition("Page '{$page->id}' references unknown parent '{$page->parentId}'.");
        }
    }

    private function assertMutable(): void
    {
        if ($this->finalized) {
            throw new InvalidDefinition('Page definitions must be registered before the registry is resolved.');
        }
    }
}
