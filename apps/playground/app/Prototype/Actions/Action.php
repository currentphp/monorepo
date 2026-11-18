<?php

declare(strict_types=1);

namespace App\Prototype\Actions;

use Closure;
use LogicException;

/**
 * Prototype of Current\Actions\Action (WK-008): describes something a user can
 * do to a subject or on a page. Never executes it: the target is the app's own
 * route, and availability callbacks only decide what the reader returns.
 *
 * Callbacks receive the subject object then the Context for subject actions,
 * and the Context alone for page actions.
 */
final class Action
{
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

    public function label(string|callable $label): self
    {
        $this->label = is_string($label) ? $label : Closure::fromCallable($label);

        return $this;
    }

    /**
     * The app's own route that performs the action, and its HTTP method.
     *
     * Subject actions pass the subject as the parameter named after its kind,
     * unless a callback maps it; page actions forward the active request's parameters.
     */
    public function route(string $name, string $method = 'GET', ?callable $parameters = null): self
    {
        $this->routeName = $name;
        $this->method = strtoupper($method);
        $this->routeParameters = $parameters === null ? null : Closure::fromCallable($parameters);

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /** Confirmation text for the app's own dialog; Current shows nothing itself. */
    public function confirm(string|callable $confirm): self
    {
        $this->confirm = is_string($confirm) ? $confirm : Closure::fromCallable($confirm);

        return $this;
    }

    public function destructive(bool $destructive = true): self
    {
        $this->destructive = $destructive;

        return $this;
    }

    public function order(int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /** Hide the action when the callback returns false. */
    public function visibleWhen(callable $visible): self
    {
        $this->visibleWhen = Closure::fromCallable($visible);

        return $this;
    }

    /** Disable the action, with an optional reason, when the callback returns false or a denied Response. */
    public function enabledWhen(callable $enabled): self
    {
        $this->enabledWhen = Closure::fromCallable($enabled);

        return $this;
    }

    /** Shorthand for enabledWhen() with Gate::inspect($ability, $subject) for the current user. */
    public function can(string $ability): self
    {
        $this->ability = $ability;

        return $this;
    }

    public function toDefinition(): ActionDefinition
    {
        if (($this->kind === null) === ($this->pageId === null)) {
            throw new LogicException("Action '{$this->name}' must use exactly one of for() or on().");
        }
        if ($this->label === null) {
            throw new LogicException("Action '{$this->name}' must have a label().");
        }
        if ($this->routeName === null) {
            throw new LogicException("Action '{$this->name}' must have a route().");
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
