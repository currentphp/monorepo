<?php

declare(strict_types=1);

namespace App\Prototype\Actions;

use Closure;
use Current\Context;
use Current\Pages\Pages;
use Current\Subjects\Subjects;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Routing\Router;
use LogicException;

/**
 * Prototype of the Current\Actions reader, built only on Current's public API.
 *
 * Returns visible actions with their target URL, method, and enabled state.
 * Hidden actions are left out; disabled ones keep their reason.
 */
final readonly class Actions
{
    public function __construct(
        private ActionRegistry $registry,
        private Subjects $subjects,
        private Pages $pages,
        private Router $router,
        private Gate $gate,
    ) {}

    /**
     * The actions for one subject, such as a table row.
     *
     * @return list<ActionItem>
     */
    public function for(object $subject): array
    {
        $kind = $this->subjects->of($subject)->kind;
        $context = $this->pages->context();

        return $this->resolve($this->registry->forKind($kind), $context, $subject);
    }

    /**
     * The current subject's actions, then the current page's.
     *
     * @return list<ActionItem>
     */
    public function current(): array
    {
        $context = $this->pages->context();
        if ($context->page === null) {
            return [];
        }

        $items = [];
        $subject = $this->currentSubject($context);
        if ($subject !== null) {
            $items = $this->resolve($this->registry->forKind($this->subjects->of($subject)->kind), $context, $subject);
        }

        return [...$items, ...$this->resolve($this->registry->forPage($context->page->id), $context, null)];
    }

    /** The bound object of the nearest page in the trail that presents a subject. */
    private function currentSubject(Context $context): ?object
    {
        foreach (array_reverse($this->pages->activeTrail($context)) as $page) {
            if ($page->presents !== null && $page->presentsParameter !== null) {
                $object = $context->parameters()[$page->presentsParameter] ?? null;

                return is_object($object) ? $object : null;
            }
        }

        return null;
    }

    /**
     * @param  list<ActionDefinition>  $actions
     * @return list<ActionItem>
     */
    private function resolve(array $actions, Context $context, ?object $subject): array
    {
        $items = [];
        foreach ($actions as $action) {
            $arguments = $subject === null ? [$context] : [$subject, $context];
            if ($action->visibleWhen !== null && ! $this->call($action->visibleWhen, $arguments, $action, 'visibility')) {
                continue;
            }

            [$enabled, $reason] = $this->availability($action, $arguments, $context, $subject);

            $items[] = new ActionItem(
                id: $action->id(),
                name: $action->name,
                label: $this->text($action->label, $arguments, $action, 'label'),
                url: $this->url($action, $context, $subject),
                method: $action->method,
                icon: $action->icon,
                destructive: $action->destructive,
                enabled: $enabled,
                reason: $reason,
                confirm: $action->confirm === null ? null : $this->text($action->confirm, $arguments, $action, 'confirmation'),
            );
        }

        return $items;
    }

    /**
     * @param  list<mixed>  $arguments
     * @return array{bool, ?string}
     */
    private function availability(ActionDefinition $action, array $arguments, Context $context, ?object $subject): array
    {
        $checks = [];
        if ($action->ability !== null) {
            $user = $context->user;
            $gate = $user === null ? $this->gate : $this->gate->forUser($user);
            $checks[] = $gate->inspect($action->ability, $subject === null ? [] : [$subject]);
        }
        if ($action->enabledWhen !== null) {
            $checks[] = ($action->enabledWhen)(...$arguments);
        }

        foreach ($checks as $check) {
            if ($check instanceof Response) {
                if ($check->denied()) {
                    return [false, $check->message()];
                }
            } elseif ($check === false) {
                return [false, null];
            } elseif ($check !== true) {
                throw new LogicException("Enabled callback for action '{$action->id()}' must return a bool or a Response.");
            }
        }

        return [true, null];
    }

    private function url(ActionDefinition $action, Context $context, ?object $subject): string
    {
        $route = $this->router->getRoutes()->getByName($action->routeName)
            ?? throw new LogicException("Action '{$action->id()}' targets unknown route '{$action->routeName}'.");

        if ($subject !== null) {
            $parameters = $action->routeParameters === null
                ? [$action->kind ?? '' => $subject]
                : ($action->routeParameters)($subject);
        } else {
            $parameters = array_intersect_key($context->parameters(), array_flip($route->parameterNames()));
        }

        if (! is_array($parameters)) {
            throw new LogicException("Route parameters for action '{$action->id()}' must be an array.");
        }

        return route($action->routeName, $parameters);
    }

    /** @param list<mixed> $arguments */
    private function text(string|Closure $value, array $arguments, ActionDefinition $action, string $fact): string
    {
        $text = is_string($value) ? $value : $value(...$arguments);

        return is_string($text) && $text !== ''
            ? $text
            : throw new LogicException(ucfirst($fact)." for action '{$action->id()}' must be a non-empty string.");
    }

    /** @param list<mixed> $arguments */
    private function call(Closure $callback, array $arguments, ActionDefinition $action, string $fact): bool
    {
        $result = $callback(...$arguments);

        return is_bool($result) ? $result : throw new LogicException(ucfirst($fact)." callback for action '{$action->id()}' must return a bool.");
    }
}
