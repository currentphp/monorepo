<?php

declare(strict_types=1);

namespace Current;

use Current\Exceptions\ResolutionFailed;
use Current\Pages\PageDefinition;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Experience snapshot passed to every Current label, parameter, and visibility callback.
 *
 * Page is the selected page (the active page for ambient readers), not necessarily
 * the ancestor or menu entry being resolved. Page may be null on unregistered
 * routes; user is null for guests and explicit readers outside HTTP.
 * Bound objects are references, not deep copies; do not retain across requests.
 */
final readonly class Context
{
    /**
     * @internal Created by Pages::context() or Pages::contextFor().
     *
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public ?PageDefinition $page,
        private array $parameters,
        public ?Authenticatable $user = null,
    ) {}

    /**
     * Read a named bound route value; a present null value is returned unchanged.
     *
     * @throws ResolutionFailed If the parameter name is absent.
     */
    public function parameter(string $name): mixed
    {
        if (! array_key_exists($name, $this->parameters)) {
            $pageId = $this->page->id ?? '(unregistered)';
            throw new ResolutionFailed("Active page '{$pageId}' is missing route parameter '{$name}'.");
        }

        return $this->parameters[$name];
    }

    /**
     * Read all active-route parameters, including bound models after binding middleware.
     *
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        return $this->parameters;
    }
}
