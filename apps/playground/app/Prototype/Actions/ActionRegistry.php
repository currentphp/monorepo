<?php

declare(strict_types=1);

namespace App\Prototype\Actions;

use LogicException;

/** Prototype: holds action definitions by subject kind and page. */
final class ActionRegistry
{
    /** @var array<string, ActionDefinition> */
    private array $definitions = [];

    public function register(Action ...$actions): void
    {
        foreach ($actions as $action) {
            $definition = $action->toDefinition();
            if (isset($this->definitions[$definition->id()])) {
                throw new LogicException("Action '{$definition->id()}' is registered twice.");
            }
            $this->definitions[$definition->id()] = $definition;
        }
    }

    /** @return list<ActionDefinition> */
    public function all(): array
    {
        return array_values($this->definitions);
    }

    /** @return list<ActionDefinition> */
    public function forKind(string $kind): array
    {
        return $this->sorted(fn (ActionDefinition $action): bool => $action->kind === $kind);
    }

    /** @return list<ActionDefinition> */
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
        $actions = array_values(array_filter($this->definitions, $filter));
        usort($actions, fn (ActionDefinition $a, ActionDefinition $b): int => $a->order <=> $b->order);

        return $actions;
    }
}
