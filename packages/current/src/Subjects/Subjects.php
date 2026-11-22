<?php

declare(strict_types=1);

namespace Current\Subjects;

use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Pages\PageRegistry;
use Current\Pages\Pages;
use Current\Support\DestinationUrls;
use Current\Support\RouteParameters;
use Illuminate\Contracts\Routing\UrlRoutable;

/**
 * Resolves subjects: any object's subject, and the current request's.
 *
 * Reads objects the application has bound; never loads or queries them.
 */
final class Subjects
{
    /** @internal Resolved from the container, scoped to the request or job. */
    public function __construct(
        private readonly SubjectResolver $resolver,
        private readonly PageRegistry $pages,
        private readonly Pages $reader,
        private readonly DestinationUrls $urls,
    ) {}

    /**
     * Resolve the subject for any object of a registered kind, with its canonical URL.
     *
     * Works outside a request (queue jobs, notifications, commands) wherever
     * Laravel can generate URLs.
     *
     * @throws InvalidDefinition If definitions are invalid.
     * @throws ResolutionFailed If no kind accepts the object, or its label, route key, or URL cannot be resolved.
     */
    public function of(object $object): SubjectItem
    {
        return $this->item($this->resolver->kindOf($object), $object);
    }

    /**
     * Resolve an object's title for headings: its kind's title, else its label.
     *
     * Runs only the title callback, unlike of(), so it suits one object at a time.
     *
     * @throws InvalidDefinition If definitions are invalid.
     * @throws ResolutionFailed If no kind accepts the object, or a callback fails or returns an empty or non-string value.
     */
    public function title(object $object): string
    {
        return $this->resolver->title($this->resolver->kindOf($object), $object);
    }

    /**
     * Resolve an object's description, or null when its kind has none; never the label.
     *
     * @throws InvalidDefinition If definitions are invalid.
     * @throws ResolutionFailed If no kind accepts the object, or the callback fails or returns anything but a non-empty string or null.
     */
    public function description(object $object): ?string
    {
        return $this->resolver->description($this->resolver->kindOf($object), $object);
    }

    /**
     * Resolve the subject the current request is about: the active page's, or
     * else the nearest ancestor page's that presents one; null when none does
     * or outside a request.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the presented route parameter is not a bound object, or the subject cannot be resolved.
     */
    public function current(): ?SubjectItem
    {
        return $this->currentFor($this->reader->context());
    }

    /**
     * @internal Shared resolution for ambient and explicit experiences.
     *
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the presented subject or its output cannot be resolved.
     */
    public function currentFor(Context $context): ?SubjectItem
    {
        $subject = $this->resolver->current($context);

        return $subject === null ? null : $this->item($subject['kind'], $subject['object']);
    }

    /**
     * @internal Resolves only the selected subject's title-or-label.
     *
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the subject or its title cannot be resolved.
     */
    public function titleFor(Context $context): ?string
    {
        $subject = $this->resolver->current($context);

        return $subject === null ? null : $this->resolver->title($subject['kind'], $subject['object']);
    }

    /**
     * @internal Resolves only the selected subject's nullable description.
     *
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the subject or its description cannot be resolved.
     */
    public function descriptionFor(Context $context): ?string
    {
        $subject = $this->resolver->current($context);

        return $subject === null ? null : $this->resolver->description($subject['kind'], $subject['object']);
    }

    /**
     * Describe every problem the registry cannot check without Laravel: kinds
     * whose class is not routable, and presenting pages whose route lacks the parameter.
     *
     * @internal Read by current:check; readers throw the first problem.
     *
     * @return list<string>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function problems(): array
    {
        return $this->resolver->problems();
    }

    /** @throws ResolutionFailed */
    private function item(SubjectDefinition $kind, object $object): SubjectItem
    {
        if (! $object instanceof UrlRoutable) {
            throw new ResolutionFailed("Subject '{$kind->name}' needs a routable object for its identity.");
        }
        $key = $object->getRouteKey();
        if (! is_scalar($key)) {
            throw new ResolutionFailed("Subject '{$kind->name}' has a route key that is not a scalar.");
        }

        return new SubjectItem(
            $kind->name,
            (string) $key,
            $this->resolver->label($kind, $object),
            $this->url($kind, $object),
        );
    }

    /** @throws ResolutionFailed */
    private function url(SubjectDefinition $kind, object $object): ?string
    {
        if ($kind->pageId === null) {
            return null;
        }
        $parameters = $kind->routeParameters === null
            ? [$kind->name => $object]
            : RouteParameters::resolve($kind->routeParameters, $object, "subject '{$kind->name}'");

        $page = $this->pages->page($kind->pageId);

        return $page->routeName === null ? null : $this->urls->generate($page->routeName, $parameters, "subject '{$kind->name}' on page '{$page->id}'");
    }
}
