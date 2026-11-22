<?php

declare(strict_types=1);

namespace Current\Actions;

use Closure;
use Current\Context;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Pages\Pages;
use Current\Subjects\SubjectResolver;
use Current\Support\DestinationUrls;
use Current\Support\Label;
use Current\Support\RouteParameters;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Throwable;

/**
 * Resolves described actions for the current request; never runs them.
 *
 * Returns visible actions with their target URL and method, and whether each
 * is enabled. Hidden actions are left out entirely; disabled ones keep their
 * reason. Callbacks run only for the actions being resolved.
 */
final class Actions
{
    /** Route references are checked once per request or job; registries are frozen by then. */
    private bool $validated = false;

    /** @internal Resolved from the container, scoped to the request or job. */
    public function __construct(
        private readonly ActionRegistry $registry,
        private readonly SubjectResolver $subjects,
        private readonly Pages $pages,
        private readonly Router $router,
        private readonly UrlGenerator $urls,
        private readonly Gate $gate,
        private readonly DestinationUrls $destinations,
    ) {}

    /**
     * Resolve one subject's actions, such as for a table row.
     *
     * Works wherever Laravel can generate URLs. Callbacks see the current
     * request's context, or an empty one outside a request.
     *
     * @return list<ActionItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If no subject kind accepts the object, or a callback or URL cannot be resolved.
     */
    public function for(object $subject): array
    {
        $kind = $this->subjects->kindOf($subject);
        $this->validate();

        return $this->resolve($this->registry->forKind($kind->name), $this->pages->context(), $subject);
    }

    /**
     * Resolve the current subject's actions, then the current page's.
     *
     * The current subject is the one the active page presents, or else its
     * nearest presenting ancestor's, as for `Current::subject()`. Empty when no
     * page is active.
     *
     * @return list<ActionItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If the presented subject is not bound, or a callback or URL cannot be resolved.
     */
    public function current(): array
    {
        return $this->currentFor($this->pages->context());
    }

    /**
     * @internal Shared resolution; explicit experiences use their logical page for GET current-state.
     *
     * @return list<ActionItem>
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If a subject, callback, or URL cannot be resolved.
     */
    public function currentFor(Context $context, bool $explicit = false): array
    {
        if ($context->page === null) {
            return [];
        }
        $this->validate();

        $subject = $this->subjects->current($context);
        $items = $subject === null ? [] : $this->resolve($this->registry->forKind($subject['kind']->name), $context, $subject['object'], $explicit);

        return [...$items, ...$this->resolve($this->registry->forPage($context->page->id), $context, null, $explicit)];
    }

    /**
     * Describe every problem that needs Laravel's routes: unknown routes,
     * methods a route does not accept, and subject actions whose route lacks
     * the kind's parameter.
     *
     * @internal Read by current:check; readers throw the first problem.
     *
     * @return list<string>
     *
     * @throws InvalidDefinition If the action registry cannot be finalized.
     */
    public function problems(): array
    {
        $problems = [];
        foreach ($this->registry->all() as $action) {
            $id = $action->id();
            $route = $this->router->getRoutes()->getByName($action->routeName);
            if ($route === null) {
                $problems[] = "Action '{$id}' targets unknown route '{$action->routeName}'.";

                continue;
            }
            $methods = self::strings($route->methods());
            if (! in_array($action->method, $methods, true)) {
                $problems[] = "Action '{$id}' uses {$action->method}, but route '{$action->routeName}' accepts ".implode(', ', $methods).'.';
            }
            if ($action->kind !== null && $action->routeParameters === null && ! in_array($action->kind, self::strings($route->parameterNames()), true)) {
                $problems[] = "Action '{$id}' targets route '{$action->routeName}', which has no '{$action->kind}' parameter; map the subject with route()'s parameter callback.";
            }
        }

        return $problems;
    }

    /**
     * @param  list<ActionDefinition>  $actions
     * @return list<ActionItem>
     *
     * @throws ResolutionFailed
     */
    private function resolve(array $actions, Context $context, ?object $subject, bool $explicit = false): array
    {
        $items = [];
        foreach ($actions as $action) {
            $id = $action->id();
            $arguments = $subject === null ? [$context] : [$subject, $context];
            if ($action->visibleWhen !== null && ! $this->visible($action->visibleWhen, $arguments, $id)) {
                continue;
            }

            [$enabled, $reason] = $this->availability($action, $arguments, $context, $subject, $explicit);
            $url = $this->url($action, $context, $subject);

            $items[] = new ActionItem(
                id: $id,
                name: $action->name,
                label: self::text($action->label, $arguments, $id, 'label'),
                url: $url,
                method: $action->method,
                icon: $action->icon,
                destructive: $action->destructive,
                enabled: $enabled,
                reason: $reason,
                confirm: $action->confirm === null ? null : self::text($action->confirm, $arguments, $id, 'confirmation'),
                current: $this->isCurrent($action, $url, $context, $explicit),
            );
        }

        return $items;
    }

    /** @throws ResolutionFailed If the explicit page destination cannot be resolved. */
    private function isCurrent(ActionDefinition $action, string $url, Context $context, bool $explicit): bool
    {
        if ($action->method !== 'GET') {
            return false;
        }
        if (! $explicit) {
            return $this->router->current()?->getName() === $action->routeName && $url === $this->urls->current();
        }
        $page = $context->page;

        return $page !== null && $page->routeName === $action->routeName && $url === $this->pages->url($page, $context);
    }

    /**
     * Check can(), then enabledWhen(); the first denial wins and later checks do not run.
     *
     * @param  list<mixed>  $arguments
     * @return array{bool, ?string}
     *
     * @throws ResolutionFailed
     */
    private function availability(ActionDefinition $action, array $arguments, Context $context, ?object $subject, bool $explicit): array
    {
        $id = $action->id();
        if ($action->ability !== null) {
            try {
                $gate = $explicit || $context->user !== null ? $this->gate->forUser($context->user) : $this->gate;
                $response = $gate->inspect($action->ability, $subject === null ? [] : [$subject]);
            } catch (Throwable $exception) {
                throw new ResolutionFailed("Could not check ability '{$action->ability}' for action '{$id}': {$exception->getMessage()}", 0, $exception);
            }
            if ($response->denied()) {
                return [false, $response->message()];
            }
        }

        if ($action->enabledWhen !== null) {
            $result = self::call($action->enabledWhen, $arguments, $id, 'enabled');
            if ($result instanceof Response) {
                return $result->denied() ? [false, $result->message()] : [true, null];
            }
            if (! is_bool($result)) {
                throw new ResolutionFailed("Enabled callback for action '{$id}' must return a bool or an Illuminate\\Auth\\Access\\Response.");
            }
            if (! $result) {
                return [false, null];
            }
        }

        return [true, null];
    }

    /** @throws ResolutionFailed */
    private function url(ActionDefinition $action, Context $context, ?object $subject): string
    {
        $id = $action->id();
        if ($subject === null) {
            $available = $context->parameters();
        } elseif ($action->routeParameters === null) {
            $available = [(string) $action->kind => $subject];
        } else {
            $available = RouteParameters::resolve($action->routeParameters, $subject, "action '{$id}'");
        }

        return $this->destinations->generate($action->routeName, $available, "action '{$id}'");
    }

    /**
     * @param  list<mixed>  $arguments
     *
     * @throws ResolutionFailed
     */
    private function visible(Closure $callback, array $arguments, string $id): bool
    {
        $visible = self::call($callback, $arguments, $id, 'visibility');

        return is_bool($visible)
            ? $visible
            : throw new ResolutionFailed("Visibility callback for action '{$id}' must return a bool.");
    }

    /**
     * @param  list<mixed>  $arguments
     *
     * @throws ResolutionFailed
     */
    private static function text(string|Closure $value, array $arguments, string $id, string $fact): string
    {
        $text = is_string($value) ? $value : self::call($value, $arguments, $id, $fact);

        return Label::nonEmpty($text, "action '{$id}'", $fact);
    }

    /**
     * @param  list<mixed>  $arguments
     *
     * @throws ResolutionFailed
     */
    private static function call(Closure $callback, array $arguments, string $id, string $fact): mixed
    {
        try {
            return $callback(...$arguments);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not resolve {$fact} for action '{$id}': {$exception->getMessage()}", 0, $exception);
        }
    }

    /**
     * @param  array<mixed>  $values
     * @return list<string>
     */
    private static function strings(array $values): array
    {
        return array_values(array_filter($values, is_string(...)));
    }

    /** @throws InvalidDefinition */
    private function validate(): void
    {
        if ($this->validated) {
            return;
        }
        $this->subjects->validate();
        $problems = $this->problems();
        if ($problems !== []) {
            throw new InvalidDefinition($problems[0]);
        }
        $this->validated = true;
    }
}
