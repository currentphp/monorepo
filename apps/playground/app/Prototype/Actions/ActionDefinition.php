<?php

declare(strict_types=1);

namespace App\Prototype\Actions;

use Closure;

/** Prototype: an immutable registration snapshot of an action. */
final readonly class ActionDefinition
{
    public function __construct(
        public string $name,
        public ?string $kind,
        public ?string $pageId,
        public string|Closure $label,
        public string $routeName,
        public string $method,
        public ?Closure $routeParameters,
        public ?string $icon,
        public string|Closure|null $confirm,
        public bool $destructive,
        public int $order,
        public ?Closure $visibleWhen,
        public ?Closure $enabledWhen,
        public ?string $ability,
    ) {}

    /** Identity in output: `task.archive` or `projects.tasks.create`. */
    public function id(): string
    {
        return ($this->kind ?? $this->pageId).'.'.$this->name;
    }
}
