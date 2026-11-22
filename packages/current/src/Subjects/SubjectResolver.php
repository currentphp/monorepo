<?php

declare(strict_types=1);

namespace Current\Subjects;

use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Pages\PageDefinition;
use Current\Pages\PageRegistry;
use Current\Support\Label;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Routing\Router;

/** @internal Resolves and validates subject identity and presentation without assembling unrelated output. */
final class SubjectResolver
{
    private bool $validated = false;

    public function __construct(
        private readonly SubjectRegistry $registry,
        private readonly PageRegistry $pages,
        private readonly Router $router,
    ) {}

    /** @return list<string> */
    public function problems(): array
    {
        $problems = [];
        foreach ($this->registry->all() as $kind) {
            if (! is_a($kind->class, UrlRoutable::class, true)) {
                $problems[] = "Subject '{$kind->name}' class {$kind->class} must be routable (implement UrlRoutable) to have an identity.";
            }
        }
        foreach ($this->pages->all() as $page) {
            if ($page->presents === null) {
                continue;
            }
            $parameter = (string) $page->presentsParameter;
            $route = $page->routeName === null ? null : $this->router->getRoutes()->getByName($page->routeName);
            if ($page->routeName !== null && $route === null) {
                continue; // Reported as an unknown route by Pages.
            }
            if ($route === null || ! in_array($parameter, $route->parameterNames(), true)) {
                $problems[] = "Page '{$page->id}' presents subject '{$page->presents}', but its route has no '{$parameter}' parameter.";
            }
        }

        return $problems;
    }

    /** @throws InvalidDefinition */
    public function validate(): void
    {
        if ($this->validated) {
            return;
        }
        $problems = $this->problems();
        if ($problems !== []) {
            throw new InvalidDefinition($problems[0]);
        }
        $this->validated = true;
    }

    /**
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the subject cannot be resolved.
     */
    public function kind(string $name): SubjectDefinition
    {
        $this->validate();

        return $this->registry->kind($name);
    }

    /**
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the subject cannot be resolved.
     */
    public function kindOf(object $object): SubjectDefinition
    {
        $this->validate();

        return $this->registry->forObject($object)
            ?? throw new ResolutionFailed('No subject kind accepts '.$object::class.'.');
    }

    /**
     * @return array{kind: SubjectDefinition, object: object}|null
     *
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the presented object is missing or has the wrong class.
     */
    public function current(Context $context): ?array
    {
        $this->validate();
        if ($context->page === null) {
            return null;
        }
        foreach (array_reverse($this->pages->trail($context->page->id)) as $page) {
            if ($page->presents !== null) {
                return ['kind' => $this->kind($page->presents), 'object' => $this->presentedObject($page, $context)];
            }
        }

        return null;
    }

    /**
     * @throws InvalidDefinition If subject declarations are invalid.
     * @throws ResolutionFailed If the subject cannot be resolved.
     */
    public function presentedObject(PageDefinition $page, Context $context): object
    {
        $parameter = $page->presentsParameter ?? throw new ResolutionFailed("Page '{$page->id}' presents no subject.");
        $kind = $this->kind((string) $page->presents);
        $object = $context->parameters()[$parameter] ?? null;
        if (! is_object($object)) {
            throw new ResolutionFailed("Page '{$page->id}' presents subject '{$kind->name}', but route parameter '{$parameter}' is not a bound object.");
        }
        if (! $object instanceof $kind->class) {
            throw new ResolutionFailed("Page '{$page->id}' presents subject '{$kind->name}', but route parameter '{$parameter}' must be an instance of {$kind->class}; got ".$object::class.'.');
        }

        return $object;
    }

    /** @throws ResolutionFailed If the presentation callback fails or returns an invalid value. */
    public function label(SubjectDefinition $kind, object $object): string
    {
        return Label::resolve($kind->label, $object, "subject '{$kind->name}'");
    }

    /** @throws ResolutionFailed If the presentation callback fails or returns an invalid value. */
    public function title(SubjectDefinition $kind, object $object): string
    {
        if ($kind->title === null) {
            return $this->label($kind, $object);
        }
        $title = Label::resolve($kind->title, $object, "subject '{$kind->name}'", 'title');

        return Label::nonEmpty($title, "subject '{$kind->name}'", 'title');
    }

    /** @throws ResolutionFailed If the presentation callback fails or returns an invalid value. */
    public function description(SubjectDefinition $kind, object $object): ?string
    {
        return $kind->description === null
            ? null
            : Label::resolveOptional($kind->description, $object, "subject '{$kind->name}'", 'description');
    }
}
