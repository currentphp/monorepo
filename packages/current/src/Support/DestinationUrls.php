<?php

declare(strict_types=1);

namespace Current\Support;

use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Routing\Router;
use Throwable;

/** @internal Generates destination URLs using only declared path and domain parameters. */
final readonly class DestinationUrls
{
    public function __construct(private Router $router, private UrlGenerator $urls) {}

    /**
     * @param  array<string, mixed>  $available
     *
     * @throws InvalidDefinition If the named route or its parameter declarations are invalid.
     * @throws ResolutionFailed If URL generation fails.
     */
    public function generate(string $routeName, array $available, string $owner): string
    {
        $route = $this->router->getRoutes()->getByName($routeName)
            ?? throw new InvalidDefinition(ucfirst($owner)." references unknown route '{$routeName}'.");
        $parameters = [];
        foreach ($route->parameterNames() as $name) {
            if (! is_string($name)) {
                throw new InvalidDefinition("Route '{$routeName}' for {$owner} contains an invalid parameter name.");
            }
            if (array_key_exists($name, $available)) {
                $parameters[$name] = $available[$name];
            }
        }

        try {
            return $this->urls->route($routeName, $parameters);
        } catch (Throwable $exception) {
            throw new ResolutionFailed("Could not generate URL for {$owner} and route '{$routeName}': {$exception->getMessage()}", 0, $exception);
        }
    }
}
