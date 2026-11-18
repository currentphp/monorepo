<?php

declare(strict_types=1);

namespace App\Prototype\Actions;

/**
 * Prototype of Current\Actions\ActionItem: a resolved, visible action.
 *
 * Serializes to `{ id, name, label, url, method, icon, destructive, enabled, reason, confirm }`.
 */
final readonly class ActionItem
{
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
    ) {}
}
