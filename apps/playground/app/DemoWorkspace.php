<?php

declare(strict_types=1);

namespace App;

use Illuminate\Contracts\Routing\UrlRoutable;

final readonly class DemoWorkspace implements UrlRoutable
{
    public function __construct(public string $name) {}

    public function getRouteKey(): string
    {
        return 'demo';
    }

    public function getRouteKeyName(): string
    {
        return 'workspace';
    }

    public function resolveRouteBinding(mixed $value, mixed $field = null): ?self
    {
        return $value === 'demo' ? app(OnboardingDemo::class)->workspace() : null;
    }

    public function resolveChildRouteBinding(mixed $childType, mixed $value, mixed $field): ?self
    {
        return null;
    }
}
