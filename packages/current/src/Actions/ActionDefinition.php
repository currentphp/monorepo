<?php

declare(strict_types=1);

namespace Current\Actions;

use Closure;

/** Immutable registration snapshot of an action; callbacks stay unevaluated until output is requested. */
final readonly class ActionDefinition
{
    /** @internal Created by ActionRegistry from an Action builder. */
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

    /** The action's identity: its kind or page, then its name, such as `task.archive`. */
    public function id(): string
    {
        return ($this->kind ?? $this->pageId).'.'.$this->name;
    }
}
