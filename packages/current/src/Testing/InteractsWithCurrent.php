<?php

declare(strict_types=1);

namespace Current\Testing;

use Current\Actions\ActionItem;
use Current\Facades\Current;
use Current\Navigation\MenuItem;
use PHPUnit\Framework\Assert;

/**
 * Assertions about the last test request's page, breadcrumbs, menus, and actions.
 *
 * Add the trait to your application's TestCase, make a request, then assert:
 * `$this->get('/projects/42'); $this->assertCurrentPage('projects.show');`.
 * Assertions read the application's state after the request through
 * Current\Facades\Current, so callbacks see that request's bound models and
 * user. Requires PHPUnit, which Laravel and Pest test suites already provide.
 */
trait InteractsWithCurrent
{
    /** Assert the active page's ID; null asserts that no page is active. */
    public function assertCurrentPage(?string $id): static
    {
        $actual = Current::pageId();
        Assert::assertSame($id, $actual, sprintf(
            'Expected the current page to be %s, but it was %s.',
            $id === null ? 'none' : "'{$id}'",
            $actual === null ? 'none' : "'{$actual}'",
        ));

        return $this;
    }

    /** Assert the active page's title, which falls back to its label. */
    public function assertTitle(string $title): static
    {
        $actual = Current::title();
        Assert::assertSame($title, $actual, sprintf(
            "Expected the title '%s', but it was %s.",
            $title,
            $actual === null ? 'none (no page is active)' : "'{$actual}'",
        ));

        return $this;
    }

    /** Assert the active page's description; null asserts that it has none. */
    public function assertDescription(?string $description): static
    {
        $actual = Current::description();
        Assert::assertSame($description, $actual, sprintf(
            'Expected the description %s, but it was %s.',
            $description === null ? 'to be empty' : "'{$description}'",
            $actual === null ? 'empty' : "'{$actual}'",
        ));

        return $this;
    }

    /**
     * Assert the breadcrumb labels, root first and in order.
     *
     * @param  list<string>  $labels
     */
    public function assertBreadcrumbs(array $labels): static
    {
        $actual = array_map(fn ($crumb): string => $crumb->label, Current::breadcrumbs());
        Assert::assertSame($labels, $actual, sprintf(
            'Expected breadcrumbs [%s], but they were [%s].',
            implode(' > ', $labels),
            implode(' > ', $actual),
        ));

        return $this;
    }

    /**
     * Assert the current subject's kind, and its ID when given; a null kind
     * asserts that the request has no subject.
     */
    public function assertCurrentSubject(?string $kind, ?string $id = null): static
    {
        $subject = Current::subject();
        $actual = $subject?->key() ?? 'none';
        $expected = $kind === null ? 'none' : ($id === null ? "{$kind}:*" : "{$kind}:{$id}");
        $matches = $kind === null
            ? $subject === null
            : $subject !== null && $subject->kind === $kind && ($id === null || $subject->id === $id);
        Assert::assertTrue($matches, "Expected the current subject to be {$expected}, but it was {$actual}.");

        return $this;
    }

    /** Assert that a menu shows a page at any depth for this request. */
    public function assertMenuHas(string $menu, string $pageId): static
    {
        $items = Current::menu($menu);
        Assert::assertNotNull(
            $this->findCurrentMenuItem($items, $pageId),
            "Expected menu '{$menu}' to show page '{$pageId}'.\n".$this->describeCurrentMenu($items),
        );

        return $this;
    }

    /** Assert that a menu does not show a page at any depth for this request. */
    public function assertMenuMissing(string $menu, string $pageId): static
    {
        $items = Current::menu($menu);
        Assert::assertNull(
            $this->findCurrentMenuItem($items, $pageId),
            "Expected menu '{$menu}' not to show page '{$pageId}'.\n".$this->describeCurrentMenu($items),
        );

        return $this;
    }

    /** Assert that a menu shows a page and marks it active for this request. */
    public function assertMenuActive(string $menu, string $pageId): static
    {
        $items = Current::menu($menu);
        $item = $this->findCurrentMenuItem($items, $pageId);
        Assert::assertTrue(
            $item?->active === true,
            sprintf(
                "Expected page '%s' to be active in menu '%s', but it was %s.\n%s",
                $pageId,
                $menu,
                $item === null ? 'not in the menu' : 'inactive',
                $this->describeCurrentMenu($items),
            ),
        );

        return $this;
    }

    /** Assert that the request's actions include this action ID, such as `task.archive`. */
    public function assertActionHas(string $id): static
    {
        Assert::assertNotNull($this->findAction($id), sprintf(
            "Expected action '%s', but the actions were [%s].",
            $id,
            $this->describeActions(),
        ));

        return $this;
    }

    /** Assert that the request's actions leave out this action ID, as when visibleWhen() hides it. */
    public function assertActionMissing(string $id): static
    {
        Assert::assertNull($this->findAction($id), sprintf(
            "Expected no action '%s', but the actions were [%s].",
            $id,
            $this->describeActions(),
        ));

        return $this;
    }

    /** Assert that the action is shown and enabled. */
    public function assertActionEnabled(string $id): static
    {
        $action = $this->findAction($id);
        Assert::assertTrue($action?->enabled === true, sprintf(
            "Expected action '%s' to be enabled, but %s.",
            $id,
            $action === null ? 'it was not shown' : 'it was disabled'.($action->reason === null ? '' : " ({$action->reason})"),
        ));

        return $this;
    }

    /** Assert that the action is shown and disabled, with this reason when one is given. */
    public function assertActionDisabled(string $id, ?string $reason = null): static
    {
        $action = $this->findAction($id);
        Assert::assertTrue($action !== null && ! $action->enabled, sprintf(
            "Expected action '%s' to be disabled, but %s.",
            $id,
            $action === null ? 'it was not shown' : 'it was enabled',
        ));
        if ($reason !== null) {
            Assert::assertSame($reason, $action->reason, sprintf(
                "Expected action '%s' to be disabled because '%s', but the reason was %s.",
                $id,
                $reason,
                $action->reason === null ? 'none' : "'{$action->reason}'",
            ));
        }

        return $this;
    }

    private function findAction(string $id): ?ActionItem
    {
        foreach (Current::actions() as $action) {
            if ($action->id === $id) {
                return $action;
            }
        }

        return null;
    }

    private function describeActions(): string
    {
        return implode(', ', array_map(
            fn (ActionItem $action): string => $action->id.($action->enabled ? '' : ' (disabled)'),
            Current::actions(),
        ));
    }

    /** @param list<MenuItem> $items */
    private function findCurrentMenuItem(array $items, string $pageId): ?MenuItem
    {
        foreach ($items as $item) {
            if ($item->pageId === $pageId) {
                return $item;
            }
            $found = $this->findCurrentMenuItem($item->children, $pageId);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /** @param list<MenuItem> $items */
    private function describeCurrentMenu(array $items, int $depth = 0): string
    {
        if ($items === [] && $depth === 0) {
            return 'The menu is empty.';
        }
        $lines = $depth === 0 ? "The menu was:\n" : '';
        foreach ($items as $item) {
            $lines .= str_repeat('  ', $depth + 1).'- '.$item->label
                .' ('.($item->pageId ?? 'group').')'
                .($item->active ? ' [active]' : '')
                ."\n"
                .$this->describeCurrentMenu($item->children, $depth + 1);
        }

        return $lines;
    }
}
