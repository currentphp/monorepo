<?php

declare(strict_types=1);

namespace Current\Actions;

use Current\Exceptions\InvalidDefinition;
use Current\Pages\PageRegistry;
use Current\Subjects\SubjectRegistry;

/** Holds action declarations, attached to subject kinds or pages. */
final class ActionRegistry
{
    /** @var array<string, ActionDefinition> Keyed by action ID. */
    private array $definitions = [];

    private bool $finalized = false;

    /** @internal Resolved from the container as a singleton. */
    public function __construct(
        private readonly PageRegistry $pages,
        private readonly SubjectRegistry $subjects,
    ) {}

    /**
     * Snapshot action declarations. A failed batch registers nothing.
     *
     * @throws InvalidDefinition If finalized, or a declaration is invalid or its ID is already used.
     */
    public function register(Action ...$actions): void
    {
        if ($this->finalized) {
            throw new InvalidDefinition('Actions cannot be registered after the action registry has been read.');
        }

        $pending = [];
        foreach ($actions as $action) {
            $definition = $action->toDefinition();
            $id = $definition->id();
            if (isset($pending[$id]) || isset($this->definitions[$id])) {
                throw new InvalidDefinition("Action '{$id}' is declared twice; names must be unique within a subject kind or page, and kind and page IDs must not collide.");
            }
            $pending[$id] = $definition;
        }
        $this->definitions += $pending;
    }

    /**
     * Validate subject kinds and pages against their registries and freeze actions.
     *
     * @internal All registry reads invoke this automatically.
     *
     * @throws InvalidDefinition For unknown subject kinds or pages.
     */
    public function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        $pages = [];
        foreach ($this->pages->all() as $page) {
            $pages[$page->id] = true;
        }
        $kinds = [];
        foreach ($this->subjects->all() as $kind) {
            $kinds[$kind->name] = true;
        }

        foreach ($this->definitions as $id => $action) {
            if ($action->kind !== null && ! isset($kinds[$action->kind])) {
                throw new InvalidDefinition("Action '{$id}' is for unknown subject '{$action->kind}'.");
            }
            if ($action->pageId !== null && ! isset($pages[$action->pageId])) {
                throw new InvalidDefinition("Action '{$id}' is on unknown page '{$action->pageId}'.");
            }
        }

        $this->finalized = true;
    }

    /**
     * Read every definition in registration order.
     *
     * @return list<ActionDefinition>
     *
     * @throws InvalidDefinition If finalization fails.
     */
    public function all(): array
    {
        $this->finalize();

        return array_values($this->definitions);
    }

    /**
     * Read a subject kind's actions in ascending order.
     *
     * @internal Read by Actions.
     *
     * @return list<ActionDefinition>
     *
     * @throws InvalidDefinition If finalization fails.
     */
    public function forKind(string $kind): array
    {
        return $this->sorted(fn (ActionDefinition $action): bool => $action->kind === $kind);
    }

    /**
     * Read a page's actions in ascending order.
     *
     * @internal Read by Actions.
     *
     * @return list<ActionDefinition>
     *
     * @throws InvalidDefinition If finalization fails.
     */
    public function forPage(string $pageId): array
    {
        return $this->sorted(fn (ActionDefinition $action): bool => $action->pageId === $pageId);
    }

    /**
     * @param  callable(ActionDefinition): bool  $filter
     * @return list<ActionDefinition>
     */
    private function sorted(callable $filter): array
    {
        $actions = array_values(array_filter($this->all(), $filter));
        // usort is stable, so ties keep registration order.
        usort($actions, fn (ActionDefinition $a, ActionDefinition $b): int => $a->order <=> $b->order);

        return $actions;
    }
}
