<?php

declare(strict_types=1);

namespace Current\Navigation;

use Closure;
use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Pages\PageDefinition;
use Current\Pages\PageRegistry;
use Current\Pages\Pages;
use Current\Support\Label;
use Throwable;

/**
 * Resolves breadcrumbs and named menus for the current Laravel request; does not enforce access.
 *
 * Page labels, URLs, context, and active ancestry come from the Pages resolver.
 */
final readonly class Navigation
{
    /** @internal Resolved from the container, scoped to the request or job. */
    public function __construct(
        private Pages $pages,
        private PageRegistry $registry,
        private MenuRegistry $menus,
    ) {}

    /**
     * Resolve root-first ancestry, including the current page and route-less parents.
     *
     * Returns an empty list for unregistered routes. Every breadcrumb keeps its URL,
     * including the current one; route-less pages have none. All labels use the
     * same active-request context.
     *
     * @return list<Breadcrumb>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If callbacks or destination URLs cannot be resolved.
     */
    public function breadcrumbs(): array
    {
        return $this->breadcrumbsFor($this->pages->context());
    }

    /**
     * @internal Shared resolution for ambient and explicit experiences.
     *
     * @return list<Breadcrumb>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If callbacks or destination URLs cannot be resolved.
     */
    public function breadcrumbsFor(Context $context): array
    {
        $current = $context->page;

        return array_map(fn (PageDefinition $page): Breadcrumb => new Breadcrumb(
            $page->id,
            $this->pages->label($page, $context),
            $this->pages->url($page, $context),
            $page->id === $current?->id,
            $this->pages->icon($page),
        ), $this->pages->activeTrail($context));
    }

    /**
     * Resolve a named menu into presentation data for this request.
     *
     * Current marks the exact page; active also marks page ancestors and explicit
     * menu parents with active visible children. Unregistered routes have no active
     * items. Callbacks may run repeatedly; output is not cached on the registries.
     *
     * @return list<MenuItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the menu is unknown, or callbacks or destination URLs cannot be resolved.
     */
    public function menu(string $name): array
    {
        return $this->menuFor($name, $this->pages->context());
    }

    /**
     * @internal Shared resolution for ambient and explicit experiences.
     *
     * @return list<MenuItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the menu is unknown, or callbacks or URLs cannot be resolved.
     */
    public function menuFor(string $name, Context $context): array
    {
        $activeIds = array_map(fn (PageDefinition $page): string => $page->id, $this->pages->activeTrail($context));

        $entries = $this->menus->entries($name);

        return $entries === null
            ? $this->items($name, $this->menus->roots($name), array_flip($this->menus->exclusions($name)), $this->menus->conditionalExclusions($name), $context, $activeIds)
            : $this->explicitItems($name, $entries, $context, $activeIds);
    }

    /**
     * @param  list<PageDefinition>  $pages
     * @param  array<string, int>  $excluded  Page IDs as keys.
     * @param  array<string, list<Closure(Context): mixed>>  $excludedWhen  Request-time exclusions by page ID.
     * @param  list<string>  $activeIds
     * @return list<MenuItem>
     */
    private function items(string $name, array $pages, array $excluded, array $excludedWhen, Context $context, array $activeIds): array
    {
        $items = [];
        foreach ($pages as $page) {
            if (isset($excluded[$page->id])) {
                continue;
            }
            // Guard before resolving the page's label, URL, or descendants.
            foreach ($excludedWhen[$page->id] ?? [] as $predicate) {
                if ($this->predicate($predicate, $context, "menu '{$name}' exclusion of page '{$page->id}'", 'exclusion')) {
                    continue 2;
                }
            }
            $items[] = new MenuItem(
                $page->id,
                $this->pages->label($page, $context),
                $this->pages->url($page, $context),
                $page->id === $context->page?->id,
                in_array($page->id, $activeIds, true),
                $this->items($name, $this->registry->children($page->id), $excluded, $excludedWhen, $context, $activeIds),
                $this->pages->icon($page),
            );
        }

        return $items;
    }

    /**
     * @param  list<MenuEntryDefinition>  $entries
     * @param  list<string>  $activeIds
     * @return list<MenuItem>
     */
    private function explicitItems(string $name, array $entries, Context $context, array $activeIds): array
    {
        $items = [];
        foreach ($entries as $entry) {
            $owner = "menu '{$name}' entry '{$entry->id}'";
            // Guard before resolving resource-dependent labels, URLs, or descendants.
            if ($entry->visibility !== null && ! $this->predicate($entry->visibility, $context, $owner, 'visibility')) {
                continue;
            }

            $children = $this->explicitItems($name, $entry->children, $context, $activeIds);
            if ($entry->isGroup && $children === []) {
                continue;
            }
            $page = $entry->isGroup ? null : $this->registry->page($entry->id);
            $label = $entry->label !== null
                ? Label::resolve($entry->label, $context, $owner)
                : ($page === null ? $entry->id : $this->pages->label($page, $context));
            $activeChild = false;
            foreach ($children as $child) {
                $activeChild = $activeChild || $child->active;
            }

            $items[] = new MenuItem(
                $page?->id,
                $label,
                $page === null ? null : $this->pages->url($page, $context),
                $page !== null && $page->id === $context->page?->id,
                $activeChild || ($page !== null && in_array($page->id, $activeIds, true)),
                $children,
                $entry->icon ?? ($page === null ? null : $this->pages->icon($page)),
                $entry->isGroup ? MenuItemKind::Group : MenuItemKind::Page,
            );
        }

        return $items;
    }

    /**
     * Run a menu predicate, such as a visibility or exclusion callback.
     *
     * @param  Closure(Context): mixed  $predicate
     *
     * @throws ResolutionFailed If the callback throws or does not return a bool.
     */
    private function predicate(Closure $predicate, Context $context, string $owner, string $fact): bool
    {
        try {
            $result = $predicate($context);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not resolve {$fact} for {$owner}: {$exception->getMessage()}", 0, $exception);
        }

        return is_bool($result) ? $result : throw new ResolutionFailed(ucfirst($fact)." callback for {$owner} must return a bool.");
    }
}
