<?php

declare(strict_types=1);

namespace Current\Actions;

/**
 * A resolved, visible action for the current request.
 *
 * Hidden actions are never returned. A disabled action keeps its reason when
 * one is known. `current` marks a GET action whose target is the current
 * request, such as Edit on the edit page. Serializes to
 * `{ id, name, label, url, method, icon, destructive, enabled, reason, confirm, current }`.
 */
final readonly class ActionItem
{
    /** @internal Created by Actions. */
    public function __construct(
        public string $id,
        public string $name,
        public string $label,
        public string $url,
        public string $method,
        public ?string $icon,
        public bool $destructive,
        public bool $enabled,
        public ?string $reason,
        public ?string $confirm,
        public bool $current,
    ) {}
}
