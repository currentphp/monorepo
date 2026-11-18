<?php

declare(strict_types=1);

namespace Current\Actions;

use Closure;
use Current\Exceptions\InvalidDefinition;

/**
 * A declaration builder for something a user can do to a subject or on a page.
 *
 * Current describes actions and never runs them: the target is the
 * application's own route, which performs and authorizes the action.
 * Registration takes a snapshot.
 *
 * Callbacks receive the subject object, then the request Context, for subject
 * actions, and the Context alone for page actions. They run when output is
 * requested and must be free of side effects.
 */
final class Action
{
    private const array METHODS = ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE'];

    private ?string $kind = null;

    private ?string $pageId = null;

    private string|Closure|null $label = null;

    private ?string $routeName = null;

    private string $method = 'GET';

    private ?Closure $routeParameters = null;

    private ?string $icon = null;

    private string|Closure|null $confirm = null;

    private bool $destructive = false;

    private int $order = 0;

    private ?Closure $visibleWhen = null;

    private ?Closure $enabledWhen = null;

    private ?string $ability = null;

    private function __construct(public readonly string $name) {}

    /** Start a declaration with a name, unique within its subject kind or page, such as `archive`. */
    public static function make(string $name): self
    {
        return new self($name);
    }

    /** Attach to a subject kind: the action applies to each subject of that kind. */
    public function for(string $kind): self
    {
        $this->kind = $kind;

        return $this;
    }

    /** Attach to a page: the action applies while that page is the current page. */
    public function on(string $pageId): self
    {
        $this->pageId = $pageId;

        return $this;
    }

    /**
     * Set the label. Required.
     */
    public function label(string|callable $label): self
    {
        $this->label = is_string($label) ? $label : Closure::fromCallable($label);

        return $this;
    }

    /**
     * Name the application's route that performs the action, and its HTTP method. Required.
     *
     * A subject action passes the subject as the route parameter named after
     * its kind, unless the callback maps the subject to parameters, such as
     * `fn (Task $task) => ['project' => $task->project, 'task' => $task]`. A
     * page action forwards the current request's parameters. Only parameters
     * the route declares are used.
     */
    public function route(string $name, string $method = 'GET', ?callable $parameters = null): self
    {
        $this->routeName = $name;
        $this->method = strtoupper($method);
        $this->routeParameters = $parameters === null ? null : Closure::fromCallable($parameters);

        return $this;
    }

    /** Set an optional presentation identifier; the application renders it. */
    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Set confirmation text for the application's own dialog; Current shows nothing.
     */
    public function confirm(string|callable $confirm): self
    {
        $this->confirm = is_string($confirm) ? $confirm : Closure::fromCallable($confirm);

        return $this;
    }

    /** Mark the action as destructive, for emphasis. */
    public function destructive(bool $destructive = true): self
    {
        $this->destructive = $destructive;

        return $this;
    }

    /** Set ascending order among the subject's or page's actions; ties keep registration order. */
    public function order(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Hide the action when the callback returns false. Presentation only:
     * the route must still authorize.
     *
     * @param  callable  $visible  Receives the subject then the Context, or the Context alone.
     */
    public function visibleWhen(callable $visible): self
    {
        $this->visibleWhen = Closure::fromCallable($visible);

        return $this;
    }

    /**
     * Disable the action when the callback returns false or a denied
     * `Illuminate\Auth\Access\Response`, whose message becomes the reason.
     *
     * @param  callable  $enabled  Receives the subject then the Context, or the Context alone.
     */
    public function enabledWhen(callable $enabled): self
    {
        $this->enabledWhen = Closure::fromCallable($enabled);

        return $this;
    }

    /**
     * Disable the action unless the current user passes this Gate ability,
     * checked with `Gate::inspect()` against the subject (or no arguments for a
     * page action). It runs before enabledWhen(); the first denial wins.
     */
    public function can(string $ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    /**
     * @internal Snapshot taken by ActionRegistry.
     *
     * @throws InvalidDefinition If the attachment, label, route, or method is invalid.
     */
    public function toDefinition(): ActionDefinition
    {
        if (trim($this->name) === '') {
            throw new InvalidDefinition('Actions must have a non-empty name.');
        }
        if (($this->kind === null) === ($this->pageId === null)) {
            throw new InvalidDefinition("Action '{$this->name}' must use exactly one of for() or on().");
        }
        $owner = ($this->kind ?? $this->pageId).'.'.$this->name;
        if ($this->label === null || $this->label === '') {
            throw new InvalidDefinition("Action '{$owner}' must have a label().");
        }
        if ($this->routeName === null || trim($this->routeName) === '') {
            throw new InvalidDefinition("Action '{$owner}' must have a route().");
        }
        if (! in_array($this->method, self::METHODS, true)) {
            throw new InvalidDefinition("Action '{$owner}' has an unsupported method '{$this->method}'.");
        }
        if ($this->confirm === '') {
            throw new InvalidDefinition("Action '{$owner}' has an empty confirmation.");
        }

        return new ActionDefinition(
            name: $this->name,
            kind: $this->kind,
            pageId: $this->pageId,
            label: $this->label,
            routeName: $this->routeName,
            method: $this->method,
            routeParameters: $this->routeParameters,
            icon: $this->icon,
            confirm: $this->confirm,
            destructive: $this->destructive,
            order: $this->order,
            visibleWhen: $this->visibleWhen,
            enabledWhen: $this->enabledWhen,
            ability: $this->ability,
        );
    }
}
