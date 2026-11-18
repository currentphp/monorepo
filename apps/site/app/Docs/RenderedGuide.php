<?php

declare(strict_types=1);

namespace App\Docs;

final readonly class RenderedGuide
{
    /**
     * @param  list<array{id: string, text: string, level: int}>  $headings
     */
    public function __construct(
        public string $html,
        public array $headings,
    ) {}
}
