<?php

declare(strict_types=1);

namespace Current;

use Current\Actions\ActionItem;
use Current\Actions\Actions;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Navigation\Breadcrumb;
use Current\Navigation\MenuItem;
use Current\Navigation\Navigation;
use Current\Pages\PageDefinition;
use Current\Pages\Pages;
use Current\Subjects\SubjectItem;
use Current\Subjects\Subjects;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;

/**
 * Reads the current request's page presentation, subject, actions, navigation, and context.
 *
 * Read-only; register declarations through their owning registries.
 * Inject this class or use the Current\Facades\Current facade. The container's
 * singleton holds no request state. forPage() returns a separate reader holding
 * an explicit context snapshot; do not retain that reader across requests/jobs.
 */
final readonly class Experience
{
    /** @internal Resolved from the container as a singleton. */
    public function __construct(private Container $container, private ?Context $boundContext = null) {}

    /**
     * Describe a registered page using server-resolved route parameters.
     *
     * Validates page/subject declarations and parameter names without evaluating
     * presentation callbacks. Binding values and URLs are resolved only when a
     * read needs them. The user is taken from the current request, or null
     * outside HTTP. Leaves this reader and the actual request unchanged.
     * Objects remain references: load/refresh them and recreate the reader after
     * mutations. Never retain across requests, or trust client data as authorization.
     *
     * @param  array<string, mixed>  $parameters  Bound values keyed by route parameter name.
     *
     * @throws InvalidDefinition If page/subject declarations or route references are invalid.
     * @throws ResolutionFailed If the page is unknown, parameter names are invalid, or the current user is invalid.
     */
    public function forPage(string $pageId, array $parameters = []): self
    {
        return new self($this->container, $this->pages()->contextFor($pageId, $parameters));
    }

    /**
     * The selected page; ambient reads return null on unmatched or unregistered routes.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     */
    public function page(): ?PageDefinition
    {
        return $this->boundContext === null ? $this->pages()->current() : $this->boundContext->page;
    }

    /**
     * The selected page's ID; ambient reads return null on unmatched or unregistered routes.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     */
    public function pageId(): ?string
    {
        return $this->page()?->id;
    }

    /**
     * Whether the selected page's ID matches any pattern; `*` is a wildcard.
     *
     * Checks the selected page only, not its ancestors. False when no page is selected.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     */
    public function is(string ...$patterns): bool
    {
        $id = $this->pageId();

        return $id !== null && $patterns !== [] && Str::is($patterns, $id);
    }

    /**
     * The selected page's title for headings and the document title, including
     * subject/label fallbacks; null when no page is selected.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If a title or label callback cannot be resolved.
     */
    public function title(): ?string
    {
        $context = $this->context();

        return $context->page === null ? null : $this->pages()->title($context->page, $context);
    }

    /**
     * The selected page's description; null when it has none or no page is selected.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the description callback cannot be resolved.
     */
    public function description(): ?string
    {
        $context = $this->context();

        return $context->page === null ? null : $this->pages()->description($context->page, $context);
    }

    /**
     * Breadcrumbs for the selected page, root first; empty when no page is selected.
     *
     * @return list<Breadcrumb>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If callbacks or destination URLs cannot be resolved.
     */
    public function breadcrumbs(): array
    {
        return $this->navigation()->breadcrumbsFor($this->context());
    }

    /**
     * A named menu resolved for this experience's context.
     *
     * @return list<MenuItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the menu is unknown, or callbacks or destination URLs cannot be resolved.
     */
    public function menu(string $name): array
    {
        return $this->navigation()->menuFor($name, $this->context());
    }

    /**
     * The context passed to label, parameter, and visibility callbacks.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If route parameters or the current user are invalid.
     */
    public function context(): Context
    {
        return $this->boundContext ?? $this->pages()->context();
    }

    /**
     * The selected page's subject, or else the nearest
     * ancestor page's that presents one; null when none does.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the presented subject is not bound or cannot be resolved.
     */
    public function subject(): ?SubjectItem
    {
        return $this->container->make(Subjects::class)->currentFor($this->context());
    }

    /**
     * The selected subject's own title-or-label, independent of page overrides.
     *
     * Uses the nearest presenting ancestor; null when no subject exists. Does
     * not resolve the subject's URL, description, or unrelated callbacks.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the subject or its title cannot be resolved.
     */
    public function subjectTitle(): ?string
    {
        return $this->container->make(Subjects::class)->titleFor($this->context());
    }

    /**
     * The selected subject's own description, independent of page overrides.
     *
     * Uses the nearest presenting ancestor; null without a subject or description.
     * Does not resolve its label, title, URL, or unrelated callbacks.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the subject or its description cannot be resolved.
     */
    public function subjectDescription(): ?string
    {
        return $this->container->make(Subjects::class)->descriptionFor($this->context());
    }

    /**
     * Visible actions for this experience: the selected subject's, then the
     * selected page's, each in order; empty when no page is selected. Current
     * describes actions and never runs them. Explicit readers mark GET actions
     * current by the selected page's route and resolved URL; ambient readers
     * use the actual request route and URL.
     *
     * @return list<ActionItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the presented subject is not bound, or a callback or URL cannot be resolved.
     */
    public function actions(): array
    {
        return $this->container->make(Actions::class)->currentFor($this->context(), $this->boundContext !== null);
    }

    /**
     * The current user as Current callbacks see it; null for guests.
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If route parameters or the current user are invalid.
     */
    public function user(): ?Authenticatable
    {
        return $this->context()->user;
    }

    private function pages(): Pages
    {
        return $this->container->make(Pages::class);
    }

    private function navigation(): Navigation
    {
        return $this->container->make(Navigation::class);
    }
}
