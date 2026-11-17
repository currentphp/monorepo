<?php

declare(strict_types=1);

namespace App\WavyKit\Subjects;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Routing\UrlRoutable;
use LogicException;
use WavyKit\Context;
use WavyKit\Pages\Page;
use WavyKit\Pages\PageRegistry;

/**
 * Prototype (WK-019): subject kinds, and which pages present them.
 *
 * Built only on wavykit/core's public API. `presents()` stands in for a
 * future `Page::presents()`; `of()` gives any object's subject, so a subject
 * can be linked from anywhere.
 */
final class SubjectRegistry
{
    /** @var array<string, SubjectKind> */
    private array $kinds = [];

    /** @var array<string, array{kind: string, parameter: string}> Page ID => presented subject. */
    private array $presentations = [];

    public function __construct(
        private readonly PageRegistry $pages,
        private readonly UrlGenerator $urls,
    ) {}

    public function register(SubjectKind ...$kinds): void
    {
        foreach ($kinds as $kind) {
            if (isset($this->kinds[$kind->name])) {
                throw new LogicException("Subject kind '{$kind->name}' is already registered.");
            }
            $this->kinds[$kind->name] = $kind;
        }
    }

    /**
     * Mark a page as presenting a subject read from a route parameter, named
     * after the kind by default, and label the page with the subject's label.
     *
     * Awkward in the prototype: core has no `Page::presents()`, so this sets a
     * label callback and keeps the link here; a page with its own label must
     * not use it, because the builder cannot tell whether a label was set.
     */
    public function presents(Page $page, string $kind, ?string $parameter = null): Page
    {
        $parameter ??= $kind;
        $this->presentations[$page->id] = ['kind' => $kind, 'parameter' => $parameter];

        return $page->label(function (Context $context) use ($kind, $parameter): string {
            $object = $context->parameter($parameter);
            if (! is_object($object)) {
                throw new LogicException("Route parameter '{$parameter}' is not a bound {$kind}.");
            }

            return $this->kind($kind)->labelOf($object);
        });
    }

    /** @return array{kind: string, parameter: string}|null */
    public function presentedBy(string $pageId): ?array
    {
        return $this->presentations[$pageId] ?? null;
    }

    public function kind(string $name): SubjectKind
    {
        return $this->kinds[$name] ?? throw new LogicException("Unknown subject kind '{$name}'.");
    }

    /** The subject for any object of a registered kind, with its canonical URL. */
    public function of(object $object): Subject
    {
        foreach ($this->kinds as $kind) {
            if ($kind->accepts($object)) {
                return new Subject(
                    $kind->name,
                    $this->idOf($kind, $object),
                    $kind->labelOf($object),
                    $this->urlOf($kind, $object),
                );
            }
        }

        throw new LogicException('No subject kind accepts '.$object::class.'.');
    }

    private function idOf(SubjectKind $kind, object $object): string
    {
        if (! $object instanceof UrlRoutable) {
            throw new LogicException("Subject kind '{$kind->name}' needs a UrlRoutable object for its ID.");
        }
        $key = $object->getRouteKey();

        return is_scalar($key) ? (string) $key : throw new LogicException("Subject '{$kind->name}' has a non-scalar route key.");
    }

    /**
     * Awkward in the prototype: core's Pages::url() needs a request Context, so
     * a link built outside the subject's own route generates the URL here.
     */
    private function urlOf(SubjectKind $kind, object $object): ?string
    {
        $pageId = $kind->pageId();
        $route = $pageId === null ? null : $this->pages->page($pageId)->routeName;

        return $route === null ? null : $this->urls->route($route, $kind->routeParametersOf($object));
    }
}
