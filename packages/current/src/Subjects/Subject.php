<?php

declare(strict_types=1);

namespace Current\Subjects;

use Closure;
use Current\Exceptions\InvalidDefinition;

/**
 * A declaration builder for a kind of subject, such as a project or a task.
 *
 * Registration takes a snapshot. Callbacks receive the subject object, typed by
 * the application, and must be free of side effects; Current never loads or
 * queries objects, only describes ones the application has bound.
 */
final class Subject
{
    /** @var class-string|null */
    private ?string $class = null;

    private ?Closure $label = null;

    private ?string $pageId = null;

    private ?Closure $routeParameters = null;

    private ?Closure $title = null;

    private ?Closure $description = null;

    private ?string $icon = null;

    private function __construct(public readonly string $name) {}

    /** Start a declaration with a unique, non-empty kind name, such as `project`. */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * The class whose instances are subjects of this kind; it must be routable
     * (UrlRoutable, such as an Eloquent model) and must not overlap another kind's class.
     *
     * @param  class-string  $class
     */
    public function for(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /**
     * Label a subject from its object, for example `fn (Project $project) => $project->name`.
     *
     * Required. Pages that present this kind without their own label use it.
     */
    public function label(callable $label): self
    {
        $this->label = Closure::fromCallable($label);

        return $this;
    }

    /**
     * Title a subject from its object, for headings and the document title,
     * for example `fn (Task $task) => "Fix login in {$task->project->name}"`.
     *
     * Optional; without one the title is the label. Pages that present this kind
     * without their own title use it. Breadcrumbs keep the label. Must return a
     * non-empty string.
     */
    public function title(callable $title): self
    {
        $this->title = Closure::fromCallable($title);

        return $this;
    }

    /**
     * Describe a subject from its object; return a non-empty string, or null for none.
     *
     * Pages that present this kind without their own description use it. The
     * description never falls back to the label.
     */
    public function description(callable $description): self
    {
        $this->description = Closure::fromCallable($description);

        return $this;
    }

    /** Set an optional presentation identifier for every subject of this kind; the application renders it. */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Name the canonical page that shows a subject of this kind; that page must present it.
     *
     * By default the route parameter named after the kind receives the object.
     * Otherwise return an array keyed by route parameter name from the object,
     * for example `fn (Task $task) => ['project' => $task->project, 'task' => $task]`.
     * Without a page, a subject has no URL.
     */
    public function page(string $pageId, ?callable $routeParameters = null): self
    {
        $this->pageId = $pageId;
        $this->routeParameters = $routeParameters === null ? null : Closure::fromCallable($routeParameters);

        return $this;
    }

    /**
     * @internal Snapshot taken by SubjectRegistry.
     *
     * @throws InvalidDefinition If the class or label is missing.
     */
    public function toDefinition(): SubjectDefinition
    {
        if ($this->class === null || (! class_exists($this->class) && ! interface_exists($this->class))) {
            throw new InvalidDefinition("Subject '{$this->name}' must name an existing class with for().");
        }
        if ($this->label === null) {
            throw new InvalidDefinition("Subject '{$this->name}' must have a label().");
        }

        return new SubjectDefinition(
            $this->name,
            $this->class,
            $this->label,
            $this->pageId,
            $this->routeParameters,
            $this->title,
            $this->description,
            $this->icon,
        );
    }
}
