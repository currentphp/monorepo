<?php

declare(strict_types=1);

namespace App\WavyKit\Subjects;

use Closure;
use LogicException;

/**
 * Prototype (WK-019): declares a kind of subject, such as a project or a task.
 *
 * Stands in for a future `WavyKit\Subjects\Subject::make()` builder in core.
 * Callbacks receive the subject object, typed by the application.
 */
final class SubjectKind
{
    /** @var class-string|null */
    private ?string $class = null;

    /** @var Closure|null Receives the object, returns its label. */
    private ?Closure $label = null;

    private ?string $pageId = null;

    /** @var Closure|null Receives the object, returns route parameters by name. */
    private ?Closure $routeParameters = null;

    private function __construct(public readonly string $name) {}

    public static function make(string $name): self
    {
        return new self($name);
    }

    /**
     * The class whose instances are subjects of this kind.
     *
     * @param  class-string  $class
     */
    public function for(string $class): self
    {
        $this->class = $class;

        return $this;
    }

    /** Label a subject from its object, for example `fn (Project $p) => $p->name`. */
    public function label(Closure $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * The canonical page that presents a subject of this kind, and how to fill
     * its route parameters from the object; by default the parameter named
     * after the kind receives the object.
     */
    public function page(string $pageId, ?Closure $routeParameters = null): self
    {
        $this->pageId = $pageId;
        $this->routeParameters = $routeParameters;

        return $this;
    }

    public function accepts(object $object): bool
    {
        return $this->class !== null && $object instanceof $this->class;
    }

    public function labelOf(object $object): string
    {
        $this->assertAccepts($object);

        $label = $this->label === null ? $this->name : ($this->label)($object);

        return is_string($label) ? $label : throw new LogicException("Label callback for subject kind '{$this->name}' must return a string.");
    }

    public function pageId(): ?string
    {
        return $this->pageId;
    }

    /** @return array<string, mixed> */
    public function routeParametersOf(object $object): array
    {
        $this->assertAccepts($object);

        $parameters = $this->routeParameters === null ? [$this->name => $object] : ($this->routeParameters)($object);

        return is_array($parameters) ? $parameters : throw new LogicException("Route parameters for subject kind '{$this->name}' must be an array.");
    }

    private function assertAccepts(object $object): void
    {
        if (! $this->accepts($object)) {
            throw new LogicException("Subject kind '{$this->name}' does not accept ".$object::class.'.');
        }
    }
}
