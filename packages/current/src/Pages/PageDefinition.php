<?php

declare(strict_types=1);

namespace Current\Pages;

use Closure;
use Current\Context;

/** Immutable registration snapshot; callbacks remain unevaluated until output is requested. */
final readonly class PageDefinition
{
    /**
     * @internal Created by PageRegistry from a Page builder.
     *
     * @param  string|Closure(Context): mixed  $label
     * @param  null|Closure(Context): mixed  $routeParameters
     * @param  null|string|Closure(Context): mixed  $title  Null falls back to the label.
     * @param  null|string|Closure(Context): mixed  $description  Null means no description.
     */
    public function __construct(
        public string $id,
        public string|Closure $label,
        public ?string $routeName,
        public ?string $parentId,
        public int $order,
        public ?Closure $routeParameters = null,
        public ?string $icon = null,
        public ?string $presents = null,
        public ?string $presentsParameter = null,
        public bool $labelled = true,
        public string|Closure|null $title = null,
        public string|Closure|null $description = null,
    ) {}
}
