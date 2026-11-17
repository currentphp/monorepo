<?php

declare(strict_types=1);

namespace App\WavyKit\Subjects;

use JsonSerializable;

/**
 * Prototype (WK-019): one resolved subject, the same in every reader.
 *
 * Identity is the kind plus the object's route key, for example `project:42`.
 */
final readonly class Subject implements JsonSerializable
{
    public function __construct(
        public string $kind,
        public string $id,
        public string $label,
        public ?string $url,
    ) {}

    public function key(): string
    {
        return "{$this->kind}:{$this->id}";
    }

    /** @return array{kind: string, id: string, label: string, url: ?string} */
    public function jsonSerialize(): array
    {
        return ['kind' => $this->kind, 'id' => $this->id, 'label' => $this->label, 'url' => $this->url];
    }
}
