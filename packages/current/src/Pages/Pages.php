<?php

declare(strict_types=1);

namespace Current\Pages;

use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Subjects\SubjectResolver;
use Current\Support\DestinationUrls;
use Current\Support\Label;
use Current\Support\RouteParameters;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

/** Resolves registered pages for the current Laravel request; does not create routes or enforce access. */
final class Pages
{
    /** Route references are checked once per request or job; registries are frozen by then. */
    private bool $routesChecked = false;

    /** @internal Resolved from the container, scoped to the request or job. */
    public function __construct(
        private readonly PageRegistry $registry,
        private readonly Router $router,
        private readonly DestinationUrls $urls,
        private readonly SubjectResolver $subjects,
    ) {}

    /**
     * Resolve the current request's exact named-route association.
     *
     * Returns null for unmatched or unregistered routes; callbacks remain unevaluated.
     * Resolve after routes have loaded. This validates all registered route references.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     */
    public function current(): ?PageDefinition
    {
        $this->validateRoutes();
        $this->subjects->validate();

        return $this->registry->forRoute($this->activeRoute()?->getName());
    }

    /**
     * Snapshot the active page, bound route parameters, and nullable current user.
     *
     * Resolve after binding middleware when callbacks require models. Do not retain
     * the context across requests or on application singletons.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If route parameters or the current user are invalid.
     */
    public function context(): Context
    {
        $parameters = [];
        foreach ($this->activeRoute()?->parameters() ?? [] as $name => $value) {
            if (! is_string($name)) {
                throw new ResolutionFailed('Active route parameters must be keyed by name.');
            }
            $parameters[$name] = $value;
        }

        $user = $this->requestUser();

        return new Context($this->current(), $parameters, $user);
    }

    /**
     * @internal Creates a local page context without evaluating presentation callbacks.
     *
     * @param  array<array-key, mixed>  $parameters  Raw caller input; names are validated before the Context is created.
     *
     * @throws InvalidDefinition If page or subject declarations or route references are invalid.
     * @throws ResolutionFailed If the page is unknown, parameter names are invalid, or the request user is invalid.
     */
    public function contextFor(string $pageId, array $parameters): Context
    {
        $this->validateRoutes();
        $this->subjects->validate();
        $page = $this->registry->page($pageId);
        $named = [];
        foreach ($parameters as $name => $value) {
            if (! is_string($name) || trim($name) === '') {
                throw new ResolutionFailed("Page '{$pageId}' route parameters must be keyed by non-empty names.");
            }
            $named[$name] = $value;
        }

        return new Context($page, $named, $this->requestUser());
    }

    /**
     * Read the active page's ancestry, root first; empty when no page is active.
     *
     * @return list<PageDefinition>
     *
     * @throws InvalidDefinition If page or subject definitions are invalid.
     */
    public function activeTrail(Context $context): array
    {
        $this->subjects->validate();

        return $context->page === null ? [] : $this->registry->trail($context->page->id);
    }

    /**
     * Resolve a page's label, running its callback with the given context.
     *
     * A page that presents a subject and has no label of its own takes the
     * subject's label, from the context's bound route parameter.
     *
     * @throws InvalidDefinition If subject definitions are invalid.
     * @throws ResolutionFailed If a callback fails or returns a non-string, or the presented subject is not bound.
     */
    public function label(PageDefinition $page, Context $context): string
    {
        $this->subjects->validate();

        if ($page->presents === null || $page->labelled) {
            return Label::resolve($page->label, $context, "page '{$page->id}'");
        }

        return $this->subjects->label($this->subjects->kind($page->presents), $this->subjects->presentedObject($page, $context));
    }

    /**
     * Resolve a page's title for headings and the document title.
     *
     * The page's own title wins; a page that presents a subject then takes the
     * subject's title; otherwise the title is the page's label.
     *
     * @throws InvalidDefinition If subject definitions are invalid.
     * @throws ResolutionFailed If a callback fails or returns an empty or non-string value, or the presented subject is not bound.
     */
    public function title(PageDefinition $page, Context $context): string
    {
        $this->subjects->validate();

        if ($page->title !== null) {
            return Label::nonEmpty(Label::resolve($page->title, $context, "page '{$page->id}'", 'title'), "page '{$page->id}'", 'title');
        }

        if ($page->presents !== null) {
            return $this->subjects->title($this->subjects->kind($page->presents), $this->subjects->presentedObject($page, $context));
        }

        return $this->label($page, $context);
    }

    /**
     * Resolve a page's description: its own, else that of the subject it presents; never the label.
     *
     * @throws InvalidDefinition If subject definitions are invalid.
     * @throws ResolutionFailed If a callback fails or returns anything but a non-empty string or null, or the presented subject is not bound.
     */
    public function description(PageDefinition $page, Context $context): ?string
    {
        $this->subjects->validate();

        if ($page->description !== null) {
            return Label::resolveOptional($page->description, $context, "page '{$page->id}'", 'description');
        }

        return $page->presents === null
            ? null
            : $this->subjects->description($this->subjects->kind($page->presents), $this->subjects->presentedObject($page, $context));
    }

    /**
     * Read a page's icon: its own, else that of the subject kind it presents.
     *
     * @throws InvalidDefinition If subject definitions are invalid.
     */
    public function icon(PageDefinition $page): ?string
    {
        $this->subjects->validate();

        return $page->icon ?? ($page->presents === null ? null : $this->subjects->kind($page->presents)->icon);
    }

    /**
     * Generate a page's URL, or null for a route-less page.
     *
     * Forwards only parameters the destination route declares, from the page's
     * route-parameter mapping when present, otherwise from the active route.
     *
     * @throws InvalidDefinition If subject definitions are invalid or the page references an unknown route.
     * @throws ResolutionFailed If the mapping is invalid or URL generation fails.
     */
    public function url(PageDefinition $page, Context $context): ?string
    {
        $this->subjects->validate();

        if ($page->routeName === null) {
            return null;
        }
        $parameters = $page->routeParameters === null
            ? $context->parameters()
            : RouteParameters::resolve($page->routeParameters, $context, "page '{$page->id}'");

        return $this->urls->generate($page->routeName, $parameters, "page '{$page->id}'");
    }

    /**
     * Describe every page whose route is not registered, in registration order.
     *
     * @internal Read by current:check, which reports every problem at once.
     *
     * @return list<string>
     *
     * @throws InvalidDefinition If registry finalization fails.
     */
    public function unknownRoutes(): array
    {
        $problems = [];
        foreach ($this->registry->all() as $page) {
            if ($page->routeName !== null && $this->router->getRoutes()->getByName($page->routeName) === null) {
                $problems[] = "Page '{$page->id}' references unknown route '{$page->routeName}'.";
            }
        }

        return $problems;
    }

    private function validateRoutes(): void
    {
        if ($this->routesChecked) {
            return;
        }
        $problems = $this->unknownRoutes();
        if ($problems !== []) {
            throw new InvalidDefinition($problems[0]);
        }
        $this->routesChecked = true;
    }

    /** @throws ResolutionFailed If the request user does not implement Authenticatable. */
    private function requestUser(): ?Authenticatable
    {
        $user = $this->router->current() === null ? null : $this->router->getCurrentRequest()->user();
        if ($user !== null && ! $user instanceof Authenticatable) {
            throw new ResolutionFailed('The current request user must implement Authenticatable.');
        }

        return $user;
    }

    private function activeRoute(): ?Route
    {
        if ($this->router->current() === null) {
            return null;
        }

        // Older router versions retain their previous route after an unmatched request.
        return $this->router->getCurrentRequest()->route();
    }
}
